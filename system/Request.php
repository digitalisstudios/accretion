<?php
	class Request extends Accretion {

		public static $vars 	= array();
		public static $post 	= array();
		public static $headers 	= array();
		public static $server 	= array();

		public function __construct(){

		}

		public static function is_ajax(){
			if($_SERVER['accretion_context'] == 'api') return true;		
			return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ? true : false;
			
		}

		/*
		public static function return_json($result = null){

			//RETURN JSON INSTEAD OF THE VIEW IF NEEDED
			if(Request::is_ajax() && (!Request::get('headers') || Request::get('headers') && Request::get('headers') !== 'true') && !is_null($result)){	
				
				//GENERATE JSON OUTPUT
				die((is_object($result) && get_class($result) == 'ORM_Wrapper') ? json_encode($result->to_array()) : (!is_string($result) ? json_encode($result) : $result));
			}
			
		}
		*/

		private static function _print_json($result = null){

			header('Content-type: application/json');

			if($_SERVER['accretion_context'] == 'api'){

				$error = [];

				if(http_response_code() !== 200){
					$error = $result;
					unset($error['error']);
					$result = null;
					if($_SERVER['dev_mode'] == 'true'){
						$stack = debug_backtrace();
						array_shift($stack);
						array_shift($stack);
						array_shift($stack);

						$error['stack'] = $stack;
					} 
				}

				$data = [
					'success' 		=> http_response_code() == 200,
					'statusCode' 	=> http_response_code(),
					'response' 		=> $result,
					'error' 		=> $error
				];
			}
			else{
				$data = $result;
			}

			die(json_encode($data, JSON_PRETTY_PRINT));

		}

		public static function return_json($result = null){			

			//RETURN JSON INSTEAD OF THE VIEW IF NEEDED
			if($_SERVER['accretion_context'] == 'api' || (Request::is_ajax() && (!Request::get('headers') || Request::get('headers') && Request::get('headers') !== 'true') && !is_null($result) || (is_object($result) || is_array($result)))){	

				if(is_object($result)){
					if(get_class($result) == 'ORM_Wrapper'){
						
						if($_SERVER['accretion_context'] == 'api'){
							self::_print_json(['object_type' => 'Collection', 'object_data' => $result->to_array(true)]);
						}

						self::_print_json($result->to_array(true));

						//header('Content-type: application/json');
						//die(json_encode($result->to_array(true), JSON_PRETTY_PRINT));
					}
					elseif(is_model($result)){
						if($_SERVER['accretion_context'] == 'api'){
							self::_print_json(['object_type' => $result->model_name(), 'object_data' => $result->expose_data()]);
						}

						self::_print_json($result->expose_data());
						//header('Content-type: application/json');
						//die(json_encode($result->expose_data(), JSON_PRETTY_PRINT));
					}					
				}
				
				if(!is_string($result)){
					self::_print_json($result);
					//header('Content-type: application/json');
					//die(json_encode($result, JSON_PRETTY_PRINT));

				}

				if($_SERVER['accretion_context'] == 'api'){
					self::_print_json($result);
					//header('Content-type: application/json');
					//die(json_encode(['content' => $result], JSON_PRETTY_PRINT));
				}

				die($result);
				
				//GENERATE JSON OUTPUT
				//die((is_object($result) && get_class($result) == 'ORM_Wrapper') ? json_encode($result->to_array()) : (!is_string($result) ? json_encode($result) : $result));
			}		
		}


		//RENDER A REQUEST ERROR
		public static function error($type = 404, $details = null, $message = null){

			$messages = [
				400 => "Bad Request",
				401 => "Not Authenticated",
				404 => "The page you requested could not be found.",
				403 => "You do not have access to view this page.",
				500 => "An unknown system error occured.",
				511 => "Not Authenticated",
			];

			if(is_null($message)){
				$message = isset($messages[$type]) ? $messages[$type] : $messages[404];
			}
			
			//SET THE RESPONSE CODE
			http_response_code($type);

			if(\Request::is_ajax()){
				return \Request::return_json(['error' => $type, 'message' => $message, 'details' => $details]);
			}

			//LOAD THE ERROR TEMPLATE
			\View::partial('header');
			$file_path = VIEW_PATH."http_error/".$type.".php";

			if(file_exists($file_path)){
				include VIEW_PATH."http_error/".$type.".php";
			}
			else{
				echo "<h1>Error: {$type}</h1><br>";
				echo "<p>{$message}</p>";

				if(!is_null($details)){
					echo "<p>{$details}</p>";
				}
			}
			\View::partial('footer');
			exit;
		}

		public function get($parameter = false){

			if(isset($_SERVER['REQUEST_URI'])){			

				//PARSE THE URI INTO PARTS
				Request::$vars = array_values(array_filter(explode('/', str_replace('#', '', trim(urldecode(explode('?', substr($_SERVER['REQUEST_URI'], strlen(WEB_APP)))[0]))))));				

				//PARSE FRAMEWORK VARS
				foreach(Request::$vars as $k => $var){
					if(strpos($var, "=")){

						//CHECK FOR BASE 64 VALS 

						if ( base64_encode(base64_decode($var)) === $var){
							if(!in_array($var, Request::$vars)){
								Request::$vars[] = $var;
							}
						}
						else{
							$sub_var = explode('=', $var);
							Request::$vars[$sub_var[0]] = $sub_var[1];
							unset(Request::$vars[$k]);
						}
						
					}
					else{
						if(!in_array($var, Request::$vars)){
							Request::$vars[] = $var;
						}
					}
				}

				//ADD THE GET VARIABLES
				if(!empty($_GET)){
					foreach($_GET as $k => $v){
						Request::$vars[$k] = $v;
					}
				}

				//SET THE FIST KEY TO 1
				Request::$vars = array_filter(array_merge(array(0 => ''), Request::$vars));

				//IF WE ARE LOOKING FOR A PARAMETER
				if($parameter){

					if(isset(Request::$vars[$parameter])){
						return Request::$vars[$parameter];
					}
					return false;				
				}
			}
			else{
				Request::$vars = array();
			}

			return Request::$vars;
		}

		public static function get_vars($parameter = false){

			$parts = array();

			if(Accretion::$controller){

				//SET UP THE CONTROLLER PATH
				$controller_path = Controller::format_url(implode('/', array_filter(explode('/', WEB_APP.Accretion::$controller->controller_template_path)))).'/';

				//GET THE ORIGINAL URL
				$url = array();
				foreach(Request::get() as $k => $v){
					if(!is_numeric($k)){
						$v = $k.'='.$v;
					}
					$url[] = $v;
				}				
				$url = implode('/', $url).'/';		

				$split = Accretion::$controller->controller_template_path;

				if(strpos($split, '/') !== false){
					$parts = explode(Controller::format_url($split), Controller::format_url($url));
				}
				else{
					$new_parts = array();
					$parts = array_filter(explode('/', Controller::format_url($url)));
					$found = false;
					foreach($parts as $k => $v){
						if(Controller::format_url($v) == Controller::format_url(Accretion::$controller->controller_name())){
							$found = true;
							continue;
						}

						if(!$found) continue;

						$new_parts[] = $v;
					}

					$parts = $new_parts;
				}

				$parts = array_values(array_filter(explode('/',implode('/', array_filter($parts)))));

				//IF THE TEMPLATE NAME IS FOUND REMOVE IT
				if($parts[0] == Controller::format_url(Accretion::$template_name)){
					unset($parts[0]);
				}

				

				//GENERATE NEW PARTS WITH THE ORGINAL UNFORMATTED URL
				$parts = explode('/', substr($url, strlen($url)-strlen('/'.implode('/', $parts))));		

				

				//PARSE THE INTEGER PARTS AND NAMED PARTS
				$int_parts 		= array();
				$named_parts 	= array();
				foreach($parts as $part){
					if(strpos($part, '=') !== false){
						if ( base64_encode(base64_decode($part)) === $part){
							$int_parts[] = $part;
						}
						else{
							$sub_parts = explode('=', $part);
							$named_parts[$sub_parts[0]] = $sub_parts[1];
						}
						
					}
					else{
						$int_parts[] = $part;
					}
				}

				//SET THE PARTS ARRAY
				$parts = array_filter(array_merge($int_parts, $named_parts));
			}

			//IF A PARAMETER WAS REQUESTED
			if($parameter !== false){

				//CHECK IF THE PARAMETER IS AN INTEGER
				if(is_numeric($parameter)){

					//IF THE PARAMETER EXISTS
					if(isset($parts[$parameter])){
						return $parts[$parameter];
					}

					//PARAMETER DOES NOT EXIST SO RETURN FALSE
					else{
						return false;
					}
				}

				//PARAMETER IS NOT AN INTEGER TO USE THE GET METHOD
				else{
					return Request::get($parameter);
				}				
			}

			//RETURN THE PARTS ARRAY
			return $parts;
		}

		

		public static function post($parameter = false, $value = null){

			Request::$post = $_POST;

			if($parameter && !is_null($value)){
				Request::$post[$parameter] = $value;
				$_POST = Request::$post;
				return Request::$post;
			}

			if($parameter){

				$arr = Request::$post;

				return getArrayByPath($arr, $parameter);

				if(isset(Request::$post[$parameter])){
					return Request::$post[$parameter];
				}
				return false;
			}
			return Request::$post;
		}

		public static function empty_post(){
			
			Request::$post = $_POST;

			if(empty(Request::$post)){
				return true;
			}
			return false;
		}

		public static function headers($parameter = null, $value = null){

			Request::$headers = getallheaders();

			if(!is_null($parameter) && !is_null($value)){
				header($parameter, $value);
				Request::$headers = getallheaders();
				return Request::$headers;
			}
			elseif(!is_null($parameter) && is_null($value)){
				if(isset(Request::$headers[$parameter])){
					return Request::$headers[$parameter];
				}
				return false;
			}

			return Request::$headers;
		}

		public static function server($parameter = null, $value = null){

			Request::$server = $_SERVER;

			if(!is_null($parameter) && !is_null($value)){
				$_SERVER[$parameter] = $value;
				Request::$server = $_SERVER;
				return Request::$server;
			}
			elseif(!is_null($parameter) && is_null($value)){
				if(isset(Request::$server[$parameter])){
					return Request::$server[$parameter];
				}
				return false;
			}

			return Request::$server;
		}

		//THIS WILL RETRIEVE A STREAM OF DATA PASSED TO THE STDIN
		public static function stream($timeout_seconds = 3){

			//RETURN BUFFERED OUTPUT
			return Buffer::start(function(){

				//WAIT FOR INPUT
				stream_set_blocking(STDIN, 0);

				//HOW LONG TO WAIT
				$timeout 			= time()+$timeout_seconds;

				//WE HAVE NOT STARTED BUFFERING THE STREAM
				$started 			= false; 

				//RUN UNTIL TIMEOUT OR STREAM STARTS
				while(time() <= $timeout){
					while(false !== ($line = fgets(STDIN))){						
						echo $line;
						$started = true;
					}

					if(feof(STDIN)){
						break;
					}

					if(!$started){
						sleep(1);	
					}
				}

				if(!$started){
					echo "";
				}
			});
		}

		public static function streamInput(){

			$fd = fopen( "php://stdin", "r" );			
			$message = "";
			while (!feof( $fd )) $message .= fread( $fd, 1024 );
			fclose( $fd );
			return $message;
		}
	}
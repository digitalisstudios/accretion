<?php
	
	class Controller extends Accretion {

		public static $_loader_vars = [];

		public function __construct(){

		}

//----------------------------------// GLOBAL CONTROLLER METHODS //-------------------------//

		//LOAD A CONTROLLER AND OPTIONAL TEMPLATE
		public static function get($controller = false){
			
			//FORCE THE CONTROLLER
			$controller 					= $controller === false ? Controller::parse_from_request() : Controller::parse_from_request($controller);

			//IF NO CONTROLLER PUT OUT 404 ERROR
			if(is_null($controller)) \Request::error(404);			

			//SET TEMPLATE AND CONTROLLER VARSs
			$template_path 					= $controller->get_controller_template_path();
			$template 						= $controller->get_requested_template();
			Accretion::$controller 			= $controller;
			Accretion::$controller_name 	= get_class($controller);
			Accretion::$template_name 		= $template;
			Accretion::$template_path 		= $template_path;
			
			//CHECK THAT THE CONTROLLER AND THE METHOD EXIST
			if(Accretion::$controller && method_exists(Accretion::$controller, $template)){

				//SET THE METHOD NAME
				Accretion::$method_name 	= $template;
				$result 					= Reflect::reflectable_class(Accretion::$controller, $template, Request::get_vars()) ? Reflect::reflect_class(Accretion::$controller, $template, Request::get_vars()) : Accretion::$controller->$template();	
				Accretion::$template_name 	= file_exists(Accretion::$template_path.'/'.Accretion::$method_name.'.php') ? Accretion::$method_name : Accretion::$template_name;
			}

			//SPECIAL CASE FOR INDEX METHOD BECAUSE WE CAN LOAD A TEMPLATE WITHOUT A REQUEST VAR
			else if(Accretion::$template_name == 'index' && method_exists(Accretion::$controller, 'index')){
				$method_name 	= 'index';
				$result 		= Accretion::$controller->index();
			}

			//MAKE SURE THE SESSION WRITE IS CLOSED
			Session::update(function(){});

			//RETURN JSON IF NEEDED
			Request::return_json($result);

			//CHECK IF THE VIEW WAS LOADED FROM THE RESULT
			if(View::$view_loaded && !is_null($result) && is_string($result)){
				echo $result;
				View::$view_loaded = false;
			}

			//WE HAVE TO CALL THE VIEW METHOD DYNAMICALLY TO SOLVE INHERITANCE ISSUES
			else{
				View::$view_loaded = false;
				Accretion::$controller->load_controller_view($template);
			}
			
			//SEND BACK THE CONTROLLER
			return Accretion::$controller;
		}

		public static function backup_loader_vars(){

			$vars = ['controller','method_name','template_name','template_path'];

			foreach($vars as $var) Controller::$_loader_vars[$var] = Accretion::$$var; 
		}

		public static function reset_loader_vars(){

			foreach(Controller::$_loader_vars as $k => $v) Accretion::$$k = $v;

			Controller::$_loader_vars = [];
		}

		//FORMAT THE URL
		public static function format_url($url){
			return str_replace('_', '-', strtolower($url));
		}

		//CHECK IF THE CONTROLLER EXISTS
		public static function controller_exists($controller_path){
			return file_exists(Controller::get_real_controller_path($controller_path)) ? true : false;
		}

		//GET THE CONTROLLER PATH INDEPENDANT OF THE URL FORMAT
		public static function get_real_controller_path($controller_path){

			//SPLIT PATH ON CONTROLLER PATH
			$post_controller 	= explode(CONTROLLER_PATH, $controller_path);

			//REMOVE THE FIRST PART IF THERE IS MORE THAN ONE PART
			if(count($post_controller) > 1){
				unset($post_controller[0]);
			}			

			//SET THE NEW PATH
			$post_controller 	= Controller::format_url(implode('/', $post_controller));

			//IF A LOADED CONTROLLER EXISTS USE IT
			if(isset(Accretion::$controllers[$post_controller])){
				$controller_path = Accretion::$controllers[$post_controller];
			}

			//SEND BACK THE LOADED CONTROLLER PATH
			return $controller_path;
		}

		//GET THE CONTROLLER FROM THE REQUEST
		public static function parse_from_request($key = 0){

			//CHECK FOR PATH 
			if(is_string($key) && strlen($key) > 0){
				$parts 		= explode('/', $key);
				$key 		= 0;
			}

			//NO PATH WAS PASSED
			else{
				$parts 		= Request::get();
			}
			
			//INIT THE PATH PARTS
			$path_parts 	= array();

			//USE THE DEFAULT CONTROLLER IF NEEDED
			if($key === 0 && empty($parts)){
				$parts[0] = Config::get('default_controller');
			}
			else{
				$path_parts[] 	= substr(CONTROLLER_PATH, 0, strlen(CONTROLLER_PATH)-1);
			}

			//CYCLE THE PARTS
			foreach($parts as $k => $part){				
				
				//IGNORE PARTS BEFORE THE KEY WE ARE LOOKING FOR
				if($k < $key) continue;

				//ADD TO THE PATH PARTS ARRAY
				$path_parts[] 		= $part;

				//SET THE CONTROLLER PATH
				$controller_path 		= Controller::get_real_controller_path(implode('/', $path_parts));
				$controller_path_name 	= str_replace('.php', '', $controller_path);
				
				//CHECK IF THE CONTROLLER EXISTS
				if(file_exists($controller_path)){
					
					//LOAD THE CONTROLLER
					include_once $controller_path;
					
					//SET THE CONTROLLER NAME
					$controller_name 	= basename($controller_path_name);

					//BUILD A NAMESPACED CONTROLLER NAME
					$name 				= 'Controller\\'.str_replace('/', '\\', str_replace(CONTROLLER_PATH, '', dirname($controller_path).'/')).$controller_name;

					//START A REFLECTOR
					$reflect 			= new ReflectionClass($name);							

					//REFLECT THE CONTROLLER
					$controller 		= $reflect->newInstanceWithoutConstructor()->reflect_controller();

					//CHECK IF THE CONTROLLER HAS SUB CONTROLLERS
					if(is_dir($controller_path_name)){	
						
						//CHECK FOR A SUB CONTROLLER	
						$sub = Request::get($key+1);

						//IF THERE IS ANOTHER URL VAR
						if($sub){

							//BUILD THE SUB CONTROLLER PATH
							$sub_path = $controller_path_name.'/'.$sub;

							//CHECK TO SEE IF THE SUB CONTROLLER ACTUALLY EXISTS
							if(Controller::controller_exists($sub_path)){

								//LOAD THE SUB CONTROLLER
								$controller = Controller::parse_from_request($key+1);
							}
						}
					}
				}
			}

			//SEND BACK THE CONTROLLER
			return $controller;			
		}


//----------------------------------------------------// CONTROLLER SPECIFIC METHODS //-----------------------------------//

		//REQUIRE LOGIN
		public function require_login($var = null, $value = null){

			//ASSUME WE NEED TO REDIRECT
			$redirect = false;

			//GET THE AUTHORIZIATION CREDENTIAL TYPES
			$by = \Auth::by();

			if(!isset($_SESSION[$by->session_name])){
				$redirect = true;
			}
			else{

				//GET THE USER DATA
				$user = $_SESSION[$by->session_name];

				//CHECK IF THE VARIABLE NAME MATHES THE VALUE
				if(!is_null($var) && !is_null($value)){
					if(!isset($user[$var]) || isset($user[$var]) && $user[$var] !== $value){
						$redirect = true;
					}
				}

				//CHECK FOR VARIABLE NAME
				elseif(!is_null($var)){
					if(!isset($user[$var])){
						$redirect = true;
					}
				}

				//CHECK FOR USER ID
				elseif(is_null($var)){
					if(!isset($user[$by->login_with])){
						$redirect = true;
					}
				}
			}

			//USE ENCRYPTION KEY TO BYPASS LOGIN
			if(\Request::get('auth') == \Config::get('encryption_key') || \Request::headers('Auth-Key') == \Config::get('encryption_key')){
				$redirect = false;
			}

			//IF WE NEED TO REDIRECT
			if($redirect){

				//SET THE SESSION REDIRECT TO VAR
				\Session::update(function(){
					if(isset($_SERVER['REQUEST_URI']) && !isset($_SESSION['redirect_to'])){
						$_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
					}
				});

				//REDIRECT THE USER
				\Helper::Redirect(WEB_APP.'Login');

			}
		}

		//LOAD A FILE IN THE CONTROLLER CONTEXT
		public function load_file($file_path){
			include $file_path;
		}

		//LOAD THE VIEW FOR THIS CONTROLLER
		public function load_controller_view($template = false){

			if(get_class($this) !== get_class(Accretion::$controller)){

				Controller::backup_loader_vars();

				Accretion::$controller 		= $this;
				Accretion::$method_name 	= $template !== false ? $template : 'index';
				Accretion::$template_name 	= Accretion::$method_name;
				Accretion::$template_path 	= $this->template_path;

				$res = View::get();

				Controller::reset_loader_vars();

				return $res;
			}

			return View::get();
		}

		//ALLOW FOR TEMPLATES WITHOUT AUTOLOAD HEADERS
		public function disable_header($names){

			if(!isset($this->disabled_headers)){
				$this->disabled_headers = array();
			}

			if(is_array($names)){
				foreach($names as $name){
					$this->disabled_headers[] = $name;
				}
			}
			else{
				$this->disabled_headers[] = $names;
			}
		}

		public function disable_subheader(){
			$this->_disable_subheader = true;
			return $this;
		}
		

		//CHECK IF THE CLASS HAS A METHOD
		public function has_method($method_name, $controller = null){

			if(is_null($controller)){
				$controller = $this;
			}

			//CYCLE THE CLASS METHODS
			foreach(get_class_methods($controller) as $method){

				//CHECK IF THE METHOD EXISTS
				if(Controller::format_url($method_name) == Controller::format_url($method)){
					return $method;
				}
			}

			//DEFAULT TO FALSE
			return false;
		}

		//SET THE TEMPLATE PATH FOR THIS CONTROLLER
		public function set_template_path(){
			$this->controller_template_path = substr(str_replace(VIEW_PATH, '', $this->get_controller_template_path()), 0, -1);
			return $this;
		}

		//GET THE REQUEST VARS FOR THE CALLING CONTROLLER
		public function get_controller_vars(){

			//SET DEFAULTS
			$get_vars 	= Request::get_vars();
			$int_vars 	= array();
			$named_vars = array();

			//CHECK IF THE CONTROLLER HAS THE METHOD
			if($this->has_method($get_vars[0])){
				Accretion::$template_name = $this->has_method($get_vars[0]);
				unset($get_vars[0]);
			}					

			//PARSE VARS
			foreach($get_vars as $x => $var){
				if(is_numeric($x)){
					$int_vars[] = $var;
				}
				else{
					$named_vars[$x] = $var; 
				}
			}

			//GET THE RESULTING CONTROLLER
			$res = Reflect::reflect_class(get_class($this), '__construct', array_filter(array_merge($int_vars, $named_vars)), true)->set_template_path();

			//SET THE RESULT CONTROLLER VARS TO THIS CONTROLLER
			foreach($res as $k => $v){
				$this->$k = $v;
			}

			//SEND BACK THIS
			return $this;
		}

		//REFLECT THE CONTROLLER
		public function reflect_controller(){

			//SET THE TEMPLATE CONTROLLER PATH
			$this->set_template_path();

			//GET THE CURRENT LOADER VALS
			$temp_controller 		= Accretion::$controller;
			$temp_template_name		= Accretion::$template_name;

			//SET THE CONTROLLER
			Accretion::$controller 	= $this;
			Accretion::$controller_name = str_replace('_Controller', '', get_class($this));



			//GET THE REFLECTED CONTROLLER FROM CONTROLLER VARS
			$this->get_controller_vars();
			
			//RESET LOADER VARS
			Accretion::$controller 	= $temp_controller;
			Accretion::$template_name 	= $temp_template_name;

			//RETURN THE THE CONTROLLER
			return $this;
		}

		//FIND THE FILE PATH OF THE CALLING CONTROLLER
		public function get_controller_path($controller = null){

			if(is_null($controller)){
				$controller = get_class($this);
			}
			elseif(is_object($controller)){
				$controller = get_class($controller);
			}

			//INIT THE REFLECTION CLASS
			$reflector 	= new ReflectionClass($controller);

			//SEND BACK THE FILE NAME
			return $reflector->getFileName();			
		}

		//GET THE TEMPLATE PATH FOR THE CALLING CONTROLLER
		public function get_controller_template_path(){
			return str_replace('.php', '/', str_replace(CONTROLLER_PATH, VIEW_PATH, $this->get_controller_path()));
		}

		//GET THE WEB FORMATTED PATH FOR THE TEMPLATE
		public function get_controller_template_web_path(){
			return str_replace(VIEW_PATH, '/', $this->get_controller_template_path());
		}

		public function get_controller_web_path(){
			return str_replace('.php', '/', str_replace(CONTROLLER_PATH, WEB_APP, $this->get_controller_path()));
		}		

		//GET THE CONTROLLER NAME
		public function controller_name($controller = null){

			if(is_null($controller)){
				$controller = get_class($this);
			}
			elseif(is_object($controller)){
				$controller = get_class($controller);
			}

			$controller = end(explode('\\', $controller));

			return str_replace('_Controller', '', $controller);
		}

		//GET THE REQUESTED TEMPLATE
		public function get_requested_template(){

			//SET DEFAULTS
			$formatted_methods 			= array();
			$formatted_templates 		= array();
			$controller_template_path 	= $this->get_controller_template_path();
			$template_names 			= explode(',', str_replace(array($controller_template_path, '.php'), array('',''), implode(',', glob($controller_template_path.'*.php'))));
			$template 					= array_values(array_filter(explode('/', str_replace(Controller::format_url($this->get_controller_template_web_path()), '', Controller::format_url('/'.implode('/', Request::get()))))))[0];

			//FORMAT THE METHODS
			foreach(get_class_methods($this) as $m){
				$formatted_methods[Controller::format_url($m)] = $m;
			}
			
			//FORMAT THE TEMPLATES
			foreach($template_names as $template_name){
				$formatted_templates[Controller::format_url($template_name)] = $template_name;
			}

			//CHECK METHOD FORMATTING
			if(isset($formatted_methods[$template])){
				return $formatted_methods[$template];
			}

			//CHECK TEMPLATE FORMATTING
			elseif(isset($formatted_templates[$template])){
				return $formatted_templates[$template];
			}
			
			//NO TEMPLATE FOUND SO SEND BACK THE INDEX
			return 'index';
			
		}
	}
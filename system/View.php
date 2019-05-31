<?php
	class View extends Accretion {

		public static $view_parent = false;
		public static $view_loaded = false;
		public static $scripts = [];
		public static $styles = [];
		public static $bodyAttributes = [];


		public function __construct(){
			
		}

		//METHOD FOR GETTING A TEMPLATE
		public function get($template = false, $controller = false, $headers = true, $sub_header = true){

			//GENERATE VARS IF PASSED AS ARRAY
			if(is_array($template)){
				foreach($template as $k => $v){
					$$k = $v;
				}
			}

			$headers 		= (Request::is_ajax() && (!\Request::get('headers') || \Request::get('headers') !== 'true')) ? false : $headers; 
			$sub_header 	= (isset(Accretion::$controller->_disable_subheader) && Accretion::$controller->_disable_subheader === true) ? false : $sub_header;
			$template_path 	= $controller === false ? \Accretion::$template_path : VIEW_PATH.$controller.'/';
			$template 		= !$template ? \Accretion::$template_name : $template;
			$file_path 		= str_replace('//', '/', $template_path.'/'.$template.'.php');			

			//LOAD HEADER AND SUBHEADER IF NEEDED
			if($headers) \View::header();
			if($sub_header) \View::sub_header();
			
			//LOAD THE VIEW IF IT EXISTS
			if(file_exists($file_path)){
				\Accretion::$controller->load_file($file_path);
			} 

			//LOAD TEMPLATE JAVASCRIPT IF LOADED BY AJAX
			if(\Request::is_ajax()){
				foreach(\View::build_search_paths($template.'.js', 'js', true) as $js){					
					echo "<script>".file_get_contents($js)."</script>";					
					break;
				}
			}

			//LOAD SUB FOOTER AND FOOTER
			if($sub_header) \View::sub_footer();
			if($headers) \View::footer();

			//TURN OFF DISABLE SUBHEADER AFTER RUN
			Accretion::$controller->_disable_subheader = false;

			//SET VIEW LOADED TO BE ON
			View::$view_loaded = true;
		}

		public function include_path($paths, $break = true){
			if(!\Accretion::$controller){
				\Accretion::$controller = new \Controller;
			}
			foreach($paths as $path){
				\Accretion::$controller->load_file($path);
				if($break) break;	
			}
		}

		public function page_title(){
			
			if(isset(Accretion::$controller->page_title)){
				return Accretion::$controller->page_title;
			}
			else{

				$res = end(explode('\\', ucwords(trim(str_replace('_', ' ', Accretion::$controller_name)))));				

				if(\Accretion::$method_name !== 'index'){
					$res .= ' | '.ucwords(trim(str_replace('_', ' ', Accretion::$method_name)));
				}

				return $res;
			}
		}

		//LOAD THE TEMPLATE HEADER
		public function header(){
			if(\View::include_header()) View::include_path(View::build_search_paths('header.php', 'partial'));			
		}

		//LOAD THE TEMPLATE FOOTER
		public function footer(){
			if(\View::include_header()) View::include_path(View::build_search_paths('footer.php', 'partial'));
		}

		//LOAD THE SUB HEADER
		public function sub_header(){
			if(\View::include_header()){			
				$paths = View::build_search_paths(\Accretion::$template_name.'_sub_header.php', 'partial');
				\View::include_path(array_reverse(empty($paths) ? View::build_search_paths('sub_header.php', 'partial') : $paths), false);
			}
		}

		//LOAD THE SUB FOOTER
		public function sub_footer(){
			if(View::include_header()){			
				$paths = View::build_search_paths(Accretion::$template_name.'_sub_footer.php', 'partial');
				View::include_path(array_reverse(empty($paths) ? View::build_search_paths('sub_footer.php', 'partial') : $paths), false);
			}
		}

		//CHECK WEATHER OR NOT THE HEADER SHOULD BE INCLUDED
		public static function include_header(){
			$method_name 	= Controller::format_url(Accretion::$method_name);
			$template_name 	= Controller::format_url(Accretion::$template_name);
			$controller 	= Accretion::$controller;
			$headers 		= array();
			if(isset($controller->disabled_headers)){
				foreach($controller->disabled_headers as $k => $v){
					$headers[] = Controller::format_url($v);
				}
			}

			if(in_array($method_name, $headers)){
				return false;
			}

			if(in_array($template_name, $headers)){
				return false;
			}

			return true;
		}

		public static function build_search_paths($file_name, $folder_name = false, $only_folder = false, $controller_template_path = false){


			//SET THE CONTROLLER TEMPLATE PATH
			if(!$controller_template_path){
				$controller_template_path 	= Accretion::$controller->controller_template_path;
			}			

			//INIT EMPTY PATHS ARRAY
			$paths = array();			

			//IF THE CONTROLLER TEMPLATE PATH HAS SUB PATHS IN IT
			if(strpos($controller_template_path, '/') !== false){

				//GET THE PARTS OF THE CONTROLLER TEMPLATE PATH
				$parts = explode('/', $controller_template_path);
				
				//CYCLE THE PARTS
				foreach($parts as $k => $part){
					
					//INIT A NEW PATH PARTS ARRAY
					$path_parts = array();

					//ADD ONLY THE PARTS UP TO THE CURRENT SEARCH PART
					for($x = 0; $x <= $k; $x++){
						$path_parts[] = $parts[$x];
					}

					//IF THERE IS A FOLDER NAME WE ARE SEARCHING FOR
					if($folder_name){
						
						//ADD THE PARTS WITH THE FOLDER NAME
						$paths[] = VIEW_PATH.implode('/', $path_parts).'/'.$folder_name.'/'.$file_name;
						
						//IF WE ARE NOT SEARCHING ONLY IN THAT FOLDER
						if(!$only_folder){

							//ADD THE PARTS FROM THE MAIN TEMPLATE PATH
							$paths[] = VIEW_PATH.implode('/', $path_parts).'/'.$file_name;
						}
					}

					//WE ARE NOT SEARCHING IN A FOLDER
					else{
						$paths[] = VIEW_PATH.implode('/', $path_parts).'/'.$file_name;
					}	
				}

				//REVERSE THE PATHS TO START AT THE END
				$paths = array_reverse($paths);
			}
			else{
				if($folder_name){
					$paths[] = VIEW_PATH.$controller_template_path.'/'.$folder_name.'/'.$file_name;
					if(!$only_folder){
						$paths[] = VIEW_PATH.$controller_template_path.'/'.$file_name;
					}
				}
				else{
					$paths[] = VIEW_PATH.$controller_template_path.'/'.$file_name;
				}
			}

			if($folder_name){
				$paths[] = VIEW_PATH.$folder_name.'/'.$file_name;
				if(!$only_folder){
					$paths[] = VIEW_PATH.$file_name;
				}
			}
			else{
				$paths[] = VIEW_PATH.$file_name;
			}

			$new_paths = array();
			foreach($paths as $path){
				if(file_exists($path)){
					$new_paths[] = $path;
				}
			}

			return $new_paths;
		}		

		public function auto_header(){

			//GET THE STYLESHEETS
			foreach(Config::get('css') as $css){
				View::css($css);
			}

			//GET THE MAIN STYLE SHEET
			View::css('style');

			//GET THE JAVASCRIPT
			foreach(Config::get('js') as $js){
				View::js($js);
			}

			$system_web_path = WEB_APP.'system/';

			echo "\n<link rel=\"stylesheet\" type=\"text/css\" href=\"{$system_web_path}css/Accretion.css\">\n";

			echo "<script>WEB_APP = '".WEB_APP."'</script>";

			$accretion_js = array(
				'Accretion',
				'Guid',
				'Modal',
				'Ajax/Ajax',
				'Ajax/AjaxForm',
				'Ajax/AjaxLink',
				'Ajax/AjaxUpload',
				'Table/Table',
				'Table/TableFilterable',
				'Table/TableSearchable',
				'Table/TableSelectable'
			);

			foreach($accretion_js as $js){
				echo "\n<script src=\"{$system_web_path}js/Accretion/{$js}.js\"></script>\n";
			}			
			
			
			//GET THE MAIN JAVASCRIPT
			View::js('script');

			//LOAD CSS AND JS FOR THE PATH
			$paths = array();
			foreach(explode('/', Accretion::$controller->controller_template_path) as $path){
				$paths[] = $path;
				View::css($path, implode('/', $paths));
				View::js($path, implode('/', $paths));				
			}

			//LOAD CSS AND JS FOR THE TEMPLATE
			View::css(Accretion::$template_name, implode('/', $paths));
			View::js(Accretion::$template_name, implode('/', $paths));
		}

		public static function parse_asset_params($arr){
			$res = [];
			foreach($arr as $k => $v) $res[] = $k.'="'.$v.'"';
			return implode(' ', $res);
		}


		public function js($file_name, $path = false, $params = []){

			if(strtolower(substr($file_name, -3)) == '.js' && validate_url($file_name)){
				if(!in_array($file_name, View::$scripts)){
					View::$scripts[] = $file_name;
					echo "<script src=\"{$file_name}\" ".\View::parse_asset_params($params)."></script>";
				}
			}
			else{

				//CHECK TEMPLATE SCRIPTS FIRST			
				$time 		= time();
				$file_name 	.= '.js';
				$path 		= $path === false ? Accretion::$controller->controller_template_path : $path;
				$paths = View::build_search_paths($file_name, 'js', true, $path);

				foreach($paths as $path){
					$web_path = str_replace(VIEW_PATH, WEB_VIEW_PATH, $path);
					if(!in_array($web_path, View::$scripts)){
						View::$scripts[] = $web_path;
						$web_path .= '?'.time();
						echo "\n<script src=\"{$web_path}\" ".\View::parse_asset_params($params)."></script>\n";
					}
					break;
				}
			}			
		}

		public function css($file_name, $path = false, $params = []){



			//$original_file_name = $file_name;


			if(strtolower(substr($file_name, -4)) == '.css' && validate_url($file_name)){
				if(!in_array($file_name, View::$styles)){
					View::$styles[] = $file_name;
					echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$file_name}\" ".\View::parse_asset_params($params).">";
				}
			}
			else{
				$path 		= $path === false ? Accretion::$controller->controller_template_path : $path;
				$file_name 	.= '.css';		
				$time 		= time();				
				$paths 		= View::build_search_paths($file_name, 'css', true, $path);

				

				foreach($paths as $path){					
					$web_path = str_replace(VIEW_PATH, WEB_VIEW_PATH, $path);


					if(!in_array($web_path, View::$styles)){
						View::$styles[] = $web_path;
						$web_path .= '?'.time();
						echo "\n<link rel=\"stylesheet\" type=\"text/css\" href=\"{$web_path}\" ".\View::parse_asset_params($params).">\n";
					}
					
					break;
				}
			}
		}

		public static function is($path = null){
			if(!is_null($path)){
				return \View::local_template_path(true) === \Controller::format_url($path) ? true : false;
			}
			return \View::local_template_path(true);
			
		}

		public static function has($path){
			return strpos(View::local_template_path(true), Controller::format_url($path)) !== false ? true : false;
		}

		//CHECK ON THE LOCAL TEMPLATE PATH
		public static function local_template_path($format = false){

			//GET THE PATH
			$path = str_replace(VIEW_PATH, '', Accretion::$template_path.Accretion::$template_name);

			//IF WE ARE COMPARING THE PROVIDED PATH
			if($format !== false && $format !== true){
				if(Controller::format_url($format) == Controller::format_url($path)){
					return true;
				}
				return false;
			}

			//RETURN THE PATH 
			return $format ? Controller::format_url($path) : $path;
		}

		public function make($template = false, $controller = false, $headers = true, $sub_header = true){

			return Buffer::start(function($vars){
				View::get($vars);
				View::$view_loaded = true;
			}, get_defined_vars());
		}

		public function make_partial($name, $controller = false, $method = false){

			return Buffer::start(function($vars){
				View::partial($vars);
			}, get_defined_vars());
		}

		public function partial($name, $controller = false, $method = false){

			if(is_array($name)){
				foreach($name as $k => $v){
					$$k = $v;
				}
			}

			//APPEND THE PHP EXTENSION
			$name .= '.php';

			//IF THERE WAS NO CONTROLLER PASSED
			if(!$controller){				
				$controller 	= Accretion::$controller_name;
				$template_path 	= Accretion::$controller->controller_template_path;
			}
			else{
				$template_path = $controller;
			}

			//SET TEMPORARY METHOD
			$method_name = Accretion::$method_name;

			if($method){
				Accretion::$method_name = $method;
			}

			//LOAD THE FILE
			View::include_path(View::build_search_paths($name, 'partial', true, $template_path));

			//RESET THE METHOD
			Accretion::$method_name = $method_name;
		}
	}
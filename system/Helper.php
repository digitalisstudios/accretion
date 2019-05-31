<?php
	class Helper extends Accretion {

		public static $helper_names = false;
		public static $_loaded_helpers = [];
		
		public function __construct(){
			
			//MAKE SURE HELPER NAMES ARE LOADED
			Helper::build_helper_names();
		}

		public static function __callStatic($name, $value = array()){

			//MAKE SURE HELPER NAMES ARE LOADED
			Helper::build_helper_names();
			
			$path = Helper::$helper_names[Controller::format_url($name)];
			$name = pathinfo($path)['filename'];

			//LOAD THE FILE
			require_once $path;

			//SET THE CLASS NAME
			$class_name = class_exists($name.'_Helper') ? $name.'_Helper' : $name;

			//GET THE NAMESPACE OF THE FILE
			$namespace = fileNameSpace($path);

			

			//RETURN THE REFLECTED CLASS
			return Reflect::reflect_class($class_name, '__construct', $value, true);	
		}

		//GET A HELPER BY NAME
		public static function get($name, $value = array()){

			//SEND BACK THE HELPER CLASS
			return Helper::$name($value);
		}

		public static function get_loaded($name, $value = []){
			if(isset(\Helper::$_loaded_helpers[Controller::format_url($name)])){
				$arr = \Helper::$_loaded_helpers[\Controller::format_url($name)];
				return end($arr);
			}

			\Helper::$_loaded_helpers[\Controller::format_url($name)] = [];
			$res = \Helper::get($name, $value);
			\Helper::$_loaded_helpers[\Controller::format_url($name)][] = $res;
			return $res;

		}

		public static function find_helpers_by_path($path, $dir = false, $extension = false){

			if(!$dir){
				if(!$extension){
					foreach(glob($path.'*.php') as $helper_path) Helper::$helper_names[Controller::format_url(pathinfo($helper_path, PATHINFO_FILENAME))] = $helper_path;
					\Helper::find_helpers_by_path($path, true);
					\Helper::find_helpers_by_path($path, false, true);
				}
				else{
					foreach(glob($path.'*_Extension.php') as $helper_path) Helper::$helper_names[Controller::format_url(str_replace('-extension', '', pathinfo($helper_path, PATHINFO_FILENAME)))] = $helper_path;
					\Helper::find_helpers_by_path($path, true, true);
				}
			}
			else{

				//FIND HELPERS IN SUB DIRECTORIES
				foreach(glob($path.'*') as $helper_path){
					if(is_dir($helper_path)){
						$ext = $extension === false ? '/*.php' : '/*_Extension.php';
						foreach(glob($helper_path.$ext) as $sub_helper_path){
							if(pathinfo($sub_helper_path, PATHINFO_FILENAME) == basename($helper_path)){
								Helper::$helper_names[Controller::format_url(basename($helper_path))] = $sub_helper_path;
							}
						}
					}
				}
			}
		}

		//GET THE HELPER PATHS GROUPED BY A FORMATTED NAME
		public static function build_helper_names(){

			if(Helper::$helper_names === false){


				\Helper::find_helpers_by_path(HELPER_PATH);
				\Helper::find_helpers_by_path(SYSTEM_HELPER_PATH);
				\Helper::find_helpers_by_path(HELPER_PATH, true);


				//FIND THE THE HELPERS
				foreach(glob(HELPER_PATH.'*_Extension.php') as $helper_path){
					$file_name = pathinfo($helper_path)['filename'];
					Helper::$helper_names[str_replace('-extension', '', Controller::format_url($file_name))] = $helper_path;
				}
				//foreach([HELPER_PATH,SYSTEM_HELPER_PATH] as $path) \Helper::find_helpers_by_path($path);

				//\Helper::find_helpers_by_path(HELPER_PATH);
				//\Helper::find_helpers_by_path(SYSTEM_HELPER_PATH);

				/*

				//FIND THE THE HELPERS
				foreach(glob(HELPER_PATH.'*.php') as $helper_path) Helper::$helper_names[Controller::format_url(pathinfo($helper_path, PATHINFO_FILENAME))] = $helper_path;

				//FIND HELPERS IN SUB DIRECTORIES
				foreach(glob(HELPER_PATH.'*') as $helper_path){
					if(is_dir($helper_path)){
						foreach(glob($helper_path.'/*.php') as $sub_helper_path){
							if(pathinfo($sub_helper_path, PATHINFO_FILENAME) == basename($helper_path)){
								Helper::$helper_names[Controller::format_url(basename($helper_path))] = $sub_helper_path;
							}
						}
					}
				}

				//FIND THE SYSTEM HELPERS
				foreach(glob(SYSTEM_HELPER_PATH.'*.php') as $helper_path){
					$file_name = pathinfo($helper_path)['filename'];
					Helper::$helper_names[Controller::format_url($file_name)] = $helper_path;
				}

				//FIND THE THE HELPERS
				foreach(glob(HELPER_PATH.'*_Extension.php') as $helper_path){
					$file_name = pathinfo($helper_path)['filename'];
					Helper::$helper_names[str_replace('-extension', '', Controller::format_url($file_name))] = $helper_path;
				}
				*/
			}
		}

		public function helper_name(){
			return str_replace('_Helper', '', get_class($this));
		}

		public function load_helper_view($view){

			$name = $this->helper_name();

			$paths = [				
				HELPER_PATH.$name.'/'.$view.'.php',
				HELPER_PATH.str_replace('_Extension', '', $name).'/'.$view.'.php',
				VIEW_PATH.$name.'/'.$view.'.php',
				SYSTEM_HELPER_PATH.str_replace('_Extension', '', $name).'/'.$view.'.php',
			];
			foreach($paths as $path){
				if(file_exists($path)){
					include $path;
					break;
				}
			}
		}

		public function load_helper_partial($view){

			$name = $this->helper_name();

			$paths = array_reverse([
				HELPER_PATH.$name.'/partial/'.$view.'.php',
				HELPER_PATH.str_replace('_Extension', '', $name).'/partial/'.$view.'.php',				
				SYSTEM_HELPER_PATH.str_replace('_Extension', '', $name).'/partial/'.$view.'.php',				
			]);

			foreach($paths as $path){
				if(file_exists($path)){
					include $path;
					break;
				}
			}
		}
	}	
?>
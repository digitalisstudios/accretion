<?php
	class Helper extends Accretion {

		public static $helper_names = false;
		
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

			//RETURN THE REFLECTED CLASS
			return Reflect::reflect_class($name.'_Helper', '__construct', $value, true);	
		}

		//GET A HELPER BY NAME
		public static function get($name, $value = array()){

			//SEND BACK THE HELPER CLASS
			return Helper::$name($value);
		}

		//GET THE HELPER PATHS GROUPED BY A FORMATTED NAME
		public static function build_helper_names(){

			if(Helper::$helper_names === false){

				//FIND THE THE HELPERS
				foreach(glob(HELPER_PATH.'*.php') as $helper_path){
					$file_name = pathinfo($helper_path)['filename'];
					Helper::$helper_names[Controller::format_url($file_name)] = $helper_path;
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

			$paths = [
				HELPER_PATH.$name.'/partial/'.$view.'.php',
				HELPER_PATH.str_replace('_Extension', '', $name).'/partial/'.$view.'.php',				
				SYSTEM_HELPER_PATH.str_replace('_Extension', '', $name).'/partial/'.$view.'.php',				
			];

			foreach($paths as $path){
				if(file_exists($path)){
					include $path;
					break;
				}
			}
		}		
	}	
?>
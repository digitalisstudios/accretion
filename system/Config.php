<?php
	class Config extends Accretion{

		public static $data 		= false;
		public static $mode 		= 'prod';
		public static $compiled 	= false;
		public static $context 		= 'app';
		public static $settings 	= [];
		public static $env;

		public function __construct(){

		}

		public function init(){
			
			//DEFINE GENERAL PATHS
			define('APP_PATH', dirname(getcwd()).'/');
			define('GLOBAL_APP_PATH', dirname(dirname(__FILE__)).'/');
			define('BASE_PATH', dirname(APP_PATH).'/');
			define('ROOT_PATH', dirname(BASE_PATH).'/');

			//DEFINE COMPONENT PATHS
			define('CONTROLLER_PATH', 		APP_PATH.'controller/');
			define('STORAGE_PATH', 			APP_PATH.'storage/');
			define('STORAGE_TEMP_PATH',		STORAGE_PATH.'_temp/');
			define('SYSTEM_PATH', 			APP_PATH.'system/');
			define('CONFIG_PATH', 			APP_PATH.'config/');
			define('SPARK_PATH', 			APP_PATH.'command/');
			define('SYSTEM_HELPER_PATH', 	SYSTEM_PATH.'helper/');
			define('HELPER_PATH', 			APP_PATH.'helper/');
			define('MODEL_PATH', 			APP_PATH.'model/');
			define('VENDOR_PATH', 			APP_PATH.'vendor/');
			define('PUBLIC_PATH', 			APP_PATH.'public/');
			define('VIEW_PATH', 			APP_PATH.'view/');
			define('JS_PATH', 				VIEW_PATH.'js/');
			define('CSS_PATH', 				VIEW_PATH.'css/');

			foreach([STORAGE_PATH, STORAGE_TEMP_PATH] as $createPath) if(!file_exists($createPath)) mkdir($createPath, 0777, true);

			//DEFINE WEB PATHS
			define('WEB_PATH', '/');			
			
			if(isset($_SERVER['SCRIPT_NAME'])){

				if(!isset($_SERVER['REQUEST_URI'])){
					$_SERVER['REQUEST_URI'] = '';
				}

				$parts 			= array();
				$script_parts 	= array_values(array_filter(explode('/', dirname($_SERVER['SCRIPT_NAME']))));
				$uri_parts 		= array_values(array_filter(explode('/', $_SERVER['REQUEST_URI'])));

				if(empty($uri_parts)){
					$web_app = '/';
				}
				else{
					$found = false;
					foreach($script_parts as $part){
						if(!$found){							
							if($part == $uri_parts[0]){
								$found = true;
							}
							else{
								continue;
							}
						}

						$parts[] = $part;
					}
					$web_app = "/".implode('/', $parts)."/";
				}

				$web_app = '/'.implode('/', array_values(array_filter(explode('/', $web_app)))).'/';
				$web_app = $web_app == '//' ? '/' : $web_app;

				define('WEB_APP', $web_app);
			}
			else{
				define('WEB_APP', '/');
			}
			
			define('WEB_VIEW_PATH', WEB_APP.'view/');
			define('WEB_JS_PATH', WEB_VIEW_PATH.'js/');
			define('WEB_CSS_PATH', WEB_VIEW_PATH.'css/');
			define('WEB_APP_URL', (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != 'on' ? 'http' : 'https').'://'.$_SERVER['SERVER_NAME'].WEB_APP);

			//CREATE THE ENV FILE IF NEEDED
			/*if(!file_exists(APP_PATH.'.env')) fclose(fopen(APP_PATH.'.env', 'w+')); 

			//LOAD THE ENV FILE
			$dotenv = \Dotenv\Dotenv::create(APP_PATH);
			$dotenv->load();
			*/

			//GET THE SERVER CONFIG VALUES
			Config::get_loader_config();

			//SET THE SERVER MODE
			Config::get_server_mode();			

			//PARSE THE SETTINGS BASED ON THE CURRENT SERVER
			Config::$data = Config::parse_server_settings();

			//SETUP SYM LINKS
			\Config::load_sym_links();			

			//AUTOLOAD THE MODELS
			\Config::load_models();

			//AUTOLOAD THE DB VIEWS
			\Config::load_db_views();

			//TEST THE DB CONNECTIONS
			//Config::test_db_connections();

			//GET THE COMPOSER AUTOLOAD
			//if(file_exists(VENDOR_PATH.'autoload.php'))	require_once VENDOR_PATH.'autoload.php';

			
			//foreach(glob(MODEL_PATH.'*.php') as $model_path) Model::get(pathinfo($model_path)['filename']);

			//AUTOLOAD ALL OF THE MODELS
			//Config::load_models();	

			//AUTOLOAD THE SYSTEM HELPERS
			//foreach(glob(SYSTEM_HELPER_PATH.'*.php') as $helper_path) include_once $helper_path;

			//LOAD THE CONTROLLER FILES
			Config::load_controllers();	

			if(function_exists('_boot')) _boot();

			//SEND BACK THE CONFIG DATA
			return Config::$data;
		}

		public static function load_sym_links(){

			$paths = [
				//PUBLIC_PATH.'view/' => '../view/',
				//PUBLIC_PATH.'storage/' => '../storage/',
			];

			/*

			$paths = [
				PUBLIC_PATH.'view/' => VIEW_PATH,
			];
			*/

			//$server_config = \Config::get_loader_config();

			$server_config = \Config::parse_server_settings();

			if(isset($server_config->sym_links)) foreach($server_config->sym_links as $k => $v) $paths[$k] = $v;

			foreach($paths as $from => $target) exec("ln -sf ".$target." ".rtrim($from, '/'));

			$cleanupPaths = [
				PUBLIC_PATH.'view/view',
				PUBLIC_PATH.'storage/storage'
			];

			foreach($cleanupPaths as $path){
				//exec("rm -f {$path}");
			}
		}

		

		//LOAD THE SERVER CONFIG DATA AND GENERATE THE JSON IF NEEDED
		public function get_loader_config(){

			//SET THE FILE PATH
			$loader_config = APP_PATH.'server_info.loaderconfig';

			//CREATE THE FILE IF IT DOES NOT EXIST
			if(!file_exists($loader_config)){

				$conf = [
					'HTTP_HOST' 		=> $_SERVER['SERVER_NAME'],
					'SERVER_ADDR' 		=> $_SERVER['SERVER_ADDR'],
					'dev_mode'			=> 'false',
					"accretion_mode" 	=> "prod",
					"accretion_context" => "app",
				];

				//GENERATE THE STRING
				$str = json_encode($conf, JSON_PRETTY_PRINT);

				//STORE THE FILE
				$handle = fopen($loader_config, 'w+');
				fwrite($handle, $str);
				fclose($handle);
			}

			//LOAD THE JSON DATA
			$server_config = json_decode(file_get_contents($loader_config), true);

			if(isset($server_config['accretion_mode'])) \Config::$mode 		= $server_config['accretion_mode'];
			if(isset($server_config['accretion_context']))\Config::$context = $server_config['accretion_context'];
			\Config::$settings 												= $server_config;

			//SET SERVER CONFIG VARS
			foreach($server_config as $k => $v) $_SERVER[$k] = $v;

			//SEND BACK THE SERVER CONFIG DATA
			return $server_config;
		}

		/*
		public static function load_models(){

			//SET MODEL NAMES IF NEEDED
			if(!\Storage::get('_model_names')) foreach(glob(MODEL_PATH.'*.php') as $model_path){
				\Storage::set('_model_names.'.\Controller::format_url(pathinfo($model_path, PATHINFO_FILENAME)), $model_path);

				$model_name = pathinfo($model_path, PATHINFO_FILENAME);		

				$sub_dir = dirname($model_path).'/'.$model_name.'/';

				if(file_exists($sub_dir) && is_dir($sub_dir)){

					$traits = glob($sub_dir.'*.php');

					if(!empty($traits)) foreach($traits as $trait){
						include_once $trait;
					} 
					
				}

				include_once $model_path;
			}
		}
		*/

		public static function load_db_views($force = false){
			//ini_set('display_errors', 1);
			//error_reporting(E_ALL);
			foreach(glob(APP_PATH.'App/Db/View/*.php') as $view_file){
				$class_name = '\App\Db\View\\'.pathinfo($view_file, PATHINFO_FILENAME);
				$view = new $class_name;
				$view->generate($force = false);
			}

			//pr('done');
			//exit;
		}

		public static function load_models(){

			if(!\Storage::get('_orm_methods')){
				
				$methods = get_class_methods('Model_Orm');

				foreach($methods as $k => $method){

					if(substr($method, 0, 5) == '_orm_') $methods[substr($method, 5)] = $method; 

					unset($methods[$k]);	
				}

				foreach(\Model_Orm::$_map as $k => $method){
					$methods[$k] = $method; 
				}

				\Model_Orm::$_map = $methods;

				\Storage::set('_orm_methods', new \ORM_Wrapper($methods));
			}

			//SET MODEL NAMES IF NEEDED
			if(!\Storage::get('_model_names')) foreach(glob(MODEL_PATH.'*.php') as $model_path){

				$class_name = pathinfo($model_path, PATHINFO_FILENAME);

				\Storage::set('_model_names.'.\Controller::format_url($class_name), $model_path);

			}
		}

		/*
		public static function load_models(){

			if(!\Storage::get('_orm_methods')){
				
				$methods = get_class_methods('Model_Orm');

				foreach($methods as $k => $method){

					if(substr($method, 0, 5) == '_orm_') $methods[substr($method, 5)] = $method; 

					unset($methods[$k]);	
				} 

				\Storage::set('_orm_methods', new \ORM_Wrapper($methods));
			}

			//SET MODEL NAMES IF NEEDED
			if(!\Storage::get('_model_names')) foreach(glob(MODEL_PATH.'*.php') as $model_path){

				$class_name = pathinfo($model_path, PATHINFO_FILENAME);

				\Storage::set('_model_names.'.\Controller::format_url($class_name), $model_path);

			}
		}
		*/

		/*
		public static function load_models(){

			if(!\Storage::get('_orm_methods')){
				$methods = get_class_methods('Model_Orm');
				//foreach($methods as $k => $method) if(substr($method, 0, 5) !== '_orm_') unset($methods[$k]);
				foreach($methods as $k => $method){
					if(substr($method, 0, 5) == '_orm_'){
						$methods[substr($method, 5)] = $method;
					}
					
					unset($methods[$k]);	
				} 
				\Storage::set('_orm_methods', new \ORM_Wrapper($methods));
			}

			//SET MODEL NAMES IF NEEDED
			if(!\Storage::get('_model_names')) foreach(glob(MODEL_PATH.'*.php') as $model_path){

				$class_name = pathinfo($model_path, PATHINFO_FILENAME);

				\Storage::set('_model_names.'.\Controller::format_url($class_name), $model_path);


				

				include_once $model_path;
			}
		}
		*/

		//RECURSIVELY PRELOAD ALL OF THE CONTROLLERS
		public static function load_controllers($path = false){			

			//LOOP THROUGH THE PATHS
			foreach(glob(($path === false ? CONTROLLER_PATH : (substr($path, -1) !== '/' ? $path.'/' : $path)).'*') as $file){

				//SET THE CONTROLLER META PATH 
				Accretion::$controllers[strtolower(str_replace('_', '-', str_replace('.php', '', str_replace(CONTROLLER_PATH, '', $file))))] = $file;

				//IF THIS IS A DIRECTORY LOAD THE SUB CONTROLLERS
				if(is_dir($file)) Config::load_controllers($file);
			}
		}

		//GET EITHER A SINGLE CONFIG VAR OR ALL
		public static function get($var = false){

			//LOAD THE CONFIG DATA
			$config = Config::compile_settings();

			//SET THE CONFIG DATA IN BOTH THE LOADER CLASS AND THE CONFIG CLASS
			if(!Config::$data) Accretion::$config = Config::$data = json_decode(json_encode($config));

			if($var){
				$data = Config::$data;

				$varParts = explode('.', $var);

				for($x = 0; $x < count($varParts); $x++){
					$data = $data->{$varParts[$x]};
				}

				return $data;
			}

			//RETURN THE REQUESTED CONFIG VAR OR ALL CONFIG DATA
			return $var ? Config::$data->$var : Config::$data;
		}

		public static function set($var, $value){

			//GET THE DATA AS AN ARRAY
			$data = json_decode(json_encode(\Config::get()), true);

			//SET THE VALUE OF THE ARRAY
			assignArrayByPath($data, $var, $value, '.');

			//SET THE ACCRETION CONFIG AND CONFIG DATA
			\Accretion::$config = \Config::$data = json_decode(json_encode($data));
		}

		public static function compile_settings(){

			//IF SETTINGS ALREADY COMPILED SKIP
			if(Config::$data && Config::$compiled) return Config::$data;

			//SET THE SETTINGS FILE
			$settings = dirname(getcwd()).'/config/Settings.php';

			//SET THE SETTINGS FILE
			//$settings = dirname(__FILE__).'/../config/Settings.php';

			//INCLUDE THE SETTINGS FILE
			include $settings;

			//CHECK IF THE ENCRYPTION KEY NEEDS TO BE GENERATED
			if($config['encryption_key'] === 'COMPILE_ENCRYPTION_KEY'){

				$config['encryption_key'] 	= Helper::Encryption()->generate_key();
				$config['servers']['dev'] 	= $_SERVER['SERVER_ADDR'];
				$config['servers']['prod'] 	= $_SERVER['SERVER_ADDR'];

				//LOAD THE RAW SETTINGS FILE CONTENT
				$content = file_get_contents($settings);

				//COMPILE THE SETTINGS
				$content = str_replace(
					array(
						'COMPILE_ENCRYPTION_KEY', 
						'COMPILE_DEV_SERVER_IP_ADDRESS', 
						'COMPILE_PROD_SERVER_IP_ADDRESS'
					), 
					array(
						$config['encryption_key'], 
						$config['servers']['dev'], 
						$config['servers']['prod']
					), 
					$content
				);

				//STORE THE SETTINGS FILE
				$handle = fopen($settings, 'w+');
				fwrite($handle, $content);
				fclose($handle);
			}

			self::$compiled = true;

			//SEND BACK THE CONFIG DATA
			return self::$data = $config;
		}

		public static function get_server_mode(){

			Config::compile_settings();

			return Config::$mode = $_SERVER['accretion_mode'];
		}

		public static function test_db_connections(){

			$dbs = Config::get('database');			

			foreach($dbs as $alias => $credentials){
				$dbs->$alias->can_connect = \DB::test_connection($credentials->host, $credentials->user, $credentials->password, $credentials->database);
			}

			Config::$data->database = $dbs;

			if(strpos(Config::$data->database->main->host, 'COMPILE_APP_') !== false){
				if(\Config::parse_server_settings()->use_db !== 'false'){
					//Helper::DB_Installer()->run();
				}				
			}
		}

		public static function parse_server_settings($data = null, $last_key = null){

			if(is_null($data)){
				$data = Config::$data;
			}

			if(is_object($data)){
				$data = json_decode(json_encode($data), true);
			}

			if(is_array($data)){
				foreach($data as $k => $v){
					if(strpos($k, '_by_server') !== false){

						unset($data[$k]);
						$k = str_replace('_by_server', '', $k);
						
						foreach($v as $x => $r){

							if($x == Config::$mode){
								$data[$k] = Config::parse_server_settings($r, $last_key);
								break;
							}
						}
					}
					else{
						$data[$k] = Config::parse_server_settings($v, $k);
					}
				}
			}

			return json_decode(json_encode($data));
		}
	}
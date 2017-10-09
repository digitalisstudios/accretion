<?php
	class Config extends Accretion{

		public static $data = false;
		public static $mode = 'prod';

		public function __construct(){

		}

		public function init(){			

			//DEFINE GENERAL PATHS
			define('APP_PATH', dirname(dirname(__FILE__)).'/');
			define('BASE_PATH', dirname(APP_PATH).'/');
			define('ROOT_PATH', dirname(BASE_PATH).'/');

			//DEFINE COMPONENT PATHS
			define('CONTROLLER_PATH', APP_PATH.'controller/');
			define('SYSTEM_PATH', APP_PATH.'system/');
			define('CONFIG_PATH', APP_PATH.'config/');
			define('SPARK_PATH', APP_PATH.'command/');
			define('SYSTEM_HELPER_PATH', SYSTEM_PATH.'helper/');
			define('HELPER_PATH', APP_PATH.'helper/');
			define('MODEL_PATH', APP_PATH.'model/');
			define('VENDOR_PATH', APP_PATH.'vendor/');
			define('PUBLIC_PATH', APP_PATH.'public/');
			define('VIEW_PATH', PUBLIC_PATH.'view/');
			define('JS_PATH', VIEW_PATH.'js/');
			define('CSS_PATH', VIEW_PATH.'css/');

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

			//GET THE SERVER CONFIG VALUES
			Config::get_loader_config();

			//SET THE SERVER MODE
			Config::get_server_mode();			

			//PARSE THE SETTINGS BASED ON THE CURRENT SERVER
			Config::$data = Config::parse_server_settings();

			//TEST THE DB CONNECTIONS
			Config::test_db_connections();

			//GET THE COMPOSER AUTOLOAD
			if(file_exists(VENDOR_PATH.'autoload.php'))	require_once VENDOR_PATH.'autoload.php';

			//AUTOLOAD ALL OF THE MODELS
			foreach(glob(MODEL_PATH.'*.php') as $model_path) Model::get(pathinfo($model_path)['filename']);

			//AUTOLOAD THE SYSTEM HELPERS
			foreach(glob(SYSTEM_HELPER_PATH.'*.php') as $helper_path) include_once $helper_path;

			//LOAD THE CONTROLLER FILES
			Config::load_controllers();	

			//SEND BACK THE CONFIG DATA
			return Config::$data;
		}

		//LOAD THE SERVER CONFIG DATA AND GENERATE THE JSON IF NEEDED
		public function get_loader_config(){

			//SET THE FILE PATH
			$loader_config = SYSTEM_PATH.'server_info.loaderconfig';

			//CREATE THE FILE IF IT DOES NOT EXIST
			if(!file_exists($loader_config)){

				$conf = [
					'HTTP_HOST' 	=> $_SERVER['SERVER_NAME'],
					'SERVER_ADDR' 	=> $_SERVER['SERVER_ADDR'],
					'dev_mode'		=> 'false'
				];

				//GENERATE THE STRING
				$str = json_encode($conf);

				//STORE THE FILE
				$handle = fopen($loader_config, 'w+');
				fwrite($handle, $str);
				fclose($handle);
			}

			//LOAD THE JSON DATA
			$server_config = json_decode(file_get_contents($loader_config), true);

			//SET SERVER CONFIG VARS
			foreach($server_config as $k => $v) $_SERVER[$k] = $v;

			//SEND BACK THE SERVER CONFIG DATA
			return $server_config;
		}

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

			//RETURN THE REQUESTED CONFIG VAR OR ALL CONFIG DATA
			return $var ? Config::$data->$var : Config::$data;
		}

		public static function compile_settings(){

			//IF SETTINGS ALREADY COMPILED SKIP
			//if(Config::$data) return Config::$data;

			//SET THE SETTINGS FILE
			$settings = dirname(__FILE__).'/../config/Settings.php';

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

			//SEND BACK THE CONFIG DATA
			return $config;
		}

		public static function get_server_mode(){

			foreach(Config::get('servers') as $type => $address){
				if($_SERVER['SERVER_ADDR'] === $address){
					Config::$mode = $type;
					return $type;
				}
			}

			return 'prod';
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
?>
<?php

	class Routes extends Accretion{

		public static $_routes = [];
		public static $_match = false;

		public static function checkRoute(){

			if($_SERVER['accretion_context'] == 'api'){
				include_once APP_PATH.'routes/api.php';
			}
			else{
				include_once APP_PATH.'routes/web.php';
			}

			return self::$_match;
		}
		
		
	}

	class Route extends Routes {

		protected $_path;
		protected $_type;
		protected $_callback;
		protected $_name;
		protected $_vars = [];

		public function __construct($path, $type, $callback){

			$this->_path = $path;
			$this->_type = $type;
			$this->_callback = $callback;

			$pathParts = array_filter(explode('/', \Controller::format_url($this->_path)));

			$matched = true;

			if($type !== 'any' && strtolower($_SERVER['REQUEST_METHOD']) != $type){
				$matched = false;
			}
			else{

				if(!\Request::get(1)){
					if(count($pathParts)){
						$matched = false;
					}
				}
				


				foreach($pathParts as $k => $v){
					if(\Request::get(($k+1))){
						if(strpos($pathParts[$k], '{') !== false){

							//$pathParts[$k] = ['name' => str_replace(['{', '}'], '', $pathParts[$k]), 'value' => \Request::get($k+1)];
							$this->_vars[str_replace(['{', '}'], '', $pathParts[$k])] = \Request::get(($k+1));
							continue;
						}
						else{
							if($v != \Controller::format_url(\Request::get(($k+1)))){
								$matched = false;
							}
						}
					}				
				}
			}

			

			Routes::$_routes[$type][$path] = $this;

			

			if($matched) \Routes::$_match = $this;

			return $this;
		}

		public static function get($path, $callback){
			return new \Route($path, 'get', $callback);
		}

		public static function post($path, $callback){
			return new \Route($path, 'post', $callback);
		}

		public static function put($path, $callback){
			return new \Route($path, 'put', $callback);
		}

		public static function delete($path, $callback){
			return new \Route($path, 'delete', $callback);
		}

		public static function any($path, $callback){
			return new \Route($path, 'any', $callback);
		}

		public function name($name){
			$this->_name = $name;

			return $this;
		}

		public function run(){

			$pass_vars = array_values($this->_vars);
			foreach($_GET as $k => $v) $pass_vars[$k] = $v;

			if(is_callable($this->_callback)){
				return call_user_func_array($this->_callback, $this->_vars);
			}
			elseif(is_string($this->_callback)){
				$callback_parts = explode('@', $this->_callback);

				$controller = Controller::get($callback_parts[0], $callback_parts[1], $pass_vars);
			}

			return $controller;
		}

	}
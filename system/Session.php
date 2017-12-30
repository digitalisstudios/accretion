<?php
	class Session extends Accretion {

		public function __construct(){

		}

		public static function update($callback, $vars = NULL){

			session_start();

			if(!is_null($vars)){
				$callback($vars);
			}
			else{
				$callback();
			}
			
			session_write_close();
		}

		public static function sessions(){
			$session_save_path = session_save_path();
			if($session_save_path == ""){
				$session_save_path = "/tmp";
			}
			$sessions = scandir($session_save_path);

			$res = array();

			foreach($sessions as $session){
				if(substr($session, 0, 5) == 'sess_'){
					$data = file_get_contents($session_save_path.'/'.$session);
					$res[] = Session::unserialize($data);
				}
			}
			return $res;
		}

		public static function unserialize($session_data) {
            $method = ini_get("session.serialize_handler");
            switch ($method) {
                case "php":
                    return Session::unserialize_php($session_data);
                    break;
                case "php_binary":
                    return Session::unserialize_phpbinary($session_data);
                    break;
                default:
                    throw new Exception("Unsupported session.serialize_handler: " . $method . ". Supported: php, php_binary");
            }
        }

        private static function unserialize_php($session_data) {
            
            $return_data 	= array();
            $offset 		= 0;
            
            while ($offset < strlen($session_data)) {
            
                if (!strstr(substr($session_data, $offset), "|")) {
                    throw new Exception("invalid data, remaining: " . substr($session_data, $offset));
                }
            
                $pos 					= strpos($session_data, "|", $offset);
                $num 					= $pos - $offset;
                $varname 				= substr($session_data, $offset, $num);
                $offset 				+= $num + 1;
                $data 					= unserialize(substr($session_data, $offset));
                $return_data[$varname] 	= $data;
                $offset 				+= strlen(serialize($data));
            }
            return $return_data;
        }

        private static function unserialize_phpbinary($session_data) {
            
            $return_data 	= array();
            $offset 		= 0;

            while ($offset < strlen($session_data)) {
                $num 					= ord($session_data[$offset]);
                $offset 				+= 1;
                $varname 				= substr($session_data, $offset, $num);
                $offset 				+= $num;
                $data 					= unserialize(substr($session_data, $offset));
                $return_data[$varname] 	= $data;
                $offset 				+= strlen(serialize($data));
            }
            return $return_data;
        }

        public static function exists($var){
            $parts = explode('.', $var);
            $current = $_SESSION;
            foreach($parts as $k => $v){
                if(isset($current[$v])){
                    $current = $current[$v];
                }
                else{
                    return false;
                }
            }
            return true;
        }

        public static function get($var = null){
            $parts = explode('.', $var);
            $current = $_SESSION;
            if(is_null($var)){
                return $current;
            }
            foreach($parts as $k => $v){
                if(isset($current[$v])){
                    $current = $current[$v];
                }
                else{
                    return false;
                }
            }
            return $current;
        }

        public static function set($var, $value = null){
            if(is_array($var)){
                Session::update(function($var){
                    $_SESSION = array_replace_recursive($_SESSION, $var);
                }, $var);
            }
            else{
                Session::update(function($arr){
                    $_SESSION[$arr[0]] = $arr[1];
                }, array($var, $value));
            }
        }

        public static function add($var, $value = null){

            if(is_array($var)){
                Session::update(function($var){
                    $_SESSION = array_merge_recursive($_SESSION, $var);
                }, $var);
            }
            else{
                Session::update(function($var, $value){
                    $_SESSION[$var] = $value;
                });
            }
        }

        public static function remove($var){

            Session::update(function($var){
                eval('unset($_SESSION'.$var.');');
            }, $var);

            return Session::get();
        }
	}
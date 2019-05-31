<?php	

	class Storage extends Accretion {

		public static $_data;

		public function __construct(){
			Storage::$_data = new \ORM_Wrapper;
		}

		public static function get($key = null){

			if(is_null(Storage::$_data)) Storage::$_data = new \ORM_Wrapper;

			if(is_null($key)){
				return Storage::$_data;
			}

			$parts = explode('.', $key);			

			$arr = Storage::$_data;

			foreach($parts as $k => $part){

				if(is_object($arr)){
					if(get_class($arr) == 'ORM_Wrapper'){
						
						if(!isset($arr[$part])) return null;
					
						$arr = $arr[$part];
					}
					else{
						if(!isset($arr->$part)) return null;

						$arr = $arr->$part;
					}
				}
				elseif(is_array($arr)){
					if(!isset($arr[$part])) return null;

					$arr = $arr[$part];
				}
				else{
					return null;
				}				
			}

			return $arr;
		}

		public static function set($key, $value){

			if(is_null(Storage::$_data)) Storage::$_data = new \ORM_Wrapper;

			$parts = explode('.', $key);

			$arr = Storage::$_data;

			foreach($parts as $k => $part){
				if(is_object($arr)){
					if(get_class($arr) == 'ORM_Wrapper'){
						if(isset($arr[$part])){

							if($k == count($parts)-1){
								$arr[$part] = $value;
							}
							else{
								$arr =& $arr[$part];
							}
						}
						else{
							if($k == count($parts)-1){


								$arr[$part] = $value;
							}
							else{
								$arr[$part] = new \ORM_Wrapper;
								$arr =& $arr[$part];
							}
						}
					}
					else{
						if(isset($arr->$part)){
							if($k == count($parts)-1){
								$arr->$part = $value;
							}
							else{
								$arr->$part = new \ORM_Wrapper;
								$arr =& $arr->$part;
							}
						}
					}
				}
				elseif(is_array($arr)){
					if(isset($arr[$part])){

						if($k == count($parts)-1){
							$arr[$part] = $value;
						}
						else{
							$arr = &$arr[$part];
						}
					}
					elseif($k == count($parts)-1){
						$arr[$part] = $value;
					}
					else{
						$arr[$part] = new \ORM_Wrapper;
						$arr =& $arr[$part];
					}
				}
				else{
					$arr = $value;
				}
			}

			return true;

		}

		public static function delete($key){

			if(is_null(Storage::$_data)) Storage::$_data = new \ORM_Wrapper;

			$parts = explode('.', $key);			

			$arr = Storage::$_data;

			foreach($parts as $k => $part){

				if(is_object($arr)){
					if(get_class($arr) == 'ORM_Wrapper'){
						
						if(!isset($arr[$part])) return false;

						if($k == count($parts)-2){
							unset($arr[$part]);
							return true;
						}
					
						$arr =& $arr[$part];
					}
					else{
						if(!isset($arr->$part)) return false;

						if($k == count($parts)-2){
							unset($arr->$part);
							return true;
						}

						$arr =& $arr->$part;
					}
				}
				elseif(is_array($arr)){
					if(!isset($arr[$part])) return false;					

					if($k == count($parts)-2){
						unset($arr[$part]);
						return true;
					}
					else{
						$arr = &$arr[$part];
					}
				}
				else{
					return false;
				}
			}
			return false;
		}

		public static function create_unique_id(){
			if(!\Storage::get('_unique_ids')){
				\Storage::set('_unique_ids', []);
			}

			$unique_ids = \Storage::get('_unique_ids');

			$unique_id = md5(microtime(true));

			$i = 0;
			while(in_array($unique_id, $unique_ids)){
				//usleep(100);
				$i++;
				$unique_id = md5(microtime(true)).'_'.$i;
			}

			$unique_ids[] = $unique_id;

			\Storage::set('_unique_ids', $unique_ids);

			return $unique_id;
		}
	}
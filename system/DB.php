<?php
	class DB extends Accretion {

		private static $_link 			= false;
		private static $_conn 			= false;
		private static $_user 			= false;
		private static $_dbhost 		= false;
		private static $_dbname			= false;
		private static $_dbuser 		= false;
		private static $_dbpass 		= false;
		private static $_dbalias		= false;
		private static $_q 				= false;
		public static $inserted_columns = array();

		public function __construct(){

		}

		public static function test_connection($host, $user, $pass, $name){
			
			$mysqli = new mysqli($host, $user, $pass, $name);

			if(mysqli_connect_errno()){
				return false;
			}
			return true;
		}

		public static function can_connect($alias = 'main'){

			if(Config::get('database')){

				$db = Config::get('database');

				if(isset($db->$alias)){
					return $db->$alias->can_connect;
				}
			}

			return false;

		}

		public static function add_error($arr){

		}

		public static function query($query){

			//MAKE SURE WE ARE CONNECTED
			self::check_conn();

			self::$_q = self::$_link->query($query);
			if(self::$_q){
				return self::$_q;
			}

			self::add_error(array(0 => $query, 1 => self::$_q->error));
			return false;
		}

		public static function get_row($query, $fields = []){

			//MAKE SURE THE QUERY WAS VALID
			if(self::query($query)){

				//MAKE SURE THERE WERE ROWS
				if(self::$_q->num_rows > 0){

					$res = self::$_q->fetch_assoc();

					if(is_array($fields) && !empty($fields)){

						$r = [];
						foreach($fields as $field){
							if(isset($res[$field])){
								$r[$field] = $res[$field];
							}
						}

						$res = $r;
					}
					elseif(is_string($fields) && !empty($fields)){
						if(isset($res[$fields])){
							$res = $res[$fields];
						}
						else{
							return false;
						}
					}

					//RETURN CLEANED ARRAY
					return self::stripslashes_deep($res);
				}
			}		
			return false;
		}

		public static function get_rows($query, $fields = []){

			//IF THE QUERY WAS VALID
			if(self::query($query)){

				//IF THERE WERE ROWS TO RETURN
				if(self::$_q->num_rows > 0){
					
					//INIT THE RETURN ARRAY
					$res = array();	

					//ADD THE ROWS TO THE RETURN ARRAY
					while($r = self::$_q->fetch_assoc()){
						$res[] = $r;
					}

					if(is_array($fields) && !empty($fields)){

						$r = [];

						foreach($res as $k => $v){
							foreach($fields as $field){
								if(isset($v[$field])){
									$r[$k][$field] = $v[$field];
								}								
							}
						}

						$res = $r;

					}
					elseif(is_string($fields) && !empty($fields)){

						$r = [];

						foreach($res as $k => $v){
							if(isset($v[$fields])){
								$r[$k] = $v[$fields];
							}
						}

						$res = $r;
					}
					
					//RETURN CLEANED ARRAY
					return  self::stripslashes_deep($res);
				}
			}
			return false;
		}

		public static function insert($table, $data = array()){			

			self::$inserted_columns = array();
		
			if(!self::query("SHOW COLUMNS FROM `{$table}`")){
				return false;
			}
			else if(self::$_q->num_rows == 0){
				return false;
			}
			else{

				$fields = array();
				while($r = self::$_q->fetch_assoc()){
					
					$fields[$r['Field']] = array(
						'type' 	=> $r['Type'],
						'key' 	=> $r['Key'],
					);
				}
				$data = self::stripslashes_deep($data);
				
				$query = "";

				foreach($data as $k => $v){

					if(is_array($fields[$k])){
						
						self::$inserted_columns[$k] = $k;
						
						$v = self::escape($v);

						$query .= " `{$k}` = '{$v}', ";
					}
				}

				$query = trim($query, ' ,');				

				if(self::query("INSERT INTO `{$table}` SET {$query}")){
					return self::$_link->insert_id;
				}
			}

			return false;
		}

		public static function update($table, $data = array(), $where = false){

			self::$inserted_columns = array(); 

			if(strpos($where, '=') === false && strpos($where, 'IN(') === false){				
				self::add_error('No where clause specified. Exiting update.');
				return FALSE;
			}			

			// Perform the query
			if(!self::query("SHOW COLUMNS FROM `{$table}`")){
				return FALSE;
			}
			// Get results from query
			if(self::$_q->num_rows == 0){
				return FALSE;
			}
			else{
			
				while($r = self::$_q->fetch_assoc()){
					$fields[$r['Field']] = array(
						'type' 	=> $r['Type'],
						'key' 	=> $r['Key'],
					);
				}

				$data = self::stripslashes_deep($data);

				$query = "";
				foreach($data as $k => $v){

					if(is_array($fields[$k])){
						self::$inserted_columns[$k] = $k;
						if(is_null($v)){
							$query .= " `{$k}` = NULL, ";
						}	
						else{
							$v = self::escape($v);
							$query .= " `{$k}` = '{$v}', ";
						}			
						
					}
				}

				$query = trim($query, ' ,');
				
				if(self::query("UPDATE `{$table}` SET {$query} WHERE {$where}")){
					return self::$_link->insert_id;
				}
			}
			return false;
		}

		public static function check_conn(){

			if(!self::$_conn){
				return self::set();
			}
			return self::$_conn;
		}		

		public static function load($dbname = false){
			return self::set($dbname);
		}

		public static function set($alias = 'main'){

			if(!\Storage::get('_Db.'.$alias)){
				
				//GET THE DB CONFIG
				$dbconf = \Config::get('database')->$alias;

				$link = mysqli_connect($dbconf->host, $dbconf->user, $dbconf->password, $dbconf->database);

				if(!$link){
					die("Failed to connect to MySQL: ".mysqli_connect_error());
				}

				\Storage::set('_Db.'.$alias, $link);

			}

			if(!self::$_conn){
				self::$_conn = new \DB_Conn;
			}

			self::$_link = \Storage::get('_Db.'.$alias);

			return self::$_conn;
		}

		public static function stripslashes_deep($value){
			if(is_array($value)){
				foreach($value as $k=>$v){
					$value[$k] = self::stripslashes_deep($v);
				}
				return $value;
			}else{
				return is_null($value) ? null : stripslashes($value);			
				//return stripslashes($value);
			}
		}

		public static function escape($value, $serialize = true){

			if(!self::$_link){
				self::set();				
			}

			if($serialize){
				if(is_array($value)){
					return self::$_link->real_escape_string(serialize($value));				
				}
				return self::$_link->real_escape_string($value);	
			}
			else{
				if(is_array($value)){
					foreach($value as $k => $v){
						$value[$k] = self::escape($v, false);
					}
				}
				else{
					$value = self::$_link->real_escape_string($value);
				}
				return $value;
			}			
		}
	}

	class DB_Conn extends DB{

		public function __construct(){

		}
	}
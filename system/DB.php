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
		public static $errors = [];
		public static $_transaction_in_progress = false;

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
			self::$errors[] = $arr;
			//pr($arr);
		}

		public static function query($query){

			//MAKE SURE WE ARE CONNECTED
			self::check_conn();

			self::$_q = self::$_link->query($query);
			if(self::$_q){

				//pr(self::$_q->error);
				//if(mysqli_errno()){
				//	self::add_error(array(0 => $query, 1 => self::$_q->error));
				//}
				return self::$_q;
			}

			self::add_error(array(0 => $query, 1 => self::$_link->error));
			return false;
		}

		public static function transaction(){
			self::query("SET autocommit=0");
			self::query("START TRANSACTION");
			\DB::$_transaction_in_progress = true;
			register_shutdown_function(function(){
				if(\DB::$_transaction_in_progress){
					\DB::rollback();
				}
			});

		}

		public static function commit(){
			self::query("COMMIT");
			self::query("SET autocommit=1");
			\DB::$_transaction_in_progress = false;
		}

		public static function rollback(){
			self::query("ROLLBACK");
			self::query("SET autocommit=1");
			\DB::$_transaction_in_progress = false;
		}

		//PARSE THE ROW DATA
		public static function parse_row($row){			

			//LOOP THROUGH THE ROW
			foreach($row as $x => $v){

				//CHECK IF THE ROW IS A STRING AND IS NOT NUMERIC
				if(is_string($v) && !is_numeric($v)){

					//SKIP BOOLEAN VALUES
					if($v == 'true' || $v == 'false') continue;

					//DETECT BASE64 ENCODED DATA					
					if(mb_detect_encoding(base64_decode($v)) === mb_detect_encoding($v) && base64_encode(base64_decode($v)) === $v){
						
						//DECODE THE DATA
						$decoded = base64_decode($v);

						//CHECK IF THE DECODED DATA IS JSON
						if($temp_v = json_decode($decoded)){
							$v = $temp_v;											
						}

						//CHECK IF THE DECODED DATA IS SERIALIZED
						elseif($temp_v = @unserialize($decoded)){
							$v = unserialize($decoded);
						}				
					}

					//NOT BASE64 ENCODED DATA
					else{

						//CHECK FOR JSON DATA
						if($temp_v = json_decode($v)){
							$v = $temp_v;											
						}

						//CHECK FOR SERIALIZED DATA
						elseif($temp_v = @unserialize($v)){
							$v = unserialize($v);
						}
					}
					
					//REPLACE ROW DATA IF IT DOESNT MATCH
					if($row[$x] !== $v) $row[$x] = $v; 
				}
			}

			//SEND BACK THE ROW
			return $row;
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
					}
					elseif(is_string($fields) && !empty($fields)){
						if(isset($res[$fields])){
							$res = $res[$fields];
						}
						else{
							return false;
						}
					}

					$res = self::parse_row($res);

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
						$res[] = self::parse_row($r);
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

				$sql = "INSERT INTO `{$table}` SET {$query} ON DUPLICATE KEY UPDATE {$query}";

				if(self::query("INSERT INTO `{$table}` SET {$query} ON DUPLICATE KEY UPDATE {$query}")){
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

			//ASSUME THAT STRICT MODE IS ALREADY OFF
			$setStrictModeOff = false;

			//CHECK IF THE DB CONNECTION IS ALREADY IN STORAGE
			if(!\Storage::get('_Db.'.$alias)){
				
				//GET THE DB CONFIG
				$dbconf = \Config::get('database')->$alias;

				//ESTABLISH A NEW PERMANENT CONNECTION
				//$link = new mysqli('p:'.$dbconf->host, $dbconf->user, $dbconf->password, $dbconf->database);
				//ESTABLISH A NEW PERMANENT CONNECTION
				$link = new mysqli($dbconf->host, $dbconf->user, $dbconf->password, $dbconf->database);

				//ESTABLISH THE LINK
				//$link 	= mysqli_connect($dbconf->host, $dbconf->user, $dbconf->password, $dbconf->database);

				//CHECK IF THE LINK FAILED
				if(!$link) die("Failed to connect to MySQL: ".mysqli_connect_error());

				//WE NEED TO RUN THE QUERY TO TURN OFF STRICT MODE
				$setStrictModeOff = true;

				//STORE THE LINK IN STORAGE
				\Storage::set('_Db.'.$alias, $link);

			}

			//SET THE CONNECTION
			if(!self::$_conn) self::$_conn = new \DB_Conn;

			//SET THE LINK FROM THE STORAGE
			self::$_link = \Storage::get('_Db.'.$alias);

			//TURN OFF STRICT MODE
			//if($setStrictModeOff === true) self::query("SET GLOBAL sql_mode = 'NO_ENGINE_SUBSTITUTION'");

			//SEND BACK THE CONNECTION
			return self::$_conn;
		}

		public static function stripslashes_deep($value){
			if(is_array($value)){
				foreach($value as $k=>$v){
					$value[$k] = self::stripslashes_deep($v);
				}
				return $value;
			}

			//CHECK IF THE VALUE IS AN OBJECT
			elseif(is_object($value)){
				return $value;
			}
			else{
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
					return base64_encode(json_encode($value));
				}
				elseif(is_object($value)){

					if(get_class($value) == 'Closure'){
						return base64_encode(json_encode((object)['Closure' => closure_dump($value)]));
					}
					elseif(get_class($value) == 'ClosureWrap'){
						return base64_encode(json_encode((object)['Closure' => $value->__toString()]));
					}
					elseif(is_model($value)){
						return json_encode(['model_id' => $value->primary_field_value(), 'model_name' => $value->model_name()]);
					}
					elseif(get_class($value) == 'ORM_Wrapper'){
						$arr = ['collection' => []];
						foreach($value as $k => $v){
							if(is_model($v)){
								$arr['collection'][] = ['model_id' => $v->primary_field_value(), 'model_name' => $v->model_name()];
							}
							else{
								$arr['collection'][] = $v;
							}

							return json_encode($arr);
							
						}
					}
					return base64_encode(json_encode($value));
				}

				return self::$_link->real_escape_string($value);

				/*
				if(is_array($value) || is_object($value)){
					return base64_encode(json_encode($value));				
				}
				return self::$_link->real_escape_string($value);	
				*/
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
<?php
	class Model_Structure_Helper extends Helper{

		public $_cache;

		public function __construct($model = false){
			if($model){
				if(is_array($model)){
					$model = $model[0];
				}
				$this->set_model($model);
			}
			return $this;
		}

		public function set_model($model){
			$this->model = $model;
			$this->db_name = $this->model->db_name();
			return $this;
		}

		public function load_cache(){

			$dir = __DIR__.'/Model_Structure/';
			if(!file_exists($dir)) mkdir($dir, 0755, true);

			if(!file_exists($dir.'cache.json')){
				
				$records = [];
				foreach(glob(MODEL_PATH.'*.php') as $file){
					$records[pathinfo($file, PATHINFO_FILENAME)] = filemtime($file)-1;
				}

				$handle = fopen($dir.'cache.json', 'w+');
				fwrite($handle, json_encode($records));
				fclose($handle);
			}

			$this->_cache = json_decode(file_get_contents($dir.'cache.json'), true);

			return $this->_cache;
		}

		public function needs_update(){

			if(is_null($this->_cache)) $this->load_cache();

			$model_name = $this->model->model_name();
			$file_path 	= MODEL_PATH.$model_name.'.php';
			$time 		= filemtime($file_path);

			return (!isset($this->_cache[$model_name]) || isset($this->_cache[$model_name]) && $this->_cache[$model_name] !== $time) ? true : false;
		}

		public function update_cache(){

			//LOAD THE CACHE IF NEEDED
			if(is_null($this->_cache)) $this->load_cache();

			//GET SOME VARS
			$model_name 				= $this->model->model_name();
			$file_path 					= MODEL_PATH.$model_name.'.php';
			$time 						= filemtime($file_path);
			$this->_cache[$model_name] 	= $time;
			$dir 						= __DIR__.'/Model_Structure/';

			//STORE THE CACHE
			$handle 					= fopen($dir.'cache.json', 'w+');
			fwrite($handle, json_encode($this->_cache));
			fclose($handle);

			return $this;
		}

		public function model_change($model_name, $change_type, $sql, $rollback){

			/*
			if(!DB::get_row("SHOW TABLES LIKE 'model_change'")){

				$res = DB::query("
					CREATE TABLE model_change(
						model_change_id int(11) AUTO_INCREMENT PRIMARY KEY,
						model_change_type VARCHAR(255),
						model_name VARCHAR(255),
						model_change_sql TEXT,
						model_change_rollback TEXT,
						model_change_datetime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
						model_change_rolledback ENUM('true','false') DEFAULT 'false'
					)
				");
			}*/

			//DB::insert('model_change', array('model_change_id' => '', 'model_change_type' => $change_type, 'model_name' => $model_name, 'model_change_sql' => $sql, 'model_change_rollback' => $rollback));
		}

		public function generate_column_sql($field_name, $data){
			$type 			= $data['Type'];
			$null 			= isset($data['Null']) && $data['Null'] == 'YES' ? 'NULL' : 'NOT NULL';
			$key 			= isset($data['Key']) && $data['Key'] == 'PRI' ? 'PRIMARY KEY' : '';
			$default 		= isset($data['Default']) && $data['Default'] != '' ? "DEFAULT '{$data['Default']}'" : '';
			$auto_increment = isset($data['Extra']) && $data['Extra'] == 'auto_increment' ? 'AUTO_INCREMENT PRIMARY KEY' : ''; 

			

			if($type == 'timestamp'){
				$type 		= 'TIMESTAMP';
				$default 	= isset($data['Default']) && $data['Default'] != '' ? "DEFAULT {$data['Default']}" : '';
			}

			return trim("`{$field_name}` {$type} {$null} {$default} {$key} {$auto_increment}");
		}

		public function set_defaults($force){
			
			if(!$force && !$this->needs_update()) return false;
			

			//SET THE MODEL NAME
			$this->model_name = get_class($this->model);

			$this->db_name = $this->model->db_name();

			//WAS THE TABLE NAME EXPLICITLY DEFINED
			if(isset($this->model->_table)){
				$this->_table = $this->model->_table;
			}
			else{				
				$this->_table = strtolower($this->model_name);
			}

			//IF THE TABLE HAS BEEN CACHED IN THE LAST DAY IGNORE IT
			/*if(isset($_SESSION['table_cache'][$this->_table]) && (strtotime($_SESSION['table_cache'][$this->_table]) > strtotime('-1 day')) && !$force){				
				return false;
			}*/

			//CHECK IF THE TABLE EXISTS
			$tables 				= \DB::set($this->db_name)->get_rows("SHOW TABLES");			
			$key_name = key($tables[0]);

			$this->table_exists 	= false;
			foreach($tables as $table){
				if($this->_table == $table[$key_name]){
					$this->table_exists = true;
					break;
				}
			}

			//GET THE TABLE STRUCTURE
			$this->table_structure 	= $this->table_exists ? \DB::set($this->db_name)->get_rows("DESCRIBE `{$this->_table}`") : false;

			//GET THE MODEL STRUCTURE
			$this->model_structure 	= isset($this->model->structure) ? $this->model->structure : false;

			return true;

		}

		public function generate($force = false){

			//ONLY ALLOW FOR MODELS
			$parents = array_values(class_parents($this->model));			
			if(!in_array('Model', $parents)) return $this;

			//ALLOW URL FORCING
			if(isset($_GET['model_sync'])) $force = true;

			if(!$this->set_defaults($force)) return $this;

			//NO TABLE AND NO MODEL STRUCTURE 
			if(!$this->table_exists && !$this->model_structure){
				return $this->generate_new_model_and_structure();
			}

			//NO TABLE BUT MODEL STRUCTURE
			else if(!$this->table_exists && $this->model_structure){
				return $this->generate_new_model_structure();
			}

			//TABLE EXISTS BUT NO MODEL STRUCTURE
			else if($this->table_exists && !$this->model_structure){
				return $this->generate_new_structure();
			}

			//BOTH TABLE AND MODEL STRUCTURE EXIST
			else if($this->table_exists && $this->model_structure){

				//CHECK FOR ALTERATIONS
				$field_key 			= 0;
				$altered_columns	= array();
				$new_columns		= array();

				foreach($this->model_structure as $field_name => $data){

					$found = false;
					
					//CYCLE THE TABLE STRUCTURE
					foreach($this->table_structure as $k => $v){
						
						//THE FIELD WAS FOUND
						if($v['Field'] == $field_name){
							
							//CHECK EACH OF THE FIELD VALUES
							foreach($v as $column_key => $column_data){

								//SKIP UNSET DATA TYPES
								if(in_array($column_key, array('Field', 'Key'))) continue;
								if($column_data == '' && !isset($data[$column_key]) || $column_key == 'Field') continue;
								if($column_data == 'NO' && !isset($data[$column_key])) continue;



								//CHECK IF THE DATA DOES NOT MATCH
								if(isset($data[$column_key]) && $column_data !== trim($data[$column_key])){
									
									unset($v['Field']);
									$altered_columns[] = array(
										'field_name' 	=> $field_name,
										'field_data' 	=> $data,
										'rollback_data' => $v
									);
									break;
								}
							}

							//THE COLUMN WAS FOUND SO STOP SEARCHING THE TABLE STRUCTURE
							$found = true;
							break;
						}
					}

					//THE COLUMN WAS NOT FOUND IN THE TABLE
					if(!$found){

						$new_columns[] = array(
							'field_name' 	=> $field_name,
							'field_data' 	=> $data,
							'rollback_data' => $v
						);
					}
					$field_key ++;
				}

				
				//THERE ARE COLUMNS THAT NEED UPDATING
				if(!empty($altered_columns)){					
				
					//UPDATE THE ALTERED COLUMNS
					foreach($altered_columns as $column){
						$sql = "ALTER TABLE `{$this->_table}` CHANGE `{$column['field_name']}` ".$this->generate_column_sql($column['field_name'], $column['field_data']);
						$rollback = "ALTER TABLE `{$this->_table}` CHANGE `{$column['field_name']}`".$this->generate_column_sql($column['field_name'], $column['rollback_data']);						
						\DB::set($this->db_name)->query($sql);
						//$this->model_change($this->model_name, 'alter_table', base64_encode($sql), base64_encode($rollback));
					}
				}

				//THERE ARE NEW COLUMNS THAT NEED TO BE ADDED
				if(!empty($new_columns)){	

					//ADD THE NEW COLUMNS
					foreach($new_columns as $column){
						$sql = "ALTER TABLE `{$this->_table}` ADD ".$this->generate_column_sql($column['field_name'], $column['field_data']);
						$rollback = "ALTER TABLE `{$this->_table}` ADD ".$this->generate_column_sql($column['field_name'], $column['rollback_data']);
						\DB::set($this->db_name)->query($sql);
						//$this->model_change($this->model_name, 'alter_table', base64_encode($sql), base64_encode($rollback));
					}
				}

				
				$this->update_cache();
				
			}


			//ADD THE TABLE TO THE TABLE CACHE
			session_start();
			$_SESSION['table_cache'][$this->_table] = date('Y-m-d H:i:s');
			session_write_close();
			return $this;
		}

		public function generate_new_model_and_structure(){
			$table_prefix = strtolower($this->model_name);
				
			$sql = "
				CREATE TABLE `{$this->_table}`(
					`{$table_prefix}_id` int(11) AUTO_INCREMENT PRIMARY KEY,
					`{$table_prefix}_created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
				)
			";

			$rollback = "DROP TABLE IF EXISTS `{$this->_table}`";
			
			//CREATE THE TABLE
			\DB::set($this->db_name)->query($sql);

			//$this->model_change($this->model_name, 'create_table', base64_encode($sql), base64_encode($rollback));

			//RUN AGAIN TO CREATE THE STRUCTURE
			return $this->generate();
		}

		public function generate_new_model_structure(){

			//GENERATE TABLE VARS FOR SQL
			$table_vars = array();

			foreach($this->model_structure as $field_name => $data){

				$table_vars[] 	= "	".$this->generate_column_sql($field_name, $data);
			}

			$table_vars = implode(",\n", $table_vars);

			//GENERATE SQL
			$sql = "CREATE TABLE `{$this->_table}` \n (\n{$table_vars}\n)";

			//pr($sql);
			//exit;

			//INSERT THE TABLE
			$res = \DB::set($this->db_name)->query($sql);
			$rollback = "DROP TABLE IF EXISTS `{$this->_table}`";

			$this->update_cache();

			//$this->model_change($this->model_name, 'create_table', base64_encode($sql), base64_encode($rollback));

			return;
		}

		public function generate_new_structure(){
			//GENERATE THE SOURCE CODE
				$structure_parts = "";

				foreach($this->table_structure as $k => $v){

			$structure_parts .= "
			'{$v['Field']}' => array(";
				foreach($v as $v_type => $v_data){
					if($v_data == "") continue;
					if($v_type == 'Field') continue;
					if($v_type == 'Key') continue;
					if($v_type == 'Null' && $v_data == "NO") continue;
					$structure_parts .= "'{$v_type}' => \"{$v_data}\",";
				}

			$structure_parts .= "),";
				}

				$structure_var = '

		//STRUCTURE ARRAY AUTOMATICALLY GENERATED BY THE FRAMEWORK ON '.date('Y-m-d g:i a').'
		public $structure = array(
			'.$structure_parts.'
		);'."\n";


				//SETUP VARS
				$lines 		= array();
				$search_for = "class {$this->model_name} extends Model";
				
				//LOAD THE MODEL SOURCE CODE
				$file_path 	= APP_PATH.'model/'.$this->model_name.'.php';
				$handle 	= fopen($file_path, 'r+');					

				//CYCLE THE CODE LINE BY LINE
				$write_next_line = false;
				while(($buffer = fgets($handle)) !== false){

					if($write_next_line){
						$lines[] = $structure_var;
						$write_next_line = false;

					}

					//OPEN OF CLASS CHECK
					if(strpos(trim($buffer), $search_for) !== false){
						$write_next_line = true;
					}

					$lines[] = $buffer;
				}
				fclose($handle);

				//REOPEN THE FILE AND CLEAR IT OUT
				$handle = fopen($file_path, 'w');

				//WRITE THE LINES TO THE FILE
				foreach($lines as $line){
					fwrite($handle, $line);
				}

				fclose($handle);

				$this->update_cache();
		}
	}
?>
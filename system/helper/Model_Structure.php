<?php
	class Model_Structure_Helper extends Helper{

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
			return $this;
		}

		public function model_change($model_name, $change_type, $sql, $rollback){
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
			}

			DB::insert('model_change', array('model_change_id' => '', 'model_change_type' => $change_type, 'model_name' => $model_name, 'model_change_sql' => $sql, 'model_change_rollback' => $rollback));
		}

		public function generate_column_sql($field_name, $data){
			$type 			= $data['Type'];
			$null 			= isset($data['Null']) && $data['Null'] == 'YES' ? '' : 'NOT NULL';
			$key 			= isset($data['Key']) && $data['Key'] == 'PRI' ? 'PRIMARY KEY' : '';
			$default 		= isset($data['Default']) && $data['Default'] != '' ? "DEFAULT '{$data['Default']}'" : '';
			$auto_increment = isset($data['Extra']) && $data['Extra'] == 'auto_increment' ? 'AUTO_INCREMENT PRIMARY KEY' : ''; 

			if($type == 'timestamp'){
				$type 		= 'TIMESTAMP';
				$default 	= isset($data['Default']) && $data['Default'] != '' ? "DEFAULT {$data['Default']}" : '';
			} 

			return trim("`{$field_name}` {$type} {$default} {$null} {$key} {$auto_increment}");
		}

		public function set_defaults($force){

			//SET THE MODEL NAME
			$this->model_name = get_class($this->model);

			//WAS THE TABLE NAME EXPLICITLY DEFINED
			if(isset($this->model->table_name)){
				$this->table_name = $this->model->table_name;
			}
			else{				
				$this->table_name = strtolower($this->model_name);
			}

			//IF THE TABLE HAS BEEN CACHED IN THE LAST DAY IGNORE IT
			if(isset($_SESSION['table_cache'][$this->table_name]) && (strtotime($_SESSION['table_cache'][$this->table_name]) > strtotime('-1 day')) && !$force){				
				return false;
			}

			//CHECK IF THE TABLE EXISTS
			$tables 				= DB::get_rows("SHOW TABLES");
			$this->table_exists 	= false;

			foreach($tables as $t => $v){
				foreach($v as $name => $x){
					break;
				}
				break;
			}
			
			foreach($tables as $table){
				if($this->table_name == $table[$name]){
					$this->table_exists = true;
					break;
				}
			}

			//GET THE TABLE STRUCTURE
			$this->table_structure 	= $this->table_exists ? DB::get_rows("DESCRIBE `{$this->table_name}`") : false;

			//GET THE MODEL STRUCTURE
			$this->model_structure 	= isset($this->model->structure) ? $this->model->structure : false;

			return true;

		}

		public function generate($force = false){

			//ONLY ALLOW FOR MODELS
			$parents = array_values(class_parents($this->model));			
			if(!in_array('Model', $parents)) return $this;

			//ALLOW URL FORCING
			if(isset($_GET['model_sync'])){
				$force = true;
			}

			if(!$this->set_defaults($force)){
				return;
			}

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

				$this->generate_new_structure();
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
						$sql = "ALTER TABLE `{$this->table_name}` CHANGE `{$column['field_name']}` ".$this->generate_column_sql($column['field_name'], $column['field_data']);
						$rollback = "ALTER TABLE `{$this->table_name}` CHANGE `{$column['field_name']}`".$this->generate_column_sql($column['field_name'], $column['rollback_data']);						
						DB::query($sql);
						$this->model_change($this->model_name, 'alter_table', base64_encode($sql), base64_encode($rollback));
					}
				}

				//THERE ARE NEW COLUMNS THAT NEED TO BE ADDED
				if(!empty($new_columns)){

				

					//ADD THE NEW COLUMNS
					foreach($new_columns as $column){
						$sql = "ALTER TABLE `{$this->table_name}` ADD ".$this->generate_column_sql($column['field_name'], $column['field_data']);
						$rollback = "ALTER TABLE `{$this->table_name}` ADD ".$this->generate_column_sql($column['field_name'], $column['rollback_data']);
						DB::query($sql);
						$this->model_change($this->model_name, 'alter_table', base64_encode($sql), base64_encode($rollback));
					}
				}
			}


			//ADD THE TABLE TO THE TABLE CACHE
			session_start();
			$_SESSION['table_cache'][$this->table_name] = date('Y-m-d H:i:s');
			session_write_close();
			return;
		}

		public function generate_new_model_and_structure(){
			$table_prefix = strtolower($this->model_name);
				
			$sql = "
				CREATE TABLE `{$this->table_name}`(
					`{$table_prefix}_id` int(11) AUTO_INCREMENT PRIMARY KEY,
					`{$table_prefix}_created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
				)
			";

			$rollback = "DROP TABLE IF EXISTS `{$this->table_name}`";
			
			//CREATE THE TABLE
			DB::query($sql);

			$this->model_change($this->model_name, 'create_table', base64_encode($sql), base64_encode($rollback));

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
			$sql = "CREATE TABLE `{$this->table_name}` \n (\n{$table_vars}\n)";

			//INSERT THE TABLE
			$res = DB::query($sql);
			$rollback = "DROP TABLE IF EXISTS `{$this->table_name}`";

			$this->model_change($this->model_name, 'create_table', base64_encode($sql), base64_encode($rollback));

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
		}
	}
?>
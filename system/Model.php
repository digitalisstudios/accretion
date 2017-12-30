<?php
	class Model extends Magic_Model {	

		public function __construct(){
			parent::__construct();
		}		

//-------------------------------------// MODEL LOAD PROPERTY METHODS //---------------------------------//

		public function autoload($val = true){
			$this->_auto_load = $val;
			return $this;
		}

		public function include_deleted($val = true){

			if(debug_backtrace()[0]['type'] == '::') return \Model::get(get_called_class())->include_deleted($val);

			$this->_include_deleted = $val;
			return $this;
		}

		public function only($only = array()){

			if(debug_backtrace()[0]['type'] == '::') return \Model::get(get_called_class())->only($only);

			if(is_array($only)){
				foreach($only as $k => $v) $this->only($v);
			}
			elseif(is_string($only)){
				$this->_only[] = $only;
			}

			return $this;
		}

		public function call_hooks($value = false){

			if(debug_backtrace()[0]['type'] == '::') return \Model::get(get_called_class())->call_hooks($value);

			$this->_call_hooks = $value;
			return $this;
		}

		public function single($value = true){

			if(debug_backtrace()[0]['type'] == '::') return \Model::get(get_called_class())->single($value);

			$this->_single = $value;
			return $this;
		}

		public function count($value = true){

			if(debug_backtrace()[0]['type'] == '::') return \Model::get(get_called_class())->count($value);

			$this->_count = $value;

			return $this;
		}

		public function join($query, $type = "JOIN"){

			if(debug_backtrace()[0]['type'] == '::') return \Model::get(get_called_class())->join($query, $type);

			if(is_array($query)){
				foreach($query as $k => $v){
					if(is_numeric($k)){
						if(is_array($v)){
							$this->join($v);
						}
						else{
							$join_type = isset($query[$k+1]) ? $query[$k+1] : "JOIN";
							$this->join($v, $join_type);
							break;
						}						
					}
				}
			}
			else{
				$this->_join[] = array('type' => $type, 'query' => $query);
			}
			
			return $this;
		}

		public function sum($field = false){

			if(debug_backtrace()[0]['type'] == '::') return \Model::get(get_called_class())->sum($field);

			$this->_sum = $field;

			return $this;
		}

		public function get_query($val = true){

			if(debug_backtrace()[0]['type'] == '::') return \Model::get(get_called_class())->get_query($val);

			$this->_get_query = $val;

			
			if($val && !$this->_auto_load){
				return $this->autoload()->load();
			}
			

			return $this;
		}

		public function limit($number = 1, $offset = null){

			if(debug_backtrace()[0]['type'] == '::') return \Model::get(get_called_class())->limit($number, $offset);

			if(is_array($number)){
				$offset = $number[1];
				$number = $number[0];
			}

			$this->_limit = $number;

			if(!is_null($offset)){
				$this->_limit_start = $offset;
			}

			return $this;
		}

		public function paginate($val = true){

			if(debug_backtrace()[0]['type'] == '::') return \Model::get(get_called_class())->paginate($val);

			is_numeric($val) && $val > 0 ? $this->_paginate['limit'] = $val : $this->_paginate = $val;		

			return $this;
		}

		public function set_parent($parent){

			if(debug_backtrace()[0]['type'] == '::') return \Model::get(get_called_class())->set_parent($parent);
			
			$this->_calling_parent = $parent;
			return $this;
		}

		public function order_by($field_name, $direction = false){

			if(debug_backtrace()[0]['type'] == '::') return \Model::get(get_called_class())->field_name($field_name, $direction);

			$direction = $direction === false ? "" : $direction;
						
			$this->_order = " ORDER BY {$field_name} {$direction}";
			return $this;
		}

		public function where($query){

			if(debug_backtrace()[0]['type'] == '::') return \Model::get(get_called_class())->where($query);

			$method_map = [
				'where' 			=> 'where',
				'order' 			=> 'order_by',
				'order_by' 			=> 'order_by',
				'limit' 			=> 'limit',
				'count' 			=> 'count',
				'single' 			=> 'single',
				'paginate' 			=> 'paginate',
				'only' 				=> 'only',
				'sum' 				=> 'sum',
				'get_query' 		=> 'get_query',
				'join'				=> 'join',
				'use_map' 			=> 'use_map',
				'call_hooks' 		=> 'call_hooks',
				'include_deleted' 	=> 'include_deleted',
				'autoload'			=> 'autoload'
			];

			//IF THE QUERY IS AN ARRAY
			if(is_array($query)){

				//LOOP THE QUERY PARAMS
				foreach($query as $k => $v){

					if(isset($method_map[$k])){
						$method = $method_map[$k];
						$this->$method($v);
					}
					elseif(is_numeric($k)){
						$this->where($v);
					}
				}
			}

			//ADD THE QUERY TO THE WHERE ARRAY
			else{
				$this->_where[] = $query;
			}
			
			//RETURN THE OBJECT
			return $this;
		}

//-------------------------------------// MODEL STORAGE METHODS //---------------------------------//

		public static function find($params = array(), $load = false){

			$class_name = get_called_class();
			$res 		= Model::get($class_name);

			if(is_numeric($params)){
				return $res->load($params);
			}

			$res->where($params);

			if($load !== false){
				return $res->load();
			}

			return $res;
		}

		public static function findBy($field_name, $field_value, $single = true){
			$class_name = get_called_class();
			$res 		= Model::get($class_name);
			return $res->where("`{$field_name}` = '{$field_value}'")->single($single)->load();
		}

		public function load($id = false){

			//SET THE ID IF THE MODEL HAS ALREADY BEEN LOADED
			$primary_field = $this->primary_field();
			if(isset($this->$primary_field) && !is_null($this->$primary_field) && !empty($this->$primary_field)){
				$id = $this->$primary_field;
			}

			//BUILD THE QUERY
			$sql = $this->generate_query($id);		

			//GET IF MODEL NEEDS TO PAGINATE
			if($this->_paginate){

				//LOAD PAGINATION
				$pagination = \Helper::Paginate()->model($this->model_name())->custom_where($sql);

				//SET PAGINATION PARAMETERS
				if(is_array($this->_paginate)){

					//CYCLE THE  PAGINATION PARAMETERS
					foreach($this->_paginate as $k => $v){

						//CHECK IF THE PAGINATION METHOD EXISTS
						if(method_exists($pagination, $k)){

							//CALL THE PAGINATION METHOD
							$pagination->$k($v);
						}
					}
				}

				//TURN HOOKS BACK ON
				$this->call_hooks(true);			
				
				//SEND BACK GENERATED PAGINATION
				return $pagination->generate();
			}

			//SEND BACK THE QUERY
			if(isset($this->_get_query) && !is_null($this->_get_query) && $this->_get_query == true){
				return $sql;
			}		

			//SEND BACK THE COUNT
			if(isset($this->_count) && !is_null($this->_count) && $this->_count == true){
				return (int)($this->model_db()->get_row($sql, 'count'));
			}

			//SEND BACK THE SUM
			elseif(isset($this->_sum) && !is_null($this->_sum) && $this->_sum !== false && is_string($this->_sum) && $this->_sum != ""){
				return $this->model_db()->get_row($sql, 'summed');
			}				
			
			//IF NO ID WAS PROVIDED AND SINGLE IS NOT ENABLED
			if($id === false && $this->_single === false){

				//GET THE RESULT
				$res = $this->model_db()->get_rows($sql);					
				
				//INIT THE RETURN
				$wrapper = new \ORM_Wrapper;

				//IF THE RESULT IS NOT FALSE
				if($res !== false){

					//SET THE MODEL NAME
					$model_name = $this->model_name();

					//LOOP THE RESULTS
					foreach($res as $key => $record){
						
						//GET THE MODEL
						$model = Model::get($model_name);

						//SET THE MODEL DATA
						$model->set($record)->_encrypt();

						//MAKE A COPY OF THE ORIGINAL DATA
						$model->_original_data = $model->_data;

						//ADD IT TO THE RETURN ARRAY
						$wrapper->push($model->_run_hook('_after_load')->call_hooks(true));
					}
				}

				//RETURN THE COLLECTION
				return $wrapper;
			}

			//ID OR SINGLE WAS PASSED
			else{

				//GET THE RESULT AND SET THE DATA
				$this->set($this->model_db()->get_row($sql))->_encrypt();

				//SET THE ORIGINAL DATA IF NEEDED
				if(isset($this->_original_data) && empty($this->_original_data)) $this->_original_data = $this->_data;			

				//RETURN THE MODEL WITH HOOKS ON
				return $this->_run_hook('_after_load')->call_hooks(true);
			}
		}

		public function set($data = []){

			if(debug_backtrace()[0]['type'] == '::') return \Model::get(get_called_class())->set($data);

			if(!empty($data)) foreach($data as $key => $value) $this->$key = $value;

			return $this;
		}

		public function _run_hook($method){

			//RUN THE HOOK IF NEEDED
			if(empty($this->_only) && method_exists($this, $method) && $this->_call_hooks === true) $this->$method();

			//SEND BACK THE OBJECT
			return $this;
		}

		public function _encrypt(){
			if(isset($this->_encrypt) && !empty($this->_encrypt)) foreach($this->_encrypt as $field) if(isset($this->_data[$field])){

				$res = \Helper::Encryption()->decrypt($this->_data[$field]);

				if(!$res) $res = \Helper::Encryption()->encrypt($this->_data[$field]);

				$this->_data[$field] = $res;
			}

			return $this;
		}

		public function _encrypt_to(){
			if(isset($this->_encrypt) && !empty($this->_encrypt)) foreach($this->_encrypt as $field) if(isset($this->_data[$field])) $this->_data[$field] = \Helper::Encryption()->decrypt($this->_data[$field]) === false ? \Helper::Encryption()->encrypt($this->_data[$field]) : $this->_data[$field];

			return $this;
		}

		public function _encrypt_from(){

			if(isset($this->_encrypt) && !empty($this->_encrypt)) foreach($this->_encrypt as $field) if(isset($this->_data[$field])) $this->_data[$field] = \Helper::Encryption()->decrypt($this->_data[$field]) === false ? $this->_data[$field] : \Helper::Encryption()->decrypt($this->_data[$field]);

			return $this;

		}

		public function _prep_data_for_db($hook_name = null){

			//RUN HOOK
			if(!is_null($hook_name)) $this->_run_hook($hook_name);

			$data = $this->_encrypt_to()->expose_data();

			//STRIP MICROSOFT WORD FORMATTING
			$data = strip_msword_formatting($data);			

			//FORMAT NULL AND DATES
			foreach($data as $k => $v){

				if(!isset($this->structure[$k])) continue;

				if((empty($v) || is_null($v) || $v == '') && $this->structure[$k]['Null'] == 'YES'){
					$data[$k] = NULL;
				}
				elseif($this->structure[$k]['Type'] == 'date'){
					$data[$k] = date('Y-m-d', strtotime($v));
				}
				elseif($this->structure[$k]['Type'] == 'datetime'){
					$data[$k] = date('Y-m-d H:i:s', strtotime($v));
				}
				elseif($this->structure[$k]['Type'] == 'timestamp'){
					$data[$k] = date('Y-m-d H:i:s', strtotime($v));
				}
				elseif($this->structure[$k]['Type'] == 'time'){
					$data[$k] = date('H:i:s', strtotime($v)); 
				}
				elseif($this->structure[$k]['Type'] == 'year'){
					$data[$k] = date('Y', strtotime($v));
				}
			}

			$this->_data = $data;

			return $data;

		}

		public function save($validate = true){
			
			//GET THE DATA AFTER RUNNING BEFORE SAVE HOOK
			$this->_prep_data_for_db('_before_save');

			//GET THE PRIMARY FIELD
			$primary_field 			= $this->primary_field();
			$primary_field_value 	= $this->primary_field_value();
			$table_name 			= $this->_table();

			//CHECK THAT THE RECORD EXISTS
			if( !is_null($primary_field_value) && (!empty($primary_field_value) || $primary_field_value === 0) && $this->model_db()->get_row("SELECT `{$primary_field}` FROM `{$table_name}` WHERE `{$primary_field}` = '{$primary_field_value}'")){

				$data = $this->_prep_data_for_db('_before_update');

				//VALIDATE THE MODEL
				if($validate && !$this->validate()) throw new Exception("Did not pass validation.");				

				//UPDATE THE RECORD
				$this->model_db()->update($table_name, $data, "{$primary_field} = '{$primary_field_value}'");

				//LOAD THE UPDATED RECORD AND RUN THE AFTER UPDATE HOOK
				$this->set($this->model_db()->get_row("SELECT * FROM `{$table_name}` WHERE `{$primary_field}` = '{$primary_field_value}'"))->_encrypt()->_run_hook('_after_update');			
			}
			
			//CREATE A NEW RECORD
			else{

				$data = $this->_prep_data_for_db('_before_create');

				//VALIDATE THE MODEL
				if($validate && !$this->validate()) throw new Exception("Did not pass validation.");

				//INSERT THE RECORD
				$id = $this->model_db()->insert($table_name, $data);

				//LOAD THE CREATED RECORD
				$this->set($this->model_db()->get_row("SELECT * FROM `{$table_name}` WHERE `{$primary_field}` = '{$id}'"))->_encrypt()->_run_hook('_after_create');
			}

			//SEND BACK THE MODEL WITH HOOKS TURNED ON
			return $this->_run_hook('_after_save')->call_hooks(true);
		}	

		//DELETE A MODEL RECORD
		public function delete(){

			if(!$this->loaded()) return $this;

			if(isset($this->_soft_delete) && $this->_soft_delete !== false){
				return $this->set([$this->_soft_delete => date('Y-m-d H:i:s')])->save();
			}

			//DELETE THE RECORD
			return $this->model_db()->query("DELETE FROM `{$this->_table()}` WHERE `{$this->primary_field()}` = '{$this->primary_field_value()}'");
		}

		public function undelete(){
			return (!$this->loaded() || !isset($this->_soft_delete) || isset($this->_soft_delete) && $this->_soft_delete === false) ? $this : $this->set([$this->_soft_delete => null])->save();
		}

		public function revert(){

			if(!$this->loaded() || !isset($this->_original_data) || isset($this->_original_data) && empty($this->_original_data)) return $this;

			//SET THE DATA BACK TO ITS ORIGINAL STATUS
			$this->_data = $this->_original_data;

			//SEND BACK THE MODEL
			return $this;
		}

		//METHOD FOR CALLING THE PARENT CONSTRUCTOR
		public function construct_parent(){
			
			//RUN PARENT CONSTRUCTOR
			parent::__construct();

			//SET THE TABLE NAME
			$this->_table();

			$this->_table_alias = $this->_table();

			//RETURN THE MODEL
			return $this;
		}

		//GET A MODEL BY NAME
		public static function get($model_name){

			//GET THE FILE PATH
			$file_path = \Storage::get('_model_names.'.\Controller::format_url($model_name));
			
			//GET THE MODEL NAME
			$model_name = pathinfo($file_path, PATHINFO_FILENAME);							

			//MAKE SURE THE CLASS EXISTS
			if(file_exists($file_path) && class_exists($model_name)){

				//LOAD A NEW INSTANCE OF THE MODEL
				$model = new $model_name;

				//RUN PARENT CONSTRUCTOR AND THE DB SCHEMA ON THE MODEL
				$model->construct_parent()->table_structure();

				return $model;
			}		

			//DEFAULT TO FALSE
			return false;
		}

//-------------------------------------// QUERY GENERATION METHODS //---------------------------------//

		//GENERATE THE WHERE STATEMENT FOR LOADING A MODEL
		public function generate_where($id = false, $field_name = false){


			if(!$id || !$field_name){

				//SET THE PRIMARY FIELD
				$field_name = $this->primary_field();

				//SET THE ID
				if(isset($this->$field_name) && $this->$field_name != ''){
					$id = $this->$field_name;
				}
			}

			//SET WHERE
			$where = " WHERE 1=1 ";

			if(isset($this->_soft_delete) && $this->_soft_delete !== false && $this->_include_deleted === false){
				$alias = (isset($this->_table_alias) && $this->_table_alias != "" && !is_null($this->_table_alias)) ? "`".$this->_table_alias."`." : "";
				$where .= " AND({$alias}`{$this->_soft_delete}` IS NULL)";
			}

			if($id){
				$alias = (isset($this->_table_alias) && $this->_table_alias != "" && !is_null($this->_table_alias)) ? "`".$this->_table_alias."`." : "";
				$where .= "
				AND ({$alias}`{$field_name}` = '{$id}')";
			}			

			//CHECK FOR WHERE STATEMENTS
			if(isset($this->_where) && count($this->_where)){				

				//LOOP THROUGH THE WHERE STATEMENTS
				foreach($this->_where as $q){

					//PARSE STRING VARIABLES
					if(strpos($q, "{{") !== false){

						preg_match_all("/{{(.*?)}}/i", $q, $matches);

						$vars = $matches[1];

						foreach($vars as $var){

							if(isset($this->_calling_parent) && isset($this->_calling_parent->$var)){

								$val = $this->_calling_parent->$var;
								$q = str_replace("{{{$var}}}", $val, $q);
							}
							else{
								$val = $this->$var;
								$q = str_replace("{{{$var}}}", $val, $q);
							}							
						}
					}

					$where .= trim(" AND ({$q}) ");
				}
			}

			return trim($where);
		}

		public function generate_query($id = false){
			
			//SET THE TABLE NAME
			$this->_table();
			
			//SET THE PRIMARY FIELD
			$field_name = $this->primary_field();

			//SET THE SELECT STATEMENT
			$select 		= "`".$this->_table_alias."`.*";

			//SET THE TABLE ALIAS
			$table_alias 	= " AS `".$this->_table_alias."`";

			//SET THE ORDER
			$order 			= trim(isset($this->_order) && $this->_order ? $this->_order : "ORDER BY `{$this->_table_alias}`.`{$field_name}` ASC");

			//SET THE ID
			$id = ($id === false && isset($this->$field_name) && $this->$field_name != '') ? $this->$field_name : $id;
			
			//GENERATE WHERE STATEMENT
			$where = $this->generate_where($id);

			//SET TABLE JOIN
			$join = "";
			if(isset($this->_join) && is_array($this->_join) && !empty($this->_join)){
				foreach($this->_join as $join) $join .= " {$join['type']} {$join['query']} ";
			}

			//SET LIMIT
			$limit = "";
			if(isset($this->_limit) && $this->_limit){
				$limit = (isset($this->_limit_start) && $this->_limit_start >= 0) ?  " LIMIT {$this->_limit_start}, {$this->_limit}" : " LIMIT {$this->_limit}";
			}			
			
			//SET ONLY SELECT
			if(isset($this->_only) && $this->_only && !empty($this->_only)){
				foreach($this->_only as $k => $v) $this->_only[$k] = "`".$this->_table_alias."`.`".$v."`";
				$select = implode(',', $this->_only);		
			}

			//SET SINGLE MODE
			if(isset($this->_single) && $this->_single){
				$limit = "LIMIT 1";
			}			

			//SET COUNTING
			if(isset($this->_count) && $this->_count){
				$limit 			= "";
				$this->_single 	= false;
				$select 		= "COUNT(*) AS `count`";
			}

			$end = "";			

			//SET SUMMING
			if(isset($this->_sum)){
				if(strpos($this->_sum, ".")){
					$select 	= "SUM(`_summing_target`.`_summing_target`) AS `summed` FROM (SELECT {$this->_sum} AS `_summing_target`";
					$end = "	) AS `_summing_target`";
				}
				else{
					$select 	= "SUM(`_summing_target`.`_summing_target`) AS `summed` FROM (SELECT DISTINCT `{$this->_table_alias}`.*, `{$this->_table_alias}`.`{$this->_sum}` AS `_summing_target`";
					$end = "	) AS `_summing_target`";
				}
				
				$this->_single 	= true;
				$this->_count 	= false;
			}			

			//SET THE TABLE NAME
			$table_name = "`".trim($this->_table())."`".$table_alias;

			//BUILD THE QUERY PARTS
			$sql_parts = array(
				"SELECT DISTINCT",
				trim($select),
				"FROM",
				$table_name,
				trim($join),
				trim($where),
				trim($order),
				trim($limit),
				trim($end)
			);

			//SEND BACK THE QUERY
			return trim(implode(" ", array_filter($sql_parts)));
		}		

//-------------------------------------// MODEL UTILITY METHODS //---------------------------------//
		
		public function table_structure($force = false){
			if(\Config::get('model_schema') && \Config::get('model_schema') == true){
				return \Helper::Model_Structure($this)->generate($force);
			}			
		}

		public function db_name(){

			if(is_null($this->_database)) $this->_database = 'main';
			
			return $this->_database;
		}

		public function _table($model = false){
			
			if($model){
				if(is_object($model)){

					if(isset($model->_table)){
						return $model->_table;
					}

					$model_name = get_class($model);
					if($model_name !== get_class($this)){
						if(!isset($model->_table)){
							$model->_table = strtolower(get_class($model));
							return $model->_table;
						}
					}
				}
				else{

					$model_obj = \Model::get($model);

					return $model_obj ? $model_obj->_table() : false;
				}			
			}

			if(!isset($this->_table) || is_null($this->_table)){
				$this->_table = strtolower(get_class($this));
			}

			return $this->_table;
		}

		public function model_name(){
			return get_class($this);
		}

		public function model_db(){
			return  \DB::set($this->db_name());
		}	

		public function primary_field($table_name = false){

			if($table_name){
				return is_object($table_name) ? $table_name->primary_field() : \Model::get($table_name)->primary_field();
			}

			if(!isset($this->_primary_field) || isset($this->_primary_field) && is_null($this->_primary_field)) $this->_primary_field = $this->model_db()->get_row("SHOW COLUMNS FROM `{$this->_table()}`", 'Field');

			return $this->_primary_field;
		}

		public function primary_field_value(){
			$primary_field = $this->primary_field();
			return $this->$primary_field;
		}

		public function loaded(){
			return $this->primary_field_value() ? true : false;
		}

		public function expose_data(){
			return $this->_data;
		}

		public function validate(){

			$rules 		= $this->_validate;
			$data 		= $this->expose_data();
			$validate 	= \Helper::Validate();
			if($validate->run($data, $rules)){
				$this->_validate_errors = [];
				return true;
			}
			$this->_validate_errors = $validate->error;
			return false;
		}
	}
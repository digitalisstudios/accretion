<?php
	class Model extends Model_Orm {

		public function __construct(){
			$this->_model_name = get_class($this);
			parent::__construct();
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

		public static function all(){
			$class_name = get_called_class();
			return Model::get($class_name)->load();
		}

		public function load($id = false){

			//SET THE ID IF THE MODEL HAS ALREADY BEEN LOADED
			$primary_field = $this->primary_field();
			if(isset($this->$primary_field) && !is_null($this->$primary_field) && !empty($this->$primary_field)){
				$id = $this->$primary_field;
			}

			$this->_run_hook('_before_load');

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
				return (float)$this->model_db()->get_row($sql, 'summed');
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

						$recordModel = \Model::get($model_name)->_set_loaded_data($record)->_run_hook('_after_load')->_orm_call_hooks(true);
						$recordModel->_relations = (object)[];

						if(isset($this->_with) && !empty($this->_with)) foreach($this->_with as $with_model => $with_where){
							$with_model = strtolower($with_model);
							$recordModel->_relations->{$with_model} = $recordModel->{'__'.$with_model}->where($with_where)->load();
						}

						$wrapper->push($recordModel);
					}
				}

				//RETURN THE COLLECTION
				return $wrapper;
			}

			//ID OR SINGLE WAS PASSED
			return $this->_set_loaded_data($this->model_db()->get_row($sql))->_run_hook('_after_load')->_orm_call_hooks(true);
			
		}

		public function _set_loaded_data($data){

			
			$primary_field = $this->primary_field();

			foreach($data as $k => $v){
				if(is_object($v) && isset($v->Closure)){
					//eval("\$closure = ".$v->Closure.";");
					//$closure->code = $v->Closure;
					$closure_data[$k] = new \ClosureWrap($v->Closure, $this);
					//$closure_data[$k]->bind($this);
				}

				if(is_object($v) && isset($v->model_id) && isset($v->model_name)){
					$data[$k] = \Model::get($v->model_name)->load($v->model_id);
				}

				
				if(is_object($v) && isset($v->collection)){

					$collection = new \ORM_Wrapper;

					foreach($v->collection as $collection_data){
						if(is_object($collection_data) && isset($collection_data->model_name) && isset($collection_data->model_id)){
							$collection->push(\Model::get($collection_data->model_name)->load($collection_data->model_id));
						}
						else{
							$collection->push($collection_data);
						}
					}

					$data[$k] = $collection;
				}
				
			}
			

			/*
			if(isset($data[$primary_field])){
				$primary_field_value = $data[$primary_field];

				if(\Storage::get('_model_data_cache.'.$this->model_name().'.'.$primary_field_value)){
					$data =  \Storage::get('_model_data_cache.'.$this->model_name().'.'.$primary_field_value)->_get_cached_update_data($data);							
				}
			}
			*/

			$this->set($data)->_encrypt_from();
			$this->_original_data 	= $this->expose_data();
			$this->_loading 		= false;	

			if(!empty($closure_data)){
				$this->set($closure_data);
			}	

			return $this;
		}

		public function _run_hook($method){

			$parentMethod = '_global'.$method;			

			//RUN THE HOOK IF NEEDED
			if(empty($this->_only) && method_exists($this, $method) && $this->_call_hooks === true) $this->$method();

			//RUN THE HOOK IF NEEDED
			if(empty($this->_only) && method_exists($this, $parentMethod) && $this->_call_hooks === true) $this->$parentMethod();

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

		public function _prep_data_for_db(){

			//CONVERT AND EXTRACT DATA
			$data = strip_msword_formatting($this->_encrypt_to()->expose_data());			

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

				$this->$k = $data[$k];
			}

			return $this;

		}

		public function get_enum($field){
			if(isset($this->structure[$field]) && strtolower(substr($this->structure[$field]['Type'], 0, 5)) == 'enum('){			
				eval(' $data = array'.trim(substr($this->structure[$field]['Type'], 4)).";");
				return $data;
			}

			return [];
		}

		public function save($validate = true){

			//GET THE PRIMARY FIELD
			$primary_field 			= $this->primary_field();
			$primary_field_value 	= $this->primary_field_value();
			$table_name 			= $this->_table();

			if($this->loaded() && !$this->_original_data){

				$data = $this->model_db()->get_row("SELECT * FROM `{$table_name}` WHERE `{$primary_field}` = '{$primary_field_value}'");

				if($data) $this->_original_data = $data;
			}
			
			//GET THE DATA AFTER RUNNING BEFORE SAVE HOOK	
			$this->_run_hook('_before_save')->get_update_data();



			//CHECK THAT THE RECORD EXISTS
			if(!is_null($primary_field_value) && (!empty($primary_field_value) || $primary_field_value === 0) && $this->model_db()->get_row("SELECT `{$primary_field}` FROM `{$table_name}` WHERE `{$primary_field}` = '{$primary_field_value}'")){

				$data = $this->_run_hook('_before_update')->_prep_data_for_db()->get_update_data();

				//VALIDATE THE MODEL
				//if($validate && !$this->validate()) throw new Exception("Did not pass validation.");				

				//UPDATE THE RECORD
				$this->model_db()->update($table_name, $data, "{$primary_field} = '{$primary_field_value}'");

				//GET THE NEW DATA
				$new_data = $this->model_db()->get_row("SELECT * FROM `{$table_name}` WHERE `{$primary_field}` = '{$primary_field_value}'");

				return $this->_set_loaded_data($new_data)->_run_hook('_after_update')->_run_hook('_after_save')->_orm_call_hooks(true);		
			}

			$data = $this->_run_hook('_before_create')->_prep_data_for_db()->get_update_data(true);

			//VALIDATE THE MODEL
			//if($validate && !$this->validate()) throw new Exception("Did not pass validation.");
			
			//INSERT THE RECORD AND GET THE ID
			$id 					= $this->model_db()->insert($table_name, $data);

			//pr($table_name);
			//pr($data);
			//pr($id);

			$table_name = isset($this->_viewTable) ? $this->_viewTable : $table_name;

			//GET THE DATA FROM THE INSERTED RECORD
			$new_data 				= $this->model_db()->get_row("SELECT * FROM `{$table_name}` WHERE `{$primary_field}` = '{$id}'");

			$this->_recentlyCreated = true;
			
			return $this->_set_loaded_data($new_data)->_run_hook('_after_create')->_run_hook('_after_save')->_orm_call_hooks(true);
		}	

		//DELETE A MODEL RECORD
		public function delete($force = false){

			if(!$this->loaded()) return $this;

			$this->_deleting 		= true;
			$this->_force_delete 	= $force;

			$this->_run_hook('_before_delete');

			if(isset($this->_soft_delete) && $this->_soft_delete !== false && $this->_force_delete === false){
				$this->set([$this->_soft_delete => date('Y-m-d H:i:s')])->save();
				$this->_deleting 		= false;
				$this->_force_delete 	= false;
				return $this->_run_hook('_after_delete');
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

				if(method_exists($model, '__boot')){
					$model->__boot();
					//$model->_relationships_set = false;
				}

				$model->_booted = true;


				

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
			$where = " WHERE \n     1=1 ";

			if(isset($this->_soft_delete) && $this->_soft_delete !== false && $this->_include_deleted === false){
				$alias = (isset($this->_table_alias) && $this->_table_alias != "" && !is_null($this->_table_alias)) ? "`".$this->_table_alias."`." : "";
				$where .= "\n     AND({$alias}`{$this->_soft_delete}` IS NULL) \n     ";
			}

			if($id !== false){
				
				$alias = (isset($this->_table_alias) && $this->_table_alias != "" && !is_null($this->_table_alias)) ? "`".$this->_table_alias."`." : "";
				$where .= "
				AND ({$alias}`{$field_name}` = '{$id}')";
			}

			//CHECK FOR WHERE STATEMENTS
			if(isset($this->_where) && count($this->_where)){	

				$this->_where = array_filter($this->_where);

				if(!count($this->_where)) return trim($where);			

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

					$where .= trim("\n          AND ({$q}) ");
				}
			}

			return trim($where);
		}

		public function generate_query($id = false){

			//GET THE TABLE NAME OR VIEW NAME IF IT IS SET
			$use_table = isset($this->_viewTable) ? $this->_viewTable : $this->_table();
			
			//SET THE TABLE NAME
			//$this->_table();
			
			//SET THE PRIMARY FIELD
			$field_name = $this->primary_field();

			//SET THE SELECT STATEMENT
			$select 		= "`".$this->_table_alias."`.*";

			//SET THE TABLE ALIAS
			$table_alias 	= " AS `".$this->_table_alias."`";

			//SET THE ORDER
			$order 			= trim(isset($this->_order) && $this->_order ? $this->_order : "ORDER BY\n     `{$this->_table_alias}`.`{$field_name}` ASC");

			//SET THE ID
			$id = ($id === false && isset($this->$field_name) && $this->$field_name != '') ? $this->$field_name : $id;
			
			//GENERATE WHERE STATEMENT
			$where = $this->generate_where($id);

			//SET TABLE JOIN
			$join = "";
			if(isset($this->_join) && is_array($this->_join) && !empty($this->_join)){
				foreach($this->_join as $j) $join .= " {$j['type']} \n     {$j['query']} ";
			}

			//SET LIMIT
			$limit = "";
			if(isset($this->_limit) && $this->_limit){
				$limit = (isset($this->_limit_start) && $this->_limit_start >= 0) ?  " LIMIT {$this->_limit_start}, {$this->_limit}" : " LIMIT {$this->_limit}";
			}

			//SET GROUP BY
			$group = "";
			if(!empty($this->_group_by)){

				$groups = []; foreach($this->_group_by as $g) $groups[] = $g;

				$group = " GROUP BY ".implode(", ", $groups);
			}			
			
			//SET ONLY SELECT
			if(isset($this->_only) && $this->_only && !empty($this->_only)){
				if($join == ""){
					foreach($this->_only as $k => $v) $this->_only[$k] = strpos($v, '.') ? $v : "`".$this->_table_alias."`.`".$v."`";
				}
				
				$select = implode(",\n     ", $this->_only);		
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
			$table_name = "`".trim($use_table)."`".$table_alias;

			//BUILD THE QUERY PARTS
			$sql_parts = array(
				"SELECT DISTINCT",
				"     ".trim($select),
				"FROM",
				"     ".$table_name,
				trim($join),
				trim($where),
				trim($group),
				trim($order),
				trim($limit),
				trim($end)
			);

			//SEND BACK THE QUERY
			$sql = trim(implode(" ", array_filter($sql_parts)));

			//PARSE STRING VARIABLES
			if(strpos($sql, "{{") !== false){

				//PARSE THE REPLACERS
				preg_match_all("/{{(.*?)}}/i", $sql, $matches);

				//LOOP THROUGH THE MATCHES AND REPLACE THE VARS
				foreach($matches[1] as $var) $sql = str_replace("{{{$var}}}", (isset($this->_calling_parent) && !is_null($this->_calling_parent) && isset($this->_calling_parent->$var) ? $this->_calling_parent->$var : $this->$var), $sql);
			}

			return $sql;

			//SEND BACK THE QUERY
			return trim(implode("\n", array_filter($sql_parts)));
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
			
			$data = array();

			/*
			foreach($this->_data->_data as $k => $v){
				$data[$k] = $v;
			}
			*/



			foreach($this->_data as $k => $v){
				//if(isset($this->structure[$k])){

					/*
					if($v === "false"){
						 $data[$k] = false;
					}
					elseif($v === "true"){
						$data[$k] = true;
					}
					else{
						$data[$k] = $v;
					}

					*/

					$data[$k] = $v;
					
					
				//}
			}

			return $data;

			foreach($this->structure as $k => $v){
				if(isset($this->$k)){
					$data[$k] = $this->$k;
				}			
			}

			return $data;
		}

		public function get_update_data($create = false){

			$primary_field = $this->primary_field();

			//EXTRACT DATA
			$data = $this->expose_data();

			//STRIP MICROSOFT WORD FORMATTING
			$data = strip_msword_formatting($data);

			//FORMAT NULL AND DATES
			foreach($data as $k => $v){
				if((empty($v) || is_null($v) || $v == '')){
					if($this->structure[$k]['Null'] == 'YES'){
						$data[$k] = NULL;
					}
					elseif(isset($this->structure[$k]['Type']) && substr($this->structure[$k]['Type'], 0, 5) == 'enum('){
						if(isset($this->structure[$k]['Default'])){
							$data[$k] = $this->structure[$k]['Default'];
						}
						else{
							$data[$k] = $this->get_enum($k)[0];
						}
					}
					
				}
				elseif($this->structure[$k]['Type'] == 'date' && !empty($v) && strtotime($v)){
					//if(strtotime($v)){
						$data[$k] = date('Y-m-d', strtotime($v));
						if(substr($data[$k], 0, 1) == '-'){
							unset($data[$k]);
						}	
					//}
					
				}
				elseif(in_array($this->structure[$k]['Type'], ['datetime','timestamp']) && !empty($v) && strtotime($v)){
					//if(strtotime($v)){
						$data[$k] = date('Y-m-d H:i:s', strtotime($v));		
						if(substr($data[$k], 0, 1) == '-'){
							unset($data[$k]);
						}
					//}			
				}
			}
			
			if(!isset($data[$primary_field]) || (isset($data[$primary_field]) && !empty($data[$primary_field]))){
				$update_data 	= [];
				$original 		= $this->_original_data;
				foreach($data as $k => $v){
					if($v !== $original[$k]){
						if((is_null($original[$k]) && empty($v) || is_null($v) && empty($original[$k])) && isset($this->structure[$k]) && isset($this->structure[$k]['Null']) && $this->structure[$k]['Null'] == 'YES'){
							if(!is_null($v)){
								continue;
							}						
						}
						$update_data[$k] = $v;
					}
				}

				if(isset($data[$primary_field]) && !empty($data[$primary_field])){
					$update_data[$primary_field] = $data[$primary_field];
				}

				$data = $update_data;
			}


			if(isset($data[$primary_field]) && empty($data[$primary_field])){
				unset($data[$primary_field]);
			}

			/*

			$data['updated_at'] = date('Y-m-d H:i:s');
			$data['updated_by'] = \Auth::user() ? \Auth::user()->primary_field_value() : null;

			if($create == true){
				$data['created_at'] = date('Y-m-d H:i:s');
				$data['created_by'] = \Auth::user() ? \Auth::user()->primary_field_value() : null;
			}

			*/

			return $data;
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

	class ClosureWrap {
		private $_code;
		private $_closure;
		//private $_binded;

		public function __construct($code, $binder = null){
			if(is_object($code)){
				$this->_code = closure_dump($code);
				$this->_closure = $code;
			}
			else{
				$this->_code = $code;
				eval("\$closure = ".$code.";");
				$this->_closure = $closure;
			}

			if(!is_null($binder)){
				$this->bind($binder);
			}
		}

		public function __toString(){
			return $this->_code;
		}

		public function bind($object){
			//$this->_binded = $object;
			$this->_closure = $this->_closure->bindTo($object);
			//$this->_closure->bindTo($object, get_class($object));
			return $this;
		}

		public function call(...$args){
			
			return call_user_func_array($this->_closure, $args);
		}
	}

	abstract class index_type
	{
		const FTEXT = "FULLTEXT INDEX";
		const INDEX = "INDEX";
		const UNIQ  = "UNIQUE INDEX";
		const SPAT  = "SPATIAL INDEX";
		const PK    = "PRIMARY INDEX";
	}

	abstract class fk_type
	{
		const RESTRICT      = "RESTRICT";
		const CASCADE       = "CASCADE";
		const SET_NULL      = "SET NULL";
		const NO_ACTION     = "NO ACTION";
		const SET_DEFAULT   = "SET DEFAULT";
	}


	abstract class index_struct
	{
		const COLUMNS       = 0;
		const INDEX_TYPE    = 1;
	}

	abstract class fk_struct
	{
		const COLUMNS       = 0;
		const REF_TABLE     = 1;
		const REF_COL       = 2;
		const ON_UPDATE     = 3;
		const ON_DELETE     = 4;
	}
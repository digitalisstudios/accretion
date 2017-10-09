<?php
	class Model extends Magic_Model {

		public static $models 		= array();
		public static $model_names 	= false;
		public static $model_cache 	= array();

		public $_where = [];
		public $_order;
		public $_limit;
		public $_limit_start;
		public $_count = false;
		public $_single = false;
		public $_paginate;
		public $_only = [];
		public $_sum;
		public $_get_query = false;
		public $_map_where;
		public $_where_join = [];
		public $_where_alias;
		public $_where_join_first;
		public $_use_map;
		public $_call_hooks = true;
		public $_has_one = [];
		public $_has_many = [];
		public $_has_one_through = [];
		public $_has_many_through = [];
		public $_has_many_merge_through = [];
		public $_database;

		public function __construct(){
		
		}		

//-------------------------------------// MODEL LOAD PROPERTY METHODS //---------------------------------//

		public function only($only = array()){
			if(!isset($this->_only)){
				$this->_only = array();
			}
			if(is_array($only)){
				foreach($only as $k => $v){
					$this->_only[] = $v;
				}
			}
			elseif(is_string($only)){
				$this->_only[] = $only;
			}
			
			if(empty($this->_only)){
				//unset($this->_only);
			}

			return $this;
		}

		public function call_hooks($value = false){
			$this->_call_hooks = $value;
			return $this;
		}

		public function single($value = true){
			$this->_single = $value;
			return $this;
		}

		public function count($value = true){
			$this->_count = $value;
			return $this;
		}

		public function where_alias($alias){
			$this->_where_alias = $alias;
			return $this;
		}

		public function where_join($query, $type = "JOIN"){
			
			if(!isset($this->_where_join)){
				$this->_where_join = array();
			}
			if(is_array($query)){
				foreach($query as $k => $v){
					if(is_numeric($k)){
						if(is_array($v)){
							$this->where_join($v);
						}
						else{
							$join_type = isset($query[$k+1]) ? $query[$k+1] : "JOIN";
							$this->where_join($v, $join_type);
							break;
						}						
					}
				}
			}
			else{
				$this->_where_join[] = array('type' => $type, 'query' => $query);
			}
			
			return $this;
		}

		public function where_join_first($query){
			$this->_where_join_first = $query;
			return $this;
		}

		public function sum($field = false){
			$this->_sum = $field;
			return $this;
		}

		public function get_query($val = true){
			$this->_get_query = $val;
			return $this;
		}

		public function limit($number = 1, $offset = null){

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

		public function use_map($val = true){
			$this->_use_map = $val;
			return $this;
		}

		public function paginate($val = true){
			if(is_numeric($val) && $val > 0){
				if(!isset($this->_paginate)){
					$this->_paginate = array();
				}
				$this->_paginate['limit'] = $val;
			}
			else{
				$this->_paginate = $val;
			}			

			return $this;
		}

		public function set_parent($parent){
			$this->_calling_parent = $parent;
			return $this;
		}

		public function order_by($field_name, $direction = false){

			if($direction == false){
				$direction = "";
			}
						
			$this->_order = " ORDER BY {$field_name} {$direction}";
			return $this;
		}

		public function where($query){			
		
			//IF THE WHERE ARRAY IS NOT SET, SET IT
			if(!isset($this->_where)){
				$this->_where = array();
			}

			//IF THE QUERY IS AN ARRAY
			if(is_array($query)){

				//LOOP THE QUERY PARAMS
				foreach($query as $k => $v){

					if($k == 'where'){
						$this->where($v);
					}
					elseif($k == 'order'){
						$this->order_by($v);
					}
					elseif($k == 'order_by'){
						$this->order_by($v);
					}
					elseif($k == 'limit'){
						$this->limit($v);						
					}					
					elseif($k == 'count'){
						$this->count($v);
					}
					elseif($k == 'single'){
						$this->single($v);
					}
					elseif($k == 'paginate'){
						$this->paginate($v);
					}
					elseif($k == 'only'){
						$this->only($v);
					}
					elseif($k == 'sum'){
						$this->sum($v);
					}
					elseif($k == 'get_query'){
						$this->get_query($v);
					}
					elseif($k == 'where_join'){
						$this->where_join($v);
					}
					elseif($k == 'where_alias'){
						$this->where_alias($v);
					}
					elseif($k == 'where_join_first'){
						$this->where_join_first($v);
					}
					elseif($k == 'use_map'){
						$this->use_map($v);
					}
					elseif($k == 'call_hooks'){
						$this->call_hooks($v);
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
			$res 		= Model::$models[$class_name] = Model::get($class_name);

			if(is_numeric($params)){
				return $res->load($params);
			}

			$res->where($params);

			if($load !== false){
				return $res->load();
			}

			return $res;
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
				$pagination = Helper::Paginate()->model($this->model_name())->custom_where($sql);

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
				return (int)($this->model_db()->get_row($sql)['count']);
			}

			//SEND BACK THE SUM
			elseif(isset($this->_sum) && !is_null($this->_sum) && $this->_sum !== false && is_string($this->_sum) && $this->_sum != ""){
				return $this->model_db()->get_row($sql)['summed'];
			}				
			
			//IF NO ID WAS PROVIDED AND SINGLE IS NOT ENABLED
			if($id === false && $this->_single === false){				

				//GET THE RESULT
				//$res = DB::get_rows($sql);

				//GET THE RESULT
				$res = $this->model_db()->get_rows($sql);					
				
				//INIT THE RETURN
				$ret = array();

				//IF THE RESULT IS NOT FALSE
				if($res !== false){

					//SET THE MODEL NAME
					$model_name = $this->model_name();

					//LOOP THE RESULTS
					foreach($res as $key => $record){
						
						//GET THE MODEL
						$primary_field_value = $record[$primary_field];
						
						//GET THE MODEL
						$model = Model::get($model_name);
						
						//SET THE MODEL DATA
						$model->set($record);						

						//IF THERE IS AN AFTER LOAD METHOD RUN IT				
						if(empty($model->_only) && method_exists($model, '_after_load') && $model->_call_hooks === true){
							$model->_after_load();
						}

						//TURN HOOKS ON
						$model->call_hooks(true);

						//ADD IT TO THE RETURN ARRAY
						$ret[$key] = $model;
					}
				}

				//INIT THE WRAPPED
				$wrapper = new ORM_Wrapper;

				//ADD THE OBJECT
				return $wrapper->push($ret);
			}

			//ID OR SINGLE WAS PASSED
			else{
				
				//GET THE RESULT
				$res = $this->model_db()->get_row($sql);

				$model_name 			= $this->model_name();
				$primary_field_value 	= $res[$primary_field];

				//SET THE DATA
				$this->set($res);

				//IF THERE IS AN AFTER LOAD METHOD RUN IT				
				if(empty($this->_only) && method_exists($this, '_after_load') && $this->_call_hooks === true){
					$this->_after_load();
				}

				$this->call_hooks(true);

				//RETURN THE MODEL WITH HOOKS ON
				return $this;
			}
		}

		public function set($data = array()){
			if(empty($data)){
				return $this;
			}
			foreach($data as $key=>$value){
				$this->$key = $value;
			}			
			return $this;
		}

		public function save(){

			//SET THE TABLE NAME
			if(!isset($this->table_name)){
				$this->table_name = strtolower($this->model_name());
			}

			//GET THE PRIMARY FIELD
			$primary_field = $this->primary_field();
			
			if(empty($this->_only) && method_exists($this, '_before_save') && $this->_call_hooks === true){
				$this->_before_save();
			}			

			//EXTRACT DATA
			$data = $this->expose_data();

			//STRIP MICROSOFT WORD FORMATTING
			$data = strip_msword_formatting($data);			

			//FORMAT NULL AND DATES
			foreach($data as $k => $v){
				if((empty($v) || is_null($v) || $v == '') && $this->structure[$k]['Null'] == 'YES'){
					$data[$k] = NULL;
				}
				elseif($this->structure[$k]['Type'] == 'date'){
					$data[$k] = date('Y-m-d', strtotime($v));
				}
				elseif($this->structure[$k]['Type'] == 'datetime'){
					$data[$k] = date('Y-m-d H:i:s', strtotime($v));
				}
			}

			//INSERT A NEW RECORD
			if(!isset($data[$primary_field])){

				if(empty($this->_only) && method_exists($this, '_before_create') && $this->_call_hooks === true){
					$this->_before_create();
				}	

				$id = $this->model_db()->insert($this->table_name, $data);
				$new_data = $this->model_db()->get_row("SELECT * FROM `{$this->table_name}` WHERE {$primary_field} = '{$id}'");
				$this->set($new_data);

				if(empty($this->_only) && method_exists($this, '_after_create') && $this->_call_hooks === true){
					$this->_after_create();
				}				
			}

			//UPDATE THE RECORD BECAUSE AN ID WAS PROVIDED
			else{
				
				//CHECK THAT THE RECORD EXISTS
				if($this->model_db()->get_row("SELECT * FROM `{$this->table_name}` WHERE {$primary_field} = '{$data[$primary_field]}'")){

					if(empty($this->_only) && method_exists($this, '_before_update') && $this->_call_hooks === true){
						$this->_before_update();
					}

					$this->model_db()->update($this->table_name, $data, "{$primary_field} = '{$data[$primary_field]}'");
					$new_data = $this->model_db()->get_row("SELECT * FROM `{$this->table_name}` WHERE {$primary_field} = '{$data[$primary_field]}'");
					$this->set($new_data);
					$id = $data[$primary_field];

					if(empty($this->_only) && method_exists($this, '_after_update') && $this->_call_hooks === true){
						$this->_after_update();
					}					
				}
				
				//CREATE A NEW RECORD
				else{				

					if(empty($this->_only) && method_exists($this, '_before_create') && $this->_call_hooks === true){
						$this->_before_create();
					}	

					$id = $this->model_db()->insert($this->table_name, $data);
					$new_data = $this->model_db()->get_row("SELECT * FROM `{$this->table_name}` WHERE {$primary_field} = '{$id}'");
					$this->set($new_data);

					if(empty($this->_only) && method_exists($this, '_after_create') && $this->_call_hooks === true){
						$this->_after_create();
					}
				}
			}

			if(empty($this->_only) && method_exists($this, '_after_save') && $this->_call_hooks === true){
				$this->_after_save();
			}

			//SEND BACK THE MODEL WITH HOOKS TURNED ON
			return $this->call_hooks(true);
		}	

		//DELETE A MODEL RECORD
		public function delete(){

			//EXTRACT DATA
			$data = $this->expose_data();

			//SET THE TABLE NAME
			if(!isset($this->table_name)){
				$this->table_name = strtolower($this->model_name());
			}

			//GET THE PRIMARY FIELD
			$primary_field = $this->primary_field();

			//DELETE THE RECORD
			return $this->model_db()->query("DELETE FROM `{$this->table_name}` WHERE {$primary_field} = '{$data[$primary_field]}'");
		}

		//METHOD FOR CALLING THE PARENT CONSTRUCTOR
		public function construct_parent(){
			parent::__construct();
			return $this;
		}

		//GET A MODEL BY NAME
		public static function get($model_name){

			//SET MODEL NAMES IF NEEDED
			if(Model::$model_names === false){

				//CYCLE THE MODELS		
				foreach(glob(MODEL_PATH.'*.php') as $model_path){

					//SET THE MODEL META NAME
					Model::$model_names[Controller::format_url(pathinfo($model_path)['filename'])] = $model_path;
				}
			}

			//GET THE FILE PATH
			$file_path 	= Model::$model_names[Controller::format_url($model_name)];
			
			//GET THE MODEL NAME
			$model_name = pathinfo($file_path)['filename'];

			//MAKE SURE THE MODEL FILE PATH EXISTS
			if(file_exists($file_path)){

				//INCLUDE THE MODEL FILE PATH
				include_once $file_path;

				//MAKE SURE THE CLASS EXISTS
				if(class_exists($model_name)){

					//LOAD A NEW INSTANCE OF THE MODEL
					$model = new $model_name;

					//RUN PARENT CONSTRUCTOR
					$model->construct_parent();

					//SET THE TABLE NAME FOR THE MODEL
					$model->table_name();

					//SET THE INITIAL TABLE ALIAS
					$model->where_alias($model->table_name());

					//RUN THE DB SCHEMA ON THE MODEL
					$model->table_structure();

					//SET THE MODEL AS A LOADED MODEL
					Model::$models[$model_name] = $model;

					//SEND BACK THE MODEL
					return Model::$models[$model_name];
				}
			}

			//DEFAULT TO FALSE
			return false;
		}

		public function collect($data = array(), $where = false){
			
			//GET THE PRIMARY FIELD
			$primary_field = $this->primary_field();

			//INIT THE IDS
			$ids = array();

			//CYCLE THE PASSED DATA
			foreach($data as $k => $v){
				$ids[] = "'".$v[$primary_field]."'";
			}

			//COLLAPSE PRIMARY FIELD VALUES
			$ids = implode(',', $ids);

			//SET THE WHERE STATEMENT
			$this->where("{$primary_field} IN({$ids})")->wrap();

			//IF AN ADDITIONAL WHERE STATEMENT WAS PASSED
			if($where !== false){
				$this->where($where);
			}

			//RETURN THE LOADED MODEL
			return $this->load();
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
			if($id){
				$alias = (isset($this->_where_alias) && $this->_where_alias != "" && !is_null($this->_where_alias)) ? "`".$this->_where_alias."`." : "";
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
			$this->table_name();
			
			//SET THE PRIMARY FIELD
			$field_name = $this->primary_field();

			//SET THE SELECT STATEMENT
			$select 		= "`".$this->_where_alias."`.*";

			//SET THE TABLE ALIAS
			$table_alias 	= " AS `".$this->_where_alias."`";

			//SET THE ORDER
			$order 			= trim(isset($this->_order) && $this->_order ? $this->_order : "ORDER BY `{$this->_where_alias}`.`{$field_name}` ASC");

			//SET THE ID
			$id = ($id === false && isset($this->$field_name) && $this->$field_name != '') ? $this->$field_name : $id;
			
			//GENERATE WHERE STATEMENT
			$where = $this->generate_where($id);

			//SET TABLE JOIN
			$where_join = "";
			if(isset($this->_where_join) && is_array($this->_where_join) && !empty($this->_where_join)){
				foreach($this->_where_join as $join) $where_join .= " {$join['type']} {$join['query']} ";
			}

			//SET LIMIT
			$limit = "";
			if(isset($this->_limit) && $this->_limit){
				$limit = (isset($this->_limit_start) && $this->_limit_start >= 0) ?  " LIMIT {$this->_limit_start}, {$this->_limit}" : " LIMIT {$this->_limit}";
			}			
			
			//SET ONLY SELECT
			if(isset($this->_only) && $this->_only && !empty($this->_only)){
				foreach($this->_only as $k => $v) $this->_only[$k] = "`".$this->_where_alias."`.`".$v."`";
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
					$select 	= "SUM(`_summing_target`.`_summing_target`) AS `summed` FROM (SELECT DISTINCT `{$this->_where_alias}`.*, `{$this->_where_alias}`.`{$this->_sum}` AS `_summing_target`";
					$end = "	) AS `_summing_target`";
				}
				
				$this->_single 	= true;
				$this->_count 	= false;
			}			

			//SET THE TABLE NAME
			$table_name = isset($this->_where_join_first) && is_string($this->_where_join_first) && $this->_where_join_first != "" ? $this->_where_join_first : "`".trim($this->table_name)."`".$table_alias;

			//BUILD THE QUERY PARTS
			$sql_parts = array(
				"SELECT DISTINCT",
				trim($select),
				"FROM",
				$table_name,
				trim($where_join),
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
			if(Config::get('model_schema') && Config::get('model_schema') == true){
				return Helper::Model_Structure($this)->generate($force);
			}			
		}

		public function db_name(){

			if(is_null($this->_database)){
				$this->_database = 'main';
			}
			
			return $this->_database;
		}

		public function table_name($model = false){
			
			if($model){
				if(is_object($model)){

					if(isset($model->table_name)){
						return $model->table_name;
					}

					$model_name = get_class($model);
					if($model_name !== get_class($this)){
						if(!isset($model->table_name)){
							$model->table_name = strtolower(get_class($model));
							return $model->table_name;
						}
					}
				}
				else{

					$model_obj = Model::get($model);

					if($model_obj){
						return $model_obj->table_name();
					}
					else{
						return false;
					}
				}			
			}

			if(!isset($this->table_name)){
				$this->table_name = strtolower(get_class($this));
			}

			return $this->table_name;
		}

		public function model_name(){
			return get_class($this);
		}

		public function model_db(){
			return  \DB::set($this->db_name());
		}	

		public function primary_field($table_name = false){

			if($table_name){
				if(is_object($table_name)){
					return $table_name->primary_field();
				}
				else{
					return Model::get($table_name)->primary_field();
				}
			}			

			if(!isset($this->primary_field)){

				$field 					= $this->model_db()->get_row("SHOW COLUMNS FROM `{$this->table_name()}`");

				$this->primary_field 	= $field['Field'];	
			}

			return $this->primary_field;
		}

		public function primary_field_value(){
			$primary_field = $this->primary_field();
			return $this->$primary_field;
		}

		public function expose_data(){
			
			$data = array();

			if(isset($this->structure)){
				foreach($this->structure as $k => $v){
					if(isset($this->$k)){
						$data[$k] = $this->$k;
					}			
				}
			}
			else{
				$data = json_decode(json_encode($this), true);
				foreach($data as $k => $v){
					if(substr($k, 0, 1) == '_'){
						unset($data[$k]);
					}
				}
			}		

			return $data;
		}

		public function parse_alias($model_name){
			
			$alias 			= strtolower($model_name);
			$model_method 	= false;

			if(strpos($model_name, '.') !== false){
				$parts 			= explode('.', $model_name);
				$alias 			= $parts[0];
				$model_name 	= $parts[1];
				if(isset($parts[2])){
					$model_method 	= $model_name;
					$model_name 	= $parts[2];
				}
			}

			return ['alias' => $alias, 'model_name' => $model_name, 'model_method' => $model_method];

		}


		public function has_one($model_name, $local_field = false, $remote_field = false, $where = array()){

			$parsed = $this->parse_alias($model_name);

			//SET FIELDS IF NEEDED
			if(is_null($local_field) || $local_field === false){
				$local_field = $this->primary_field();
			}

			if(is_null($remote_field) || $remote_field === false){
				$remote_field = $this->primary_field();
			}
			
			$this->_has_one[$parsed['alias']] = [
				'model' 		=> $parsed['model_name'], 
				'local_field' 	=> $local_field, 
				'remote_field' 	=> $remote_field, 
				'where' 		=> $where, 
				'alias' 		=> $parsed['alias'],
				'model_method'	=> $parsed['model_method'],
			];
		}

		public function has_many($model_name, $local_field = false, $remote_field = false, $where = array()){

			
			$parsed = $this->parse_alias($model_name);			

			if(is_array($local_field)){

				$this->_has_many[$parsed['alias']] = [
					'model' 		=> $parsed['model_name'], 
					'where' 		=> $local_field, 
					'alias' 		=> $parsed['alias'],
					'model_method'	=> $parsed['model_method'],
				];
			}
			else{

				//SET FIELDS IF NEEDED
				if($local_field == false){
					$local_field = $this->primary_field();
				}

				if($remote_field == false){
					$remote_field = $this->primary_field();
				}

				$this->_has_many[$parsed['alias']] = [
					'model' 		=> $parsed['model_name'],
					'local_field' 	=> $local_field, 
					'remote_field' 	=> $remote_field, 
					'where' 		=> $where, 
					'alias' 		=> $parsed['alias'],
					'model_method'	=> $parsed['model_method'],
				];
			}
		}

		public function has_many_through($model_name, $map_model, $map_where = array(), $where = array()){
		

			$parsed 	= $this->parse_alias($model_name);
			$parsed_map = $this->parse_alias($map_model);
			
			$this->_has_many_through[$parsed['alias']] = [
				'model' 			=> $parsed['model_name'], 
				'map_model' 		=> $parsed_map['model_name'],
				'map_where'			=> $map_where,
				'where' 			=> $where,	
				'alias'				=> $parsed['alias'],
				'map_alias'			=> $parsed_map['alias'],
				'model_method'		=> $parsed['model_method'],
				'map_method'		=> $parsed_map['model_method'],
			];
		}

		public function has_one_through($model_name, $map_model, $map_where = array(), $where = array()){

			$parsed 	= $this->parse_alias($model_name);
			$parsed_map = $this->parse_alias($map_model);
			
			$this->_has_one_through[$parsed['alias']] = [
				'model' 			=> $parsed['model_name'], 
				'map_model' 		=> $parsed_map['model_name'],
				'map_where'			=> $map_where,
				'where' 			=> $where,	
				'alias'				=> $parsed['alias'],
				'map_alias'			=> $parsed_map['alias'],
				'model_method'		=> $parsed['model_method'],
				'map_method'		=> $parsed_map['model_method'],	
			];
		}

		public function has_many_merge_through($model_name, $merge_models, $where = array()){

			$parsed 	= $this->parse_alias($model_name);
			
			$merged = [];
			foreach($merge_models as $k => $v){
				if(is_numeric($k)){
					$p = $this->parse_alias($v);
					$p['where'] = [];
					$merged[] = $p;
				}
				else{
					$p = $this->parse_alias($k);
					$p['where'] = $v;
					$merged[] = $p;
				}
			}
			
			$this->_has_many_merge_through[$parsed['alias']] = [
				'model' 			=> $parsed['model_name'], 
				'merge_models' 		=> $merged,
				'where' 			=> $where,	
				'alias'				=> $parsed['alias'],
				'model_method'		=> $parsed['model_method'],
			];
		}
	}
?>
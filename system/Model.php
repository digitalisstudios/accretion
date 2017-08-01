<?php
	class Model extends Magic_Model {

		public static $models 		= array();
		public static $model_names 	= false;

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
				unset($this->_only);
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
			if($this->_get_query){
				return $sql;
			}		

			//SEND BACK THE COUNT
			if($this->_count){
				return (int)(DB::get_row($sql)['count']);
			}

			//SEND BACK THE SUM
			elseif($this->_sum){
				return DB::get_row($sql)['summed'];
			}					
			
			//IF NO ID WAS PROVIDED AND SINGLE IS NOT ENABLED
			if($id === false && $this->_single === false){				

				//GET THE RESULT
				$res = DB::get_rows($sql);			
				
				//INIT THE RETURN
				$ret = array();

				//IF THE RESULT IS NOT FALSE
				if($res !== false){

					//SET THE MODEL NAME
					$model_name = $this->model_name();

					//LOOP THE RESULTS
					foreach($res as $key => $record){
						
						//GET THE MODEL
						$model 		= new $model_name;	

						//DEFUNCT METHOD (SHOULD BE REMOVED)
						if(isset($this->_pass_to_child)){
							foreach($this->_pass_to_child as $name => $var){
								$model->$name = $var;
							}	
						}
						
						//SET THE MODEL DATA
						$model->set($record);

						//CHECK FOR HOOKS
						$use_hooks = true;
						if(isset($model->_call_hooks) && $model->_call_hooks === false){
							$use_hooks = false;
						}

						//IF THERE IS AN AFTER LOAD METHOD RUN IT				
						if((!isset($model->_only) && !isset($this->_only)) && isset($model->_after_load) && $use_hooks === true){					
							foreach($model->_after_load as $method){
								if(method_exists($model, $method));
								$model->$method();
							}
						}

						//TURN HOOKS ON
						$model->call_hooks(true);

						//ADD IT TO THE RETURN ARRAY
						$ret[$key] = $model;
					}
				}

				//IF THE MODEL NEEDS TO BE WRAPPED
				//if(isset($this->_wrap) && $this->_wrap){

					//INIT THE WRAPPED
					$wrapper = new ORM_Wrapper;

					//ADD THE OBJECT
					return $wrapper->push($ret);
				//}
				
				//RETURN THE ARRAY
				//return $ret;
			}

			//ID OR SINGLE WAS PASSED
			else{
				
				//GET THE RESULT
				$res = DB::get_row($sql);

				//SET THE DATA
				$this->set($res);

				//CHECK FOR HOOKS
				$use_hooks = true;
				if(isset($this->_call_hooks) && $this->_call_hooks === false){
					$use_hooks = false;
				}
				
				//IF THERE IS AN AFTER LOAD METHOD RUN IT
				if(!isset($this->_only) && isset($this->_after_load) && $use_hooks === true){					
					foreach($this->_after_load as $method){
						if(method_exists($this, $method));
						$this->$method();
					}
				}

				//RETURN THE MODEL WITH HOOKS ON
				return $this->call_hooks(true);
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

			//CHECK FOR HOOKS
			$use_hooks = true;
			if(isset($this->_call_hooks) && $this->_call_hooks === false){
				$use_hooks = false;
			}

			if(!isset($this->_only) && isset($this->_orm_before_save) && $use_hooks === true){
				foreach($this->_orm_before_save as $before){
					if(method_exists($this, $before['method'])){
						$method = $before['method'];
						$this->$method($before['params']);							
					}						
				}
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

				if(!isset($this->_only) && isset($this->_orm_before_create) && $use_hooks === true){
					foreach($this->_orm_before_create as $before){
						if(method_exists($this, $before['method'])){
							$method = $before['method'];
							$this->$method();
						}

					}
				}	
				$id = DB::insert($this->table_name, $data);
				$this->load($id);

				if(!isset($this->_only) && isset($this->_orm_after_create) && $use_hooks === true){
					foreach($this->_orm_after_create as $after){
						if(method_exists($this, $after['method'])){
							$method = $after['method'];
							$this->$method();
						}
					}
				}
			}

			//UPDATE THE RECORD BECAUSE AN ID WAS PROVIDED
			else{
				
				//CHECK THAT THE RECORD EXISTS
				if(DB::get_row("SELECT * FROM `{$this->table_name}` WHERE {$primary_field} = '{$data[$primary_field]}'")){
					DB::update($this->table_name, $data, "{$primary_field} = '{$data[$primary_field]}'");
					$new_data = DB::get_row("SELECT * FROM `{$this->table_name}` WHERE {$primary_field} = '{$data[$primary_field]}'");
					$this->set($new_data);
					$id = $data[$primary_field];					
				}
				
				//CREATE A NEW RECORD
				else{					
					$id = DB::insert($this->table_name, $data);
					$this->load($id);
				}
			}			

			if(!isset($this->_only) && isset($this->_orm_after_save) && $use_hooks === true){
				foreach($this->_orm_after_save as $after){
					if(method_exists($this, $after['method'])){
						$method = $after['method'];
						if(isset($method['params'])){
							
							$this->$method($method['params']);
						}
						else{
							$this->$method();
						}						
					}
				}
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
			return DB::query("DELETE FROM `{$this->table_name}` WHERE {$primary_field} = '{$data[$primary_field]}'");
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

					//SET THE TABLE NAME FOR THE MODEL
					$model->table_name();

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
				$where .= "
				AND ({$field_name} = {$id})";
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

			//SET THE ID
			if(isset($this->$field_name) && $this->$field_name != ''){
				$id = $this->$field_name;
			}	
			
			//GENERATE WHERE STATEMENT
			$where = $this->generate_where($id);

			//SET TABLE JOIN
			$where_join = "";
			if(isset($this->_where_join) && $this->_where_join){
				foreach($this->_where_join as $join){
					$where_join .= " {$join['type']} {$join['query']} ";
				}
			}

			//SET LIMIT
			$limit = "";
			if(isset($this->_limit) && $this->_limit){
				$limit = " LIMIT {$this->_limit}";
			}

			//SET LIMIT START
			if(isset($this->_limit_start) && $this->_limit_start){
				$limit = " LIMIT '{$this->_limit_start}', '{$this->_limit}'";
			}

			//SET WHERE AND TABLE ALIAS
			$select = "*";
			$table_alias = "";
			if(isset($this->_where_alias) && $this->_where_alias){
				$select = $this->_where_alias.".*";
				$table_alias = " AS ".$this->_where_alias;
			}

			//SET ORDER
			$order = "ORDER BY {$field_name} ASC";
			if($table_alias != ""){
				$order = "ORDER BY {$this->_where_alias}.{$field_name} ASC";
			}

			if(isset($this->_order) && $this->_order){
				$order = $this->_order;
			}

			$order = trim($order);

			//SET ONLY MODE
			$only = false;
			if(isset($this->_only) && $this->_only && !empty($this->_only)){
				if($table_alias != ""){
					foreach($this->_only as $k => $v){
						$this->_only[$k] = $this->_where_alias.".".$v;
					}
				}
				$select = implode(',', $this->_only);
				$only = true;
				
			}

			//SET SINGLE MODE
			$single = false;
			if(isset($this->_single) && $this->_single){
				$limit = "LIMIT 1";
				$this->_single = true;
			}
			else{
				$this->_single = false;
			}

			//SET COUNTING
			$count = false;
			if(isset($this->_count) && $this->_count){
				$limit = "";
				$this->_single = false;
				$select = "COUNT(*) AS count";
				if($table_alias != ""){
					//$select = "COUNT({$this->_where_alias}.*) AS count";
				}
				$this->_count = true;
			}
			else{
				$this->_count = false;
			}

			//SET SUMMING
			$sum = false;
			if(isset($this->_sum)){
				$select = "SUM({$this->_sum}) AS summed";
				if($table_alias != ""){
					$select = "SUM({$this->_where_alias}.{$this->_sum}) AS summed";
				}
				$this->_sum 	= true;
				$this->_single 	= true;
				$this->_count 	= false;
			}
			else{
				$this->_sum = false;

			}

			//SET THE TABLE NAME
			$table_name = "`".trim($this->table_name)."`";
			if(isset($this->_where_join_first)){
				$table_name = $this->_where_join_first;
			}

			$sql_parts = array(
				"SELECT DISTINCT",
				trim($select),
				"FROM",
				$table_name,
				trim(isset($this->_where_join_first) ? "" : $table_alias),
				trim($where_join),
				trim($where),
				trim($order),
				trim($limit)
			);

			return trim(implode(" ", array_filter($sql_parts)));

		}		

//-------------------------------------// MODEL UTILITY METHODS //---------------------------------//
		
		public function table_structure($force = false){
			if(Config::get('model_schema') && Config::get('model_schema') == true){
				return Helper::Model_Structure($this)->generate($force);
			}
			
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
					
					//return Model::get($model)->table_name();
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
				$field 					= DB::get_row("SHOW COLUMNS FROM `{$this->table_name()}`");
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


//-------------------------------------// MODEL RELATIONSHIP SETTER METHODS //---------------------------------//


		public function has_one($model_name, $local_field = null, $remote_field = null, $where = array()){

			$alias = false;
			if(strpos($model_name, '.') !== false){
				$parts 		= explode('.', $model_name);
				$alias 		= $parts[0];
				$model_name = $parts[1];
			}

			if(!isset($this->_has_one)){
				$this->_has_one = array();
			}

			//SET FIELDS IF NEEDED
			if($local_field == false){
				$local_field = $this->primary_field();
			}

			if($remote_field == false){
				$remote_field = $this->primary_field();
			}
			
			$this->_has_one[] = array('model' => $model_name, 'local_field' => $local_field, 'remote_field' => $remote_field, 'where' => $where, 'alias' => $alias);
		}

		public function has_many($model_name, $local_field = false, $remote_field = false, $where = array()){

			$alias = false;
			if(strpos($model_name, '.') !== false){
				$parts 		= explode('.', $model_name);
				$alias 		= $parts[0];
				$model_name = $parts[1];
			}
			
			if(!isset($this->_has_many)){
				$this->_has_many = array();
			}

			if(is_array($local_field)){
				$this->_has_many[] = array('model' => $model_name, 'where' => $local_field, 'alias' => $alias);
			}
			else{

				//SET FIELDS IF NEEDED
				if($local_field == false){
					$local_field = $this->primary_field();
				}

				if($remote_field == false){
					$remote_field = $this->primary_field();
				}

				$this->_has_many[] = array('model' => $model_name, 'local_field' => $local_field, 'remote_field' => $remote_field, 'where' => $where, 'alias' => $alias);
			}
		}

		public function has_many_through($model_name, $map_model, $map_where = array(), $where = array()){

			$alias = false;
			if(strpos($model_name, '.') !== false){
				$parts 		= explode('.', $model_name);
				$alias 		= $parts[0];
				$model_name = $parts[1];
			}

			$map_alias = false;
			if(strpos($map_model, '.') !== false){
				$parts 		= explode('.', $map_model);
				$map_alias	= $parts[0];
				$map_model 	= $parts[1];
			}

			if(!isset($this->_has_many_through)){
				$this->_has_many_through = array(); 
			}
			
			$this->_has_many_through[] = array(
				'model' 			=> $model_name, 
				'map_model' 		=> $map_model,
				'map_where'			=> $map_where,
				'where' 			=> $where,	
				'alias'				=> $alias,
				'map_alias'			=> $map_alias		
			);
		}

		public function has_one_through($model_name, $map_model, $map_where = array(), $where = array()){

			$alias = false;
			if(strpos($model_name, '.') !== false){
				$parts 		= explode('.', $model_name);
				$alias 		= $parts[0];
				$model_name = $parts[1];
			}

			$map_alias = false;
			if(strpos($map_model, '.') !== false){
				$parts 		= explode('.', $map_model);
				$map_alias	= $parts[0];
				$map_model 	= $parts[1];
			}

			if(!isset($this->_has_one_through)){
				$this->_has_one_through = array(); 
			}
			
			$this->_has_one_through[] = array(
				'model' 			=> $model_name, 
				'map_model' 		=> $map_model,
				'map_where'			=> $map_where,
				'where' 			=> $where,	
				'alias'				=> $alias,
				'map_alias'			=> $map_alias		
			);
		}	


//-------------------------------------// MODEL ORM HOOKS //---------------------------------//	

		public function _orm_before_save($method_name, $method_data = array()){
			if(!isset($this->_orm_before_save)){
				$this->_orm_before_save = array();
			}
			$this->_orm_before_save[] = array(
				'method' => $method_name,
				'params' => $method_data
			);
		}

		public function _orm_before_create($method_name, $method_data = array()){
			if(!isset($this->_orm_before_create)){
				$this->_orm_before_create = array();
			}
			$this->_orm_before_create[] = array(
				'method' => $method_name,
				'params' => $method_data
			);
		}

		public function _orm_after_create($method_name, $method_data = array()){
			if(!isset($this->_orm_after_create)){
				$this->_orm_after_create = array();
			}
			$this->_orm_after_create[] = array(
				'method' => $method_name,
				'params' => $method_data
			);
		}

		public function _orm_after_save($method_name, $method_data = array()){
			if(!isset($this->_orm_after_save)){
				$this->_orm_after_save = array();
			}
			$this->_orm_after_save[] = array(
				'method' => $method_name,
				'params' => $method_data
			);
		}

		public function after_load($method){
			if(!isset($this->_after_load)){
				$this->_after_load = array();
			}
			$this->_after_load[] = $method;
		}		

//-------------------------------------// LEGACY METHODS (REQUIRED FOR BACKWARDS COMPATABILITY) //---------------------------------//		

		public function limit_start($number){
			$this->_limit_start = $number;
			return $this;
		}			

		public function orm_load($id = false){
			return $this->load($id);
		}

		public function orm_set($data = array()){
			return $this->set($data);
		}

		public function orm_save(){
			return $this->save();
		}

		public function orm_delete(){
			return $this->delete();
		}

		public function load_model($model_name){
			return Model::get($model_name);
		}
	}
?>
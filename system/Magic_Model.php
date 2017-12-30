<?php
	class Magic_Model extends Global_Model_Method {

		public $_data 						= []; 
		public $_original_data				= [];
		public $_where 						= [];
		public $_order;
		public $_limit;
		public $_limit_start;
		public $_count 						= false;
		public $_single 					= false;
		public $_paginate;
		public $_only 						= [];
		public $_sum;
		public $_get_query 					= false;
		public $_map_where;
		public $_join 						= [];
		public $_table_alias;
		public $_use_map;
		public $_call_hooks 				= true;		
		public $_database;
		public $_load_query;
		public $_with 						= [];
		public $_select_join 				= [];
		public $_auto_load 					= true;
		public $_relationships_set 			= false;		
		public $_table;
		public $_encrypt 					= [];
		public $_soft_delete 				= false;
		public $_primary_field;
		public $_include_deleted 			= false;
		public $_calling_parent;
		public $_validate 					= [];
		public $_validate_errors			= [];

		public function __construct(){

		}

		public function __set($name, $value){
			$this->_data[$name] = $value;
			return $this;
		}

		public function __get($name){
			return array_key_exists($name, $this->_data) ? $this->_data[$name] : null;
		}

		public function __isset($name){
			return isset($this->_data[$name]);
		}

		public function __unset($name){
			if(isset($this->_data[$name])){
				unset($this->_data[$name]);
				return true;
			}
			return false;
		}

		public function __toString(){

			$parts 		= explode("\n",\Buffer::start(function(){ pr($this); }));
			$debug 		= debug_backtrace()[0];
			$parts[0] 	= '<pre><b>File: '.$debug['file'].' Line: '.$debug['line'].'</b>'."\n";

			return implode("\n", $parts);			
		}

		public function __callStatic($name, $value = array()){

			return Model::get($name)->where($value);			
		}

		//FORWARD UNFOUND METHODS
		public function __call($name, $value){

			$this->boot_relationships();

			//TRY TO FORWARD THE METHOD
			$res = $this->forward($name, $value);

			//IF THE METHOD FORWARDED
			if($res !== false){
				return $res;
			}
		}

		//FORWARD TO APPROPRIATE RELATIONSHIP METHOD
		public function forward($name, $value){

			//LOOP THROUGH THE FORWARD METHODS
			foreach(array('forward_has_many','forward_has_many_through','forward_has_one','forward_has_one_through') as $forwarder){
				
				$res = $this->$forwarder($name, $value);

				if($res !== false){
					return $res;
				}
			}

			//NOTHING RETURNED SO RETURN FALSE
			return false;				
		}

		public function forward_has_one($name, $value){
			return $this->get_has($name, $value, true);
		}

		public function forward_has_many($name, $value){
			return $this->get_has($name, $value, false);	
		}

		public function forward_has_one_through($name, $value){
			return $this->get_has_through($name, $value, true);				
		}

		public function forward_has_many_through($name, $value){
			return $this->get_has_through($name, $value, false);
		}

		public function get_has($name, $value, $single = false){

			$check 		= $single === true ? '_has_one' : '_has_many';
			$model_name = $this->model_name();
			$check 		= \Storage::get($this->_get_storage_name($check));

			//CHECK IF THIS MODEL HAS MANY		
			if(isset($check[strtolower($name)])){

				$get 			= $check[strtolower($name)];

				//SET THE TABLE NAME
				$get['table'] 	= $this->_table($get['model']);	

				//LOAD THE MODEL AND SET THE PARENT
				$model 	= Model::get($get['model'])->set_parent($this);

				$model->where($get['where'])->where($value);

				if(isset($get['local_field'])){
					$local_field 	= $get['local_field'];
					$field_value 	= $this->$local_field;
					$field_name 	= $get['remote_field'];
					$model->where("`{$model->_table_alias}`.`{$field_name}` = '{$field_value}'");
				}

				$single = $model->_single ? true : $single;				

				//RETURN THE LOADED MODEL
				$model->single($single);

				return $model->_auto_load ? $model->load() : $model; 
				//->load();

			}	

			return false;
		}				

		public function get_has_through($name, $value, $single = false){

			$check 		= $single === true ? '_has_one_through' : '_has_many_through';
			$model_name = $this->model_name();
			$check 		= \Storage::get($this->_get_storage_name($check));

			if(isset($check[strtolower($name)])){

				$get 							= $check[strtolower($name)];
				$get['table'] 					= $this->_table($get['model']);
				$model_name 					= $get['map_alias'];
				$map_where 						= [];
				$map_where['where'][] 			= $get['map_where'];
				if(!empty($value) && isset($value[0]['map_where'])) $map_where['where'][] = $value[0]['map_where'];
				
				$map_model 						= $this->$model_name($map_where);
				$where 							= [];
				$target_model 					= $get['model'];
				$target_model_name 				= $get['map_method'] !== false ? $get['map_method'] : strtolower($get['model']);
				$target_primary_field 			= Model::get($target_model)->primary_field();
				$where['only'] 					= array($target_primary_field);
				$ids 							= array();	

				if(is_object($map_model)){
					if(get_class($map_model) == 'ORM_Wrapper'){
						if($map_model->count()){

							foreach($map_model as $k => $v){																	

								$sub_res = $v->$target_model_name($where);

								if(get_class($sub_res) == 'ORM_Wrapper'){
									if($sub_res->count()) foreach($sub_res as $x => $r) $ids[] = $r->primary_field_value();
									
								}
								else{
									$ids[] = $sub_res->$target_primary_field;
								}
							}
						}
					}
					else{

						$sub_res = $map_model->$target_model_name($where);

						if(get_class($sub_res) == 'ORM_Wrapper' ){
							if($sub_res->count()) foreach($sub_res as $x => $r) $ids[] = $r->$target_primary_field;
						}
						elseif($sub_res->loaded()){
							$ids[] = $sub_res->$target_primary_field;
						}
					}
				}				

				$target_records = Model::get($target_model);
				$ids 			= implode(',', array_unique($ids));

				$target_records->where("`{$target_primary_field}` IN({$ids})")->where($get['where'])->where($value)->set_parent($this)->single($target_records->_single ? true : $single);

				return $target_records->_auto_load ? $target_records->load() : $target_records;
			}

			return false;			
		}

		public function boot_relationships(){

			if(!$this->_relationships_set){

				$model_name = $this->model_name();

				if(!\Storage::get('_relationship_cache.'.$model_name) && method_exists($this, '_relationships')) $this->_relationships();

				$this->_relationships_set = true;
			}

			return $this;
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

			$current_model = $this->model_name();

			if(!\Storage::get($this->_get_storage_name('_has_one', $model_name))){

				//SET FIELDS IF NEEDED
				if(is_null($local_field) || $local_field === false){
					$local_field = $this->primary_field();
				}

				if(is_null($remote_field) || $remote_field === false){
					$remote_field = $this->primary_field();
				}

				\Storage::set($this->_get_storage_name('_has_one', $model_name), [
					'model' 		=> $parsed['model_name'], 
					'local_field' 	=> $local_field, 
					'remote_field' 	=> $remote_field, 
					'where' 		=> $where, 
					'alias' 		=> $parsed['alias'],
					'model_method'	=> $parsed['model_method'],
				]);
			}
		}

		public function has_many($model_name, $local_field = false, $remote_field = false, $where = array()){

			
			$parsed = $this->parse_alias($model_name);	

			$current_model = $this->model_name();

			if(!\Storage::get($this->_get_storage_name('_has_many', $model_name))){

				if(is_array($local_field)){

					\Storage::set($this->_get_storage_name('_has_many', $model_name), [
						'model' 		=> $parsed['model_name'], 
						'where' 		=> $local_field, 
						'alias' 		=> $parsed['alias'],
						'model_method'	=> $parsed['model_method'],
					]);
				}
				else{

					//SET FIELDS IF NEEDED
					if($local_field == false){
						$local_field = $this->primary_field();
					}

					if($remote_field == false){
						$remote_field = $this->primary_field();
					}

					\Storage::set($this->_get_storage_name('_has_many', $model_name), [
						'model' 		=> $parsed['model_name'],
						'local_field' 	=> $local_field, 
						'remote_field' 	=> $remote_field, 
						'where' 		=> $where, 
						'alias' 		=> $parsed['alias'],
						'model_method'	=> $parsed['model_method'],
					]);
				}
			}			
		}

		public function has_many_through($model_name, $map_model, $map_where = array(), $where = array()){
		

			$parsed = $this->parse_alias($model_name);

			$current_model = $this->model_name();

			if(!\Storage::get($this->_get_storage_name('_has_many_through', $model_name))){

				$parsed_map = $this->parse_alias($map_model);

				\Storage::set($this->_get_storage_name('_has_many_through', $model_name), [
					'model' 			=> $parsed['model_name'], 
					'map_model' 		=> $parsed_map['model_name'],
					'map_where'			=> $map_where,
					'where' 			=> $where,	
					'alias'				=> $parsed['alias'],
					'map_alias'			=> $parsed_map['alias'],
					'model_method'		=> $parsed['model_method'],
					'map_method'		=> $parsed_map['model_method'],		
				]);
			}
		}

		public function has_one_through($model_name, $map_model, $map_where = array(), $where = array()){

			$parsed 	= $this->parse_alias($model_name);

			$current_model = $this->model_name();

			if(!\Storage::get($this->_get_storage_name('_has_one_through', $model_name))){

				$parsed_map = $this->parse_alias($map_model);

				\Storage::set($this->_get_storage_name('_has_one_through', $model_name), [
					'model' 			=> $parsed['model_name'], 
					'map_model' 		=> $parsed_map['model_name'],
					'map_where'			=> $map_where,
					'where' 			=> $where,	
					'alias'				=> $parsed['alias'],
					'map_alias'			=> $parsed_map['alias'],
					'model_method'		=> $parsed['model_method'],
					'map_method'		=> $parsed_map['model_method'],		
				]);
			}			
		}

		public function _get_storage_name($relationship, $model_name = null){
			

			$current_model = $this->model_name();

			if(!is_null($model_name)){
				$parsed 	= $this->parse_alias($model_name);
				return '_relationship_cache.'.$current_model.'.'.$relationship.'.'.$parsed['alias'];
			}

			return '_relationship_cache.'.$current_model.'.'.$relationship;

			
		}
	}
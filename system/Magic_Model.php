<?php
	class Magic_Model extends Global_Model_Method {

		public $_model_name;
		public $_data; 
		public $_original_data;
		public $_where 						= [];
		public $_loading 					= false;
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
		public $_relationships_set 			= false;
		public $_booted 					= false;	
		public $_table;
		public $_encrypt 					= [];
		public $_soft_delete 				= false;
		public $_primary_field;
		public $_include_deleted 			= false;
		public $_calling_parent;
		public $_deleting 					= false;
		public $_force_delete 				= false;
		public $_recentlyCreated 			= false;
		public $_validate 					= [];
		public $_validate_errors			= [];

		public function __construct(){
			if(!isset($this->_data) || !is_object($this->_data) || is_object($this->_data) && get_class($this->_data) !== 'Model_Data'){
				$this->_data = new \Model_Data($this);
			}
		}

		public static function __callStatic($name, $value = array()){			

			$method_name = \Storage::get('_orm_methods.'.$name);

			/*
			if($method_name){

				$res = \Model::get(get_called_class());

				return isset($value[0]) ? $res->$method_name($value[0]) : $res->$method_name();
			}
			*/

			if($method_name){

				$res = \Model::get(get_called_class());

				if(isset($value[0]) && !isset($value[1])) return $res->$method_name($value[0]);

				if(!empty($value)) return call_user_func_array([$res, $method_name], $value);

				return !empty($value) ? $res->$method_name($value) : $res->$method_name();
			}

			if(\Storage::get('_model_names.'.\Controller::format_url($name))){
				$res = Model::get($name);

				if(is_string($value)){
					$value = array($value);
				}

				if(!empty($value)){
					if(count($value) == 1){
						if(!is_array($value[0])){
							if(!is_numeric($value[0])){
								return $res->_orm_where($value[0]);
							}
							return $res->load($value[0]);
						}
						else{
							return $res->_orm_where($value[0]);
						}
					}
					else{
						$res->_orm_where($value);
					}
				}
				return $res;
			}

			return false;		
		}

		public function &__get($name){

			if(isset($this->_data->_data[$name])) return $this->_data->_data[$name];

			if(substr($name, 0, 6) == '_date_') return new DateTime($this->{substr($name, 6)});

			if(substr($name, 0, 6) == '_bool_') return filter_var($this->{substr($name, 6)}, FILTER_VALIDATE_BOOLEAN);

			if(substr($name, 0, 7) == '_phone_') return format_phone($this->{substr($name, 7)});

			if(substr($name, 0, 7) == '_money_') return number_format($this->{substr($name, 7)}, 2);

			if(substr($name, 0, 9) == '_decimal_') return number_format($this->{substr($name, 9)}, 2, '.', '');
			
			//GET THE MODEL NAME
			$model_name = $this->model_name();

			//BOOT THE MODEL RELATIONSHIPS
			$this->boot_relationships();

			$forms = \Helper::Plural()->forms($name);

			//SET DEFAULTS
			$method_name 	= $name;
			$method_name2 	= $forms->isPlural ? $forms->single : $forms->plural;
			$params 		= ['single' => $forms->isSingle];

			//HANDLE LOADING ALL RELATIONSHIP
			if(substr($name, 0, 5) == '_all_'){
				$method_name 	= substr($method_name, 5);
				$method_name2 	= substr($method_name2, 5);
			}

			//HANDLE RELATIONSHIP WITHOUT LOADING
			elseif(substr($name, 0, 2) == '__'){
				$method_name 	= substr($method_name, 2);
				$method_name2 	= substr($method_name2, 2);
				$params 		= false;
			}

			//LOOP THROUGH THE RELATIONSHIPS AND LOAD THE FIRST ONE
			foreach(\Storage::get('_relationship_cache.'.$model_name) as $type => $relationships){
				if(isset($relationships->$method_name)) return $this->$method_name($params);
				if(isset($relationships->$method_name2)) return $this->$method_name2($params);
			}

			//HANDLE CLASS METHODS LIKE RELATIONSHIPS
			if(method_exists($this, $method_name)) return $this->$method_name($params);
			if(method_exists($this, $method_name2)) return $this->$method_name2($params);
			

			return null;
		}

		public function &__get2($name){

			if(isset($this->_data->_data[$name])) return $this->_data->_data[$name];

			//CHECK IF CASTING AS DATE
			if(substr($name, 0, 6) == '_date_') return new DateTime($this->{substr($name, 6)});

			//CHECK IF CASTING AS BOOLEAN
			if(substr($name, 0, 6) == '_bool_') return filter_var($this->{substr($name, 6)}, FILTER_VALIDATE_BOOLEAN);

			if(substr($name, 0, 7) == '_phone_') return format_phone($this->{substr($name, 7)});
			
			//GET THE MODEL NAME
			$model_name = $this->model_name();

			//BOOT THE MODEL RELATIONSHIPS
			$this->boot_relationships();

			//CHECK IF THE RELATIONSHIP CACHE EXISTS
			if(\Storage::get('_relationship_cache.'.$model_name)){

				//SET DEFAULTS
				$method_name 	= $name;
				$params 		= ['single' => true];

				//HANDLE LOADING ALL RELATIONSHIP
				if(substr($name, 0, 5) == '_all_'){
					$method_name 	= substr($name, 5);
					$params = null;
				}

				//HANDLE RELATIONSHIP WITHOUT LOADING
				elseif(substr($name, 0, 2) == '__'){
					$method_name 	= substr($name, 2);
					$params 		= false;
				}

				//LOOP THROUG THE RELATIONSHIPS AND LOAD THE FIRST ONE (IF WE ARENT LOADING ADD THE WRAPPER)
				foreach(\Storage::get('_relationship_cache.'.$model_name) as $type => $relationships) if(isset($relationships->$method_name)){
					return $this->$method_name($params);
				} 
			}
			

			return null;
		}

		public function __set($name, $value){
			return $this->_data[$name] = $value;
		}

		public function __isset($name){
			return isset($this->_data[$name]);
		}

		public function __unset($name) {
			unset($this->_data[$name]);
    	}

		public function __toString(){

			$parts 		= explode("\n",\Buffer::start(function(){ pr($this); }));
			$debug 		= debug_backtrace()[0];
			$parts[0] 	= '<pre><b>File: '.$debug['file'].' Line: '.$debug['line'].'</b>'."\n";

			return implode("\n", $parts);			
		}

		/*
		public function __callStatic($name, $value = array()){

			return Model::get($name)->where($value);			
		}
		*/

		//FORWARD UNFOUND METHODS
		/*public function __call($name, $value){

			$this->boot_relationships();

			//TRY TO FORWARD THE METHOD
			$res = $this->forward($name, $value);

			//IF THE METHOD FORWARDED
			if($res !== false){
				return $res;
			}
		}
		*/

		//FORWARD UNFOUND METHODS
		public function __call($name, $value){
			$this->boot_relationships();

			$method_name = \Storage::get('_orm_methods.'.$name);

			if($method_name){

				return isset($value[0]) ? call_user_func_array([$this, $method_name], $value) : $this->$method_name();
				//return isset($value[0]) ? $this->$method_name($value[0]) : $this->$method_name();

				/*
				if(isset($value[0])){
					return $this->$method_name($value[0]);
				}

				return $this->$method_name();
				*/
			}

			/*

			$relationship = '_relationship_'.$name;
			
			if(isset($this->$relationship)){
				if(empty($this->$relationship->_only)){
					return $this->$relationship;
				}
				
			}

			*/

			//TRY TO FORWARD THE METHOD
			$res = $this->forward($name, $value);

			//IF THE METHOD FORWARDED
			if($res !== false){
				return $res;
			}
		}

		//FORWARD TO APPROPRIATE RELATIONSHIP METHOD
		public function forward($name, $value){

			/*
			$storage = \Storage::get("_relationship_cache.{$this->model_name()}");

			if(!is_null($storage)){

				$lcname = strtolower($name);

				foreach(['has_many', 'has_many_through','has_one','has_one_through'] as $rtype){

				}

				foreach($storage as $relationshipType => $relations){
					if(isset($relations[$lcname])){

						if($relationshipType == '_has_one'){
							return $this->new_get_has($name, $value, $relations[$lcname], true);
						}
						elseif($relationshipType == '_has_one_through'){
							return $this->new_get_has_through($name, $value, $relations[$lcname], true);
						}
						elseif($relationshipType == '_has_many'){
							return $this->new_get_has($name, $value, $relations[$lcname]);
						}
						elseif($relationshipType == '_has_many_through'){
							return $this->new_get_has_through($name, $value, $relations[$lcname]);
						}
					}
				}
			}

			return false;
			*/
			

			//LOOP THROUGH THE FORWARD METHODS
			foreach(array('forward_has_many','forward_has_many_through','forward_has_one','forward_has_one_through','forward_morph_one','forward_morph_many') as $forwarder){
				
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

		public function forward_morph_one($name, $value){
			return $this->getMorph($name, $value, true);
		}

		public function forward_morph_many($name, $value){
			return $this->getMorph($name, $value, false);
		}

		public function getMorph($name, $value, $single = false){
			$check = $single === true ? '_morph_one' : '_morph_many';
			$model_name = $this->model_name();

			$check = \Storage::get($this->_get_storage_name($check));

			if(isset($check[strtolower($name)])){

				$get 			= $check[strtolower($name)];

				$morphModel = $get['many'] ? $get['many'] : $this->{$get['morph_model']};

				//SET THE TABLE NAME
				//$get['table'] 	= $this->_table($this->{$get['morph_model']});		
				$get['table'] 	= $this->_table($morphModel);		

				//LOAD THE MODEL AND SET THE PARENT
				$model 	= Model::get($morphModel)->set_parent($this);

				$model->where($get['where'])->where($value);

				if($get['many']){
					$field_name = $get['morph_id'];
					$field_value = $this->primary_field_value();
					$field_name2 = $get['morph_model'];
					$field_value2 = $this->model_name();
					$model->where("`{$model->_table_alias}`.`{$field_name}` = '{$field_value}'");
					$model->where("`{$model->_table_alias}`.`{$field_name2}` = '{$field_value2}'");
				}
				else{
					$local_field 	= $get['morph_id'];
					$field_value 	= $this->$local_field;
					$field_name 	= $model->primary_field();
					$model->where("`{$model->_table_alias}`.`{$field_name}` = '{$field_value}'");
				}

				$single = $model->_single ? true : $single;				

				//RETURN THE LOADED MODEL
				$model->single($single);

				if(isset($value[0]) && $value[0] === false) return $model;

				return $model->load();

			}

			return false;
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

				if(isset($value[0]) && $value[0] === false) return $model;

				return $model->load();
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
				//$where['only'] 					= array($target_primary_field);
				$where['only'] 					= $target_primary_field;
				$ids 							= array();	


				if(is_object($map_model)){

					if(get_class($map_model) == 'ORM_Wrapper'){
						if($map_model->count()){

							$sub_res_queries = [];

							foreach($map_model as $k => $v){

								//$sub_res_query = $v->{$target_model_name}(['where' => $where, 'get_query' => true]);

								//$sub_res_queries[] = '('.$sub_res_query.')';

								
								$sub_res = $v->$target_model_name($where);

								if(get_class($sub_res) == 'ORM_Wrapper'){
									if($sub_res->count()) foreach($sub_res as $x => $r) $ids[] = $r->primary_field_value();
									
								}
								else{
									$ids[] = $sub_res->$target_primary_field;
								}								
							}

							//$new_sub_query = implode("\nUNION\n",$sub_res_queries);
//pr($new_sub_query);
							//$ids = \DB::get_rows($new_sub_query, $target_primary_field);
							
							
							
						}
					}
					else{

						//$sub_res_query = $map_model->{$target_model_name}(['where' => $where, 'get_query' => true]);

						//$ids = \DB::get_rows($sub_res_query, $target_primary_field);
						
						
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
				$ids 			= implode(',', array_filter(array_unique($ids)));

				$target_records->where("`{$target_primary_field}` IN({$ids})")->where($get['where'])->where($value)->set_parent($this)->single($target_records->_single ? true : $single);

				if(isset($value[0]) && $value[0] === false) return $target_records;

				return $target_records->load();
			}

			return false;			
		}

		public function boot_relationships(){			
			

			if(!$this->_relationships_set){

				//$model_name = $this->model_name();

				if(!$this->_booted){
					if(method_exists($this, '_relationships')) $this->_relationships();
				}
				else{
					if(!\Storage::get('_relationship_cache.'.$model_name) && method_exists($this, '_relationships')) $this->_relationships();
				}

				
				

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

			if(!\Storage::get($this->_get_storage_name('_has_one', $model_name))){

				$parsed 		= $this->parse_alias($model_name);
				$current_model 	= $this->model_name();

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

		public function belongsTo($model_name, $local_field = false, $remote_field = false, $where = []){

			$parsed 		= $this->parse_alias($model_name);
			$targetModel 	= \Model::get($parsed['model_name']);
			$local_field 	= (is_null($local_field) || $local_field == false) ? $targetModel->primary_field() : $local_field;
			$remote_field 	= (is_null($remote_field) || $remote_field == false) ? $targetModel->primary_field() : $remote_field;

			$this->has_one($model_name, $local_field, $remote_field, $where);
		}

		public function belongsToMany($model_name, $local_field = false, $remote_field = false, $where = []){

			$parsed 		= $this->parse_alias($model_name);
			$targetModel 	= \Model::get($parsed['model_name']);
			$local_field 	= (is_null($local_field) || $local_field == false) ? $targetModel->primary_field() : $local_field;
			$remote_field 	= (is_null($remote_field) || $remote_field == false) ? $targetModel->primary_field() : $remote_field;

			$this->has_many($model_name, $local_field, $remote_field, $where);
		}

		public function morphOne($morphName, $where = []){

			$storageName = $this->_get_storage_name('_morph_one', $morphName);

			if(!\Storage::get($storageName)){

				$parsed = $this->parse_alias($morphName);
				
				\Storage::set($storageName, [
					'many' 			=> false,
					'morph_model' 	=> $parsed['model_name'].'_type',
					'morph_id' 		=> $parsed['model_name'].'_id',
					'where' 		=> $where, 
					'alias' 		=> $parsed['alias'],
					'model_method'	=> $parsed['model_method'],
				]);
			}
		}

		public function morphMany($model_name, $morphName, $where = []){

			$storageName = $this->_get_storage_name('_morph_many', $model_name);

			//pr($storageName);

			//return $this;

			if(!\Storage::get($storageName)){

				$parsed = $this->parse_alias($model_name);
				
				\Storage::set($storageName, [
					'many' 			=> $parsed['model_name'],
					'morph_model' 	=> $morphName.'_type',
					'morph_id' 		=> $morphName.'_id',
					'where' 		=> $where, 
					'alias' 		=> $parsed['alias'],
					'model_method'	=> $parsed['model_method'],
				]);
			}

			return $this;
		}

		public function has_many($model_name, $local_field = false, $remote_field = false, $where = array()){			

			if(!\Storage::get($this->_get_storage_name('_has_many', $model_name))){

				$parsed 		= $this->parse_alias($model_name);
				$current_model 	= $this->model_name();

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

			$storageName = $this->_get_storage_name('_has_many_through', $model_name);

			if(!\Storage::get($storageName)){

				$parsed 		= $this->parse_alias($model_name);
				$current_model 	= $this->model_name();
				$parsed_map 	= $this->parse_alias($map_model);

				\Storage::set($storageName, [
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

			$storageName = $this->_get_storage_name('_has_one_through', $model_name);

			if(!\Storage::get($storageName)){

				$parsed 		= $this->parse_alias($model_name);
				$current_model 	= $this->model_name();
				$parsed_map 	= $this->parse_alias($map_model);

				\Storage::set($storageName, [
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
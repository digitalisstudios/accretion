<?php
	class Magic_Model extends Global_Model_Method {

		public function __construct(){

		}

		public function __callStatic($name, $value = array()){

			$res = Model::get($name);

			if(is_string($value)){
				$value = array($value);
			}

			if(!empty($value)){
				if(count($value) == 1){
					if(!is_array($value[0])){
						return $res->load($value[0]);
					}
					else{
						return $res->where($value[0]);
					}
				}
				else{
					$res->where($value);
				}
			}

			return $res;			
		}

		//FORWARD UNFOUND METHODS
		public function __call($name, $value){

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
			foreach(array('forward_has_many','forward_has_many_through','forward_has_one','forward_has_one_through','forward_has_many_merge_through') as $forwarder){
				
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

		public function forward_has_many_merge_through($name, $val){
			return $this->get_has_many_merge_through($name, $val);
		}



		public function get_has($name, $value, $single = false){

			$check = $single === true ? '_has_one' : '_has_many';
			$check = $this->$check;

			//CHECK IF THIS MODEL HAS MANY		
			if(isset($check[strtolower($name)])){

				$get 			= $check[strtolower($name)];

				//SET THE TABLE NAME
				$get['table'] 	= $this->table_name($get['model']);						
				

				//LOAD THE MODEL AND SET THE PARENT
				$model 	= Model::get($get['model'])->set_parent($this);

				//IF NO LOCAL FIELD WAS PASSED DO NOT USE THE MAP
				if(!isset($get['local_field'])){
					$model->use_map(false);
				}

				//THE LOCAL FIELD WAS PASSED SO GET THE VARS
				else{
					$local_field 	= $get['local_field'];
					$field_value 	= $this->$local_field;
					$field_name 	= $get['remote_field'];
				}

				//SET RELATIONSHIP WHERE VALUE
				if(!empty($get['where'])){
					$model->where($get['where']);
				}

				//SET THE PASSED WHERE VALUE
				if(!empty($value)){				
					$model->where($value[0]);							
				}

				//SET THE WHERE VALUE IF WE NEED TO USE THE MAP
				if(!isset($model->_use_map) || $model->_use_map === true){
					$model->where("`{$model->_where_alias}`.`{$field_name}` = '{$field_value}'");	
				}

				if($model->_single == true){
					$single = true;
				}

				if($single === true){					

					$res = $model->single($single)->load();

					if(is_model($res)){
						
						$primary_field = $res->primary_field();

						if(isset($res->$primary_field)){
							return $res;
						}
						else{
							return array();
						}
					}	
					return $res;
				}
				

				//RETURN THE LOADED MODEL
				return $model->single($single)->load();

			}	

			return false;
		}

		public function get_has_many_merge_through($name, $val){
			
			$check = $this->_has_many_merge_through;

			if(isset($check[strtolower($name)])){
				$get 							= $check[strtolower($name)];
				$get['table'] 					= $this->table_name($get['model']);
				$where 							= [];
				$target_model 					= $get['model'];
				$target_primary_field 			= Model::get($target_model)->primary_field();
				$where['only'] 					= array($target_primary_field);
				$ids 							= array();

				foreach($get['merge_models'] as $merge_model){
					$model_name = $merge_model['alias'];
					$map_where = ['only' => [$target_primary_field]];
					$map_where[] = $merge_model['where'];
					$map_model = $this->$model_name($map_where);
					if(is_object($map_model)){
						if(get_class($map_model) == 'ORM_Wrapper'){
							$ids = array_merge($map_model->get_column($target_primary_field));
						}
						else{
							if($map_model->$target_primary_field){
								$ids[] = $map_model->$target_primary_field;
							}
							
						}
					}
				}

				$target_records = Model::get($target_model);
				$ids 			= implode(',', array_unique($ids));
				$where 			= array('where' => array(
					$target_primary_field." IN({$ids}) "
				));

				if(!empty($get['where'])){
					$where['where'][] = $get['where'];
				}

				if(!empty($value)){
					$where['where'][] = $value[0];
				}

				$target_records->where($where)->set_parent($this);

				$single = false;

				if($target_records->_single == true){
					$single = true;
				}

				return $target_records->single($single)->load();
			}

		}				

		public function get_has_through($name, $value, $single = false){

			$check = $single === true ? '_has_one_through' : '_has_many_through';
			$check = $this->$check;

			if(isset($check[strtolower($name)])){
				$get = $check[strtolower($name)];
				$get['table'] 					= $this->table_name($get['model']);
				$model_name 					= $get['map_alias'];
				$map_where = [];

				if(!empty($get['map_where'])){
					$map_where['where'][] 		= $get['map_where'];
				}

				if(!empty($value)){
					if(isset($value[0]['map_where'])){
						$map_where['where'][] 	= $value[0]['map_where'];
					}
				}
				
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
									if($sub_res->count()){
										foreach($sub_res as $x => $r){
											$ids[] = $r->$target_primary_field;
										}
									}
								}
								else{
									$ids[] = $sub_res->$target_primary_field;
								}
							}
						}
					}
					else{
						$sub_res = $map_model->$target_model_name($where);

						if(get_class($sub_res) == 'ORM_Wrapper'){
							if($sub_res->count()){
								foreach($sub_res as $x => $r){
									$ids[] = $r->$target_primary_field;
								}
							}
						}
						else{
							$ids[] = $sub_res->$target_primary_field;
						}
					}
				}
				elseif(is_array($map_model)){
					if(!empty($map_model)){
						foreach($map_model as $k => $v){
							$ids[] = $v->$target_primary_field;
						}
					}
				}

				$target_records = Model::get($target_model);
				$ids 			= implode(',', array_unique($ids));
				$where 			= array('where' => array(
					$target_primary_field." IN({$ids}) "
				));

				if(!empty($get['where'])){
					$where['where'][] = $get['where'];
				}

				if(!empty($value)){
					$where['where'][] = $value[0];
				}

				$target_records->where($where)->set_parent($this);

				if($target_records->_single == true){
					$single = true;
				}

				return $target_records->single($single)->load();
			}

			return false;			
		}
	}
?>
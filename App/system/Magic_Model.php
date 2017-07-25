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
			foreach(array('forward_has_many','forward_has_many_through','forward_has_one','forward_has_one_through') as $forwarder){
				
				$res = $this->$forwarder($name, $value);

				if($res !== false){
					return $res;
				}
			}

			//NOTHING RETURNED SO RETURN FALSE
			return false;				
		}

		public function forward_has_many($name, $value){

			//CHECK IF THIS MODEL HAS MANY
			if(isset($this->_has_many)){

				foreach($this->_has_many as $get){
				
					//SET THE TABLE NAME
					$get['table'] = $this->table_name($get['model']);

					//CHECK THAT THIS IS THE PROPERTY WE WANT
					if($get['alias'] === false && (strtolower($name) == strtolower($get['table']) || strtolower($name) == strtolower($get['model'])) || $get['alias'] !== false && strtolower($name) == $get['alias']){

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
							$model->where("`{$field_name}` = '{$field_value}'");
						}

						//RETURN THE LOADED MODEL
						return $model->load();
					}
				}
			}

			return false;
		}

		public function forward_has_many_through($name, $value){
			
			//CHECK FOR A HAS MANY THROUGH RELATIONSHIP
			if(isset($this->_has_many_through)){

				//CYCLE THROUGH THE 
				foreach($this->_has_many_through as $get){

					//GET THE TABLE NAME FOR THE MODEL
					$get['table'] = $this->table_name($get['model']);					
					
					//CHECK FOR A MATCH
					if($get['alias'] === false && (strtolower($name) == strtolower($get['table']) || strtolower($name) == strtolower($get['model'])) || $get['alias'] !== false && strtolower($name) == $get['alias']){		


							
						$model_name = $get['map_model'];

						$map_where = array();
						//$map_where 	= array('wrap' => true);

						if(!empty($get['map_where'])){
							$map_where['where'][] = $get['map_where'];
						}

						if(!empty($value)){
							if(isset($value[0]['map_where'])){
								$map_where['where'][] = $value[0]['map_where'];
							}
						}

						
						$map_model 		= $this->$model_name($map_where);
						$target_model 	= $get['model'];
						$target_model_name = strtolower($target_model);
						if($get['map_alias']){
							$target_model_name = $get['map_alias'];
						}

						$target_primary_field 	= Model::get($target_model)->primary_field();
						$where['only'] 			= array($target_primary_field);
						$ids 					= array();	



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

						return $target_records->where($where)->set_parent($this)->load();
					}
				}
			}
			return false;
		}

		public function forward_has_one($name, $value){

			//CHECK IF THIS MODEL HAS ONE
			if(isset($this->_has_one)){
				foreach($this->_has_one as $get){			
					$get['table'] = $this->table_name($get['model']);
					if($get['alias'] === false && (strtolower($name) == strtolower($get['table']) || strtolower($name) == strtolower($get['model'])) || $get['alias'] !== false && strtolower($name) == $get['alias']){

						$local_field = $get['local_field'];

						if(!isset($this->$local_field)){
							continue;
						}
						
						$field_value 	= $this->$local_field;
						$field_name 	= $get['remote_field'];				
						$model 			= Model::get($get['model'])->where("{$field_name} = '{$field_value}'")->limit(1);

						if(!empty($get['where'])){
							$model->where($get['where']);
						}

						if(!empty($value)){
							$model->where($value[0]);
						}

						$model = $model->set_parent($this)->load();
						
						if(get_class($model) == 'ORM_Wrapper'){
							if($model->count()){
								return $model->first();
							}
							else{
								return $model;
							}
						} 
						else{
							return $model;
						}
					}
				}
			}
			return false;
		}

		public function forward_has_one_through($name, $value){
			
			//CHECK FOR A HAS MANY THROUGH RELATIONSHIP
			if(isset($this->_has_one_through)){

				//CYCLE THROUGH THE 
				foreach($this->_has_one_through as $get){

					//GET THE TABLE NAME FOR THE MODEL
					$get['table'] = $this->table_name($get['model']);					
					
					//CHECK FOR A MATCH
					if($get['alias'] === false && (strtolower($name) == strtolower($get['table']) || strtolower($name) == strtolower($get['model'])) || $get['alias'] !== false && strtolower($name) == $get['alias']){						
							
						$model_name = $get['map_model'];
						//$map_where 	= array('wrap' => true);
						$map_where = array();

						if(!empty($get['map_where'])){
							$map_where['where'][] = $get['map_where'];
						}

						if(!empty($value)){
							if(isset($value[0]['map_where'])){
								$map_where['where'][] = $value[0]['map_where'];
							}
						}
						
						$map_model 		= $this->$model_name($map_where);
						$target_model 	= $get['model'];
						$target_model_name = strtolower($target_model);
						if($get['map_alias']){
							$target_model_name = $get['map_alias'];
						}

						$target_primary_field 	= Model::get($target_model)->primary_field();
						$where['only'] 			= array($target_primary_field);
						$ids 					= array();						

						
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

						return $target_records->where($where)->set_parent($this)->single(true)->load();
					}
				}
			}
			return false;
		}
	}
?>
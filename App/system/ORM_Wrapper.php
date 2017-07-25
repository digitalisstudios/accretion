<?php
	class ORM_Wrapper extends Accretion implements Iterator{

		private $_position 	= 0;
		private $_data 		= array();
		private $_results;

		public function __construct($data = array()){
			if(!empty($data)){
				$this->push($data);
				return $this;
			}
		}

		public function __call($name, $value = array()){

			//START THE WRAPPER
			$this->_results = new ORM_Wrapper;

			//SET THE FOUND MODELS ARRAY
			$found_models = array();
			
			//SET DIRECT VALUE
			$direct_val = !empty($value) ? $value[0] : null;

			//LOOP THROUGH THE CURRENT DATA
			foreach($this->_data as $k => $v){

				//REFLECT THE CLASS IF NEEDED
				$res = Reflect::reflectable_class($v, $name, $value) ? Reflect::reflect_class($v, $name, $value) : $v->$name($direct_val);

				//ARRAY OF MODELS OR AN ORM WRAPPER
				if((is_array($res)) || (is_object($res) && get_class($res) == 'ORM_Wrapper')){
					$is_model_array = false;
					foreach($res as $x => $y){
						if(is_object($y) && is_subclass_of($y, 'Model')){

							//SET SOME VARS
							$is_model_array = true;
							$primary_field 	= $y->primary_field();
							$id 			= $y->$primary_field;

							//ONLY ADD UNFOUND MODELS
							if(!isset($found_models[$id])){
								$found_models[$id] = true;
								$this->_results->push($y);
								continue;
							}							
						}
						else{
							break;
						}
					}

					if(!$is_model_array){
						$this->_results->push($v);
					}
				}

				//SINGLE MODEL
				elseif(is_object($res) && is_subclass_of($res, 'Model')){

					//SET SOME VARS
					$primary_field 	= $res->primary_field();
					$id 			= $res->$primary_field;

					//ONLY ADD UNFOUND MODELS
					if(!isset($found_models[$id])){
						$found_models[$id] = true;
						$this->_results->push($res);
					}							
				}

				//JUST PUSH THE RESULT
				else{
					$this->_results->push($res);
				}
			}

			//SEND THIS BACK
			return $this;
		}

		public function results(){
			return $this->_results;
		}

		public function to_array(){
			return $this->_data;
		}

		public function filter($where, $new = false){
			$model 			= $this->first();
			$class_name 	= get_class($model);
			$primary_field 	= $model->primary_field();
			$ids = array();
			foreach($this->_data as $record){	
				$ids[] = $record->$primary_field;
			}
			$ids 			= implode(',', $ids);
			$res 			= Model::get($class_name)->where("`{$primary_field}` IN({$ids})")->where($where)->only([$primary_field])->wrap()->load();
			$new_data 		= array();

			foreach($res as $record){
				foreach($this->_data as $current){
					if($current->$primary_field == $record->$primary_field){
						$new_data[] = $current;
						break;
					}
				}
			}

			if($new === true){
				return new ORM_Wrapper($new_data);
			}

			$this->_data = $new_data;
			return $this;
		}

		public function get_column($column_name){
			$res = [];
			foreach($this->_data as $k => $v){
				$res[] = $v->$column_name;
			}

			return $res;
		}

		public function set($data){
			foreach($this->_data as $record){
				$record->set($data)->save();
			}
			return $this;
		}

		public function chunk($size){
			
			$chunks = [];
			foreach(array_chunk($this->_data, $size) as $chunk){
				$chunks[] = new ORM_Wrapper($chunk);				
			}
			return $chunks;
		}


		public function count($value = true){
			return count($this->_data);
		}

		public function valid(){
			return isset($this->_data[$this->_position]);
		}

		public function rewind(){
			$this->_position = 0;
			return $this;
		}

		public function current(){
			return $this->_data[$this->_position];
		}

		public function key(){
			return $this->_position;
		}

		public function next(){
			$this->_position ++;
			return $this;
		}

		public function previous(){
			if($this->_position > 0){
				$this->_position --;
			}
			return $this;
		}

		public function first(){
			foreach($this->_data as $item){
				return $item;
			}
		}

		public function update($key = false, $value){
			if(!$key){
				$key = $this->_position;
			}

			$this->_data[$key] = $value;
			return $this;
		}

		public function push($data){

			if(is_array($data)){
				foreach($data as $k => $v){
					$this->_data[] = $v;
				}
			}
			else{
				$this->_data[] = $data;
			}

			return $this;
		}
	}
?>
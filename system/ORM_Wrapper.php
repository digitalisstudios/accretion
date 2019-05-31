<?php	

	class ORM_Wrapper extends Accretion implements Iterator, ArrayAccess, Countable {

		private $_position 	= 0;
		private $_data 		= array();
		private $_keys 		= [];
		private $_results;

		public function __construct($data = []){
			if(!empty($data)){
				$this->push($data);
				return $this;
			}
		}

		public function __get($name){
			if(substr($name, 0, 1) == '_'){
				$new_name = substr($name, 1);
				if(is_numeric($new_name)){
					return $this->offsetGet($new_name);
				}
			}

			return $this->offsetGet($name);
		}

		public function __isset($name){
			return isset($this->_data[$name]);
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

		public function offsetSet($offset, $value) {
	        if (is_null($offset)) {
	            $this->_data[] = $value;
	        } else {
	            $this->_data[$offset] = $value;
	        }
	        $this->_keys = array_keys($this->_data);
	    }

	    public function offsetExists($offset) {
	        return isset($this->_data[$offset]);
	    }

	    public function offsetUnset($offset) {
	        unset($this->_data[$offset]);
	        $this->_keys = array_keys($this->_data);
	    }

	    public function offsetGet($offset) {
	        return isset($this->_data[$offset]) ? $this->_data[$offset] : null;
	    }

		public function results(){
			return $this->_results;
		}

		

		public function to_array($full = false){

			if($full){
				$res = [];

				foreach($this->_data as $k => $v){
					if(is_object($v)){
						if(is_model($v)){
							$res[$k] = $v->expose_data();
						}
						elseif(get_class($v) == 'ORM_Wrapper'){
							$res[$k] = $v->to_array(true);
						}
					}
					else{
						$res[$k] = $v;
					}
				}

				return $res;
			}

			return $this->_data;
		}

		public function merge($data){
			if(is_object($data)){
				if(get_class($data) == 'ORM_Wrapper'){
					foreach($data as $k => $v){
						if(is_numeric($k)){
							$this->push($v);
						}
						else{
							$this->_data[$k] = $v;
						}
					}
				}
				else{
					$this->push($data);
				}
			}
			else{
				$this->push($data);
			}

			$this->_keys = array_keys($this->_data);

			return $this;
		}

		public function flatten(){
			
			$new_data = [];

			foreach($this->_data as $k => $v){
				if(is_object($v) && get_class($v) == 'ORM_Wrapper'){
					foreach($v->flatten() as $x => $y){
						$new_data[] = $y;
					}
				}
				else{
					$new_data[] = $v;
				}
			}

			$this->_data = $new_data;

			$this->_keys = array_keys($this->_data);

			return $this;
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

			$this->_keys = array_keys($this->_data);
			
			return $this;
		}

		public function get_column($column_name, $column_key = null){
			$res = [];
			foreach($this->_data as $k => $v){
				if(!is_null($column_key)){
					$res[$v->$column_key] = $v->$column_name;
				}
				else{
					$res[] = $v->$column_name;
				}
				
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
			
			$chunks = new ORM_Wrapper;
			foreach(array_chunk($this->_data, $size) as $chunk){
				$chunks->push(new ORM_Wrapper($chunk));				
			}
			return $chunks;
		}

		public function count($value = true){
			return count($this->_data);
		}

		public function valid(){
			return isset($this->_keys[$this->_position]);
		}

		public function rewind(){
			$this->_position = 0;
			return $this;
		}

		public function current(){
			return $this->_data[$this->_keys[$this->_position]];
		}

		public function key(){
			return $this->_keys[$this->_position];
		}

		public function nth($offset){
			return $this->offsetGet($offset);
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

		public function last(){

			$keys = $this->_keys;
			$last_key = array_pop($keys);
			return $this->_data[$last_key];
			
		}

		public function update($key = false, $value){
			if(!$key){
				$key = $this->_position;
			}

			$this->_data[$key] = $value;
			return $this;
		}

		public function make($data){
			if(is_array($data)){
				$this->_data = $data;
			}
			elseif(is_object($data)){
				if(get_class($data) == 'ORM_Wrapper'){
					$this->_data = $data->to_array();
				}
				elseif(is_model($data)){
					$this->_data[] = $data;
				}
			}

			$this->_keys = array_keys($this->_data);

			return $this;
		}

		public static function collect($data){

			$res = new \ORM_Wrapper;

			return $res->make($data);
		}

		public function order_by(){

			$direction = [
				'asc' 	=> SORT_ASC,
				'desc' 	=> SORT_DESC
			];

			$passed_data 	= func_get_args();
			$args 	 		= [];

		    foreach ($passed_data as $n => $field_params) {
		    	foreach($field_params as $field => $dir){
		    		$tmp = array();
		            foreach ($this->_data as $key => $row){
		            	if(is_object($row)){
		            		$tmp[$key] = $row->$field;
		            	}
		            	else{
		            		$tmp[$key] = $row[$field];
		            	}		                
		            }
		            $args[] = $tmp;
		            $args[] = isset($direction[strtolower($dir)]) ? $direction[strtolower($dir)] : SORT_ASC;
		    	}        
		    }

		    $args[] = &$this->_data;

		   
		    call_user_func_array('array_multisort', $args);

		    $this->_data = array_pop($args);

		    return $this;

		}

		public function push($data){

			if(is_array($data)){
				foreach($data as $k => $v){
					$this->_data[$k] = $v;
				}
			}
			else{
				$this->_data[] = $data;
			}

			$this->_keys = array_keys($this->_data);

			return $this;
		}
	}

	
?>
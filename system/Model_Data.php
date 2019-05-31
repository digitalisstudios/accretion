<?php
	class Model_Data implements Iterator, ArrayAccess, Countable {
		
		public $_position 			= 0;
		public $_data	= [];
		public $_keys 				= [];
		//private $_model;
		//public $_unique_id;
		//private $_structure 	= [];

		public function __construct($model){
			//$this->_unique_id = \Storage::create_unique_id();
			//\Storage::set('_model_data_models.'.$this->_unique_id, $model);
			//$this->_model = $model;
			//$this->_position 	= 0;
			//$this->_structure 	= $model->structure;
		}

//--------------------------/ ITERATOR METHODS /-------------------//

		public function rewind(){
			$this->_position = 0;
		}

		public function current(){
			return $this->_data[$this->_keys[$this->_position]];
		}

		public function key(){
			return $this->_keys[$this->_position];
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

		public function valid(){
			return isset($this->_keys[$this->_position]);
		}
	

//--------------------------/ ARRAYACCESS METHODS /-------------------//
		
		public function offsetSet($offset, $value = null){
			if(is_null($offset)){
				$this->_data[] = $value;
			}
			else{
				$this->_data[$offset] = $value;
			}
			$this->_keys = array_keys($this->_data);
		}

		public function offsetExists($offset){
			return isset($this->_data[$offset]);
		}

		public function offsetUnset($offset){
			unset($this->_data);
			$this->_keys = array_keys($this->_data);
		}

		public function offsetGet($offset){
			return isset($this->_data[$offset]) ? $this->_data[$offset] : null;
		}

//--------------------------/ COUNTABLE METHODS /-------------------//

		public function count(){
			return count($this->_data);
		}

		/*
		public function _get_model(){
			return \Storage::get('_model_data_models.'.$this->_unique_id);
			//return $this->_model;
		}

		
		public function _get_cached_update_data($res){
			$existing_model = $this->_get_model();
			$update_data 	= $existing_model->get_update_data();

			foreach($update_data as $k => $v){
				if(isset($res[$k]) && $res[$k] !== $v){
					$res[$k] = $v;
				}
			}

			return $res;
		}
		*/
	}
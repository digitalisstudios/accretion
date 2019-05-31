<?php
	class Model_Orm extends Magic_Model {

		public static $_map = [
			'orderBy' 			=> '_orm_order_by',
			'groupBy' 			=> '_orm_group_by',
			'show_query' 		=> '_orm_get_query',
			'sql' 				=> '_orm_get_query',
			'show_sql' 			=> '_orm_get_query',
			'get_sql' 			=> '_orm_get_query',
			'getSql' 			=> '_orm_get_query',
			'withTrashed' 		=> '_orm_include_deleted',			
		];

		public function __construct(){
			parent::__construct();
		}

		public function _orm_where($query){

			//IF THE QUERY IS AN ARRAY
			if(is_array($query)){

				/*
				$map = [
					'where' 			=> '_orm_where',
					'whereIn' 			=> '_orm_whereIn',
					'order' 			=> '_orm_order_by',
					'order_by' 			=> '_orm_order_by',
					'orderBy' 			=> '_orm_order_by',
					'group_by'			=> '_orm_group_by',
					'groupBy' 			=> '_orm_group_by',
					'limit' 			=> '_orm_limit',
					'count' 			=> '_orm_count',
					'single' 			=> '_orm_single',
					'paginate' 			=> '_orm_paginate',
					'only' 				=> '_orm_only',
					'sum' 				=> '_orm_sum',
					'get_query' 		=> '_orm_get_query',
					'getSql' 			=> '_orm_get_query',
					'map_where' 		=> '_orm_map_where',
					'join' 				=> '_orm_join',
					'use_map' 			=> '_orm_use_map',
					'call_hooks' 		=> '_orm_call_hooks',
					'include_deleted'	=> '_orm_include_deleted',
					'withTrashed' 		=> '_orm_include_deleted',
					'set_parent' 		=> '_orm_set_parent',
					'with' 				=> '_orm_with'
				];
				*/

				//LOOP THE QUERY PARAMS
				foreach($query as $k => $v){

					if(isset(\Model_Orm::$_map[$k])){
						$method = \Model_Orm::$_map[$k];
						$this->$method($v);
					}
					elseif(is_numeric($k)){
						$this->_orm_where($v);
					}
					elseif(is_string($k) && is_string($v)){
						$this->_orm_where("{$k} = '{$v}'");
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

		public function _orm_whereIn($name, $vals = []){

			if(is_array($vals)){
				foreach($vals as $k => $val){
					$vals[$k] = "'".\DB::escape($val)."'";
				}

				$vals = implode(',', $vals);
			}

			$this->where("{$name} IN({$vals})");
			

			return $this;
		}

		public function _orm_set($data = array()){
			if(empty($data)){
				return $this;
			}

			$primary_field = $this->primary_field();

			if(isset($data[$primary_field]) && !empty($data[$primary_field]) && !is_null($data[$primary_field]) && is_numeric($data[$primary_field])){
				if(!\Storage::get('_model_data_cache.'.$this->model_name().'.'.$data[$primary_field])){
					\Storage::set('_model_data_cache.'.$this->model_name().'.'.$data[$primary_field], $this->_data);
				}
			}


			foreach($data as $key => $value){

				if($value === '' && isset($this->structure[$key]) && isset($this->structure[$key]['Null']) &&  $this->structure[$key]['Null'] == 'YES'){
					//$value = NULL;
				}

				$this->$key = $value;
			}

			return $this;
		}

		public function _orm_only($only = array()){
			if(!$this->_only){
				$this->_only = array();
			}
			foreach($only as $k => $v){
				$this->_only[] = $v;
			}
			if(empty($this->_only)){
				unset($this->_only);
			}
			return $this;
		}

		public function _orm_autoload($val = true){
			$this->_autoload = $val;
			return $this;
		}

		public function _orm_call_hooks($value = false){
			$this->_call_hooks = $value;
			return $this;
		}

		public function _orm_single($value = true){
			$this->_single = $value;
			return $this;
		}

		public function _orm_count($value = true){
			$this->_count = $value;
			return $this;
		}

		public function _orm_where_alias($alias){
			$this->_where_alias = $alias;
			return $this;
		}

		public function _orm_with($models = []){

			if(!$this->_with) $this->_with = [];

			if(is_string($models)) $models = [$models];

			$with = [];

			foreach($models as $k => $v){
				if(is_int($k)){
					if(!is_array($v)){
						$with[$v] = [];
					}
					else{
						$this->_orm_with($v);
					}
				}
				else{
					$with[$k] = $v;
				}
			}			

			foreach($with as $k => $v) $this->_with[$k] = $v;

			if(empty($this->_with)) unset($this->_with);

			return $this;
		}

		public function _orm_join($query, $type = "JOIN"){
			
			if(!isset($this->_join)){
				$this->_join = [];
			}
			if(is_array($query)){
				foreach($query as $k => $v){
					if(is_numeric($k)){
						if(is_array($v)){
							$this->_orm_join($v);
						}
						else{
							$join_type = isset($query[$k+1]) ? $query[$k+1] : "JOIN";
							$this->_orm_join($v, $join_type);
							break;
						}						
					}
				}
			}
			else{
				$this->_join[] = ['type' => $type, 'query' => $query];
			}
			
			return $this;
		}

		public function _orm_sum($field = false){
			$this->_sum = $field;
			return $this;
		}

		public function _orm_get_query($val = true){
			$this->_get_query = $val;
			return $this;
		}

		public function _orm_map_where($query){
			
			if(!isset($this->_map_where)){
				$this->_map_where = array();
			}
			$this->_map_where[] = $query;

			return $this;
		}

		public function _orm_limit($number = 1, $offset = null){

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

		public function _orm_use_map($val = true){
			$this->_use_map = $val;
			return $this;
		}

		public function _orm_paginate($val = true){
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

		public function _orm_set_parent($parent){
			$this->_calling_parent = $parent;
			return $this;
		}

		public function _orm_order_by($field_name, $direction = false){

			if($direction == false){
				$direction = "";
			}

			if(is_array($field_name)){
				$field_name = trim(implode(', ', $field_name));
			}
						
			$this->_order = " ORDER BY {$field_name} {$direction}";
			return $this;
		}

		public function _orm_group_by($query){

			if($query === false){
				$this->_group_by = [];
			}
			else{
				$this->_group_by[] = $query;
			}
			
			return $this;
		}

		public function _orm_include_deleted($val = true){
			$this->_include_deleted = $val;
			return $this;
		}
	}
<?php
	class Reflect extends Accretion {

		public function __construct(){

		}

		public static function parse_parameter_name($param, $values = array(), $key = 0){

			$res = array(
				'original' 	=> $param->name,
				'as' 		=> false,
				'in' 		=> false,
				'type' 		=> 'raw',
				'name' 		=> $param->name,
				'class'		=> $param->getClass() ? $param->getClass()->name : false,				
				'single'	=> true,
				'key'		=> $key,
				'value'		=> false,
				'use'		=> false,
			);

			if(strpos($res['name'],'_as_') !== false){
				$parts 			= explode('_as_', $res['name']);
				$res['as'] 		= $parts[1];
				$res['name'] 	= $parts[0];
			}			

			if(strpos($res['name'], '_in_') !== false){
				$parts 			= explode('_in_', $res['name']);
				$res['in'] 		= $parts[1];
				$res['name'] 	= $parts[0];
				$res['single']	= false;
			}

			if(strpos($res['name'], '_by_') !== false){
				$parts 			= explode('_by_', $res['name']);
				$res['in'] 		= $parts[1];
				$res['name'] 	= $parts[0];
				$res['single']	= true;
			}

			if(strpos($res['name'], 'get_') !== false){
				$res['type'] 	= 'get';
				$res['name']	= str_replace('get_', '', $res['name']);
			}

			if(strpos($res['name'], 'post_') !== false){
				$res['type'] 	= 'post';
				$res['name']	= str_replace('post_', '', $res['name']);
			}

			if(in_array($res['type'], ['get','post'])){
				$res['key'] 	= $res['name'];
				$type 			= $res['type'];
				if($res['key'] == ''){
					if($type == 'post' && !Request::empty_post()){
						$res['name'] 	= 'post';
						$res['key'] 	= 'post';
						$res['value'] 	= Request::post();
						$res['use'] 	= true;
					}					
				}
				elseif(Request::$type($res['key'])){

					$res['value'] 	= Request::$type($res['key']);
					$res['use'] 	= true;
				}
			}
			else{
				if(isset($values[$res['key']])){
					$res['value'] 	= $values[$res['key']];
					$res['use'] 	= true;
				}
			}

			if($res['as'] === false){
				$res['as'] = $res['name'];
			}

			if($res['class'] !== false && $res['in'] === false){
				
				$res['in'] = Model::get($res['class'])->primary_field();
			}

			if($res['class'] !== false){
				if(is_string($res['value'])){
					if(strpos($res['value'], "','") !== false){
						$res['value'] = explode("','", $res['value']);
					}
					elseif(strpos($res['value'], ',') !== false){
						$res['value'] = explode(',', $res['value']);
					}
				}

				if(is_array($res['value'])){
					$res['value'] = "'".implode("','", $res['value'])."'";
				}
				elseif(is_string($res['value'])){
					$res['value'] = "'".$res['value']."'";
				}
			}	
			

			return $res;
		}

		public static function reflectable_class($class_name, $method_name, $values = array(), $return_class = false){	

			if(!method_exists($class_name, $method_name)){
				return false;
			}		

			//SET DEFAULTS
			$r 			= new ReflectionMethod($class_name, $method_name);
			$params 	= $r->getParameters();
			$args 		= array();
			$classes 	= array();
			$aliases 	= array();
			$use_args 	= array();
			$in 		= array();
			$single 	= array();

			//IF THERE ARE PARAMS
			if(!empty($params)){

				//LOOP THROUGH THE PARAMS
				foreach($params as $k => $param){

					//CHECK FOR GET AND POST VARIABLES FIRST
					foreach(array('get', 'post') as $type){

						//PARSE THE PARAMETER
						$parsed = Reflect::parse_parameter_name($param);

						//IF THE PARSE TYPE IS THE TYPE WE ARE LOOKING FOR
						if($parsed['type'] == $type){
							
							//IF WE WANT TO USE THE PARAMETER
							if($parsed['use']){

								//SET THE VALS
								$args[$param->name] 	= $parsed['value'];
								$aliases[$param->name] 	= $parsed['as'];
								$classes[$param->name]	= $parsed['class'];
								$single[$param->name]	= $parsed['single'];
								$in[$param->name]		= $parsed['in'];
								$use_args[$param->name]	= true;
							}
						}
					}
				}				

				//CYCLE THE PARAMS
				foreach($params as $k => $param){

					//IF THE PARAM WAS ALREADY SET SKIP IT
					if(isset($args[$param->name])) continue;

					//PARSE THE PARAMETER NAME
					$parsed = Reflect::parse_parameter_name($param, $values, $k);

					//IF WE WANT TO USE THE PARAMETER
					if($parsed['use']){

						//SET THE VALS
						$args[$param->name] 	= $parsed['value'];
						$aliases[$param->name] 	= $parsed['as'];
						$classes[$param->name]	= $parsed['class'];
						$single[$param->name]	= $parsed['single'];
						$in[$param->name]		= $parsed['in'];
						$use_args[$param->name]	= true;
					}
				}
			}

			//ONLY RETURN IF NOT EMPTY
			if(!empty($use_args)){
				$return = [
					'args' 		=> $args, 
					'classes' 	=> $classes, 
					'aliases' 	=> $aliases,
					'single'	=> $single,
					'in'		=> $in,
					'params' 	=> $params, 
				];

				
				return $return;
			}

			//NOTHING WAS RETURNED SO RETURN FALSE
			return false;
		}

		public static function reflect_class($class_name, $method_name, $values = array(), $return_class = false){

			//GET THE REFLECTION PARAMETER DATA
			$reflect_data = Reflect::reflectable_class($class_name, $method_name, $values, $return_class);					

			//IF THERE ARE REFLECTION PARAMETERS
			if($reflect_data){


				//EXTRACT THE COMPONENTS
				$args 			= $reflect_data['args'];
				$classes 		= $reflect_data['classes'];
				$aliases 		= $reflect_data['aliases'];
				$single 		= $reflect_data['single'];
				$in 			= $reflect_data['in'];
				$params 		= $reflect_data['params'];
				$multi_results 	= array();

				//CYCLE THE CLASSES
				foreach($classes as $k => $v){

					//IF WE NEED TO LOAD A MODEL
					if($v !== false && !is_object($args[$k]) && is_subclass_of($v, 'Model')){
						
						//START TO LOAD THE MODEL
						$model = Model::get($v)->where("`{$in[$k]}` IN({$args[$k]})")->order_by("FIELD(`{$in[$k]}`,{$args[$k]})");

						//IF WE NEED THE MODEL TO BE A SINGLE
						if($single[$k] === true){

							//SET THE ARGUMENT AS THE LOADED MODEL
							$args[$k] = $model->single()->load();
						}

						//WE ARE LOADING MULTIPLE MODELS
						else{

							//SET THE MULTI RESULT FOR THIS MODEL
							$multi_results[$k] 	= $model->load();

							//SET THE FIRST ITERATION OF THE RESULT AS THE ARGUMENT
							$args[$k] 			= $multi_results[$k]->first();
						}											
					}

				}

				//MAKE SURE THE METHOD IS NOT A CONSTRUCTOR
				if($method_name !== '__construct'){

					//INIT THE OBJECT IF NEEDED
					$class_name = !is_object($class_name) ? new $class_name : $class_name;
				}				

				//GET THE CLASS WITHOUT THE CONSTRUCTOR
				else{

					//START A NEW REFLECTION CLASS
					$reflect  	= new ReflectionClass($class_name);

					//INSTANTIATE WITHOUT THE CONSTRUCTOR
					$class_name = $reflect->newInstanceWithoutConstructor();

				}

				//CYCLE THE ARGUMENTS
				foreach($args as $k => $v){	

					if(isset($multi_results[$k])){
						$v = $multi_results[$k];
					}

					//SET THE KEY TO THE ALIAS IF NEEDED
					$k = isset($aliases[$k]) ? $aliases[$k] : $k;							
					
					//SET THE ARGUMENT AS A CLASS PROPERTY
					$class_name->$k = $v;
					
				}				

				$new_args = array();
				foreach($params as $k => $param){
					$new_args[$k] = isset($args[$param->name]) ? $args[$param->name] : null;
				}
				$args = $new_args;

				//RUN THE CLASS METHOD WITH PARAMETERS
				return call_user_func_array([$class_name, $method_name], $args);
			}

			if($return_class){
				
				//INIT THE OBJECT IF NEEDED
				$class_name = !is_object($class_name) ? new $class_name : $class_name;

				//SEND BACK THE CLASS
				return $class_name;
			}

			//DEFAULT TO FALSE
			return false;
		}
	}
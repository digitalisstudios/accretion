<?php

	//THIS IS THE FRAMEWORK GLOBAL FUNCTIONS CLASS.
	//ALL OF THE BELOW FUNCTIONS ARE REQUIREMENTS FOR THE FRAMEWORK TO WORK

	//START AND CLOSE THE SESSION
	if(!session_id()){
		session_start();
	}
	session_write_close();

	//SET ERRORS
	ini_set('display_errors', 1);
	error_reporting(E_ALL & ~(E_STRICT|E_NOTICE|E_WARNING|E_DEPRECATED));	
	

	//CHECK IF THE OBJECT IS A MODEL
	if(!function_exists('is_model')){
		function is_model($data){
			if(is_object($data)){
				$parents = array_values(class_parents($data));
				if(in_array('Model', $parents)){
					return true;
				}
			}
			return false;
		}
	}

	//FUNCTION FOR VALIDATING A URL
	if(!function_exists('validate_url')){
		function validate_url($url){
			$regex = "((https?|ftp)\:\/\/)?"; // SCHEME 
		    $regex .= "([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?"; // User and Pass 
		    $regex .= "([a-z0-9-.]*)\.([a-z]{2,3})"; // Host or IP 
		    $regex .= "(\:[0-9]{2,5})?"; // Port 
		    $regex .= "(\/([a-z0-9+\$_-]\.?)+)*\/?"; // Path 
		    $regex .= "(\?[a-z+&\$_.-][a-z0-9;:@&%=+\/\$_.-]*)?"; // GET Query 
		    $regex .= "(#[a-z_.-][a-z0-9+\$_.-]*)?"; // Anchor 

		       if(preg_match("/^$regex$/i", $url)) // `i` flag for case-insensitive
		       { 
		               return true; 
		       }
		    return false;
		}
	}

	//CREATE ARRAY COLUMN 
	if (!function_exists('array_column')){
	    function array_column(array $input, $columnKey, $indexKey = null) {
	        $array = array();
	        foreach ($input as $value) {
	            if ( !array_key_exists($columnKey, $value)) {
	                trigger_error("Key \"$columnKey\" does not exist in array");
	                return false;
	            }
	            if (is_null($indexKey)) {
	                $array[] = $value[$columnKey];
	            }
	            else {
	                if ( !array_key_exists($indexKey, $value)) {
	                    trigger_error("Key \"$indexKey\" does not exist in array");
	                    return false;
	                }
	                if ( ! is_scalar($value[$indexKey])) {
	                    trigger_error("Key \"$indexKey\" does not contain scalar value");
	                    return false;
	                }
	                $array[$value[$indexKey]] = $value[$columnKey];
	            }
	        }
	        return $array;
	    }
	}

	if(!function_exists('strip_msword_formatting')){
		function strip_msword_formatting($data){

			if(is_array($data) || is_object($data)){

				if(is_array($data)){

					$res = array();

					foreach($data as $k => $v){
						$res[$k] = strip_msword_formatting($v);
					}

					return $res;
				}
				elseif(is_object($data)){
					if(get_class($data) == 'ORM_Wrapper'){
						foreach($data as $k => $v){
							$data->update($k, strip_msword_formatting($v));
						}
						return $data;
					}
					else{
						foreach($data as $k => $v){
							$data->$k = strip_msword_formatting($v);
						}
						return $data;
					}
				}
			}
			else{
				$search = [   
	                "\xC2\xAB",     // « (U+00AB) in UTF-8
	                "\xC2\xBB",     // » (U+00BB) in UTF-8
	                "\xE2\x80\x98", // ‘ (U+2018) in UTF-8
	                "\xE2\x80\x99", // ’ (U+2019) in UTF-8
	                "\xE2\x80\x9A", // ‚ (U+201A) in UTF-8
	                "\xE2\x80\x9B", // ‛ (U+201B) in UTF-8
	                "\xE2\x80\x9C", // “ (U+201C) in UTF-8
	                "\xE2\x80\x9D", // ” (U+201D) in UTF-8
	                "\xE2\x80\x9E", // „ (U+201E) in UTF-8
	                "\xE2\x80\x9F", // ‟ (U+201F) in UTF-8
	                "\xE2\x80\xB9", // ‹ (U+2039) in UTF-8
	                "\xE2\x80\xBA", // › (U+203A) in UTF-8
	                "\xE2\x80\x93", // – (U+2013) in UTF-8
	                "\xE2\x80\x94", // — (U+2014) in UTF-8
	                "\xE2\x80\xA6"  // … (U+2026) in UTF-8
	    		];

			    $replacements = [
	                "<<", 
	                ">>",
	                "'",
	                "'",
	                "'",
	                "'",
	                '"',
	                '"',
	                '"',
	                '"',
	                "<",
	                ">",
	                "-",
	                "-",
	                "..."
			    ];

	    		return str_replace($search, $replacements, $data);
			}

			return $data;	
		}
	}

	//DEBUG FUNCTION FOR PRINTING OUT DATA
	if(!function_exists('pr')){
		function pr($data, $extra = false){
			
			if(is_object($data) && !$extra && is_model($data)){
				$new_model_name = get_class($data);
				$new_model = new $new_model_name;
				foreach($new_model as $k => $v){
					unset($new_model->$k);
				}
				$model_data = $data->expose_data();
				foreach($model_data as $k => $v){
					$new_model->$k = $v;
				}
				$new_data = $new_model;
			}
			elseif(is_array($data)){
				$new_data = array();
				foreach($data as $k => $d){
					if(is_object($d) && !$extra && is_model($d)){
						$new_model_name = get_class($d);
						$new_model = new $new_model_name;
						foreach($new_model as $x => $v){
							unset($new_model->$x);
						}
						$model_data = $d->expose_data();
						foreach($model_data as $x => $v){
							$new_model->$x = $v;
						}
						$new_data[$k] = $new_model;
					}
					else{
						$new_data[$k] = $d;
					}
				}
			}
			elseif(is_object($data) && !$extra && get_class($data) == 'ORM_Wrapper'){

				$new_data = new ORM_Wrapper;

				foreach($data as $k => $d){
					if(is_object($d) && !$extra && is_model($d)){

						
						$new_model_name = get_class($d);
						$new_model = new $new_model_name;

						foreach($new_model as $x => $v){
							unset($new_model->$x);
						}

						$model_data = $d->expose_data();


						foreach($model_data as $x => $v){
							$new_model->$x = $v;
						} 

						$new_data->push($new_model);			
						
					}
				}
			}
			else{
				$new_data = $data;
			}
			echo '<pre>';

			if(isset($_GET['pr_info'])){
				$debug = debug_backtrace()[0];
				echo '<br>';
				print_r('File: '.$debug['file']);
				echo '<br>';
				print_r('Line: '.$debug['line']);
				echo '<br>';
			}

			if(!$new_data){
				var_dump($new_data);
			}
			else{
				print_r($new_data);
			}

			echo '</pre>';
		}
	}
?>
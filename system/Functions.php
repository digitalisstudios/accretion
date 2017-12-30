<?php
	//THESE ARE THE FRAMEWORK FUNCTION.
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
		function pr($data, $extra = false, $print = true){

			//IF WE DONT WANT TO PRINT FULL
			if(!$extra){

				//IF WE ARE PRINTING AN OBJECT
				if(is_object($data)){
					
					//CLONE THE DATA
					$new_data = clone $data;

					//IF THE OBJECT IS A MODEL
					if(is_model($data)){


						foreach($new_data as $k => $v){
							if($k == '_data') continue;
							unset($new_data->$k);
						}

						//GET THE KEYS
						//$keys = array_keys($new_data->structure);

						//UNSET THE DATA
						//foreach($new_data as $k => $v) if(!in_array($k, $keys)) unset($new_data->$k);
					}

					//IF WE ARE PRINTING A WRAPPER
					elseif(get_class($data) == 'ORM_Wrapper'){
						foreach($new_data as $k => $v) $new_data[$k] = pr($v, false, false);
					}
				}
				elseif(is_array($data)){
					$new_data = $data;
					foreach($new_data as $k => $v){
						$new_data[$k] = pr($v, false, false);
					}
				}
				elseif(is_string($data)){
					$new_data = htmlentities($data);
				}
				else{
					$new_data = $data;
				}
			}
			else{
				$new_data = $data;
			}

			//JUST RETURN THE DATA IF WE ARE NOT PRINTING
			if(!$print) return $new_data;

			echo '<pre>';			
			$debug = debug_backtrace()[0];
			echo '<b>File: '.$debug['file'].' Line: '.$debug['line'].'</b>'."\n";

			!$new_data ? var_dump($new_data) : print_r($new_data);

			echo '</pre>';
		}
	}

	if(!function_exists('array_group')){
		function array_group(&$arr = []){

			//RETURN EMPTY ARRAYS
			if(empty($arr)) return $arr;

			//INITIALIZE SOME VARS
			$res 		= [];
			$res_keys 	= [];
			$keys 		= array_keys($arr);

			//LOOP THROUGH THE RESULTS
			foreach($keys as $key) 		foreach($arr[$key] as $k => $v) 								$res[$k][$key] 	= $v;
			foreach($res as $k => $v) 	foreach($v as $x => $y) 		if(!in_array($x, $res_keys)) 	$res_keys[] 	= $x;
			foreach($res as $k => $v) 	foreach($res_keys as $x) 		if(!isset($res[$k][$x])) 		$res[$k][$x] 	= null;

			//SEND BACK THE RESULTS
			return $arr = $res;
		}
	}
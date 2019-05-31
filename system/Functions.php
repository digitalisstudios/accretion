<?php

	//SET THE SESSION SAVE PATH
	$session_save_path = __DIR__.'/../session';

	//MAKE THE FOLDER IF NEEDED
	if(!file_exists($session_save_path)) mkdir($session_save_path, 0755, true);

	//SET THE SESSION SAVE PATH
	session_save_path(realpath($session_save_path));

	//START THE SESSION IF NEEDED
	if(!session_id()) session_start();

	//CLOSE THE SESSION (NEED FOR SESSIONS IN MULTIPLE BROWSER TABS)
	session_write_close();

	//SET THE DEFAULT CHARACTER SET
	ini_set('default_charset', 'utf-8');
	ini_set('allow_url_fopen',1);

	//SET ERRORS
	ini_set('display_errors', 1);
	error_reporting(E_ALL & ~(E_STRICT|E_NOTICE|E_WARNING|E_DEPRECATED));

	//SET THE DEFAULT CHARACTER SET
	ini_set('default_charset', 'utf-8');
	ini_set('allow_url_fopen',1);

	if(!function_exists('is_ajax')){
		function is_ajax(){
			return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ? true : false;
		}
	}

	if(!function_exists('mime_content_type')){
		function mime_content_type( $filename ) {
		    $finfo = finfo_open( FILEINFO_MIME_TYPE );
		    $mime_type = finfo_file( $finfo, $filename );
		    finfo_close( $finfo );
		    return $mime_type;
		}
	}

	function unique_id(){
		return \Storage::create_unique_id();
	}

	function closure_dump(Closure $c) {

		$r = new ReflectionFunction($c);
		$lines = file($r->getFileName());
	    $str = "";
	    for($l = $r->getStartLine()-1; $l < $r->getEndLine(); $l++) {
	        $str .= $lines[$l];
	    }

	    $re = '~\s*[\w\s]+\(.*\)\s*({((?>"(?:[^"\\\\]*+|\\\\.)*"|\'(?:[^\'\\\\]*+|\\\\.)*\'|//.*$|/\*[\s\S]*?\*/|#.*$|<<<\s*["\']?(\w+)["\']?[^;]+\3;$|[^{}<\'"/#]++|[^{}]++|(?1))*)})~m';

	    preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0);

	    return ltrim($matches[0][0]);

	    /*
	    $str = 'function (';
	    $r = new ReflectionFunction($c);
	    $params = array();
	    foreach($r->getParameters() as $p) {
	        $s = '';
	        if($p->isArray()) {
	            $s .= 'array ';
	        } else if($p->getClass()) {
	            $s .= $p->getClass()->name . ' ';
	        }
	        if($p->isPassedByReference()){
	            $s .= '&';
	        }
	        $s .= '$' . $p->name;
	        if($p->isOptional()) {
	            $s .= ' = ' . var_export($p->getDefaultValue(), TRUE);
	        }
	        $params []= $s;
	    }
	    $str .= implode(', ', $params);
	    $str .= '){' . PHP_EOL;
	    $lines = file($r->getFileName());
	    //$str .= "\n//".$r->getFileName()."\n";
	    for($l = $r->getStartLine(); $l < $r->getEndLine(); $l++) {
	        $str .= $lines[$l];
	    }
	    return $str;
	    */
	}

	function getArrayByPath(array $a, $path, $default = null)
	{
	  $current = $a;
	  $p = strtok($path, '.');

	  while ($p !== false) {
	    if (!isset($current[$p])) {
	      return $default;
	    }
	    $current = $current[$p];
	    $p = strtok('.');
	  }

	  return $current;
	}


	if(!function_exists('assignArrayByPath')){
		function assignArrayByPath(&$arr, $path, $value, $separator='.') {
		    $keys = explode($separator, $path);

		    foreach ($keys as $key) {
		        $arr = &$arr[$key];
		    }

		    $arr = $value;
		}
	}

	if (!function_exists('getallheaders')) {
	    function getallheaders() {
	    $headers = [];
	    foreach ($_SERVER as $name => $value) {
	        if (substr($name, 0, 5) == 'HTTP_') {
	            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
	        }
	    }
	    return $headers;
	    }
	}

	if(!function_exists('arr2ini')){
		function arr2ini(array $a, array $parent = array())
		{
		    $out = '';
		    foreach ($a as $k => $v)
		    {
		        if (is_array($v))
		        {
		            //subsection case
		            //merge all the sections into one array...
		            $sec = array_merge((array) $parent, (array) $k);
		            //add section information to the output
		            $out .= '[' . join('.', $sec) . ']' . PHP_EOL;
		            //recursively traverse deeper
		            $out .= arr2ini($v, $sec);
		        }
		        else
		        {
		            //plain key->value case
		            $out .= "$k=$v" . PHP_EOL;
		        }
		    }
		    return $out;
		}
	}

	if(!function_exists('get_timezone_offset')){
		function get_timezone_offset($remote_tz, $origin_tz = null) {
		    if($origin_tz === null) {
		        if(!is_string($origin_tz = date_default_timezone_get())) {
		            return false; // A UTC timestamp was returned -- bail out!
		        }
		    }
		    $origin_dtz = new DateTimeZone($origin_tz);
		    $remote_dtz = new DateTimeZone($remote_tz);
		    $origin_dt = new DateTime("now", $origin_dtz);
		    $remote_dt = new DateTime("now", $remote_dtz);
		    $offset = $origin_dtz->getOffset($origin_dt) - $remote_dtz->getOffset($remote_dt);
		    return $offset;
		}
	}

	if(!function_exists('human_time')){
		function human_time($time, $format = 'YMDHIS', $showRel = false, $simple = false){
			$format = is_null($format) ? 'YMDHIS' : $format;
			$time 	= is_numeric($time) ? date('Y-m-d H:i:s', $time) : $time;
			$ago 	= strtotime($time) >= time() ? 'in' : 'ago';			
			$date1 	= new \DateTime($time);
			$date2 	= $date1->diff(new \DateTime);

			$parts = [];

			if($showRel){
				if($ago == 'in'){
					$parts[] = $ago;
				}
			}

			foreach(str_split($format) as $format_part){

				if($format_part == 'Y') $parts[] = $date2->y > 0 ? ($date2->y.' Year'.($date2->y > 1 ? 's' : '')) : '';
				elseif($format_part == 'y') $parts['y'] = $date2->y > 0 ? ($date2->y.'y'.($date2->y > 1 ? 'rs' : '')) : '';
				elseif($format_part == 'M') $parts['m'] = $date2->m > 0 ? ($date2->m.' Month'.($date2->m > 1 ? 's' : '')) : '';
				elseif($format_part == 'm') $parts['m'] = $date2->m > 0 ? ($date2->m.'mth'.($date2->m > 1 ? 's' : '')) : '';
				elseif($format_part == 'D') $parts['d'] = $date2->d > 0 ? ($date2->d.' Day'.($date2->d > 1 ? 's' : '')) : '';
				elseif($format_part == 'd') $parts['d'] = $date2->d > 0 ? ($date2->d.'d') : '';
				elseif($format_part == 'H') $parts['h'] = $date2->h > 0 ? ($date2->h.' Hour'.($date2->h > 1 ? 's' : '')) : '';
				elseif($format_part == 'h') $parts['h'] = $date2->h > 0 ? ($date2->h.'hr'.($date2->h > 1 ? 's' : '')) : '';
				elseif($format_part == 'I') $parts['i'] = $date2->i > 0 ? ($date2->i.' Minute'.($date2->i > 1 ? 's' : '')) : '';
				elseif($format_part == 'i') $parts['i'] = $date2->i > 0 ? ($date2->i.'min'.($date2->i > 1 ? 's' : '')) : '';
				elseif($format_part == 'S') $parts['s'] = $date2->s > 0 ? ($date2->s.' Second'.($date2->s > 1 ? 's' : '')) : '';
				elseif($format_part == 's') $parts['s'] = $date2->s > 0 ? ($date2->s.'s') : '';
			}

			$parts = array_filter($parts);

			if($showRel){
				if($ago == 'ago'){
					if($parts['s'] && ($parts['y'] || $parts['m'] || $parts['d'] || $parts['h'] || $parts['i'])){
						unset($parts['s']);
					}

					if($simple){
						$new_parts = [];
						$new_parts[] = array_shift($parts);
						$parts = $new_parts;
					}

					$parts[] = 'ago';
				}
			}

			return implode(' ',$parts);
		}
	}

	/*
	override_function('count', '$data', 'return override_count($data)');

	if(!function_exists('override_count')){
		function override_count($data){
			pr('override called');
			exit;
		}
	}
	*/

	//FUNCTION FOR FORMATTING A PHONE
	if(!function_exists('format_phone')){
		function format_phone($number){

			//STRIP NON NUMERIC CHARACTERS
			$number = substr(preg_replace('/[^0-9]/', '', str_replace(' ', '', $number)), 0, 11);

			//FORCE 10 DIGIT PHONE NUMBER
			if(strlen($number) == 11) $number = substr($number, 1, 10); 

			//FORMAT THE PHONE NUMBER
			$number = trim('('.substr($number, 0, 3).') '.substr($number, 3, 3).'-'.substr($number, 6, 4));

			return $number == '() -' ? '' : $number;
		}
	}

	//GET THE NAMESPACE FROM A FILE
	function fileNameSpace($src) {
		$tokens = token_get_all($src);
		$count = count($tokens);
		$i = 0;
		$namespace = '';
		$namespace_ok = false;
		while ($i < $count) {
			$token = $tokens[$i];
			if (is_array($token) && $token[0] === T_NAMESPACE) {
				// Found namespace declaration
				while (++$i < $count) {
					if ($tokens[$i] === ';') {
						$namespace_ok = true;
						$namespace = trim($namespace);
						break;
					}
					$namespace .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
				}
				break;
			}
			$i++;
		}
		if (!$namespace_ok) {
			return null;
		} else {
			return $namespace;
		}
	}

	if(!function_exists('is_prime')){
		function is_prime($n){for($i=$n>>1;$i&&$n%$i--;);return!$i&&$n>1;}
	}

	if(!function_exists('is_base64')){
		function is_base64($string){
            
			if($decoded = base64_decode($string, true)){

				if(mb_detect_encoding($decoded) === mb_detect_encoding($string)){

                    return true;

					$charas = [1 => '==',2 => '=',3 => false];

					$i = 0; for($x = 0; $x < strlen($decoded); $x++) $last_chara = $charas[($i = $i === 3 ? 1 : $i+1)];

					if(!$last_chara){
						
						if(strlen($decoded) === 1 && substr($string, -2) === '==') return true;
                        elseif(strlen($decoded) % 3 === 0 && substr($string, -1) !== '=') return true;
						elseif(strlen($decoded) % 2 === 0 && substr($string, -1) === '=') return true;
					}
					elseif(substr($string, -(strlen($last_chara))) === $last_chara) return true;
				}
			}

			return false;
		}
	}

	if(!function_exists('extract_emails')){
		function extract_emails($string){

			$pattern = "/(?:[A-Za-z0-9!#$%&'*+=?^_`{|}~-]+(?:\.[A-Za-z0-9!#$%&'*+=?^_`{|}~-]+)*|\"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*\")@(?:(?:[A-Za-z0-9](?:[A-Za-z0-9-]*[A-Za-z0-9])?\.)+[A-Za-z0-9](?:[A-Za-z0-9-]*[A-Za-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[A-Za-z0-9-]*[A-Za-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])/";
			
			preg_match_all($pattern, $string, $matches);

			return is_array($matches[0]) ? array_unique($matches[0]) : [];
		}
	}

	if(!function_exists('is_valid_date')){
		function is_valid_date($date, $format = 'Y-m-d'){
			
			if(!strtotime($date)) return false;

		    $d = DateTime::createFromFormat($format, $date);
   			return $d && $d->format($format) == $date;
		}
	}

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

	if(!function_exists('rrmdir')){
		function rrmdir($dir) { 
		   if (is_dir($dir)) { 
		     $objects = scandir($dir); 
		     foreach ($objects as $object) { 
		       if ($object != "." && $object != "..") { 
		         if (is_dir($dir."/".$object))
		           rrmdir($dir."/".$object);
		         else
		           unlink($dir."/".$object); 
		       } 
		     }
		     rmdir($dir); 
		   } 
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

				//FORMAT POLISH CHARACTERS
				$specialChars = [
			        '\u0105', # ą
			        '\u0107', # ć
			        '\u0119', # ę
			        '\u0142', # ł
			        '\u0144', # ń
			        '\u00f3', # ó
			        '\u015b', # ś
			        '\u017a', # ź
			        '\u017c', # ż
			        '\u0104', # Ą
			        '\u0106', # Ć
			        '\u0118', # Ę
			        '\u0141', # Ł
			        '\u0143', # Ń
			        '\u00d3', # Ó
			        '\u015a', # Ś
			        '\u0179', # Ż
			        '\u017b', # Ż
			    ];

			    $polishHtmlCodes = [
			        '&#261;', # ą
			        '&#263;', # ć
			        '&#281;', # ę
			        '&#322;', # ł
			        '&#322;', # ń
			        '&#243;', # ó
			        '&#347;', # ś
			        '&#378;', # ź
			        '&#380;', # ż
			        '&#260;', # Ą
			        '&#262;', # Ć
			        '&#280;', # Ę
			        '&#321;', # Ł
			        '&#323;', # Ń
			        '&#211;', # Ó
			        '&#346;', # Ś
			        '&#377;', # Ż
			        '&#379;', # Ż
			    ];

			    //REPLACE POLISH CHARACTERS
			    //$data = json_decode(str_replace($specialChars, $polishHtmlCodes, json_encode($v)));

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

				$printer = new \Debug_Printer($data);
				$printer->render();
				return;

				

				//IF WE ARE PRINTING AN OBJECT
				if(is_object($data)){
					
					//CLONE THE DATA
					$new_data = clone $data;

					//IF THE OBJECT IS A MODEL
					if(is_model($data)){

						//GET THE KEYS
						$keys = array_keys($new_data->structure);

						//UNSET THE DATA
						foreach($new_data as $k => $v) if(!in_array($k, $keys)) unset($new_data->$k);
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
			echo '<b>File: '.$debug['file'].' Line: '.$debug['line'].'</b><br>';

			!$new_data ? var_dump($new_data) : print_r($new_data);

			echo '</pre>';
		}
	}

	if(!function_exists('urlize')){
		function urlize($data){
			return str_replace('_', '-', strtolower($data));
		}
	}

	class Debug_Printer {

		public $_data = [];
		public $_single = false;

		public function __construct($data){

			$this->generate($data);

		}

		public function generate($data = null, $key = false){
			if(is_object($data)){
				if(is_model($data)){

					$arr = [];

					foreach($data->_data->_data as $k => $v){
						$arr[$k] = $this->generate($v, $k);
					}

					$res = new \Debug_Printer_Object([
						'name' => $data->model_name().' Object',
						'data' => $arr
					]);

					if($key === false){
						$this->_data = $res;
						$this->_single = true;
						return $this;
					}
					else{
						return $res;
					}
				}

				elseif(get_class($data) == 'Closure'){

					$rawCode = closure_dump($data);
					$lines = explode("\n", $rawCode);

					foreach($lines as $k => $line){
						if($k > 0){
							$trimmedLine = ltrim($line);
							$baseDiff = strlen($line)-strlen($trimmedLine);

							if($baseDiff > 0){
								if(!isset($baseTrim)) $baseTrim = $baseDiff;
								if($baseDiff < $baseTrim) $baseTrim = $baseDiff;
							}
						}
					}

					if(!isset($baseTrim)) $baseTrim = 0;

					foreach($lines as $k => $line) if($k > 0) $lines[$k] = substr($line, $baseTrim);

					$lines = implode("\n", $lines);

					$res = new \Debug_Printer_Object([
						'name' => 'Closure Object',
						'data' => ['code' => $lines],
					]);

					if($key === false){
						$this->_data = $res;
						return $this;
					}
					else{
						return $res;
					}
				}

				elseif(get_class($data) == 'ClosureWrap'){

					$rawCode = $data->__toString();
					$lines = explode("\n", $rawCode);

					foreach($lines as $k => $line){
						if($k > 0){
							$trimmedLine = ltrim($line);
							$baseDiff = strlen($line)-strlen($trimmedLine);

							if($baseDiff > 0){
								if(!isset($baseTrim)) $baseTrim = $baseDiff;
								if($baseDiff < $baseTrim) $baseTrim = $baseDiff;
							}
						}
					}

					if(!isset($baseTrim)) $baseTrim = 0;

					foreach($lines as $k => $line) if($k > 0) $lines[$k] = substr($line, $baseTrim);

					$lines = implode("\n", $lines);

					$res = new \Debug_Printer_Object([
						'name' => 'Closure Object',
						'data' => ['code' => $lines],
					]);

					if($key === false){
						$this->_data = $res;
						return $this;
					}
					else{
						return $res;
					}
				}
				
				elseif(get_class($data) == 'ORM_Wrapper'){

					$arr = [];

					foreach($data as $k => $v){
						$arr[$k] = $this->generate($v, $k);
					}

					$res = new \Debug_Printer_Object([
						'name' => 'ORM_Wrapper Object',
						'data' => $arr
					]);

					if($key === false){
						$this->_data = $res;
						return $this;
					}
					else{
						return $res;
					}
				}
				elseif(get_class($data) == 'Model_Data'){

					$arr = [];

					foreach($data as $k => $v){
						$arr[$k] = $this->generate($v, $k);
					}

					$res = new \Debug_Printer_Object([
						'name' => 'Model_Data Object',
						'data' => $arr
					]);

					if($key === false){
						$this->_data = $res;
						return $this;
					}
					else{
						return $res;
					}
				}				
				else{	

					if(isset(class_parents($data)['Accretion']) || get_class($data) == 'stdClass'){
						$arr = [];

						foreach($data as $k => $v){
							$arr[$k] = $this->generate($v, $k);
						}
					}
					else{
						$arr = $data;
					}

					

					$res = new \Debug_Printer_Object([
						'name' => get_class($data).' Object',
						'data' => $arr
					]);

					if($key === false){
						$this->_data = $res;
						return $this;
					}
					else{
						return $res;
					}
				}
			}
			elseif(is_array($data)){

				$arr = [];

				foreach($data as $k => $v){
					$arr[$k] = $this->generate($v, $k);
				}

				$res = new \Debug_Printer_Object([
					'name' => 'Array',
					'data' => $arr
				]);

				if($key === false){
					$this->_data = $res;
					return $this;
				}
				else{
					return $res;
				}
			}
			elseif(is_string($data)){

				if($r = @unserialize($data)){

					$arr = [];

					foreach($r as $k => $v){
						$arr[$k] = $this->generate($v, $k);
					}

					$res = new \Debug_Printer_Object([
						'name' => 'Array',
						'data' => $arr
					]);

					if($key === false){
						$this->_data = $res;
						return $this;
					}
					
					return $res;
				}

				$res = new \Debug_Printer_Object([
					'name' => 'string',
					'data' => $data
				]);

				if($key === false){
					$this->_data = $res;
					$this->_single = true;
					return $this;
				}
				return $res;
			}
			else{
				$res = new \Debug_Printer_Object([
					'name' => '',
					'data' => $data
				]);

				if($key === false){
					$this->_data = $res;
					$this->_single = true;
					return $this;
				}

				return $res;
			}
		}

		public function render($wrap = true, $indent = null, $debug_here = false){

			if(is_null($indent)){
				$indent = -8;
			}
			$indent += 4;
			$spacer = "\n";
			$spacer_indent = "";
			//$extra_indent = $indent == 0 ? "" : "    ";
			$extra_indent = "    ";

			for($x = 0; $x < $indent; $x++){
				$spacer_indent .= ' ';
			}
			$spacer .= $spacer_indent;

			if($wrap){
				
				echo '<pre>';
				$debug = debug_backtrace()[1];
				echo '<b>File: '.$debug['file'].' Line: '.$debug['line'].'</b>'."\n";
			}

			if(is_array($this->_data)){

				echo $this->_name."\n{$spacer_indent}(";

				foreach($this->_data as $k => $v){
					echo $spacer.$extra_indent."[".$k.'] => ';

					if(is_object($v) && get_class($v) == 'Debug_Printer_Object'){
						$v->render(false, $indent+4);
					}
					else{			
						echo htmlentities($v);
					}
				}
				echo $spacer.")";
			}
			elseif(is_object($this->_data) && get_class($this->_data) == 'Debug_Printer_Object'){
				$this->_data->render(false, $indent, true);
			}
			elseif(is_object($this->_data)){
				print_r($this->_data);
			}
			elseif(is_string($this->_data)){
				$parts = [];
				foreach(explode("\n",$this->_data) as $part_key => $part){
					$parts[] = $part_key === 0 ? $part : $spacer.$extra_indent.' '.$part;
				}

				echo htmlentities(implode("", $parts));

				//echo htmlentities($this->_data);
			}
			elseif(is_null($this->_data)){
				echo "NULL";
			}
			else{
				echo trim(\Buffer::start(function(){
					var_dump($this->_data);
				}));
			}

			if($wrap){
				echo '</pre>';
			}
		}
	}

	class Debug_Printer_Object extends Debug_Printer {

		public $_name;
		public $_data;
		public $_single = true;

		public function __construct($data){
			$this->_name = $data['name'];
			$this->_data = $data['data'];
		}
	}
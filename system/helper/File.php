<?php
	class File_Helper extends Helper {

		public function __construct(){

		}

		public function createUnique($path = null, $ext = false, $content = null){

			$path = !$path ? STORAGE_TEMP_PATH : $path;

			$filePath = $this->check_filename($path, true, $ext);

			$dir = dirname($filePath);

			if(!file_exists($dir)) mkdir($dir, 0777, true);

			$handle = fopen($filePath, 'w+');
			if(!is_null($content)){
				fwrite($handle, $content);
			}
			fclose($handle);

			return $filePath;
		}

		public function createUniqueFolder($path = null){
			
			$path = !$path ? STORAGE_TEMP_PATH : $path;

			$filePath = $this->check_filename($path, true);

			mkdir($filePath, 0777);

			return rtrim($filePath, '/').'/';
		}

		public function check_filename($path, $fullPath = false, $ext = false){

			$ext = $ext ? ".{$ext}" : "";

			if(!is_dir($path) && is_file($path)) $path = dirname($path);

			if(!file_exists($path)) mkdir($path, 0777, true);

			$path = realpath($path).'/';

			//$microtime 			= microtime();
			//$microtime_parts 	= explode(' ', $microtime);
			//$nano_seconds 		= explode('.', $microtime_parts[0])[1];
			//$user_id 			= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1500;
			//$hash 				= $user_id.'_'.date('Ymd\THis', $microtime_parts[1]).$nano_seconds;
			$hash = md5(microtime(true));

			if(file_exists($path.$hash.$ext)){
				$number = 0;
				while(file_exists($path.$hash.$ext)){
					$number++;
					$hash = $number.'_'.$hash.$ext;
				}
			}

			return ($fullPath ? $path : "").$hash.$ext;
	    }

	    public function get_files_by_date($dir, $start_date = false, $end_date = false, $res_files = array()){

			$compare = 'between';
			
			if(!$start_date){
				$compare = 'before';
			}
			if(!$end_date){
				$compare = 'after';
			}
			if(!$start_date && !$end_date){
				pr('Start and end date are both required');
				return;
			}

			if($start_date){
				$start_date = date('Y-m-d 00:00:00', strtotime($start_date));
			}

			if($end_date){
				$end_date = date('Y-m-d 23:59:59', strtotime($end_date));
			}

			$files = glob($dir."/*");


			foreach($files as $file){
				
				if(!is_dir($file)){
					
					$file_create_time = date('Y-m-d H:i:s', filectime($file));

					if($compare == 'between'){
						if($file_create_time >= $start_date && $file_create_time <= $end_date){
							$res_files[] = array(
								'time' => $file_create_time,
								'path' => $file
							);
						}
					}
					elseif($compare == 'before'){
						if($file_create_time <= $end_date){
							$res_files[] = array(
								'time' => $file_create_time,
								'path' => $file
							);
						}
					}
					elseif($compare == 'after'){
						if($file_create_time >= $start_date){
							$res_files[] = array(
								'time' => $file_create_time,
								'path' => $file
							);
						}
					}				
				}
				else{
					$res_files = $this->get_files_by_date(realpath($file), $start_date, $end_date, $res_files);
				}
			}

			return $res_files;
		}
	}
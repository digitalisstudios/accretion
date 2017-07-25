<?php
	class File_Helper extends Helper {

		public function __construct(){

		}

		public function check_filename($path){

			if(!is_dir($path)){
				if(is_file($path)){
					$path = dirname($path);
				}
			}

			$path = realpath($path).'/';

			$microtime 			= microtime();
			$microtime_parts 	= explode(' ', $microtime);
			$nano_seconds 		= explode('.', $microtime_parts[0])[1];
			$user_id 			= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1500;
			$hash 				= $user_id.'_'.date('Ymd\THis', $microtime_parts[1]).$nano_seconds;

			if(file_exists($path.$hash)){
				$number = 0;
				while(file_exists($path.$hash)){
					$number++;
					$hash = $number.'_'.$hash;
				}
			}

			return $hash;
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
?>
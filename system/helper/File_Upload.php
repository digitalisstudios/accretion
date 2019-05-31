<?php
	class File_Upload_Helper extends Helper {

		public $directory 	= '';
		public $files 		= array();

		public function __construct(){
			$this->directory = STORAGE_PATH;
		}		

		public function directory($path){
			$this->directory = $path;
			return $this;
		}

		public function files($files = array()){

			if(is_array($files) && !empty($files)){
				if(is_array($files['error'])){
					foreach($files['error'] as $key => $error){
						if($error !== 4){
							$this->files[] = array(
								'name' 		=> $files['name'][$key],
								'type' 		=> $files['type'][$key],
								'tmp_name' 	=> $files['tmp_name'][$key],
								'error' 	=> $files['error'][$key],
								'size' 		=> $files['size'][$key]
							);
						}
					}
				}
				elseif($files['error'] !== 4){
					$this->files[] = $files;
				}
			}

			return $this;
		}

		public function upload(){

			$return = array();

			if(!empty($this->files) && $this->directory !== ''){

				$directory_exists = true;
				if(!file_exists($this->directory)){
					$directory_exists = false;
					if(mkdir($this->directory, 0777, true)){
						$directory_exists = true;
					}
				}

				if($directory_exists){

					//ADD TRAILING SLASH
					if(substr($this->directory, -1) !== '/'){
						$this->directory .= '/';
					}

					foreach($this->files as $file){
						$file_name = \Helper::get('File')->check_filename($this->directory);
						if(move_uploaded_file($file['tmp_name'], $this->directory.$file_name)){
							$return[] = array(
								'original_name' => $file['name'],
								'file_path' 	=> $this->directory.$file_name
							);
						}
					}
				}				
			}
			return $return;
		}
	}
?>
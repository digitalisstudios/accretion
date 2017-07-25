<?php

	//DEBUG FUNCTION
	if(!function_exists('pr')){
		function pr($data){
			if(!$data){
				var_dump($data);
			}
			else{
				?>
				<pre>
					<? print_r($data) ?>
				</pre>
				<?
			}
		}
	}

	class Code_Release_Helper{

		//INIT PATH VARS
		public $ignore_request_data = array('action');

		public function __construct(){
			$this->init();
		}

		//RUN BEFORE DOING ANYTHING
		public function init(){

			//INIT SESSION IF NEEDED
			if(!session_id()){
				session_start();
			}
			$csr = new Code_Release_Settings;
			$this->settings = $csr->settings;

			//CHECK FOR PAGE REQUESTS
			$this->route();
		}

//----------------------------------// ROUTING METHODS //--------------------------------//

		//HANDLE REQUESTS AND ROUTE TO ACTIONS FIRST THEN PAGES
		public function route(){

			if(isset($_REQUEST['cred'])){

				$credentials = json_decode(base64_decode($_REQUEST['cred']));

				$crs = new Code_Release_Settings;

				if($crs->settings->general->use_login->value == 'on'){


					if($credentials->user == $crs->settings->general->user_email->value && $credentials->pass == $crs->settings->general->user_password->value){
						$passed = true;
					}
					else{
						$passed = false;
					}
				}
				else{
					$passed = true;
				}

				if($passed){
					if(isset($_REQUEST['send_crb'])){
						
						//SET THE FILE NAME			
						$filename 	= base64_decode($_REQUEST['crb_name']);
						$content 	= base64_decode($_REQUEST['send_crb']);
						$path 		= $this->settings->path->unzip_dir->value.$filename;				

						//CREATE THE FILE
						$this->overwrite_file($path, $content);	

						$this->install_package($path);

						die(json_encode(array('error' => 'false', 'message' => 'CRB Successfully Installed.')));
					}
					elseif(isset($_REQUEST['preview_crb'])){
						die(base64_encode(json_encode(array('error' => 'false', 'message' => '', 'files' => $this->get_crb_preview_for_remote($_REQUEST['preview_crb'])))));
					}
					elseif(isset($_REQUEST['get_crb'])){
						die(base64_encode(json_encode(array('error' => 'false', 'message' => '', 'crb' => $this->get_crb_for_remote($_REQUEST['get_crb'])))));
					}
					else{
						
						die(json_encode(array('error' => 'false', 'message' => 'Logged in successfully.')));
					}
				}
				else{
					die(json_encode(array('error' => 'true', 'message' => 'You do not have permission.')));
				}

				die(json_encode(array('error' => 'true', 'message' => 'Unknown error.')));
			}

			//CHECK FOR REQUESTED ACTION FIRST
			if(isset($_REQUEST['action'])){
				$method_name = $_REQUEST['action'];
				$this->$method_name();
			}

			$this->load_template();
		}

		public function get_crb_for_remote($data){
			$data = json_decode(base64_decode($data), true);

			$file_path = $this->build_code_release($data);

			$content 	= file_get_contents($file_path);
			$file_name 	= basename($file_path);
			return array('file_name' => $file_name, 'content' => $content);
		}

		public function get_crb_preview_for_remote($date){

			if($date == ''){
				$date = date('Y-m-d H:i:s', $this->check_date(false));
			}
			else{
				$date = date('Y-m-d H:i:s', strtotime($date.' + 8 hours'));
			}

			return $this->find_modified_files($this->check_date($date));
		}

		//LOGIN HANDLER (IF AUTHENTICATION IS ENABLED)
		public function login(){

			$real_email = $this->settings->general->user_email->value;
			$real_password = $this->settings->general->user_password->value;
			//pr($real_email);
			//pr($real_password);
			//exit;
			if($_POST['email'] !== $real_email || $_POST['password'] !== $real_password){
				$this->error_handler('Email or password was incorrect');
			}
			else{
				session_start();
				$_SESSION['code_release_session']['login'] = true;
				//session_write_close();
				header('Location: '.$this->settings->path->web_path->value);
			}
		}

		//UPDATE THE JSON FILE
		public function update_settings(){

			$csr = new Code_Release_Settings;
			$csr->update_settings($_POST);
			$this->settings = $csr->settings;
			$this->error_handler("Your settings were updated");
		}

		//BUILD A NEW CODE RELEASE FUNCTION
		public function build_code_release($for_remote = false){

			if($for_remote){
				$data = $for_remote;
			}
			else{
				$data = $_REQUEST;
			}
			
			$date = false;
			if($data['date']){
				$date = date('Y-m-d H:i:s', strtotime($data['date'].' + 8 hours'));
			}
						
			
			//MAKE SURE THE CODE RELEASE PATH EXISTS
			$this->force_path($this->settings->path->code_release_path->value);
			
			//COPY THE FILES
			$this->backup_files($data['files']);			

			//ZIP THE BACKUP TEMP FOLDER
			$time = date('Y-m-d H-i-s', time());
			$target_file_name = $this->settings->path->code_release_path->value.$time.'.zip';
			$this->zipDir($this->settings->path->backup_dir->value, $target_file_name);

			//CONVERT THE ZIP TO A CRB
			$converted_file_path = $this->convert_zip_to_crb($target_file_name, $data['files'], false, $data['options']);
			
			//DELETE THE BACKUP
			$this->delete_directory($this->settings->path->backup_dir->value);

			if($for_remote){
				return $converted_file_path;
			}

			if(isset($data['send_to_remote_server']) && $data['send_to_remote_server'] == 'true'){
				$this->send_to_remote_server($converted_file_path);
			}
			else{
				$this->force_download($converted_file_path);
			}
		}

		public function send_to_remote_server($crb_path){

			$crs = new Code_Release_Settings;

			$server = $crs->settings->server->server_credentials->value->remote_server->value;

			$host = $server->host->value;
			$user = $server->user_name->value;
			$pass = $server->password->value;

			$content = base64_encode(file_get_contents($crb_path));

			$url = $host;
			$fields = array(
				'cred' => base64_encode(json_encode(array('user' => $user, 'pass' => $pass))),
				'send_crb' => $content,
				'crb_name' => base64_encode(basename($crb_path))
			);

			//url-ify the data for the POST
			foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
			rtrim($fields_string, '&');

			//open connection
			$ch = curl_init();

			//set the url, number of POST vars, POST data
			curl_setopt($ch,CURLOPT_URL, $url);
			curl_setopt($ch,CURLOPT_POST, count($fields));
			curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);

			//execute post
			ob_start();
			$result = curl_exec($ch);
			$result = ob_get_clean();

			//close connection
			curl_close($ch);

			$res = json_decode($result);

			unset($_GET['action']);

			if($res->error == 'false'){
				$page = 'install_remote_package';
				
			}
			else{
				$this->error_handler($res->message);
				$page = 'install_remote_package_fail';
			}


			$this->load_template($page);
			exit;
		}

		public function get_remote_code_release(){			

			$crs = new Code_Release_Settings;

			$server = $crs->settings->server->server_credentials->value->remote_server->value;

			$host = $server->host->value;
			$user = $server->user_name->value;
			$pass = $server->password->value;
			$encryption_key = $server->encryption_key->value;

			$url = $host;
			$fields = array(
				'cred' 		=> base64_encode(json_encode(array('user' => $user, 'pass' => $pass))),
				'get_crb' 	=> base64_encode(json_encode($_POST)),
			);

			//url-ify the data for the POST
			foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
			rtrim($fields_string, '&');

			//open connection
			$ch = curl_init();

			//set the url, number of POST vars, POST data
			curl_setopt($ch,CURLOPT_URL, $url);
			curl_setopt($ch,CURLOPT_POST, count($fields));
			curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			    'CRAuth: '.$encryption_key,
		    ));

			//execute post
			ob_start();
			$result = curl_exec($ch);
			$result = ob_get_clean();

			//close connection
			curl_close($ch);

			$res = json_decode(base64_decode($result), true);

			//SET THE FILE NAME			
			$filename = $res['crb']['file_name'];
			$content = $res['crb']['content'];	

			//FORCE THE PATH TO EXIST
			$this->force_path($this->settings->path->unzip_dir->value);

			//SAVE THE CRB
			$this->overwrite_file($this->settings->path->unzip_dir->value.$filename, $content);

			//INSTALL THE CRB
			$this->install_package($this->settings->path->unzip_dir->value.$filename);

		}

		public function preview_remote(){

			$crs = new Code_Release_Settings;

			$server = $crs->settings->server->server_credentials->value->remote_server->value;

			$host = $server->host->value;
			$user = $server->user_name->value;
			$pass = $server->password->value;
			$encryption_key = $server->encryption_key->value;

			$url = $host;
			$fields = array(
				'cred' 					=> base64_encode(json_encode(array('user' => $user, 'pass' => $pass))),
				'preview_crb' 	=> $_POST['date'] != '' ? date('Y-m-d', strtotime($_POST['date'])) : '',
			);

			//url-ify the data for the POST
			foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
			rtrim($fields_string, '&');

			//open connection
			$ch = curl_init();

			//set the url, number of POST vars, POST data
			curl_setopt($ch,CURLOPT_URL, $url);
			curl_setopt($ch,CURLOPT_POST, count($fields));
			curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			    'CRAuth: '.$encryption_key,
		    ));

			//execute post
			ob_start();
			$result = curl_exec($ch);
			$result = ob_get_clean();

			//close connection
			curl_close($ch);

			$res = json_decode(base64_decode($result), true);

			$this->files = $res['files'];
		}

		//HANDLE PACKAGE INSTALLS
		//IF $crb_path == false THIS METHOD WILL LOOK FOR UPLOADED FILES
		//IF $reinstall == true THIS METHOD WILL REQUIRE A $crb_path
		public function install_package($crb_path = false, $reinstall = false){
			
			//MAKE SURE DIRECTORYS EXIST
			$this->force_path($this->settings->path->backup_dir->value);
			$this->force_path($this->settings->path->code_release_backup->value);
			$this->force_path($this->settings->path->unzip_dir->value);
			
			if($crb_path === false){

				//SET THE FILE NAME			
				$filename = $_FILES['package']['name'];	

				//EXIT IF ERROR
				if(!move_uploaded_file($_FILES['package']['tmp_name'], $this->settings->path->unzip_dir->value.$filename)){
					$this->error_handler('Unable to upload file. Please try again.', true);
				}

				//CHECK CRB DATA
				$crb_data = $this->parse_crb($this->settings->path->unzip_dir->value.$filename);
				$crb_path = $this->settings->path->unzip_dir->value.$filename;
			}
			else{
				$filename = basename($crb_path);
				$crb_data = $this->parse_crb($crb_path);
			}
			

			//SHOULD WE CREATE A BACKUP WHEN LOADING THE PACKAGE?
			$create_backup = false;
			if(strpos($filename, 'backup_') === false){
				$create_backup = true;
			}

			if($reinstall === true){
				$create_backup = false;
			}

			//LOAD THE PACKAGE
			$this->load_package($crb_path, $create_backup);

			//DELETE BACKUP_TEMP DIR
			$this->delete_directory($this->settings->path->backup_dir->value);

			//DELETE UNZIP TEMP DIR
			$this->delete_directory($this->settings->path->unzip_dir->value);
			
			//IF WE ARE NOT LOADING A BACKUP
			if($create_backup === true){

				//SAVE THE CRB TO THE INSTALLED DIRECTORY
				$crb_data['date_installed'] = date('Y-m-d H:i:s');

				$this->force_path($this->settings->path->package_installs->value);

				$this->overwrite_file($this->settings->path->package_installs->value.$filename, base64_encode(serialize($crb_data)));
			}
		}		

		//LOAD A PACKAGE BY THE CRB PATH
		//BACKUP WILL DETERMINE IF A BACKUP FILE IS CREATED
		public function load_package($crb_path, $backup = true){
			
			//GET THE DATA FROM THE CRB
			$crb_data = $this->parse_crb($crb_path);

			//SHOULD WE CREATE A BACKUP WHEN LOADING
			if($backup == true){
				
				//BACKUP THE FILES THAT THE CRB WILL REPLACE
				$this->backup_files($crb_data['files']);

				//MAKE SURE THE PATH EXISTS
				$this->force_path($this->settings->path->code_release_backup->value);

				//SET THE TARGET NAME
				$target_file_name = str_replace('.crb', '.zip', $this->settings->path->code_release_backup->value.basename($crb_path));

				//ZIP THE DIRECTORY
				$this->zipDir($this->settings->path->backup_dir->value, $target_file_name);

				//CONVERT THE ZIP TO A CRB
				$this->convert_zip_to_crb($target_file_name, $crb_data['files'], true);
			}	

			//CONVERT CRB DATA TO A ZIP FILE
			$zip_path = $this->convert_crb_to_zip($crb_path, $crb_data['content'], $backup);

			//EXTRACT ZIP FILES TO MAIN FOLDER
			$this->extract_zip_to_path($zip_path, $this->settings->path->path_to_backup->value);	
		}

//----------------------------------// UTILITY METHODS //--------------------------------//	

		//REPLACE A FILE BY PATH WITH PROVIDED CONTENT
		public function overwrite_file($file_path, $content){

			//MODIFIED TIME DEFAULTS TO PRESENT
			$mod_time = time();

			//DELETE THE OLD FILE IF IT EXISTS
			if(file_exists($file_path)){
				$mod_time = filemtime($file_path);
				unlink($file_path);
			}

			//CREATE AND OPEN THE FILE
			$handle = fopen($file_path, 'w+');

			//ADD THE CONTENT
			fwrite($handle, $content);
			
			//CLOSE THE FILE
			fclose($handle);

			//SET FILE PERMISSIONS
			chmod($file_path, 0755);

			//FIND HOW LONG AGO THE FILE WAS MODIFIED
			$diff = time()-$mod_time;

			//SET THE MOD TIME
			touch($file_path, time()-$diff); 
		}

		//RECURSIVELY DELETE A DIRECTORY
		public function delete_directory($dir) { 
		   	
			//FIND FILES IN CURRENT DIRECTORY
		   	$files = array_diff(scandir($dir), array('.','..')); 
		    
		    //CYCLE THE FILES
		    foreach ($files as $file) { 

		    	//DELETE THE  FILE OR CALL THE DELETE DIRECTORY METHOD AGAIN IF IT IS A DIRECTORY
		      	(is_dir("$dir/$file")) ? $this->delete_directory("$dir/$file") : unlink("$dir/$file"); 
		    } 

		    //DELETE THE DIRECTORY
		    return rmdir($dir); 
		}

		//SET A SESSION VARIABLE FOR THE CODE RELEASE SESSION
		public function session_logger($key_name, $message){
			//session_start();
			if(!isset($_SESSION[$key_name])){
				$_SESSION['code_release_session'][$key_name] = array();
			}
			$_SESSION['code_release_session'][$key_name][] = $message;
			//session_write_close();
		}

		//THIS METHOD CLEANS THE OUTPUT AND PRINTS TEMPLATE
		public function load_clean_template($name = false){
			
			//START OUTPUT BUFFERING
			ob_start();

			//CHECK FOR METHOD
			$this->load_template($name);

			//GRAB THE OUTPUT
			$output = ob_get_clean();
			
			//PRINT OUTPUT
			echo $output;
			exit;
		}

		//HANDLE ERRORS
		public function error_handler($message = 'Unknown Error Occurred', $exit = true){
			
			//SET THE ERROR IN THE SESSION
			$this->session_logger('error', $message);

			if($exit == true){
				//LOAD TEMPLATE
			$this->load_clean_template();
				exit;
			}
			
		}

		//DOWNLOAD A FILE
		public function force_download($path){			
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header("Content-Transfer-Encoding: Binary"); 
			header("Content-disposition: attachment; filename=\"" . basename($path) . "\""); 
			header('Expires: 0');
    		header('Cache-Control: must-revalidate');
    		header('Pragma: public');
    		header('Content-Length: ' . filesize($path));
			readfile($path); // do the double-download-dance (dirty but worky)
		}

		//START BACKING UP FILES TO THE BACKUP DIR
		public function backup_files($files){

			if(!is_array($files[0])){
				$files = $this->prep_files_from_path($files);				
			}

			//CYCLE THE FILES			
			foreach($files as $file){

				//MAKE A DIR IF NEEDED
				if(!file_exists($this->settings->path->backup_dir->value.$file['path'])){
					mkdir($this->settings->path->backup_dir->value.$file['path'], 0777, true);
				}
					
				//COPY THE FILE				
				copy($file['fullname'], $this->settings->path->backup_dir->value.$file['path'].$file['name']);
				
			}
		}

		//FORCE A FOLDER TO EXIST	
		public function force_path($dir){			
			if(!file_exists($dir)){
				mkdir($dir, 0777, true);
			}
		}

		public function check_date($date = false){
			
			//CHECK FOR THE DATE
			if($date == false){

				//SET DEFAULT DATE
				$date = filectime(__FILE__);
				
				//FIND THE LAST CODE RELEASE
				$code_releases = array_diff(scandir($this->settings->path->code_release_path->value), array('.','..')); 

				if(count($code_releases)){
				
					$times = array();
					foreach($code_releases as $release){
						$parts 		= explode(' ', str_replace('.crb', '', $release));
						$date_parts = explode('-', $parts[0]);
						$time_parts = explode('-', $parts[1]);
						$t 			= $date_parts[0].'-'.$date_parts[1].'-'.$date_parts[2].' '.$time_parts[0].':'.$time_parts[1].':'.$time_parts[2];
						$times[$t] 	= $t;
					}
					ksort($times);
					$date = end($times);
				}				
			}
			return strtotime($date);
		}

		public function find_modified_files($time){

			//INIT VARS
			$files = array();

			//FIND FILES THAT HAVE BEEN MODIFIED SINCE THE DATE
			$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->settings->path->path_to_backup->value), RecursiveIteratorIterator::SELF_FIRST);

			foreach($objects as $fullname => $object){

				//INIT VARS
				$filename 	= basename($fullname);				
				$pathname 	= str_replace(basename($fullname), '', str_replace($this->settings->path->path_to_backup->value, '', $fullname));
				$path_parts = array_filter(explode('/', $pathname));

				//CHECK IS FILE - FILE TYPE - TIME
				$path_parts = pathinfo($filename);
				$extension = $path_parts['extension'];

				$ignore_file_type = $this->settings->exclude->ignore_file_type->value;
				

				if(!is_file($fullname) || in_array($extension, $ignore_file_type) || $extension == '' || filemtime($fullname) < $time){
					continue;
				}

				
				$ignore_directories = $this->settings->exclude->ignore_directory->value;
				
				$skip = false;
				foreach($ignore_directories as $dir){


					
					if(strpos($fullname, $dir) !== false){
						$skip = true;
						break;
						continue;
					}
				}

				if($skip) continue;


				


				
				//ADD TO FILES ARRAY
				$files[] = array(
					'fullname' 		=> $fullname,
					'path' 			=> str_replace(basename($fullname), '', str_replace($this->settings->path->path_to_backup->value, '', $fullname)),
					'name' 			=> basename($fullname),
					'date_modified' => date('Y-m-d H:i:s', filemtime($fullname))
				);
				
			}
			return $files;
		}

		public function prep_files_from_path($file_paths){
			$files = array();
			foreach($file_paths as $fullname){

				//ADD TO FILES ARRAY
				$files[] = array(
					'fullname' 		=> $fullname,
					'path' 			=> str_replace(basename($fullname), '', str_replace($this->settings->path->path_to_backup->value, '', $fullname)),
					'name' 			=> basename($fullname),
					'date_modified' => date('Y-m-d H:i:s', filemtime($fullname))
				);
			}
			return $files;
		}


//----------------------------------// ZIP ARCHIVE METHODS //--------------------------------//

		public function extract_zip_to_path($file_path, $extract_path){

			//CONVERT CRB TO ZIP AND GET FILE PATH
			$zip = zip_open($file_path);

			//EXTRACT THE ZIP FILES
			while($zip_entry = zip_read($zip)){
				$this->extract_zip_file($extract_path, $zip, $zip_entry);
			}
		}

		public function extract_zip_file($path, $zip, $zip_entry){

			//FIND THE FILE PATH
			$file_path = substr($path, 0, strlen($path)-1).zip_entry_name($zip_entry);

			//DO NOT OVERWRITE THIS FILE
			if($file_path == __FILE__){
				return;
			}
			
			//CREATE FILES PARENT DIRECTORY IF IT DOESNT EXIST
			$this->force_path(dirname($file_path));

			//OPEN THE ZIP ENTRY FOR READING
			if(zip_entry_open($zip, $zip_entry, 'r')){

				//GET THE CONTENT OF THE FILE
				$file_contents = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));

				if($file_contents){
					
					//REPLACE THE FILE
					$this->overwrite_file($file_path, $file_contents);
				}				
			}
		}

		//ZIP UP A DIRECTORY
		public static function zipDir($sourcePath, $outZipPath){ 
			
			//INIT VARS
    		$pathInfo 	= pathInfo($sourcePath); 
    		$parentPath = $pathInfo['dirname']; 
    		$dirName 	= $pathInfo['basename']; 
    		$z 			= new ZipArchive(); 

    		$z->open($outZipPath);

    		//START ZIPPING
    		$z->open($outZipPath, ZIPARCHIVE::CREATE); 
    		self::folderToZip($sourcePath, $z, strlen("$parentPath/$dirName/")); 
    		$z->close();

    		return $outZipPath;
  		}

  		//ZIP UP A FOLDER
  		private static function folderToZip($folder, &$zipFile, $exclusiveLength) { 
		    
		    //LOAD THE FOLDER
		    $handle = opendir($folder); 
		   
		   	//CYCLE THE FILES
		    while(false !== $f = readdir($handle)) { 
		      	if($f != '.' && $f != '..') { 
		        	
		        	//INIT VARS
		        	$filePath 	= "$folder/$f"; 
		        	$localPath 	= substr($filePath, $exclusiveLength); 
		        	
		        	//ADD A FILE
		        	if(is_file($filePath)) { 
		          		$zipFile->addFile($filePath, $localPath); 
		        	} 

		        	//ADD A FOLDER
		        	elseif(is_dir($filePath)){ 
		          		$zipFile->addEmptyDir($localPath); 
		          		self::folderToZip($filePath, $zipFile, $exclusiveLength); 
		        	} 
				} 
			} 
			closedir($handle); 
		}


//----------------------------------// CRB FILE METHODS //--------------------------------//

		//LOAD CRB FILES IN A DIRECTORY
		public function get_crbs($path = false){
			
			//DEFAULT TO PACKAGE INSTALLS DIRECTORY
			if($path == false){
				$path = $this->settings->path->package_installs->value;
			}

			//GET THE FILES
			$files = array_reverse(array_diff(scandir($path), array('.', '..')));
			
			//INIT RETURN ARRAY
			$return = array();

			//CYCLE THE FILES
			foreach($files as $file_name){

				//IGNORE FILES THAT ARE NOT .CRB
				if(pathinfo($file_name, PATHINFO_EXTENSION) !== 'crb') continue;
				
				$return[] = $this->parse_crb($path.$file_name, false);
			}

			//SEND BACK THE FILES
			return $return;
		}

		//REINSTALL A PREVIOUSLY INSTALLED CRB
		public function reinstall_crb(){

			//GET THE PATH OF THE INSTALLED PACKAGE
			$crb_path = $this->settings->path->package_installs->value.htmlentities($_GET['code_release']).'.crb';

			//GET THE CRB DATA
			$installed_data = $this->parse_crb($crb_path);

			$content = $installed_data['content'];
			unset($installed_data['content']);
			
			//MARK THIS AS NOT INSTALLED
			$installed_data['installed'] = 'yes';
			$installed_data['content'] = $content;
			
			//UPDATE THE FILE
			$this->overwrite_file($crb_path, base64_encode(serialize($installed_data)));

			$this->install_package($crb_path, true);

			return true;
		}

		//UNINSTALL A CRB AND REPLACE IT WITH THE BACKUP FILE
		public function rollback_crb(){

			//GET THE PATH OF THE INSTALLED PACKAGE
			$crb_path = $this->settings->path->package_installs->value.htmlentities($_GET['code_release']).'.crb';

			//GET THE CRB DATA
			$installed_data = $this->parse_crb($crb_path);

			$content = $installed_data['content'];
			unset($installed_data['content']);
			
			//MARK THIS AS NOT INSTALLED
			$installed_data['installed'] = 'no';
			$installed_data['content'] = $content;
			
			//UPDATE THE FILE
			$this->overwrite_file($crb_path, base64_encode(serialize($installed_data)));
			$backup_path = $this->settings->path->code_release_backup->value.'backup_'.htmlentities($_GET['code_release']).'.crb';

			$this->install_package($backup_path);

			return true;
		}

		//CONVERT A CRB FILE INTO A DATA ARRAY
		public function parse_crb($crb_path, $return_content = true){	

			if(!file_exists($crb_path)){
				return $this->error_handler("The path {$crb_path} doesnt exist.");
			}

			$content = file_get_contents($crb_path);
			if(strpos($content, '<!doctype html>')){
				$parts = explode('<!doctype html>', $content);
				$content = $parts[0];
			}

			
			$crb_data = unserialize(base64_decode($content));
			if($return_content === false){
				unset($crb_data['content']);
			}
			return $crb_data;
		}

		//CONVERT ZIP TO PROPRIETARY FORMAT THAT CONTAINS EXTRA DATA ABOUT THE FILE
		public function convert_zip_to_crb($file_path, $files, $backup = false, $options = array()){
			
			//SET DEFAULTS
			$pre 			= '';
			$type 			= 'new';
			
			$file_contents 	= file_get_contents($file_path);

			//CHECK IF WE ARE CREATING A BACKUP
			if($backup == true){
				$pre 		= 'backup_';
				$type 		= 'backup';
			}

			$name 				= str_replace('.zip', '', basename($file_path));
			$parts 				= explode(' ', $name);
			$date_parts 		= explode('-', $parts[0]);
			$time_parts 		= explode('-', $parts[1]);
			$date_created 		= $date_parts[0].'-'.$date_parts[1].'-'.$date_parts[2].' '.$time_parts[0].':'.$time_parts[1].':'.$time_parts[2];
			$date_installed 	= false;
			$options['source'] 	= $_SERVER['SERVER_NAME'];

			//ENCODE CRB DATA			
			$data 			= base64_encode(serialize(array(
				'version' 			=> '1.0', 
				'name' 				=> $name,
				'date_created'		=> $date_created,
				'date_installed' 	=> $date_installed, 
				'type' 				=> $type, 
				'files' 			=> $files, 
				'options' 			=> $options, 
				'content' 			=> $file_contents))
			);
			

			$new_name 		= str_replace('.zip', '.crb', basename($file_path));
			$new_path 		= dirname($file_path).'/'.$pre.$new_name;

			//SAVE THE NEW CRB FILE
			$this->overwrite_file($new_path, $data);

			//DELETE ZIP FILE
			unlink($file_path);

			//SEND BACK THE PATH TO THE NEW CRB
			return $new_path;
		}

		//CONVERT A CRB FILE INTO A ZIP FILE
		public function convert_crb_to_zip($crb_path, $content = false, $unlink = true){
			
			//BUILD NEW FILE PATH
			$file_path 	= str_replace(basename($crb_path), str_replace('.crb', '.zip', basename($crb_path)), $crb_path);
			
			//IF NO CRB DATA WAS PASSED GET THE DATA FROM THE URL
			if($content == false){
				
				//PARSE THE crb INTO A HEADER AND A ZIP
				$crb_parts	= $this->parse_crb($crb_path);
				$content 	= $crb_path['content'];
			}

			//SAVE THE ZIP FILE
			$this->overwrite_file($file_path, $content);
			
			//DELETE THE crb FILE
			if($unlink === true){
				unlink($crb_path);
			}

			//SEND BACK THE PATH TO THE ZIP
			return $file_path;

		}

		public function create_crb_from_files($crb_path, $files, $backup = false){

			//SET BACKUP FILENAME
			$pre = '';
			if($backup == true){
				$pre = 'backup_';				
			}

			//GET FILENAME FROM PATH
			$filename = basename($crb_path);

			//BACKUP THE FILES THAT THE CRB WILL REPLACE
			$this->backup_files($files);	

			//CREATE THE BACKUP ZIP
			$zip_path = $this->zipDir($this->settings->path->backup_dir->value, $this->settings->path->code_release_backup.$pre->value.$filename);

			$converted_file_path = $this->convert_zip_to_crb($zip_path, $files, $backup);
		}




//----------------------------------// TEMPLATE METHODS //--------------------------------//

		//GENERATE THE FIRST PART OF A LINK
		public function link_pre(){
			$href_pre = $_SERVER['REQUEST_URI'];
			if(strpos($href_pre, '?') !== false){
				$parts = explode('?', $href_pre);
				$href_pre = $parts[0];
			}
			if(substr($href_pre, -1) !== '/'){
				$href_pre .= '/';
			}
			return $href_pre;
		}

		//HANDLE TEMPLATE LOADING
		public function load_template($name = false){

			if($name == false){	
				if($_GET['page']){
					$name = $_GET['page'];
				}
				else{
					$name = 'splash';
				}
			}

			if($this->settings->general->use_login->value == 'on'){
				if(!$_SESSION['code_release_session']['login']){
					$name = 'login';					
				}			
			}

			$method_name = $name.'_template';
			if(method_exists($this, $method_name)){
				$this->$method_name();
			}
			$this->template_footer();
		}

		public function template_header($title = false){

			$title = $title == false ? 'Code Release Manager' : $title;
			?>
			<!doctype html>
				<html lang="en">
				<head>
				  <meta charset="utf-8">
				  <title><?=$title?></title>
				<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
				<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css" integrity="sha512-dTfge/zgoMYpP7QbHy4gWMEGsbsdZeCXz7irItjcC3sPUFtf0kuFbDz/ixG7ArTxmDjLXDmezHubeNikyKGVyQ==" crossorigin="anonymous">
				
				<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
				<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.4.0/animate.min.css">
				<script src="//code.jquery.com/jquery-1.10.2.js"></script>
				<script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
				<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js" integrity="sha512-K1qjQ+NcF2TYO/eI3M6v8EiNYZfA95pQumfvcVrTHtwQVDG+aHRqLi/ETn2uB+1JqwYqVG3LIvdm9lj6imS/pQ==" crossorigin="anonymous"></script>
  				
  				
				<style type="text/css">
					body {
						background:#ddd;
					}
					body * {
						border-radius: 0 !important;
					}
					
					.main-content{
						background:#fff;
						box-shadow: rgba(153, 153, 153, 0.36) 0 5px 8px;
						margin:auto;
						overflow:hidden;
					}
					.main-content-inner {
						padding:20px;
					}

					#main-footer {
						padding: 20px;
					    background: #C7C7C7;
					    box-shadow: rgba(0,0,0,0.3) 0 2px 7px inset;
					    border-bottom: 1px solid #fff;
					    color: #666;
					    text-shadow: rgba(255,255,255,0.7) 0 1px 0;
					    text-transform: uppercase;
					    letter-spacing: .4em;
					    font-size: 10px;
					}
					.page-title{
						color: #fff;
					    background: rgb(126, 222, 2);
					    margin: 0;
					    padding: 20px;
					}
					.btn.btn-primary{
						
					        background: rgb(126, 222, 2);
					    border:none;
					}
					.center {
						text-align: center;
					}
					h1.brand{
						margin-top: 60px;
					    margin-bottom: 0px;
					    text-transform: uppercase;
					    color: #C7C7C7;
					    text-shadow: #fff 0 1px 0, #868686 0 -1px 0;
					    font-weight: bold;
					}
					.short-template {
						max-height: 300px;
						overflow: auto;
					}
				</style>
				</head>
				<body>
				<div class="container">
					<h1 class="brand">Code Release Manager</h1>
					<div class="main-content">
					<h3 class="page-title"><div class="fadeInDown animated"><?=$title?></div></h3>
					<? $this->template_navigation() ?>
					<div class="main-content-inner fadeInUp bounceIn animated">
					<? $this->template_errors() ?>
			<?
		}

		public function template_errors(){
			if(isset($_SESSION['code_release_session']['error'])){
				foreach($_SESSION['code_release_session']['error'] as $error){
					?>
						<div class="alert alert-info fadeInDown animated center" role="alert"><?=$error?></div>
					<?

				}
				unset($_SESSION['code_release_session']['error']);
			}
		}

		public function template_navigation(){
			
			$nav_items = array(
				'' 					=> array('icon' => 'fa-cubes', 'title' => 'Installed Packages', 'pages' => array('info', 'rollback')),
				'created_packages'	=> array('icon' => 'fa-cubes', 'title' => 'Exported Packages', 'pages' => array()),
				'create_release'	=> array('icon' => 'fa-plus', 'title' => 'Create Package', 'pages' => array()),
				'upload' 			=> array('icon' => 'fa-cloud-upload', 'title' => 'Install Package', 'pages' => array()),
				'settings'			=> array('icon' => 'fa-cog', 'title' => 'Settings', 'pages' => array()),
			);

			if($_SESSION['code_release_session']['login']){
				$nav_items['logout'] = array('icon' => 'fa-cog', 'title' => 'Logout', 'pages' => array());
			}

			$current_page = '';
			if(isset($_GET['page'])){
				$current_page = $_GET['page'];
			}

			?>

			<nav class="navbar navbar-default">
			  <div class="container-fluid">
			    <div class="navbar-header">
			      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
			        <span class="sr-only">Toggle navigation</span>
			        <span class="icon-bar"></span>
			        <span class="icon-bar"></span>
			        <span class="icon-bar"></span>
			      </button>
			    </div>

			    <!-- Collect the nav links, forms, and other content for toggling -->
			    <div class="collapse navbar-collapse">
			      <ul class="nav navbar-nav">
					<? foreach($nav_items as $target=>$nav_item): ?>
						<li <?= $current_page == $target || in_array($current_page, $nav_item['pages']) ? 'class="active"' : ''?>><a href="<?=$this->link_pre()?><?=$target !== '' ? '?page=' : '';?><?=$target?>"><i class="fa <?=$nav_item['icon']?>"></i> <?=$nav_item['title']?></a></li>
					<? endforeach; ?>
			      </ul>
			    </div><!-- /.navbar-collapse -->
			  </div><!-- /.container-fluid -->
			</nav>							
			<?
		}

		public function template_footer(){
			?>
					</div>
					</div>
					<div class="center" id="main-footer"></div>
				</div>
				</body>
				</html>
			<?
		}

		public function logout_template(){
			unset($_SESSION['code_release_session']['login']);
			header("Location: ".$this->settings->path->web_path->value);
		}

		public function login_template(){
			$this->template_header('Login');
			?>
				<div style="max-width:700px; margin:auto">
					<form method="post" class="form-horizontal" action="<?=$this->link_pre()?>?action=login">
						<div class="form-group">
							<label class="col-sm-2 control-label">Email</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" name="email" placeholder="example@domain.com">
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-2 control-label">Password</label>
							<div class="col-sm-10">
								<input type="password" class="form-control" name="password" placeholder="password">
							</div>
						</div>
						<div class="form-group">
							<div class="col-sm-10 col-sm-offset-2">
								<input type="submit" class="btn btn-primary" value="Login">
							</div>
						</div>
					</form>
				</div>
			<?
		}

		public function preview_remote_template(){
			$this->template_header('Code Release Package Preview');
			$files = $this->files;
			date_default_timezone_set('America/Los_Angeles');
			?>
				<div style="max-width:700px; margin:auto">
					<p>This code release will include <strong><?=count($files)?> files</strong>. Please uncheck any files you wish to remove from the code release.</p>
					<form method="post" action="<?=$this->link_pre()?>?page=get_remote_code_release&action=get_remote_code_release">
						<input type="hidden" name="date" value="<?=$date?>">
						<table class="table table-hover table-striped">
							<thead>
								<th></th>
								<th>File Name</th>
								<th>File Location</th>
								<th>Date Modified</th>
							</thead>
							<tbody>
							<? foreach($files as $file): ?>
								<tr>
									<td><input type="checkbox" name="files[]" value="<?=$file['fullname']?>" checked></td>
									<td><?=$file['name']?></td>
									<td><?=$file['path']?></td>
									<td><?=date('m/d/y g:i a', strtotime($file['date_modified'].' UTC'))?></td>
								</tr>
							<? endforeach; ?>
							</tbody>
						</table>
						<div class="form-group">
							<label class="col-sm-2 control-label">Code Release Description</label>
							<div class="col-sm-10">
								<textarea class="form-control" name="options[notes]" placeholder="Notes about this code release"></textarea>
							</div>
						</div>
						<p class="center">
							<button class="btn btn-primary" name="save_new_package" value="true">Install</button>
						</p>
					</form>
				</div>
			<?

		}

		public function create_preview_template(){
			$this->template_header('Code Release Package Preview');
			$date = date('Y-m-d H:i:s', $this->check_date(false));
			if($_POST['date']){
				$date = date('Y-m-d H:i:s', strtotime($_POST['date'].' + 8 hours'));
			}
			$files = $this->find_modified_files($this->check_date($date));
			
			date_default_timezone_set('America/Los_Angeles');
			?>
				<div style="max-width:700px; margin:auto">
					<p>This code release will include <strong><?=count($files)?> files</strong>. Please uncheck any files you wish to remove from the code release.</p>
					<form method="post" action="<?=$this->link_pre()?>?action=build_code_release">
						<input type="hidden" name="date" value="<?=$date?>">
						<table class="table table-hover table-striped">
							<thead>
								<th></th>
								<th>File Name</th>
								<th>File Location</th>
								<th>Date Modified</th>
							</thead>
							<tbody>
							<? foreach($files as $file): ?>
								<tr>
									<td><input type="checkbox" name="files[]" value="<?=$file['fullname']?>" checked></td>
									<td><?=$file['name']?></td>
									<td><?=$file['path']?></td>
									<td><?=date('m/d/y g:i a', strtotime($file['date_modified'].' UTC'))?></td>
								</tr>
							<? endforeach; ?>
							</tbody>
						</table>
						<div class="form-group">
							<label class="col-sm-2 control-label">Code Release Description</label>
							<div class="col-sm-10">
								<textarea class="form-control" name="options[notes]" placeholder="Notes about this code release"></textarea>
							</div>
						</div>
						<p class="center">
							<button class="btn btn-primary" name="save_new_package" value="true">Save And Download</button>
							<button class="btn btn-primary" name="send_to_remote_server" value="true">Send To Remote Server</button>
						</p>
					</form>
				</div>
			<?

		}

		public function create_release_template(){
			$this->template_header('Create A code Release');
			?>
				<div style="max-width:700px; margin:auto">
				<form method="post" action="<?=$this->link_pre()?>?page=create_preview">
					<p>Code releases are created by searching the server for files that have been changed after a specific date. By default the date will be the the last date that you created a code release. If you have not created a code release before, the initial date will be the original install time of this softward.</p>
					<p class="center">
						<strong>Select alternate starting date (optional)</strong><br>
					</p>
						<div class="row">
							<div class="col-md-6">
								<input type="text" style="display:block; width:100%" class="create-package-date-picker" name="date" placeholder="date-select"> 
							</div>
							<div class="col-md-6">
								<input class="btn btn-primary" style="display:block; width:100%" type="submit" name="preview_code_release" value="Preview Code Release Package"></p>
							</div>
						</div>
				</form>
				</div>
				<script>
					$(function(){
						$('.create-package-date-picker').datepicker();
					})
				</script>
			<?
		}

		public function info_template(){

			

			$crb_data = $this->parse_crb($this->settings->path->$_GET['package_type']->value.htmlentities($_GET['code_release']).'.crb');
			$this->template_header('Installed Packages');
			date_default_timezone_set('America/Los_Angeles');
			?>
				
				<div style="max-width:700px; margin:auto">
					<p>CRB Format: <strong><?=$crb_data['version']?></strong></p>
					<p>Date Created: <strong><?=date('m/d/y g:i a', strtotime($crb_data['date_created'].' UTC'))?></strong></p>
					<p>Date Installed: <strong><?=$crb_data['date_installed']?></strong></p>
					<p>Total Files Affected: <strong><?=count($crb_data['files'])?></strong></p>
					<p>CRB Source: <strong><?=$crb_data['options']['source']?></strong></p>
					<div class="panel panel-default">
						<div class="panel-heading">Notes about this code release</div>
						<div class="panel-body">
							<?=nl2br($crb_data['options']['notes'])?>
						</div>
					</div>

					<hr>
					<h3>Files Affected</h3>
					<ul class="list-group short-template">
					<? foreach($crb_data['files'] as $file): ?>
						<li class="list-group-item"><?=$file?></li>
					<? endforeach; ?>
					</ul>
				</div>
			<?	
		}

		public function confirm_rollback_template(){
			$this->error_handler('The code release has been rolled back', false);
			
			$this->template_header('Rollback Code Release');

		}

		public function confirm_reinstall_template(){
			$this->error_handler('The code release has been reinstalled', false);
			$this->template_header('Reinstall Code Release');

		}

		public function rollback_template(){
			
			$this->error_handler('Are you sure you would like to uninstall code release <strong>'. $_GET['code_release'] .'</strong>? <a class="btn btn-success" href="'.$this->link_pre().'?page=confirm_rollback&action=rollback_crb&package_type='.$_GET['package_type'].'&code_release='.$_GET['code_release'].'">Continue with Uninstall.</a>', false);
			$this->info_template();
		}

		public function reinstall_template(){
			
			$this->error_handler('Are you sure you would like to reinstall code release <strong>'. $_GET['code_release'] .'</strong>? <a class="btn btn-success" href="'.$this->link_pre().'?page=confirm_reinstall&action=reinstall_crb&package_type='.$_GET['package_type'].'&code_release='.$_GET['code_release'].'">Continue with Install.</a>', false);
			$this->info_template();
		}

		public function download_template(){

			$path = $this->settings->path->$_GET['package_type']->value.htmlentities($_GET['code_release']).'.crb';
			$this->force_download($path);
		}

		//DEFAULT TEMPLATE
		public function splash_template(){
			$this->template_header('Installed Packages');
			date_default_timezone_set('America/Los_Angeles');
			?>

				<table class="table table-striped table-hover">
					<thead>
						<th><i class="fa fa-calendar"></i> Date Created</th>
						<th><i class="fa fa-check-circle"></i> Date Installed</th>
						<th width="40%" class="center"><i class="fa fa-cog"></i> Options</th>
												
					</thead>
					<tbody>
						<? foreach($this->get_crbs($this->settings->path->package_installs->value) as $name=>$release): ?>
							<tr>
								<td><?=date('m/d/y g:i a', strtotime($release['date_created'].' UTC'))?></td>
								<td><?=date('m/d/y g:i a', strtotime($release['date_installed'].' UTC'))?></td>
								<td width="40%" align="right">
								
									<a class="btn btn-primary" href="<?=$this->link_pre()?>?page=info&code_release=<?=$release['name']?>&package_type=package_installs"><i class="fa fa-info-circle"></i> Info</a>
									<? //if(!$disable_earlier_release): ?>
										<? if($release['installed'] == 'no'): ?>
											<? $disable_earlier_release = true; ?>
											<a class="btn btn-default" href="<?=$this->link_pre()?>?page=reinstall&code_release=<?=$release['name']?>&package_type=package_installs"><i class="fa fa-refresh"></i> Install</a>
										<? else: ?>
											<a class="btn btn-primary" href="<?=$this->link_pre()?>?page=rollback&code_release=<?=$release['name']?>&package_type=package_installs"><i class="fa fa-refresh"></i> Rollback</a>
										<? endif; ?>
									<? //endif; ?>
									<a class="btn btn-primary" href="<?=$this->link_pre()?>?page=download&code_release=<?=$release['name']?>&package_type=package_installs"><i class="fa fa-cloud-download"></i> Download</a>
								</td>
								
							</tr>
						<? endforeach; ?>
					</tbody>
				</table>
			<?
		}

		//DEFAULT TEMPLATE
		public function created_packages_template(){
			$this->template_header('Exported Packages');
			date_default_timezone_set('America/Los_Angeles');
			?>
				<table class="table table-striped table-hover">
					<thead>
						<th><i class="fa fa-calendar"></i> Date Created</th>
						
						<th width="40%" class="center"><i class="fa fa-cog"></i> Options</th>
												
					</thead>
					<tbody>
						<? foreach($this->get_crbs($this->settings->path->code_release_path->value) as $name=>$release): ?>
						<? if(empty($release['files'])) continue; ?>
						
							<tr>
								<td><?=date('m/d/y g:i a', strtotime($release['date_created'].' UTC'))?></td>
								<td width="40%" align="right">
									<a class="btn btn-primary" href="<?=$this->link_pre()?>?page=info&code_release=<?=$release['name']?>&package_type=code_release_path"><i class="fa fa-info-circle"></i> Info</a>
									<a class="btn btn-primary" href="?page=download&code_release=<?=$release['name']?>"><i class="fa fa-cloud-download"></i> Download</a>
								</td>
								
							</tr>
						<? endforeach; ?>
					</tbody>
				</table>
			<?
		}

		public function backups_template(){
			$this->template_header('Backups');
			?>
				<table class="table table-striped table-hover">
					<thead>
						<th><i class="fa fa-calendar"></i> Date Created</th>
						
						<th width="40%" class="center"><i class="fa fa-cog"></i> Options</th>
												
					</thead>
					<tbody>
						<? foreach($this->get_crbs($this->settings->path->code_release_backup->value) as $release): ?>
						<? if(empty($release['files'])) continue; ?>
						
							<tr>
								<td><?=$release['date_created']?></td>
								<td width="40%" align="right">
									<a class="btn btn-primary" href="?page=rollback&code_release=<?=$release['name']?>"><i class="fa fa-refresh"></i> Rollback</a>
									<a class="btn btn-primary" href="?page=download&code_release=<?=$release['name']?>"><i class="fa fa-cloud-download"></i> Download</a>
								</td>
								
							</tr>
						<? endforeach; ?>
					</tbody>
				</table>
			<?
		}

		public function upload_template(){
			
			$this->template_header('Install A Code Release');
			?>
				<div class="row">	
					<div class="col-md-12">			
						<form method="post" action="<?=$this->link_pre()?>?page=install_package&action=install_package" enctype="multipart/form-data">						
							<p class="center">
								<input style="display:none" type="file" id="file-package-upload" name="package"><label class="btn btn-primary" for="file-package-upload">Choose A Code Release</label>
							</p>
							<p class="center">
								<input class="btn" type="submit" name="upload_package" value="Upload">
							</p>
						</form>
						<form method="post" action="<?=$this->link_pre()?>?page=preview_remote&action=preview_remote">
							<p class="center">
								<br><br>
								Or get a code release from the remote server.
							</p>
							<p class="center">
								<div class="row">
									<div class="col-sm-6 col-sm-offset-3">
										<div class="input-group">
											<input type="text" class="form-control datepicker" name="date" placeholder="Start date (leave blank to only get the latest files)">
											<span class="input-group-btn">
												<button class="btn" name="preview_release_from_remote">Preview</button>
											</span>
										</div>
									</div>
								</div>
								
							</p>
						</form>
					</div>
				</div>
				<script>
					$('.datepicker').datepicker();
				</script>
			<?
		}



		public function install_package_template(){
			$this->template_header('Install A Code Release');
			?>
				<div class="row">	
					<div class="col-md-12">			
						The code release has been installed.
					</div>
				</div>
			<?
		}

		public function get_remote_code_release_template(){
			$this->template_header('Install A Code Release');
			?>
				<div class="row">	
					<div class="col-md-12">			
						The code release has been installed.
					</div>
				</div>
			<?
		}

		public function install_remote_package_template(){
			$this->template_header('Install A Code Release');
			?>
				<div class="row">	
					<div class="col-md-12">			
						The code release was installed on the remote server
					</div>
				</div>
			<?
		}

		public function install_remote_package_fail_template(){
			$this->template_header('Install A Code Release');
			?>
				<div class="row">	
					<div class="col-md-12">			
						The code release failed to install on the remote server
					</div>
				</div>
			<?
		}

		public function test_local_db(){
			error_reporting(E_ALL);
			$link = mysql_connect($_POST['db_host'], $_POST['db_user'], $_POST['db_pass']);
			if (!$link){
			    die('Could not connect: ' . mysql_error());
			}
			die("Connected Successfully");
		}

		public function test_connection(){

	
			$host 			= $_POST['host'];
			$user 			= $_POST['user'];
			$pass 			= $_POST['pass'];
			$encryption_key = $_POST['encryption_key'];

			if(strpos($host, '?') === false){
				$host .= '/?';
			}
			else{
				$host .= '&';
			}

			$host .= 'cred='.base64_encode(json_encode(array('user' => $user, 'pass' => $pass)));

			
			$ch = curl_init();
			$timeout = 5;
			curl_setopt($ch, CURLOPT_URL, $host);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			    'CRAuth: '.$encryption_key,
		    ));

			//curl_setopt($ch, CURLOPT_PORT, 80);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36');
			$data = curl_exec($ch);
			if(curl_error($ch)){
				die(curl_error($ch));
			}
			curl_close($ch);

			//die($data);

			//$res = json_decode(file_get_contents($host));

			$res = json_decode($data);

			die($res->message);

		}
	


		public function settings_template(){
			$this->template_header('Settings');
			$crs = new Code_Release_Settings;
			?>
				<form class="form-horizontal" method="post" action="<?=$this->link_pre()?>?page=settings&action=update_settings">

						<div>
						  	<!-- Nav tabs -->
						  	<ul class="nav nav-tabs" role="tablist">
						  		<? $k = 0; foreach($this->settings as $section_name => $section_settings): ?>
						    		<li role="presentation" class="<?= $k == 0 ? 'active' : '' ?>"><a href="#<?=$section_name?>" aria-controls="<?=$section_name?>" role="tab" data-toggle="tab"><?=ucwords(str_replace('_', ' ', $section_name))?></a></li>
						    	<? $k++; endforeach; ?>
						  	</ul>

						  	<!-- Tab panes -->
						  	<div class="tab-content">
						  		<? $k = 0; foreach($this->settings as $section_name => $section_settings): ?>
						    		<div role="tabpanel" class="tab-pane <?= $k == 0 ? 'active' : ''?>" id="<?=$section_name?>">
						    			<br>
						    			<? foreach($section_settings as $setting_name => $setting): ?>
											<? $crs->render_setting($section_name, $setting_name, $setting);  ?>
										<? endforeach; ?>
						    		</div>
						    	<? $k++; endforeach; ?>
						  	</div>
						</div>						


					<p class="center">
						<button class="btn btn-primary">Save</button>
					</p>
				</form>

				<script>

					$(document).off('click.testLocalDb').on('click.testLocalDb', '.test-db', function(e){
						e.preventDefault();
						e.stopPropagation()
						var send_data 	= {
							db_host : $(this).closest('.panel-body').find('#db_host').val(),
							db_name : $(this).closest('.panel-body').find('#db_name').val(),
							db_user : $(this).closest('.panel-body').find('#db_user').val(),
							db_pass : $(this).closest('.panel-body').find('#db_password').val()
						}
						$.ajax({
							url: '<?=$this->settings->path->web_path->value?>?action=test_local_db',
							type: 'POST',
							data: send_data,
							success : function(data){
								alert(data);
							}
						});
						
					})


					$(document).off('click.testConnection').on('click.testConnection', '.test-connection', function(e){
						e.preventDefault();
					
						var send_data 	= {
							host : $(this).closest('.panel-body').find('[data-type="server_host"]').val(),
							user : $(this).closest('.panel-body').find('[data-type="server_user"]').val(),
							pass : $(this).closest('.panel-body').find('[data-type="server_pass"]').val(),
							encryption_key : $(this).closest('.panel-body').find('[data-type="encryption_key"]').val()
 						}
						//console.log(send_data);
						$.ajax({
							url: '<?=$this->settings->path->web_path->value?>/?action=test_connection',
							type: 'POST',
							data: send_data,
							success : function(data){
								alert(data);
							}
						})
					})
				</script>
			<?
		}
	}

	class ftp{ 
    	public $conn; 

	    public function __construct($url){ 
	        $this->conn = ftp_connect($url); 
	    } 
	    
	    public function __call($func,$a){ 
	        if(strstr($func,'ftp_') !== false && function_exists($func)){ 
	            array_unshift($a,$this->conn); 
	            return call_user_func_array($func,$a); 
	        }else{ 
	            // replace with your own error handler. 
	            die("$func is not a valid FTP function"); 
	        } 
	    } 
	} 

	class Code_Release_Settings{

		public function __construct(){

			//THIS SHOULD FIND/PLACE A SETTINGS FILE OUTSIDE OF THE PUBLIC FOLDER
			$file_path = dirname($_SERVER['DOCUMENT_ROOT']).'/settings.crs';

			//unlink($file_path);

			$doc_root = $_SERVER['DOCUMENT_ROOT'].'/';

			$content = json_encode(array(

				'general' => array(
					'use_login' 			=> array('name' => 'Use Login', 'type' => 'checkbox', 'value' => ''),
					//'use_database' 			=> array('name' => 'Use Database', 'type' => 'checkbox', 'value' => ''),
					'user_email' 			=> array('name' => 'User Email (if use login)', 'type' => 'text', 'value' => '', 'attributes' => 'placeholder="example@domain.com"'),
					'user_password' 		=> array('name' => 'User Password (if use login)', 'type' => 'password', 'value' => '', 'attributes' => 'placeholder="your password"'),
					'encryption_key'		=> array('name' => 'Encryption Key', 'type' => 'text', 'value' => $this->get_encryption_key(), 'attributes' => 'readonly="true"'),
				),
				'path' => array(
					'base_path' 			=> array('name' => 'Base Path', 'type' => 'text', 'value' => $doc_root),
					'code_release_path' 	=> array('name' => 'Created Code Release Path', 'type' => 'text', 'value' => $doc_root.'code_releases/'),						
					'package_installs' 		=> array('name' => 'Installed code Release Path', 'type' => 'text', 'value' => $doc_root.'package_installs/'),
					'backup_dir' 			=> array('name' => 'Backup Temp Directory', 'type' => 'text', 'value' => $doc_root.'_backup_temp/'),
					'path_to_backup' 		=> array('name' => 'Directory to Backup', 'type' => 'text', 'value' => $doc_root),
					'web_path'				=> array('name' => 'Web Path To Code Release Manager', 'type' => 'text', 'value' => $_SERVER['REDIRECT_URL']),
					'code_release_backup' 	=> array('name' => 'Code Release Backup Path', 'type' => 'text', 'value' => $doc_root.'code_release_backup/'),
					'unzip_dir' 			=> array('name' => 'Unzip Temp Directory', 'type' => 'text', 'value' => $doc_root.'_unzip_temp/'),
				),
				'exclude' => array(
					'ignore_directory'		=> array('name' => 'Ignore Directory(s)', 'type' => 'text_repeater', 'value' => array()),
					'ignore_file_type'		=> array('name' => 'Ignore Files By Extension', 'type' => 'text_repeater', 'value' => array()),
				),
				'database' => array(
					'database_credentials' 	=> array('name' => 'Database Credentials', 'type' => 'tabs', 'title' => 'false', 'value' => array(
						'local_database' 	=> array('name' => 'Local Database', 'type' => 'tab', 'title' => 'false', 'value' => array(
							'db_host'		=> array('name' => 'Database Host', 'type' => 'text', 'value' => 'localhost', 'attributes' => 'placeholder="localhost" id="db_host"'),
							'db_name'		=> array('name' => 'Database Name', 'type' => 'text', 'value' => '', 'attributes' => 'placeholder="Name of the local database" id="db_name"'),
							'db_user'		=> array('name' => 'Database User', 'type' => 'text', 'value' => '', 'attributes' => 'placeholder="Your database user name" id="db_user"'),
							'db_password'	=> array('name' => 'Database Password', 'type' => 'password', 'value' => '', 'attributes' => 'placeholder="Your database password" id="db_password"'),
							'test_conn'		=> array('name' => '', 'type' => 'link', 'value' => 'Test Connection', 'attributes' => ' href="#" class="btn btn-primary test-db"')
						)),
						'remote_database' 	=> array('name' => 'Remote Database', 'type' => 'tab', 'title' => 'false', 'value' => array(
							'db_host'		=> array('name' => 'Database Host', 'type' => 'text', 'value' => '', 'attributes' => 'placeholder="Remote IP address or domain name" id="db_host"'),
							'db_name'		=> array('name' => 'Database Name', 'type' => 'text', 'value' => '', 'attributes' => 'placeholder="Name of the remote database" id="db_name"'),
							'db_user'		=> array('name' => 'Database User', 'type' => 'text', 'value' => '', 'attributes' => 'placeholder="Remote database user name" id="db_user"'),
							'db_password'	=> array('name' => 'Database Password', 'type' => 'password', 'value' => '', 'attributes' => 'placeholder="Remote database password" id="db_password"'),
							'test_conn'		=> array('name' => '', 'type' => 'link', 'value' => 'Test Connection', 'attributes' => 'href="#" class="btn btn-primary test-db"')
						)),
					)),
				),
				'server' => array(
					'server_credentials' 	=> array('name' => 'Server Credentials', 'type' => 'tabs', 'title' => 'false', 'value' => array(
						'local_server' 		=> array('name' => 'Local Server', 'type' => 'tab', 'title' => 'false', 'value' => array(
							'host'			=> array('name' => 'Host Url', 'type' => 'text', 'value' => '', 'attributes' => 'placeholder="www.example.com or ip address" data-type="server_host"'),
							'user_name'		=> array('name' => 'User Name', 'type' => 'text', 'value' => '', 'attributes' => 'placeholder="FTP User Name" data-type="server_user"'),
							'password'		=> array('name' => 'Password', 'type' => 'password', 'value' => '', 'attributes' => 'placeholder="FTP Pasword" data-type="server_pass"'),
							'test_conn'		=> array('name' => '', 'type' => 'link', 'value' => 'Test Connection', 'attributes' => 'href="#" class="btn btn-primary test-connection" data-target="local_server"')
						)),
						'remote_server' 	=> array('name' => 'Remote Server', 'type' => 'tab', 'title' => 'false', 'value' => array(
							'host'			=> array('name' => 'Code Release Url', 'type' => 'text', 'value' => '', 'attributes' => 'placeholder="www.example.com" data-type="server_host"'),
							'user_name'		=> array('name' => 'User Name', 'type' => 'text', 'value' => '', 'attributes' => 'placeholder="User Name" data-type="server_user"'),
							'password'		=> array('name' => 'Password', 'type' => 'password', 'value' => '', 'attributes' => 'placeholder="FTP Pasword"  data-type="server_pass"'),
							'encryption_key' => array('name' => "Encryption Key", 'type' => 'text', 'value' => '', 'attributes' => "The encryption key for the remote server"),
							'test_conn'		=> array('name' => '', 'type' => 'link', 'value' => 'Test Connection', 'attributes' => 'href="#" class="btn btn-primary test-connection" data-target="remote_server"')
						)),
					)),
				)
			));


			if(!file_exists($file_path)){				

				//ENCRYPT THE CONTENT (PARANOID SECURITY SINCE THE FILE IS NOT PUBLICLY AVAILABLE BUT NEVER CAN BE TOO CAREFUL)				
				$content = $this->encrypt($content);

				//CREATE AND OPEN THE FILE
				$handle = fopen($file_path, 'w+');

				//ADD THE CONTENT
				fwrite($handle, $content);
				
				//CLOSE THE FILE
				fclose($handle);

				//SET FILE PERMISSIONS
				chmod($file_path, 0755);	
			}
			/*else{
				$current_settings = json_decode(trim($this->decrypt(file_get_contents($file_path))), true);

				$check_settings = json_decode($content, true);

				$new_settings = $this->merge_settings($current_settings, $check_settings);

				//ENCRYPT THE CONTENT (PARANOID SECURITY SINCE THE FILE IS NOT PUBLICLY AVAILABLE BUT NEVER CAN BE TOO CAREFUL)				
				$content = $this->encrypt(json_encode($new_settings));

				//CREATE AND OPEN THE FILE
				$handle = fopen($file_path, 'w+');

				//ADD THE CONTENT
				fwrite($handle, $content);
				
				//CLOSE THE FILE
				fclose($handle);

				//SET FILE PERMISSIONS
				chmod($file_path, 0755);
			}*/
		
			$this->settings = json_decode(trim($this->decrypt(file_get_contents($file_path))));
		}

		public function merge_settings($old, $new){
			foreach($new as $k => $v){
				if(is_array($v)){
					$new[$k] = $this->merge_settings($old[$k], $new[$k]);
				}
				else if($k == 'value'){
					$new[$k] = $old[$k];
				}
			}

			return $new;
		}

		public function get_encryption_key(){
			
			$file_path = dirname($_SERVER['DOCUMENT_ROOT']).'/key.crs';

			if(!file_exists($file_path)){
				$handle = fopen($file_path, 'w+');
				fwrite($handle, bin2hex(openssl_random_pseudo_bytes(32)));

				//CLOSE THE FILE
				fclose($handle);

				//SET FILE PERMISSIONS
				chmod($file_path, 0755);
			}

			return file_get_contents($file_path);
		}

		public function encrypt($string){
		    $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC), MCRYPT_RAND);
		    return base64_encode($iv.mcrypt_encrypt(MCRYPT_RIJNDAEL_128, pack('H*', $this->get_encryption_key()), $string, MCRYPT_MODE_CBC, $iv));
		}

		public function decrypt($string){
		    return mcrypt_decrypt(MCRYPT_RIJNDAEL_128, pack('H*', $this->get_encryption_key()), substr(base64_decode($string), mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC)), MCRYPT_MODE_CBC, substr(base64_decode($string), 0, mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC)));
		}

		public function update_settings($data){

			
			$file_path = dirname($_SERVER['DOCUMENT_ROOT']).'/settings.crs';
			unlink($file_path);

			$content = $this->encrypt(json_encode($data));

			//CREATE AND OPEN THE FILE
			$handle = fopen($file_path, 'w+');

			//ADD THE CONTENT
			fwrite($handle, $content);
			
			//CLOSE THE FILE
			fclose($handle);

			//SET FILE PERMISSIONS
			chmod($file_path, 0755);

			$this->settings = json_decode(trim($this->decrypt(file_get_contents($file_path))));
		}

		public function render_link($setting_name, $setting){
			?>
				<textarea name="<?=$setting_name?>[value]" style="display:none"><?=$setting->value?></textarea>
				<a <?=$setting->attributes?>><?=$setting->value?></a>
			<?
		}

		public function render_setting($section_name, $setting_name, $setting, $post_value = false){
		
			$setting_name = $section_name.'['.$setting_name.']';
			if($post_value){
				$setting_name = $setting_name.'[value]';
			}
			$method = 'render_'.$setting->type;
		

		
			?>
				<div class="form-group">

					<? foreach($setting as $setting_key => $setting_property):?>						
						<? if($setting_key == 'value' || is_object($setting_property)) continue; ?>
						<textarea name="<?=$setting_name?>[<?=$setting_key?>]" style="display:none"><?=$setting_property?></textarea>
					<? endforeach; ?>

					<? if($setting->title !== 'false'): ?>
						<label class="col-sm-2 control-label"><?=$setting->name?></label>
					<? endif; ?>
					<div class="col-sm-<?= $setting->title !== 'false' ? '10' : '12'?>">

						<? $this->$method($setting_name, $setting); ?>
					</div>
				</div>
			<?
		}

		public function render_checkbox($setting_name, $setting){
			?>
				<input type="checkbox" name="<?=$setting_name?>[value]" <?=$setting->value == 'on' ? 'checked' : ''?> <?=$setting->attributes?>>
			<?
		}



		public function render_tabs($setting_name, $setting){

			if(!isset($this->tabs)){
				$this->tabs = 0;
			}
			$this->tabs++;

			$setting_name = $setting_name.'[value]';

			?>
				<div>
					<ul class="nav nav-tabs" role="tablist">
						<? $first = true; foreach($setting->value as $tab_name => $tab): ?>
							<li role="presentation" class="<?=$first ? 'active': ''?>"><a href="#tabs_<?=$this->tabs?>_<?=$tab_name?>" aria-controls="home" role="tab" data-toggle="tab"><?=$tab->name?></a></li>
						<? $first = false; endforeach; ?>
					</ul>

					
					<div class="tab-content">
						<? $first = true; foreach($setting->value as $tab_name => $tab): ?>	
							<div role="tabpanel" class="tab-pane <?=$first ? 'active': ''?>" id="tabs_<?=$this->tabs?>_<?=$tab_name?>">
								<? $this->render_setting($setting_name, $tab_name, $tab); ?>								
							</div>
						<? $first = false; endforeach; ?>
					</div>
				</div>
			<?
		}

		public function render_tab($setting_name, $setting){

			$setting_name = $setting_name.'[value]';

				
			?>
					<div class="panel panel-default">
						<div class="panel-body">
							<? foreach($setting->value as $tab_setting_name => $tab_setting): ?>
								<? $this->render_setting($setting_name, $tab_setting_name, $tab_setting); ?>	
							<? endforeach; ?>
						</div>
					</div>
			<?
		}	

		public function render_text($setting_name, $setting){
			?>
				<input type="text" name="<?=$setting_name?>[value]" class="form-control" value="<?=$setting->value?>" <?=$setting->attributes?>>
			<?
		}

		public function render_password($setting_name, $setting){
			?>
				<input type="password" name="<?=$setting_name?>[value]" class="form-control" value="<?=$setting->value?>" <?=$setting->attributes?>>
			<?
		}

		public function render_text_repeater($setting_name, $setting){
				
			?>
				<div class="list-group text-repeater-wrapper">
					<a class="btn btn-primary add-text-repeater"><i class="fa fa-plus"></i> Add</a>
					<div class="list-group-item template" style="display:none">
						<div class="row">
							<div class="col-sm-11">
								<input type="text" class="form-control" disabled name="<?=$setting_name?>[value][]">
							</div>
							<div class="col-sm-1">
								<a class="btn btn-default remove-text-repater"><i class="fa fa-trash"></i></a>
							</div>
						</div>
					</div>
					<? foreach($setting->value as $setting_val): ?>
						<div class="list-group-item">
							<div class="row">
								<div class="col-sm-11">
									<input type="text" class="form-control" name="<?=$setting_name?>[value][]" value="<?=$setting_val?>">
								</div>
								<div class="col-sm-1">
									<a class="btn btn-default remove-text-repater"><i class="fa fa-trash"></i></a>
								</div>
							</div>
						</div>
					<? endforeach; ?>
					
				</div>

				<script>

					$(document).off('click.addTextRepeater').on('click.addTextRepeater', '.add-text-repeater', function(e){
						e.preventDefault();
						var template = $(this).closest('.text-repeater-wrapper').find('.template')[0].outerHTML;
						$(this).closest('.text-repeater-wrapper').append(template);
						$(this).closest('.text-repeater-wrapper').find('.template:last').show().removeClass('template').find('.form-control').removeAttr('disabled');

					});

					$(document).off('click.removeTextRepeater').on('click.removeTextRepeater', '.remove-text-repater', function(e){
						e.preventDefault();
						$(this).closest('.list-group-item').remove();
					});
				</script>
			<?
		}
	}
?>
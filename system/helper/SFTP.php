<?php

	//THIS CLASS HELPS CONNECT TO REMOTE SERVERS/READ AND MANAGE FILES ON THERE
	class SFTP_Helper extends Helper{

		private $user;
		private $host;
		private $pass;
		private $conn;
		private $sftp;
		public $root_dir = "/";

		public function __construct(){

		}
		
		//ESTABLISH CONNECTION TO THE REMOTE SERVER
		public function connect($host, $user, $pass){

			//SET CREDENTIALS
			$this->host = $host;
			$this->user = $user;
			$this->pass = $pass;			

			//LOGIN IN
			$this->conn	= ssh2_connect($this->host, 22);
			ssh2_auth_password($this->conn, $this->user, $this->pass);
			$this->sftp = ssh2_sftp($this->conn);

			//RETURN OBJECT
			return $this;
		}

		public function set_root_directory($dir){
			$this->root_dir = $dir;
			return $this;
		}

		

		public function exec($cmd){

			$stream = ssh2_exec($this->conn, $cmd);
			stream_set_blocking($stream, true);
			$stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
			return trim(stream_get_contents($stream_out));

			//return ssh2_exec($this->conn, $cmd);
		}

		public function prepare_path($path){
			if($this->root_dir != "/"){
				$path = $this->root_dir.str_replace($this->root_dir, '', $path);
			}
			$path = str_replace('//', '/', $path);
			return $path;
		}

		//GET THE REMOTE DIRECTORY
		public function get_directory($directory = './', $extension_filter = "*"){

			$directory = $this->prepare_path($directory);


			//OPEN THE DIRECTORY
			$dir = opendir("ssh2.sftp://{$this->sftp}/".$directory);

			//READ THE FILES
			$files = array();
			while($filename = readdir($dir)) {						
				if (!in_array($filename, array('.','..'))) {

					$filename = basename($filename);	
					if($extension_filter != '*'){
						$extension = pathinfo($filename)['extension'];
						if(strtolower(pathinfo($filename)['extension']) === strtolower($extension_filter)){
							$files[] = $directory.$filename;
						}
					}
					else{
						$files[] = $directory.$filename;
					}
					
				}
			}
			return $files;
		}

		public function stats($path){

			$res = ssh2_sftp_stat($this->sftp, $path);

			return $res;
		}

		public function is_directory($path){

			$path = $this->prepare_path($path);

			$res = $this->exec("[ -d {$path} ] && echo 'true' || echo 'false'");
			if($res == 'true'){
				return true;
			}
			return false;
		}

		public function is_file($path){

			$path = $this->prepare_path($path);

			$res = $this->exec("[ -f {$path} ] && echo 'true' || echo 'false'");
			if($res == 'true'){
				return true;
			}
			return false;
		}

		//READ A FILE FROM THE REMOTE SERVER
		public function get_file($file_path){

			$file_path = $this->prepare_path($file_path);

			//GET THE CASE INSENSITIVE FILE PATH
			$file_path 	= $this->file_exists($file_path, false);

			return fopen("ssh2.sftp://".$this->sftp.'/'.$file_path, 'r');
		}

		//DELETE A FILE ON THE REMOTE SERVER
		public function delete_file($file_path = ''){		

			if($file_path == '' && isset($_GET['file_location'])){
				$file_path = base64_decode($_GET['file_location']);

			}

			if($file_path !== ''){

				$file_path = $this->prepare_path($file_path);			

				//return $this->exec("rm -rf {$file_path}");
				return ssh2_sftp_unlink($this->sftp, $file_path);
			}
			return false;
			
		}

		public function delete_folder($file_path){
			if($file_path == '' && $_GET['file_location']){
				$file_path = base64_decode($_GET['file_location']);
			}

			if($file_path !== ''){

				$file_path = $this->prepare_path($file_path);

				$this->exec("rm -rf {$file_path}");
				return ssh2_sftp_rmdir($this->sftp, $file_path);
				
			}
			return false;
		}

		public function file_exists($file_path, $case_sensative = true){

			$file_path = $this->prepare_path($file_path);

			if($case_sensative === false){
				$dir 		= dirname($file_path);
				$filename 	= basename($file_path);
				$files 		= $this->get_directory($dir.'/');
				foreach($files as $file){
					if(strtolower(str_replace($dir.'/', '', $file)) === strtolower($filename)){
						return $file;
					}
				}
				
				/*$res 		= $this->exec("find {$dir} -iname {$filename}");
				if(!empty($res)){
					return $res;
				}*/
				return false;
			}

			return file_exists('ssh2.sftp://'.$this->sftp.$file_path);
		}

		public function create_file($file_path){

			$file_path = $this->prepare_path($file_path);

			$this->create_directory(dirname($file_path));
			return fopen("ssh2.sftp://".$this->sftp.'/'.$file_path, 'w');
		}

		
		public function create_unique_file($path, $local_file_path, $content = false){

			$path = $this->prepare_path($path);

			$this->create_directory($path);
			$token 		= md5($this->make_random());
			$token 		= substr(strtoupper($token),2,19);
			$extension 	= pathinfo($local_file_path)['extension'];
		
			$REALfilename = $token.'.'.$extension;
			if (file_exists($path . $REALfilename)){
				$fnum = 0;
				$newFileName = $fnum.'_'.$REALfilename;
		
				while (file_exists($path . $newFileName)){
					$fnum++;
					$newFileName = $fnum.'_'.$REALfilename;
				}
		
				$REALfilename = $newFileName;
			}

			$remote_file_path = $path.$REALfilename;

			if($content){
				file_put_contents("ssh2.sftp://{$this->sftp}".$remote_file_path, $content);
			}
			else{

				//OPEN THE FILES
				$remote_file 		= fopen("ssh2.sftp://{$this->sftp}".$remote_file_path, 'w');
				$local_file 		= fopen($local_file_path, 'r');
				
				//COPY THE FILE DATA
				$res 				= stream_copy_to_stream($local_file, $remote_file);

				//CLOSE THE FILES
				fclose($local_file);
				fclose($remote_file);
			}
			return $remote_file_path;
		}

		
	    public function make_random() {
	    	$salt = "abchefghjkmnpqrstuvwxyz123456789";
	    	srand((double)microtime()*1000000);
	      	$i = 0;
	      	$pass = '';
	      	while ($i <= 8) {
	            $num = rand() % 33;
	            $tmp = substr($salt, $num, 1);
	            $pass = $pass . $tmp;
	            $i++;
	      	}
	    	return $pass;
	    }

	    public function move_to_local($remote_file_path, $local_file_path){

	    	$remote_file_path = $this->prepare_path($remote_file_path);

	    	$local_directory = dirname($local_file_path);
	    	
	    	//FORCE THE LOCAL DIRECTORY
			if(!file_exists($local_directory)){
				mkdir($local_directory, 0777, true);
			}

			//GET THE CASE INSENSITIVE FILE PATH
			$remote_file_path 	= $this->file_exists($remote_file_path, false);
			
			//OPEN THE FILES
			$remote_file 		= fopen("ssh2.sftp://{$this->sftp}".$remote_file_path, 'r');
			$local_file 		= fopen($local_file_path, 'w');
			
			//COPY THE FILE DATA
			$res 				= stream_copy_to_stream($remote_file, $local_file);
			
			//CLOSE THE FILES
			fclose($local_file);
			fclose($remote_file);

			$this->delete_file($remote_file_path);

			//SEND BACK THE STATUS
			return $local_file_path;
	    }

		//COPY A REMOTE FILE TO THE LOCAL SERVER
		public function copy_to_local($remote_file_path, $local_directory){

			$remote_file_path = $this->prepare_path($remote_file_path);

			//FORCE THE LOCAL DIRECTORY
			if(!file_exists($local_directory)){
				mkdir($local_directory, 0777, true);
			}			
			
			//BUILD THE LOCAL FILE FILE PATH
			$local_file_path 	= $local_directory.basename($remote_file_path);

			//GET THE CASE INSENSITIVE FILE PATH
			$remote_file_path 	= $this->file_exists($remote_file_path, false);
			
			//OPEN THE FILES
			$remote_file 		= fopen("ssh2.sftp://{$this->sftp}".$remote_file_path, 'r');
			$local_file 		= fopen($local_file_path, 'w+');
			
			//COPY THE FILE DATA
			$res 				= stream_copy_to_stream($remote_file, $local_file);
			
			//CLOSE THE FILES
			fclose($local_file);
			fclose($remote_file);

			//SEND BACK THE STATUS
			return $local_file_path;
				
		}

		public function rename($file_path = '', $newname = ''){
			
			//HANDLE RENAMES FROM THE GUI
			if($_GET['old_name']){
				$file_path = base64_decode($_GET['old_name']);
			}

			//FORCE LEADING SLASH
			if($_GET['new_name']){
				$newname = '/'.$_GET['new_name'];
			}
			
			//MAKE SURE WE HAVE A FILE TO RENAME
			if($file_path !== '' && $newname !== ''){

				$file_path = $this->prepare_path($file_path);

				//REMOVE TRAILING SLASH IF EXISTS
				if(substr($file_path, -1) == '/'){
					$file_path = substr($file_path, 0, strlen($file_path)-1);
				}
				
				//RENAME THE FILE IN THE SAME LOCATION
				$newname = dirname($file_path).$newname;

				//RENAME THE FILE
				$res = ssh2_sftp_rename($this->sftp, $file_path, $newname);
					
				//IF SUCCESS SEND BACK FILE PATH
				if($res){
					return $newname;
				}

				return false;	
			}			
		}

		//COPY A LOCAL FILE TO THE REMOTE SERVER
		public function copy_to_remote($local_file_path, $remote_directory){

			$remote_directory = $this->prepare_path($remote_directory);

			//FORCE THE REMOTE DIRECTORY
			$this->create_directory($remote_directory);

			//BUILD THE REMOTE FILE PATH
			$remote_file_path 	= $remote_directory.basename($local_file_path);
			
			//OPEN THE FILES
			$remote_file 		= fopen("ssh2.sftp://{$this->sftp}".$remote_file_path, 'w');
			$local_file 		= fopen($local_file_path, 'r');
			
			//COPY THE FILE DATA
			$res 				= stream_copy_to_stream($local_file, $remote_file);
			
			//CLOSE THE FILES
			fclose($local_file);
			fclose($remote_file);

			//SEND BACK THE STATUS
			if($res){
				return $remote_file_path;
			}
			return $res;
		}

		//CREATE A DIRECTORY ON THE REMOTE SERVER
		public function create_directory($remote_directory = ''){



			//HANDLE GUI CREATE DIRECTORY
			if($remote_directory == '' && $_GET['action'] == 'create_directory'){				
				if($_GET['folder']){
					$remote_directory = base64_decode($_GET['folder']).'new_folder_'.time();	
				}
				else{
					$remote_directory = $this->root_dir.'new_folder_'.time();
				}			
			}

			//DONT TRY IF WE DONT HAVE A NAMEf
			if($remote_directory == ''){
				return false;
			}

			$remote_directory = $this->prepare_path($remote_directory);

			//ONLY CREATE DIRECTORY IF IT DOES NOT EXIST
			if(!file_exists('ssh2.sftp://'.$this->sftp.$remote_directory)){
				return ssh2_sftp_mkdir($this->sftp, $remote_directory, 0777, true);
			}

			//DIRECTORY EXISTS
			return true;			
		}


//-----------------------------------// GUI METHODS // -------------------------------------------//

		public function gui(){
			$this->gui_route();
		}

		public function gui_render_css(){
			?>
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

					.file-list-item {
						text-align: center;
					}
					.file-list-item i {
						font-size:60px;
					}
					.file-list-row {
						margin-bottom:20px;
					}
					#contextmenu {
						position: absolute;
						box-shadow: rgba(0,0,0,0.2) 5px 10px 5px;
					}
					.active-folder a {
						color:rgb(126, 222, 2);
					}
				</style>
			<?
		}

		public function gui_upload(){


			$tmp_dir = (dirname(__FILE__)).'/_tmp';
			mkdir($tmp_dir, 0777, true);

			foreach($_FILES['files']['error'] as $key => $error){
				$tmp_name 	= $_FILES['files']['tmp_name'][$key];
				$name 		= $_FILES['files']['name'][$key];
				$new_name 	=  $tmp_dir.'/'.$name;
				if(move_uploaded_file($tmp_name,$new_name)){
					$this->copy_to_remote($new_name, $_POST['folder']);
				}
			}
			die('success');
			exit;
		}

		public function gui_render_js(){
			?>
				<script>

				
					
						/*var is_dragging = false;

						$(document).mousedown(function(e){
							e.preventDefault();
							e.stopPropagation();
							drag_startx = e.pageX;
							drag_starty = e.pageY;
							
							$('#select-box').remove();
							$('body').append('<div id="select-box" style="background;blue"></div>')


							is_dragging = true;
						}).mousemove(function(e){
							e.preventDefault();
							e.stopPropagation();
							if(is_dragging == true){
								$('#select-box').width(199).height(200);

							}
						 }).mouseup(function() {
						 	is_dragging = false;
						});*/


						//INIT URL ROUTING
						url_pre = '<?=$this->link_pre()?>?';
						<? if($_GET['folder']): ?>
							url_pre = '<?=$this->link_pre()?>?folder=<?=$_GET['folder']?>&';
						<? endif; ?>

						console.log(url_pre);
					
						//HANDLE GENERAL FINISHING ACTIONS
						$(document).off('click.GeneralDocument').on('click.GeneralDocument', function(){
							$('#contextmenu').remove();
							$('.active-folder').removeClass('active-folder');
							$('.active-file').removeClass('active-file');
						});

						

						//GENERAL RIGHT CLICK MENU
						$(document).off('contextmenu.GeneralContextMenu').on('contextmenu.GeneralContextMenu', function(e){
							e.preventDefault();
							$('#contextmenu').remove();
							var x = e.pageX;
							var y = e.pageY;
							$('body').append(
								'<div id="contextmenu" style="top:'+y+'px; left:'+x+'px" class="list-group">'+
									'<a href="#" id="add-folder" class="list-group-item">Add Folder</a>'+
									'<a href="#" id="add-file" class="list-group-item">Add File</a>'+
								'</div>'
							);
						});

						$(document).off('click.AddFile').on('click.AddFile', '#add-file', function(e){
							e.preventDefault();
							e.stopPropagation();
							$('#contextmenu').remove();
							$('#file_upload').click();
						});

						$(document).on('change', '#file_upload', function(e){
							console.log('called');
							var formdata = new FormData($('#file_upload_form')[0]);

							

							$.ajax({
								url: $('#file_upload_form').attr('action'),
								type: typeof($('#file_upload_form').attr('method')) !== 'undefined' ? $('#file_upload_form').attr('method') : 'get',			
								data: formdata,
								processData: false,
								contentType: false,
								success: function(data){
									window.location.reload();
									//console.log(data);
								}
							})
						});

						

						//FOLDER RIGHT CLICK MENU
						$(document).on('contextmenu', '.folder', function(e){
							e.preventDefault();
							e.stopPropagation();
							$('#contextmenu').remove();
							$('.active-folder').removeClass('active-folder');
							$(this).addClass('active-folder');
							var x = e.pageX;
							var y = e.pageY;
							$('body').append(
								'<div id="contextmenu" style="top:'+y+'px; left:'+x+'px" class="list-group">'+
									'<a href="#" id="rename-folder" class="list-group-item">Rename Folder</a>'+
									'<a href="#" id="delete-folder" class="list-group-item">Delete Folder</a>'+
								'</div>'
							);
						});

						//FILE RIGHT CLICK MENU
						$(document).on('contextmenu', '.file', function(e){
							e.preventDefault();
							e.stopPropagation();
							$('#contextmenu').remove();
							$('.active-folder').removeClass('active-folder');
							$(this).addClass('active-folder');
							var x = e.pageX;
							var y = e.pageY;
							$('body').append(
								'<div id="contextmenu" style="top:'+y+'px; left:'+x+'px" class="list-group">'+
									'<a href="#" id="rename-folder" class="list-group-item">Rename File</a>'+
									'<a href="#" id="delete-file" class="list-group-item">Delete File</a>'+
								'</div>'
							);
						});

						//DELETE A FOLDER
						$(document).on('click', '#delete-folder', function(e){
							e.preventDefault();
							e.stopPropagation();
							$('#contextmenu').remove();
							var file_location 		= $('.active-folder').find('a').attr('data-location');
							window.location.href 	=  url_pre+'action=delete_folder&file_location='+file_location;
						});	

						//DELETE A FILE/FOLDER
						$(document).on('click', '#delete-file', function(e){
							e.preventDefault();
							e.stopPropagation();
							$('#contextmenu').remove();
							var file_location 		= $('.active-folder').find('a').attr('data-location');

							window.location.href 	=  url_pre+'action=delete_file&file_location='+file_location;
						});						

						//ADD A FILE/FOLDER
						$(document).on('click', '#add-folder', function(e){
							e.preventDefault();
							e.stopPropagation();
							$('#contextmenu').remove();
							window.location.href 	= url_pre+'action=create_directory';
						});
						
						//RENAME A FILE/FOLDER
						$(document).on('click', '#rename-folder', function(e){
							e.preventDefault();
							e.stopPropagation();
							$('#contextmenu').remove();
							$('.active-folder').next().hide();
							var folder_name = $('.active-folder').next().text().trim();
							var folder_location = $('.active-folder').find('a').attr('data-location')
							$('.active-folder').next().after('<input type="text" data-location="'+folder_location+'"  class="form-control rename-folder-textbox" value="'+folder_name+'">');
							$('.active-folder').next().next().focus();
						});

						//DONE RENAMING A FILE/FOLDER
						$(document).on('blur', '.rename-folder-textbox', function(){
							var old_name = $(this).attr('data-location');
							var new_name = $(this).val();
							window.location.href = url_pre+'action=rename&old_name='+old_name+'&new_name='+new_name;
						});

						$(document).off('click.RenameFolderTextBox').on('click.RenameFolderTextBox', '.rename-folder-textbox', function(e){
							e.stopPropagation();
							
						})

						$(document).off('contextmenu.RenameFolderTextBoxContextMenu').on('contextmenu.RenameFolderTextBoxContextMenu', '.rename-folder-textbox', function(e){
							e.stopPropagation();
							
						})



					</script>
			<?
		}

		public function gui_header($title = "Welcome"){

			if($title == './'){
				$title = 'Home Directory';
			}
			else{
				if(strpos($title, '/') !== false){
					$title = ucwords(end(array_filter(explode('/', $title))));
				}
				
			}
			
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
						<? $this->gui_render_css() ?>
						<? $this->gui_render_js() ?>
					</head>
					<body>
					<div class="container">
						<h1 class="brand">SFTP Manager</h1>
						<div class="main-content">
							<h3 class="page-title"><div class="fadeInDown animated"><?=$title?></div></h3>
							<? $this->gui_navigation() ?>
							<div class="main-content-inner">
								<? $this->gui_errors() ?>	
								<form id="file_upload_form" action="<?=$this->link_pre()?>?action=gui_upload" method="post" enctype="multipart/form-data">
									<input type="hidden" name="folder" value="<?=base64_decode($_GET['folder'])?>">
									<input type="file" style="display:none;" multiple id="file_upload" name="files[]">
								</form>	
			<?
		}

		public function gui_footer(){
			?>
					</div>
					</div>
					<div class="center" id="main-footer">Created by i-Tul Design &amp; Software 2015.</div>
				</div>
				</body>
				</html>
			<?
		}

		public function gui_navigation(){

		}

		public function gui_errors(){

		}

		//ROUTE REQUESTS
		public function gui_route(){
			
			//CHECK FOR $_GET['folder']
			$this->gui_parse_directory();

			//CHECK FOR $_GET['action']
			$this->gui_action();

			//CHECK FOR $_GET['file']
			$this->gui_download();

			//ROUTE THE VIEW
			$method = 'gui_template_'.$_GET['page'];
			if($_GET['page'] && method_exists($this, $method)){
				$this->$method();
			}
			else{
				$this->gui_template_index();
			}
			exit;
		}

		public function gui_action(){

			if($_GET['action'] && method_exists($this, $_GET['action'])){
				$method = $_GET['action'];
				$this->$method();
			}
		}

		public function gui_download(){
			if($_GET['file']){
				$file = base64_decode($_GET['file']);

				$tmp_dir = dirname(__FILE__).'/_sftp_tmp/';
				if(!file_exists($tmp_dir)){
					mkdir($tmp_dir, 0777, true);
				}

				$file_path = $this->copy_to_local($file, $tmp_dir);


				header('Content-Type: application/octet-stream');
				header("Content-Transfer-Encoding: Binary"); 
				header("Content-disposition: attachment; filename=\"" . basename($file_path) . "\""); 
				readfile($file_path);
				exit;
			}
		}

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
			//if(strpos($href_pre, '?') === false){
				//$href_pre .= '?';
			//}

			return $href_pre;
		}

		public function gui_breadcrumbs(){
			$directory = './';
			if($_GET['folder']){
				$directory = base64_decode($_GET['folder']);
			}



			$directories = array_filter(explode('/', $directory));
			if(!in_array('.', $directories)){
				array_unshift($directories, '.');
			}
			$rendered = array();

			$found = false;
			$new_dirs = array();
			foreach($directories as $dir){

				if($dir == '.'){
					$found = true;
				}
				if(!$found){
					$rendered[] = $dir;
					continue;
				}
				$new_dirs[] = $dir;

			}

			$directories = $new_dirs;

			

			?>
				<ol class="breadcrumb">
					<? foreach($directories as $directory): $rendered[] = $directory?>
						<li><a href="<?=$this->link_pre()?>?folder=<?=$directory == '.' ? base64_encode('./') : base64_encode('/'.implode('/', $rendered).'/')?>">
							<?=$directory == '.' ? '<i class="fa fa-home"></i>' : $directory ?>
						</a></li>
					<? endforeach; ?>
				</ol>
			<?			
		}

		public function gui_parse_directory(){

			$this->directory = './';
			if($_GET['folder']){
				$this->directory = base64_decode($_GET['folder']);
			}

			//IF NOT HOME DIRECTORY REMOVE STARTING PERIOD
			if($this->directory !== './' && substr($this->directory, 0, 2) == './'){
				$this->directory = substr($directory, 1);
			}

			//FORCE TRAILING SLASH
			if(substr($this->directory, -1) !== '/'){
				$this->directory = $this->directory.'/';
			}
		}

		public function gui_load_template(){

		}


		public function gui_template_index(){
			
			//LOAD THE HEADER
			$this->gui_header($this->directory);

			$this->gui_breadcrumbs();
			
			//INIT SOME VARS
			$i 			= 0;
			$files 		= $this->get_directory($this->directory);
			$file_count = count($files);
			
			//CYCLE THE FILES
			foreach($files as $k => $file):
				
				//GET FILE INFO
				$filename 	= basename($file);
				$type 		= 'folder';
				if(strpos($filename, '.')){
					$type = 'file';
				}
				else{
					if(substr($file, 0, 2) == './'){
						$file = substr($file, 1);
					}
					if(substr($file, -1) !== '/'){
						$file = $file.'/';
					}
				}

				//START A ROW IF NEEDED
				if($i == 0 ):
					?>
						<div class="row file-list-row">
					<? 
				endif; 

				//PRINT THE FILE TO THE SCREEN
				?>
					<div class="col-sm-3 file-list-item">
						<div class="icon <?=$type?>">
						<a href="<?=$this->link_pre()?>?<?=$type?>=<?=base64_encode($file)?>" data-location="<?=base64_encode($file)?>" class="file-list-icon"><i class="fa fa-<?=$type?>"></i></a>
						</div>
						<div class="icon-name">
							<a href="<?=$this->link_pre()?>?<?=$type?>=<?=base64_encode($file)?>"><?=$filename?></a>
						</div>
					</div>
				<?

				//CLOSE THE ROW IF NEEDED
				if($i == 3 || $k+1 == $file_count): 
					$i = 0;
					?>
						</div> <!-- END OF ROW -->
					<?

				//MOVE THE ITERATOR FORWARD
				else: 
					$i++;
				endif; 
			endforeach; 

			//GET THE FOOTER
			$this->gui_footer();
		}


// ------------------------------ // COLLECTION DEBUG //-----------------------//
		public function create_collection(){
			error_reporting(E_ALL);


			

			//$collection = new SFTP_Directory_Collection($this, '/pdf_temp/50/', '/home/calsrv/www/wfm/pdf_temp/50/');

			$files 		= $this->load_model('File')->order_by("file_id DESC")->limit(1)->orm_load();
			$file 		= $files[0];			
			$item 		= new SFTP_Directory_Item($file, $this, '/pdf_temp/50/', '/home/calsrv/www/wfm/pdf_temp/50/');

			$collection = $item->convert_to_images()->move_to_local();
			

			$item->stamp($stamp)->move_to_local();

			//$collection->push($files);

			//$collection->copy_to_remote('/pdf_temp/50/');

			$merged = $collection->merge('/pdf_temp/50/');

			$merged->move_to_local('/home/calsrv/www/wfm/pdf_temp/50/test.pdf');

			pr($merged);
			exit;
		}
	}

	class SFTP_Directory_Collection implements Iterator {
		
		private $_position 	= 0;
		private $_data 		= array();
		private $_parent;
		private $_remote_temp;
		private $_local_temp;

		public function __construct($parent, $remote_temp, $local_temp){
			$this->_parent 		= $parent;
			$this->_remote_temp = $remote_temp;
			$this->_local_temp 	= $local_temp;
		}

		public function get_by_key($key){
			return $this->_data[$key];
		}

		public function create_file_name($path, $extension){

			$token 		= md5($this->make_random());
			$token 		= substr(strtoupper($token),2,19);
		
			$REALfilename = $token.'.'.$extension;
			if (file_exists($path . $REALfilename)){
				$fnum = 0;
				$newFileName = $fnum.'_'.$REALfilename;
		
				while (file_exists($path . $newFileName)){
					$fnum++;
					$newFileName = $fnum.'_'.$REALfilename;
				}
		
				$REALfilename = $newFileName;
			}

			return $path.$REALfilename;
		}
		
	    public function make_random() {
	    	$salt = "abchefghjkmnpqrstuvwxyz123456789";
	    	srand((double)microtime()*1000000);
	      	$i = 0;
	      	$pass = '';
	      	while ($i <= 8) {
	            $num = rand() % 33;
	            $tmp = substr($salt, $num, 1);
	            $pass = $pass . $tmp;
	            $i++;
	      	}
	    	return $pass;
	    }

		public function valid(){
			return isset($this->_data[$this->_position]);
		}

		public function rewind(){
			$this->_position = 0;
			return $this;
		}

		public function current(){
			return $this->_data[$this->_position];
		}

		public function key(){
			return $this->_position;
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

		public function first(){
			foreach($this->_data as $item){
				return $item;
			}
		}

		public function push($data){

			if(is_string($data)){
				$this->_data[] = new SFTP_Directory_Item($data, $this->_parent, $this->_remote_temp, $this->_local_temp);
			}
			else if(is_array($data)){
				foreach($data as $d){
					$this->_data[] = new SFTP_Directory_Item($d, $this->_parent, $this->_remote_temp, $this->_local_temp);
				}
			}
			else if(is_object($data)){
				if(get_class($data) == 'SFTP_Directory_Collection'){
					$this->_data[] = $data;
				}
				elseif(get_class($data) == 'SFTP_Directory_Item'){
					$this->_data[] = $data;
				}
				else{
					$this->_data[] = new SFTP_Directory_Item($data, $this->_parent, $this->_remote_temp, $this->_local_temp);
				}
			}			

			return $this;
		}

		public function merge(){

			//CONVERT THE FILES TO PDFS
			$this->convert_to_pdf();

			//EXTRACT THE REMOTE PATHS
			$paths = array();
			foreach($this->_data as $item){
				$paths[] = $item->_remote_path;
			}

			//START AN EMPTY PDF
			$remote_output_file = $this->_parent->create_unique_file($this->_remote_temp, 'merged.pdf', true);

			//MERGE ON REMOVE SERVER
			$this->_parent->exec('pdftk '.implode(' ', $paths).' cat output '.$remote_output_file);

			//DELETE THE TEMP FILES
			foreach($this->_data as $item){
				$this->_parent->delete_file($item->_remote_path);
			}

			//SEND BACK THE NEW MERGED ITEM
			return new SFTP_Directory_Item($remote_output_file, $this->_parent, $this->_remote_temp, $this->_local_temp);

		}

		public function convert_to_pdf(){

			//CONVERT EACH ITEM TO PDF
			foreach($this->_data as $item){

				//SKIP DIRECTORIES
				if($item->_type == 'directory') continue;

				//CONVERT ITEM TO PDF
				$item->convert_to_pdf();
			}

			return $this;
		}

		public function copy_to_remote(){

			foreach($this->_data as $item){
				
				//SKIP ITEMS THAT ARE NOT ON THE LOCAL SERVER
				if(is_null($item->_local_path)){
					continue;
				}

				//COPY THE ITEM TO THE REMOTE SERVER
				$item->copy_to_remote();
			}

			return $this;
		}

		public function copy_to_local(){

			foreach($this->_data as $item){
				if(is_null($item->_remote_path) || $item->_location == 'local'){
					continue;
				}

				$item->copy_to_local();
			}

		}

		public function move_to_remote(){

			foreach($this->_data as $item){
				if(is_null($item->_local_path) || $item->_location == 'remote'){
					continue;
				}

				$item->move_to_remote();
			}
		}

		public function move_to_local(){

			foreach($this->_data as $item){

				//SKIP FILES THAT DONT EXIST ON THE REMOTE SERVER
				if(is_null($item->_remote_path)){
					continue;
				}

				//MOVE THE ITEM TO THE LOCAL PATH
				$item->move_to_local();
			}

			return $this;

		}
	}

	class SFTP_Directory_Item extends SFTP_Helper{

		public 	$_location;
		public 	$_type;
		public 	$_local_path;
		public 	$_remote_path;
		public 	$_path;
		public 	$_extension;
		public 	$original_data;
		public 	$_parent;
		public 	$_remote_temp;
		public 	$_local_temp;
		public 	$_file_id;

		public function __construct($data, $parent, $remote_temp, $local_temp){
			
			$this->_parent 		= $parent;
			$this->_remote_temp = $remote_temp;
			$this->_local_temp 	= $local_temp;

			//SET THE ORIGINAL DATA
			//$this->_original_data = $data;

			//SET PATH DATA
			$path_info 					= $this->get_path_info($data);
			$this->_path 				= $path_info['path'];
			$this->_extension 			= $path_info['extension'];
			if(isset($path_info['file_id'])){
				$this->_file_id = $path_info['file_id'];
			}

			//CHECK IF IS A LOCAL FILE
			if(file_exists($this->_path)){
				$this->_local_path 	= $this->_path;
				$this->_location 	= 'local';

				if(is_dir($this->_local_path)){
					$this->_type 	= 'directory';
				}
				else{
					$this->_type 	= 'file';
				}
			}

			//CHECK IF IS A REMOTE FILE
			else{
				$this->_remote_path = $this->_path;
				$this->_location 	= 'remote';

				if($this->_parent->is_directory($this->_remote_path)){
					$this->_type 	= 'directory';
				}
				else{
					$this->_type 	= 'file';
				}
			}
		}

		public function get_path_info($path){

			//THE PATH IS A SINGLE FILE OBJECT
			if(is_object($path)){
				return array(
					'path' 		=> $path->file_path.$path->file_name,
					'extension' => strtolower(pathinfo($path->file_org_name)['extension']),
					'file_id' 	=> $path->file_id
				);
			}

			//THE PATH IS A STRING
			else{
				$file_id 	= false;

				//SET SEARCH PARAMS
				$fpath 		= dirname($path).'/';
				$fname 		= basename($path);						

				//SEARCH FOR FILE
				$file_check = $this->Db()->get_row("SELECT * FROM files WHERE file_path = '{$fpath}' AND file_name = '{$fname}' ORDER BY file_id DESC");
				
				//FILE WAS FOUND SO GET THE EXTENSION AND FILE ID
				if($file_check){
					$extension 	= pathinfo($file_check['file_org_name'])['extension'];
					$file_id 	= $file_check['file_id'];
				}

				//NO FILE WAS FOUND SO TRY TO FIND THE EXTENSION
				else{
					$extension 	= strtolower(pathinfo($path)['extension']);
				}

				//BUILD THE PATH ARRAY
				$arr = array(
					'path' 		=> $path,
					'extension' => $extension !== '' ? $extension : 'pdf'
				);

				//ADD THE FILE ID IF IT WAS FOUND
				if($file_id){
					$arr['file_id'] = $file_id;
				}
			}
			return $arr;
		}

		public function create_file_name($path, $extension){

			$token 		= md5($this->make_random());
			$token 		= substr(strtoupper($token),2,19);
		
			$REALfilename = $token.'.'.$extension;
			if (file_exists($path . $REALfilename)){
				$fnum = 0;
				$newFileName = $fnum.'_'.$REALfilename;
		
				while (file_exists($path . $newFileName)){
					$fnum++;
					$newFileName = $fnum.'_'.$REALfilename;
				}
		
				$REALfilename = $newFileName;
			}

			return $path.$REALfilename;
		}
		
	    public function make_random() {
	    	$salt = "abchefghjkmnpqrstuvwxyz123456789";
	    	srand((double)microtime()*1000000);
	      	$i = 0;
	      	$pass = '';
	      	while ($i <= 8) {
	            $num = rand() % 33;
	            $tmp = substr($salt, $num, 1);
	            $pass = $pass . $tmp;
	            $i++;
	      	}
	    	return $pass;
	    }

		public function files(){
			if($this->_type == 'directory'){
				if($this->_location == 'local'){
					return $this->local_files();
				}
				else{
					return $this->remote_files();
				}
			}
			return false;
		}

		public function remote_files(){

			$collection = new SFTP_Directory_Collection($this->_remote_path, $this->_parent, $this->_remote_temp, $this->_local_temp);

			foreach($this->_parent->get_directory($this->_remote_path) as $remote_file){
				$collection->push($remote_file);
			}

			return $collection;

		}

		public function local_files(){
			$collection = new SFTP_Directory_Collection($this->_local_path, $this->_parent, $this->_remote_temp, $this->_local_temp);

			foreach(glob($this->_local_path.'*') as $local_file){
				$collection->push($local_file);
			}

			return $collection;
		}

		//CHECK IF THE FILE EXISTS
		public function exists(){
			if($this->_location == 'local'){
				return $this->exists_local();
			}
			return $this->exists_remote();
		}

		//CHECK IF THE FILE EXISTS LOCALLY
		public function exists_local(){
			if(!is_null($this->_local_path) || $this->_local_path != ''){
				return file_exists($this->_local_path);
			}
			return false;
		}

		//CHECK IF THE FILE EXISTS ON THE REMOTE SERVER
		public function exists_remote(){
			if(!is_null($this->_remote_path) || $this->_remote_path != ''){
				return $this->file_exists($this->_remote_path);				
			}
			return false;
		}

		public function convert_to_pdf(){

			//COPY THE FILE IF NEEDED
			if($this->_location == 'local' || is_null($this->_remote_path)){
				$this->copy_to_remote();
			}

			if($this->_extension != 'pdf'){

				//SET THE NEW FILE
				$new_file = str_replace($this->_extension, 'pdf', $this->_remote_path);

				//CONVERT THE FILE TO PDF
				$this->_parent->exec("libreoffice --headless --convert-to pdf {$this->_remote_path} --outdir {$this->_remote_temp}");

				//DELETE THE TEMP FILE
				$this->_parent->delete_file($this->_remote_path);

				//SET THE NEW REMOTE PATH
				$this->_remote_path = $new_file;

				//SET THE EXTENSION TO PDF
				$this->_extension 	= 'pdf';
			}
			
			return $this;
		}

		public function convert_pdf_to_pdf(){

			if($this->_extension == 'pdf'){

				//COPY THE FILE IF NEEDED
				if($this->_location == 'local' || is_null($this->_remote_path)){
					$this->copy_to_remote();
				}

				//COPY THE FILE TO THE PDF SERVER
				$remote_file = $this->_parent->create_unique_file($this->_remote_temp, "name.pdf", true);

				//CONVERT THE PDF INTO SOMETHING GUARENTEED TO BE READ BY WFM
				$this->_parent->exec("convert -density 200 {$this->_remote_path} -quality 100 -compress zip {$remote_file} > /dev/null 2>/dev/null &");

				//DELETE THE TEMP FILE
				$this->_parent->delete_file($this->_remote_path);

				//SET THE NEW NAME
				$this->_remote_path = $remote_file;
			}

			return $this;
		}

		//COPY THE FILE TO THE REMOTE SERVER
		public function copy_to_remote($directory = false, $rename = true){

			
			if(!$directory){
				
				$directory = $this->_remote_temp;
			}

			
			if($this->_location == 'remote' && !is_null($this->_remote_path)) return $this;

			//COPY AND USE THE SAME NAME
			if($rename === false){

				//SET THE REMOTE NAME
				$name = basename($this->_local_path);
				if(!isset(pathinfo($this->_local_path)['extension'])){
					$name .= '.'.$this->_extension;
				}

				//COPY THE FILE TO THE REMOTE FOLDER
				$this->_remote_path = $this->_parent->copy_to_remote($name, $directory);

				//SET THE LOCATION
				$this->_location = 'remote';

				//SEND BACK THE REMOTE PATH
				return $this;
			}

			//COPY AND USE A UNIQUE NAME
			elseif($rename === true){

				//NO EXTENSION WAS FOUND SO GET THE CONTENT AND FORCE THE EXTENSION
				if(!isset(pathinfo($this->_local_path)['extension'])){
					$content 			= file_get_contents($this->_local_path);
					$file_name 			= 'name.'.$this->_extension;
					$this->_remote_path = $this->_parent->create_unique_file($directory, $file_name, $content);
				}

				//CREATE THE REMOTE FILE
				else{				
					$this->_remote_path = $this->_parent->create_unique_file($directory, $this->_local_path);
				}				

				//SET THE LOCATION
				$this->_location = 'remote';			

				//SEND BACK THE REMOTE PATH
				return $this;
			}

			//COPY AND USE A SPECIFIC NAME
			$this->_remote_path = $this->_parent->copy_to_remote($rename, $directory);

			//SET THE LOCATION
			$this->_location = 'remote';

			//SEND BACK THE REMOTE PATH
			return $this;
		}

		public function copy_to_local($local_directory = false){

			if(!$local_directory){
				$local_directory = $this->_local_temp;
			}
			
			//COPY THE FILE TO THE LOCAL SERVER
			$this->_local_path = $this->_parent->copy_to_local($this->_remote_path, $local_directory);

			//SET THE LOCATION
			$this->_location = 'local';

			//SEND BACK THE LOCAL PATH
			return $this;
		}

		public function move_to_remote($directory = false, $rename = true){

			if(!$directory){
				$directory = $this->_remote_directory;
			}

			//COPY THE FILE TO REMOTE
			$this->copy_to_remote($directory, $rename);

			//DELETE THE LOCAL FILE
			unlink($this->_local_path);

			//RESET THE LOCAL PATH
			$this->_local_path = null;

			//SET THE LOCATION
			$this->_location = 'remote';

			//SEND BACK THE REMOTE FILE
			return $this;
		}

		public function move_to_local($file_path = false){			

			//SeT THE FILE PATH IF IT WAS NOT PROVIDED
			if(!$file_path){
				$file_path = $this->create_file_name($this->_local_temp, $this->_extension);
			}

			//MOVE THE FILE TO LOCAL SERVER
			$this->_local_path = $this->_parent->move_to_local($this->_remote_path, $file_path);

			//CLEAR THE REMOTE PATH
			$this->_remote_path = null;

			//SET THE LOCATION
			$this->_location = 'local';

			//SEND BACK THE LOCAL FILE
			return $this;

		}

		public function stamp($filepath){

			//GET THE STAMP
			$stamp = new SFTP_Directory_Item($filepath, $this->_parent, $this->_remote_temp, $this->_local_temp);

			//FORCE THE STAMP TO BE A PDF
			$stamp->convert_to_pdf();

			//FORCE THIS TO BE A PDF
			$this->convert_to_pdf();

			//START A NEW FiLE
			$new_file = $this->_parent->create_unique_file(dirname($this->_remote_path).'/', 'name.pdf', true);

			//STAMP THE FILE
			$this->_parent->exec("pdftk {$this->_remote_path} stamp {$stamp->_remote_path} output {$new_file}");

			//DELETE THE STAMP
			$this->_parent->delete_file($stamp->_remote_path);

			//DELETE THE TEMP FILE
			$this->_parent->delete_file($this->_remote_path);

			//SET THE NEW FILE AS THE REMOTE PATH
			$this->_remote_path = $new_file;

			//SEND BACK THE STAMPED FILE
			return $this;
		}

		public function convert_to_images(){

			//SET THE PREVIEW DIRECTORY
			$preview_dir = $this->_remote_temp.'preview/';

			//CLEAR THE PREVIEW DIRECTORY
			$this->_parent->delete_folder($preview_dir);

			//FORCE THE PREVIEW DIRECTORY
			$this->_parent->create_directory($preview_dir);

			//CONVERT THIS FILE TO A PDF
			$this->convert_to_pdf();

			//CREATE THE PREVIEW IMAGES
			$this->_parent->exec("convert -density 100 {$this->_remote_path} {$preview_dir}page-%04d.jpg");

			//START A NEW COLLECTION
			$collection = new SFTP_Directory_Collection($this->_parent, $this->_remote_temp, $this->_local_temp);

			//GET THE IMAGES
			$remote_files = $this->_parent->get_directory($preview_dir);

			$burst_files = array();
			foreach($remote_files as $file){
				$burst_files[$file] = $file;
			}

			ksort($burst_files);

			//ADD THE IMAGES TO THE COLLECTION
			$collection->push($burst_files);

			//SEND BACK THE COLLECTION
			return $collection;
		}

		public function rotate(){

			//CONVERT TO PDF IF NEEDED
			$this->convert_to_pdf();

			$this->_parent->exec("convert -rotate 90 -density 300X300 -compress lzw {$this->_remote_path} {$this->_remote_path}");

			return $this;
		}

		public function read_barcode(){

			//READ THE BARCODE
			$result = $this->_parent->exec("zbarimg {$this->_remote_path}");

			//RETURN PARSED RESULT
			if($result != ''){
				return trim(explode(':', $result)[1]);
			}

			return false;			
		}

		public function split($images = false){

			//SET THE BURST DIRECTORY
			$burst_directory 	= $this->_remote_temp.'burst/'.str_replace('.pdf', '', basename($this->_remote_path));

			//FORCE CREATE BURST DIRECTORY
			$this->_parent->create_directory($burst_directory);

			//UPLOAD THE FILE IF NEEDED
			$this->copy_to_remote();

			//BURST INTO PAGES
			$this->_parent->exec('pdftk '.$this->_remote_path.' burst output '.$burst_directory.'page-%003d.pdf');

			//GET A LIST OF ALL FILES IN THE DIRECTORY
			$burst_files = $this->_parent->get_directory($burst_directory, 'pdf');

			$tmp_arr = array();
			foreach($burst_files as $file){
				$tmp_arr[$file] = $file;
			}

			$burst_files = $tmp_arr;

			ksort($burst_files);

			//$burst_files = array_reverse($burst_files);

			//START A NEW COLLECTION
			$collection = new SFTP_Directory_Collection($this->_parent, $this->_remote_temp, $this->_local_temp);

			//ADD THE FILES
			foreach($burst_files as $file){
				$collection->push($file);
			}

			//SEND BACK THE COLLECTION
			return $collection;
		}

		public function split_by_barcode(){

			//SET THE BURST DIRECTORY
			/*$burst_directory 	= $this->_remote_temp.'burst/'.str_replace('.pdf', '', basename($this->_remote_path));

			//FORCE CREATE BURST DIRECTORY
			$this->_parent->create_directory($burst_directory);

			//UPLOAD THE FILE IF NEEDED
			$this->copy_to_remote();

			//BURST INTO PAGES
			$this->_parent->exec('pdftk '.$this->_remote_path.' burst output '.$burst_directory.'page-%003d.pdf');

			//GET A LIST OF ALL FILES IN THE DIRECTORY
			$burst_files = $this->_parent->get_directory($burst_directory, 'pdf');*/

			//SPLIT THE PDF INTO PAGES
			$burst_files = $this->split();

			//INIT THE GROUP ARRAY
			$grouped_by_barcode = array();

			//INIT THE CURRENT ORDER
			$current_barcode = 0;

			//GROUP THE FILES BY BARCODE
			foreach($burst_files as $file){

				//$file = new SFTP_Directory_Item($file, $this->_parent, $this->_remote_temp, $this->_local_temp);

				//GET THE BARCODE
				$barcode = $file->read_barcode();

				//SET THE CURRENT ORDER IF ONE WAS FOUND
				if($barcode){
					$current_barcode = $barcode;
				}

				//ADD TO THE CURRENT BARCODE GROUP
				$grouped_by_barcode[$current_barcode][] = $file;			
			}

			//INIT THE MAIN COLLECTION
			$collection = new SFTP_Directory_Collection($this->_parent, $this->_remote_temp, $this->_local_temp);

			//CYCLE THE GROUPS
			foreach($grouped_by_barcode as $barcode => $group){

				//START A SUBCOLLECTION
				$sub_collection = new SFTP_Directory_Collection($this->_parent, $this->_remote_temp, $this->_local_temp);
				
				//SET THE BARCODE
				$sub_collection->barcode = $barcode;
				
				//ADD THE FILES TO THE COLLECTION
				foreach($group as $file){
					$sub_collection->push($file);
				}

				//ADD THE SUB COLLECTION
				$collection->push($sub_collection);				
			}

			//SEND BACK THE COLLECTION
			return $collection;			
		}

		//CREATE PDFTK PDF
		public function create_pdftk_pdf($file, $info){
			
			$data="%FDF-1.2\n%���\"\n1 0 obj\n<< \n/FDF << /Fields [ ";
			
			foreach($info as $field => $val){
				if(is_array($val)){
					$data.='<</T('.$field.')/V[';
					foreach($val as $opt)
						$data.='('.trim($opt).')';
					$data.=']>>';
				}else{
					$data.='<</T('.$field.')/V('.trim($val).')>>';
				}
			}
			
			$data.="] \n/F (".$file.") /ID [ <".md5(time()).">\n] >>".
				" \n>> \nendobj\ntrailer\n".
				"<<\n/Root 1 0 R \n\n>>\n%%EOF\n";

			return $data;
		}

		//FILL OUT A PDF
		public function fill($data){

			//UPLOAD THE FILE IF NEEDED
			$this->copy_to_remote();

			//CREATE THE FDF ON THE REMOTE SERVER
			$remote_fdf_path = $this->_parent->create_unique_file($this->_remote_temp, 'data.fdf', $this->create_pdftk_pdf($this->_remote_path, $data));

			//START A BLANK PDF
			$filled_pdf_path = $this->_parent->create_unique_file($this->_remote_temp, "temp.pdf", true);

			$cmd = "pdftk {$this->_remote_path} fill_form {$remote_fdf_path} output {$filled_pdf_path} 2>&1";

			$this->_parent->exec($cmd);		

			//DELETE THE TEMP FIlES
			$this->_parent->delete_file($remote_fdf_path);
			$this->_parent->delete_file($this->_remote_path);
			$this->_remote_path = $filled_pdf_path;

			return $this;
		}
		


	}
?>
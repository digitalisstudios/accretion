<?php
	class DB_Installer_Helper extends Helper {

		public function __construct(){

		}

		public function run(){

			//LOAD THE HEADER
			$this->header();

			//CHECK THE ACTION
			if(\Request::post('action')){
				$method = \Request::post('action');
				$this->$method();
			}

			//DEFAULT TO THE INDEX
			else{
				$this->index();
			}

			//LOAD THE FOOTER
			$this->footer();
			
		}

		public function header(){

			?>
				<!DOCTYPE html>
				<html lang="en">
				  <head>
				    <meta charset="utf-8">
				    <meta http-equiv="X-UA-Compatible" content="IE=edge">
				    <meta name="viewport" content="width=device-width, initial-scale=1">
				    <title>Database Installer</title>

				    <!-- CSS -->
				    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
				    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

				    <!-- JS -->
				    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
				    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>

				    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
				    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
				    <!--[if lt IE 9]>
				      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
				      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
				    <![endif]-->
				  </head>
				  <body>
				  	<div class="container">
				  		<br><br><br><br>				    
			<?

		}

		public function footer(){
			?>
					</div>
				  </body>
				</html>
			<?
			exit;
		}

		public function index(){

			?>
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title">No Database Set Up.</h3>
					</div>
					<div class="panel-body">
						<p>You are seeing this screen because no database has been set up yet.</p>
						<p>Please select one of the options below.</p>

						<form method="post">
							<button class="btn btn-default" name="action" value="new_database">I want to create a new database.</button>
							<button class="btn btn-default" name="action" value="existing_database">I want to use an existing database.</button>
							<button class="btn btn-default" name="action" value="no_database">I dont want to use a database.</button>
						</form>
					</div>				
				</div>
			<?
		}

		public function recompile_settings($search = null, $replace = null, $setting_name, $setting_value){

			$comments = [
				'start' 				=> '//INIT THE CONFIG ARRAY',
				'default_controller' 	=> '//SET THE DEFAULT CONTROLLER',
				'model_schema' 			=> '//TELL ACCRETION WEATHER OR NOT TO AUTOMATICALLY UPDATE THE DB WITH THE MODEL SCHEMA',
				'encryption_key' 		=> '//GLOBAL ENCRYPTION KEY',
				'css' 					=> '//DEFAULT CSS',
				'js' 					=> '//DEFAULT JS FILES',
				'servers' 				=> '//SET IP ADDRESSES TO IDENTIFY THE SERVER MODE',
				'database' 				=> '//DATABASE CREDENTIALS'
			];

			$content = file_get_contents(SYSTEM_PATH.'global/Settings.php');

			if(!is_null($search)){
				$content = str_replace($search, $replace, $content);
			}
			
			$content = str_replace(['<?php', '<?', '?>'], ['','',''], $content);

			eval($content);

			$config[$setting_name] = $setting_value;

			$new_content = ['<?php', "\n", $comments['start'], '$config = [];'];

			foreach($config as $k => $v){
				$new_content[] = "";
				if(isset($comments[$k])) $new_content[] = $comments[$k];

				$v = preg_replace("/[0-9]+ \=\>/i", '', var_export($v, true));
				$new_content[] = '$config[\''.$k.'\'] = '.$v.';';
			}

			$new_content[] = '?>';

			//COLLAPSE THE CONTENT INTO A SINGLE STRING
			$content = implode("\n", $new_content);

			//UPDATE THE FILE
			$handle = fopen(SYSTEM_PATH.'global/Settings.php', 'w+'); 
			fwrite($handle, $content);
			fclose($handle);

			//TELL THE CONFIG CLASS TO COMPILE THE SETTINGS AGAIN
			$config = Config::compile_settings(true);

			//UPDATE THE FRAMEWORK GLOBALS
			Accretion::$config = Config::$data = json_decode(json_encode($config));

			//REPARSE THE SERVER SETTINGS
			Accretion::$config = Config::$data = Config::parse_server_settings();

			//SEND BACK THE DATA
			return Config::$data;

		}

		public function new_database(){

			if(\Request::post('new_database_action') == 'login'){

				//SET POST VALS
				$servername = \Request::post('host');
				$username 	= \Request::post('user');
				$password 	= \Request::post('password');
				$name 		= \Request::post('name');

				//GET THE CONNECTION
				$conn 		= new mysqli($servername, $username, $password);

				//CHECK IF CONNECTED SUCCESSFULLY
				if($conn->connect_error){
					pr($conn->connect_error);
				}

				else{
					if(strlen(trim(\Request::post('name'))) == 0){
						pr("The database name is required");
					}
					else{						

						if($conn->query("CREATE DATABASE {$name}")){

							$conn->query("GRANT ALL ON {$name}.* TO '{$username}'@'{$servername}'");

							//GET THE TYPE OF DB WE NEED TO CHANGE
							$type = str_replace('HOST', '', \Config::get('database')->main->host);

							$this->recompile_settings([$type.'HOST', $type.'NAME', $type.'USER', $type.'PASS'], [\Request::post('host'), \Request::post('name'), \Request::post('user'), \Request::post('password')], 'model_schema', true);

							$_GET['model_sync'] = true;
							\Model::get('User');

							$user = \Model::get('User')->set([
								'user_first_name' 			=> \Request::post('user'),
								'user_last_name' 			=> \Request::post('user'),
								'user_email' 				=> \Request::post('user').'@'.$_SERVER['HTTP_HOST'],
								'user_password' 			=> md5(\Request::post('password')),
								'user_role' 				=> 'admin',
							])->save();

							//REDIRECT THE USER
							\Helper::Redirect()->app();
						}
						else{
							pr("there was an error with creating the database");
						}
					}
				}
			}


			?>
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title">Create a new database.</h3>
					</div>
					<div class="panel-body">

						<p>Please enter the credentials and the name of the database.</p>
					
						<form class="form-horizontal" method="post">

							<div class="form-group">
								<label class="control-label col-md-2">Mysql Host</label>
								<div class="col-md-10">
									<input type="text" class="form-control" name="host" required value="localhost">
								</div>
							</div>

							<div class="form-group">
								<label class="control-label col-md-2">Mysql User</label>
								<div class="col-md-10">
									<input type="text" class="form-control" name="user" required>
								</div>
							</div>
							
							<div class="form-group">
								<label class="control-label col-md-2">Mysql User Password</label>
								<div class="col-md-10">
									<input type="password" class="form-control" name="password" required>
								</div>
							</div>

							<div class="form-group">
								<label class="control-label col-md-2">Name</label>
								<div class="col-md-10">
									<input type="text" class="form-control" name="name" required>
								</div>
							</div>

							<div class="form-group">
								<div class="col-md-10 col-md-offset-2">
									<input type="hidden" name="action" value="new_database">
									<button class="btn btn-primary" name="new_database_action" value="login">Create</button>
								</div>
							</div>
						</form>
					</div>				
				</div>
			<?
		}

		public function existing_database(){

			if(\Request::post('existing_database_action') == 'login'){

				$servername = \Request::post('host');
				$username 	= \Request::post('user');
				$password 	= \Request::post('password');
				$name 		= \Request::post('name');
				$conn 		= new mysqli($servername, $username, $password, $name);

				if($conn->connect_error){
					pr($conn->connect_error);
				}

				else{
					
					//GET THE TYPE OF DB WE NEED TO CHANGE
					$type = str_replace('HOST', '', \Config::get('database')->main->host);

					$this->recompile_settings([$type.'HOST', $type.'NAME', $type.'USER', $type.'PASS'], [\Request::post('host'), \Request::post('name'), \Request::post('user'), \Request::post('password')], 'model_schema', true);

					//REDIRECT THE USER
					\Helper::Redirect()->app();
				}
			}

			?>
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title">Use an existing new database.</h3>
					</div>
					<div class="panel-body">

						<p>Please enter the credentials and the name of the database.</p>
					
						<form class="form-horizontal" method="post">

							<div class="form-group">
								<label class="control-label col-md-2">Mysql Host</label>
								<div class="col-md-10">
									<input type="text" class="form-control" name="host" required value="localhost">
								</div>
							</div>

							<div class="form-group">
								<label class="control-label col-md-2">Mysql User</label>
								<div class="col-md-10">
									<input type="text" class="form-control" name="user" required>
								</div>
							</div>
							
							<div class="form-group">
								<label class="control-label col-md-2">Mysql User Password</label>
								<div class="col-md-10">
									<input type="password" class="form-control" name="password" required>
								</div>
							</div>

							<div class="form-group">
								<label class="control-label col-md-2">Name</label>
								<div class="col-md-10">
									<input type="text" class="form-control" name="name" required>
								</div>
							</div>

							<div class="form-group">
								<div class="col-md-10 col-md-offset-2">
									<input type="hidden" name="action" value="existing_database">
									<button class="btn btn-primary" name="existing_database_action" value="login">Create</button>
								</div>
							</div>
						</form>
					</div>
				</div>
			<?

		}

		public function no_database(){

			$content = json_decode(file_get_contents(SYSTEM_PATH.'server_info.loaderconfig'), true);
			$content['use_db'] = 'false';
			$content = json_encode($content);

			$handle = fopen(SYSTEM_PATH.'server_info.loaderconfig', 'w+');
			fwrite($handle, $content);
			fclose($handle);

			\Helper::Redirect()->app();

		}
	}
?>
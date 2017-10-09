<?php

	namespace Command;

	class Make extends \SparkCLI {

		public $methods = [
			'controller' 	=> "Creates a controller and the necessary view componenets needed.",
			'model' 		=> "Creates a model and asks for initial schema.",
			'command' 		=> "Creates a spark command."
		];

		public function __construct($arguments){

		}

		public function controller($arguments){

			if(!empty($arguments)){
				if(isset($arguments[0])){
					$parts = explode("/", $arguments[0]);

					if(count($parts) > 1){
						$parts_copy = $parts;
						$parts_count = count($parts_copy);
						$name = $parts_copy[$parts_count-1];
						unset($parts_copy[$parts_count-1]);
						$path = CONTROLLER_PATH.implode('/', $parts_copy).'/';
						$namespace = 'Controller\\'.implode('\\', $parts_copy);
					}
					else{
						$name = $parts[0];
						$path = CONTROLLER_PATH;
						$namespace = 'Controller';
					}

					$extends = '\\'.$namespace;

					if(!file_exists($path.$name.'.php')){

						if(!file_exists($path)){
							mkdir($path, 0777, true);
						}

						$handle = fopen($path.$name.'.php', 'w+');
						$string = "<?php

	namespace {$namespace};

	class {$name} extends {$extends}{

		public function __construct(){

		}

		public function index(){

		}

	}
?>";

						fwrite($handle, $string);
						fclose($handle);
					}

					$template_path 	= str_replace(CONTROLLER_PATH, VIEW_PATH, $path);
					$partial_path 	= $template_path.'partial/';
					$css_path 		= $template_path.'css/';
					$js_path 		= $template_path.'js/';

					if(!file_exists($template_path)){
						mkdir($template_path, 0777, true);
					}

					if(!file_exists($partial_path)){
						mkdir($partial_path, 0777, true);
					}

					if(!file_exists($css_path)){
						mkdir($css_path, 0777, true);
					}

					if(!file_exists($js_path)){
						mkdir($js_path, 0777, true);
					}

					if(!file_exists($css_path.$name.'.css')){
						$handle = fopen($css_path.$name.'.css', 'w+');
						fclose($handle);
					}

					if(!file_exists($css_path.'index.css')){
						$handle = fopen($css_path.'index.css', 'w+');
						fclose($handle);
					}

					if(!file_exists($js_path.$name.'.js')){
						$handle = fopen($js_path.$name.'.js', 'w+');
						fclose($handle);
					}

					if(!file_exists($js_path.'index.js')){
						$handle = fopen($js_path.'index.js', 'w+');
						fclose($handle);
					}

					if(!file_exists($template_path.'index.php')){
						$handle = fopen($template_path.'index.php', 'w+');
						fclose($handle);
					}
					
					echo "Controller Created.\n";
					exit;
				}
			}
			else{
				echo "No arguments were passed to create the controller.\n";
				exit;
			}
		}

		private function format_model($string){
			return substr(str_replace(' ', '_', preg_replace('/(?<!\ )[A-Z]/', ' $0', str_replace(' ', '', ucwords(str_replace('_', ' ', str_replace('-', ' ', preg_replace("/[^a-z_-]+/i", "", $string))))))), 1);
		} 

		public function model($arguments = null, $data = null, $print_data = true){

			//print_r($data);

			if(is_null($data)){
				$model_name = $this->format_model($arguments[0]);
				$table_name = strtolower($model_name);

				$this->cli_print("\n<cli:b>Model Creation Options:</cli:b>\n");

				$this->cli_print_table([
					[
						'Option' 		=> '--name',
						'Description' 	=> 'Rename the model.',
						'Example' 		=> '--name=New_Model_Name_Here'
					],
					[
						'Option' 		=> '--table_name',
						'Description' 	=> 'Specify a different table name for the model.',
						'Example' 		=> '--table_name=new_table_name_here'
					],
					[
						'Option' 		=> '--schema:add',
						'Description' 	=> 'Add a new field to the schema.',
						'Example' 		=> '--schema:add="name_of_field_here" -Type="enum(\'val1\',\'val2\')" -Default="val2"' 
					],
					[
						'Option' 		=> '--schema:edit',
						'Description' 	=> 'Edit an existing field.',
						'Example' 		=> '--schema:edit="name_of_field_here" -Type="text"'
					],
					[
						'Option' 		=> '--schema:delete',
						'Description' 	=> 'Delete a field from the schema.',
						'Example' 		=> '--schema:delete="name_of_field_here"' 
					],				
					[
						'Option' 		=> '--done',
						'Description' 	=> 'Finish and create the model.',
						'Example' 		=> ''
					],
				]);

				$data = [
					['name', $model_name],
					['table_name', $table_name],
					['schema', [
						['Name' => $table_name.'_id', 'Type' => 'int(11)', 'Default' => '', 'Null' => 'NO', 'Extra' => 'auto_increment'],
						['Name' => $table_name.'_date_created', 'Type' => 'timestamp', 'Default' => 'CURRENT_TIMESTAMP', 'Null' => 'NO', 'Extra' => ''],
					]],
				];
			}			

			if($print_data){
				$this->cli_print_table($data, false);
			}
			

			echo 'Type a command'."\n";
			$input = $this->get_input();

			if(strpos($input, "--name=") !== false){
				$model_name = trim(str_replace("--name=", '', $input));

				if(strlen($model_name) > 0){
					$model_name = $this->format_model($model_name);
					$table_name = strtolower($model_name);
					$data[0] = ['name', $model_name];
					$data[1] = ['table_name', $table_name];
				}
				else{
					$this->cli_print("Error: please provide a valid model name.");
				}
			}
			elseif(strpos($input, "--table_name=") !== false){
				$table_name = trim(str_replace("--table_name=", '', $input));

				if(strlen($table_name) > 0){
					$data[1] = ['table_name', $table_name];
				}
				else{
					$this->cli_print("Error: please provide a valid table name.");
				}

			}
			elseif(strpos($input, "--schema:add=") !== false){

				preg_match("/add=\"(.*?)\"/",$input, $m);
				if(isset($m[1])){
					$name = $m[1];
				}
				else{
					$this->cli_print("Error: please provide a valid field name.");
					$this->model(null, $data, false);
					exit;
				}

				$type = "varchar(255)";				
				preg_match("/-Type=\"(.*?)\"/",$input, $m);
				if(isset($m[1])){
					$type = $m[1];
				}
				
				$default = "";
				preg_match("/-Default=\"(.*?)\"/",$input, $m);
				if(isset($m[1])){
					$default = $m[1];
				}
				
				$null = "NO";
				preg_match("/-Null=\"(.*?)\"/",$input, $m);
				if(isset($m[1])){
					if(trim(strtoupper($m[1])) == 'YES'){
						$null = 'YES';
					}
				}
				
				$extra = "";
				preg_match("/-Extra=\"(.*?)\"/",$input, $m);
				if(isset($m[1])){
					$extra = $m[1];
				}

				$schema = $data[2][1];
				$schema[] = ['Name' => $name, 'Type' => $type, 'Default' => $default, 'Null' => $null, 'Extra' => $extra];
				$data[2][1] = $schema;

			}
			elseif(strpos($input, "--schema:edit=") !== false){

				$schema = $data[2][1];
				$edit_field = false;
				$schema_key = false;

				preg_match("/edit=\"(.*?)\"/",$input, $m);
				if(isset($m[1])){
					$name = $m[1];

					foreach($schema as $k => $field){
						if($field['Name'] == $name){
							$edit_field = $field;
							$schema_key = $k;
							break;
						}
					}
				}
				else{
					$this->cli_print("Error: please provide a valid field name.");
					$this->model(null, $data, false);
					exit;
				}

				if(!$edit_field){
					$this->cli_print("Error: please provide a valid field name.");
					$this->model(null, $data, false);
					exit;
				}

				$type = "varchar(255)";				
				preg_match("/-Type=\"(.*?)\"/",$input, $m);
				if(isset($m[1])){
					$edit_field['Type'] = $m[1];
					//$type = $m[1];
				}
				
				$default = "";
				preg_match("/-Default=\"(.*?)\"/",$input, $m);
				if(isset($m[1])){
					$edit_field['Default'] = $m[1];
					$default = $m[1];
				}
				
				preg_match("/-Null=\"(.*?)\"/",$input, $m);
				if(isset($m[1])){
					if(trim(strtoupper($m[1])) == 'YES'){
						$edit_field['Null'] = 'YES';
					}
					elseif(trim(strtoupper($m[1])) == 'NO'){
						$edit_field['Null'] = 'NO';
					}
				}

				preg_match("/-Extra=\"(.*?)\"/",$input, $m);
				if(isset($m[1])){
					$edit_field['Extra'] = $m[1];
				}

				
				$schema[$schema_key] = $edit_field;
				$data[2][1] = $schema;

			}
			elseif(strpos($input, "--schema:delete=") !== false){

				$schema = $data[2][1];
				$edit_field = false;
				$schema_key = false;

				preg_match("/delete=\"(.*?)\"/",$input, $m);
				if(isset($m[1])){
					$name = $m[1];

					foreach($schema as $k => $field){
						if($field['Name'] == $name){
							$edit_field = $field;
							$schema_key = $k;
							break;
						}
					}
				}
				else{
					$this->cli_print("Error: please provide a valid field name.");
					$this->model(null, $data, false);
					exit;
				}

				if(!$edit_field){
					$this->cli_print("Error: please provide a valid field name.");
					$this->model(null, $data, false);
					exit;
				}

				unset($schema[$schema_key]);
				$schema = array_values($schema);
				$data[2][1] = $schema;

			}
			elseif(strpos($input, "--done") !== false){

				$model_name = $data[0][1];
				$table_name = $data[1][1];
				$schema 	= $data[2][1];
				$structure 	= [];

				$structure_string = "
	public \$structure = array(\n";

				foreach($schema as $field_data){
					if($field_data['Null'] == "NO"){
						unset($field_data['Null']);
					}
					if($field_data['Extra'] == ""){
						unset($field_data['Extra']);
					}
					if($field_data['Default'] == ""){
						unset($field_data['Default']);
					}
					$name = $field_data['Name'];
					unset($field_data['Name']);
					$sub = "'".$name."'".' => ';
					ob_start();
					var_export($field_data);
					$export = ob_get_clean();
					$sub .= trim(str_replace("\n", '', $export));
					$sub .= ",";
					
					//$sub .= var_export($field_data).",";
					$structure_string .= "		".$sub."\n";
				}
				$structure_string .= "	);";

				$string = "<?php

class $model_name extends Model {

$structure_string

	public function __construct(){

	}
}

?>";

				$file_path = MODEL_PATH.$model_name.'.php';

				if(!file_exists($file_path)){
					$handle = fopen($file_path, 'w+');
					fwrite($handle, $string);
					fclose($handle);
					exit;
				}
			}

			$this->model(null, $data);
			exit;
		}

		public function command($arguments){
			if(!empty($arguments)){
				if(isset($arguments[0])){
					$name = $arguments[0];

					$path = SYSTEM_PATH.'spark/';

					if(!file_exists($path.$name.'.php')){
						$handle = fopen($path.$name.'.php', 'w+');
						$string = "<?php

	namespace Command;

	class $name extends \SparkCLI {

		public \$methods = [
			//NAME YOUR METHOD HERE AND GIVE IT A DESCRIPTION ex. 'method_name' => 'method description here',
		];

		public function __construct(\$arguments){

		}

		/*
		public function method_name($arguments){
			//DO SOMETHING HERE
		}
		*/

		
	}
?>";
						fwrite($handle, $string);
						fclose($handle);
						echo "Command Created.\n";
						exit;
					}
					else{
						echo "Command Already Exists.\n";
						exit;
					}
				}
			}
			echo "No command name passed.\n";
			exit;	
		}
	}
?>
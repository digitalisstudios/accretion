<?php

	namespace Command;

	class Schema extends \SparkCLI {

		public $methods = [
			'sync' 		=> "Updates the table schema for a model.",
		];

		public function __construct($arguments){

		}

		public function sync(){
			
			echo "\n\nTable Sync Started\n\n";

			$files = glob(APP_PATH.'model/*.php');			

			foreach($files as $file){

				//GET THE MODEL NAME
				$model_name = pathinfo($file)['filename'];				

				//TRY TO LOAD THE MODEL
				$model = \Model::get($model_name);

				//IF THE MODEL WAS LOADED
				if($model){

					//OUTPUT THE MESSAGE
					echo 'Updating '.$model_name."\n";

					//RUN THE TABLE STRUCTURE
					$model->table_structure(true);
				}
			}
			echo "\n\nDone\n\n";
		}
	}
?>
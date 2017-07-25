<?php
	
	namespace Controller;

	class Builder extends \Controller {

		public function __construct(){

			$this->require_login();
			return $this;
		}
		
		public function index(){
			
		}

		public function code_release(){
			\Helper::Code_Release();
			exit;
		}

		public function table_sync(){

			$_GET['model_sync'] = true;
			foreach(glob(MODEL_PATH.'/*.php') as $file){
				$model_name = pathinfo($file)['filename'];
				pr('Updating '.$model_name);
				\Model::get($model_name);
			}
			pr('Done');
			exit;
		}
	}
?>
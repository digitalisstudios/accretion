<?php
	class Redirect_Helper extends Helper {
		
		public function __construct($location = null, $flash = null){
			
			//IF A LOCATION WAS PASSED
			if(!is_null($location) && is_string($location) && !empty($location)){
				return $this->to($location, $flash);
			}

			//SEND BACK THIS OBJECT
			return $this;
		}

		//ADD A FLASH MESSAGE
		public function flash($message){
			
			//ADD THE FLASH MESSAGE(S)
			Helper::get('Flash')->add_flash($message);

			//SEND BACK THE OBJECT
			return $this;
		}

		//REDIRECT TO A SPECIFIC LOCATION
		public function to($location, $flash = null){

			//IF FLASH MESSAGE(S) WERE PASSED
			if(!is_null($flash)){

				//ADD THE FLASH MESSAGE(S)
				Helper::get('Flash')->add_flash($flash);
			}

			//SEND BACK THE URL IF IS AJAX
			if(Request::is_ajax()){

				\Request::return_json(['url' => $location, 'redirect' => true]);

				header('Content-type: application/json');
				die(json_encode(['url' => $location, 'redirect' => true]));
				//return ['url' => $location];
			}
			else{
				if(headers_sent()){
					echo "<script>window.location.href = '{$location}'</script>";
					exit;
				}

				//REDIRECT TO THE LOCATION
				header("Location: {$location}");
				exit;
			}

			
		}

		//REDIRECT TO THE CURRENT CONTROLLER
		public function local($location = null, $flash = null){			
			
			//BUILD THE TARGET
			$target = \Controller::format_url(WEB_APP.Accretion::$controller->controller_template_path.'/');

			//APPEND THE LOCATION IF IT WAS PASSED
			if(!is_null($location)){
				$target .= $location;
			}

			//SEND BACK THE REDIRECT
			return $this->to($target, $flash);
		}

		//REDIRECT TO ANOTHER LOCATION IN THE APP
		public function app($location = '', $flash = null){
			return $this->to(WEB_APP.$location, $flash);
		}

		//SEND BACK TO THE REFERRING PAGE (SHOULD BE REMOVED BECAUSE ->back() IS BETTER SYNTAX)
		public function from(){
			return $this->to($_SERVER['HTTP_REFERER']);
		}

		//SEND BACK TO THE REFERRING PAGE
		public function back(){
			return $this->to($_SERVER['HTTP_REFERER']);
		}
	}
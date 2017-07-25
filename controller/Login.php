<?php

	namespace Controller;

	class Login extends \Controller {

		public function __construct(){

			return $this;
		}

		public function index(){
			$this->error = false;

			if(isset($_SESSION['user_id'])){
				\Helper::Redirect()->app();
			}

			if(\Request::post('login')){				

				$email 		= trim(\DB::escape(\Request::post('email')));				
				$password 	= md5(trim(\DB::escape(\Request::post('password'))));
				$user 		= \User::find()->where("user_email = '{$email}' AND user_password = '{$password}'")->limit(1)->load();				

				if($user->count()){

					\Session::set('user_id', $user->first()->user_id);

					if($_SESSION['redirect_to'] != ""){ 

						$to = $_SESSION['redirect_to'];
						\Session::remove('redirect_to');
						\Helper::Redirect()->to($to);
					}
					else { 
						\Helper::Redirect()->app();
					}
				}
				$this->error = 'Password or email is incorrect.';
			}
		}

		

		public function logout(){
			\Session::update(function(){
				session_destroy();
				\Helper::Redirect()->app();
			});
		}

		
	}
?>
<?php

	namespace Controller;

	class Login extends \Controller {

		public function __construct(){

			return $this;
		}

		public function index(){

			//ASSUME WE ARE NOT REDIRECTING
			$redirect = false;

			//IF THE USER IS ALREADY SET REDIRECT
			if(\Auth::user()){
				$redirect = true;
			}

			//USER IS NOT SET
			else{

				if(\Request::post('login')){

					$email 		= trim(\DB::escape(\Request::post('email')));
					$password 	= \Helper::Encryption()->encrypt(trim(\DB::escape(\Request::post('password'))));
					$by 		= \Auth::by();
					$user 		= \Model::get($by->model_name)->where("`{$by->login_with}` = '{$email}' AND `{$by->login_pass}` = '{$password}'")->limit(1)->load();					

					if($user->count()){

						//UPDATE THE SESSION
						\Session::set($by->session_name, $user->first()->expose_data());

						//SET TO REDIRECT
						$redirect = true;
					}
					else{
						\Helper::Flash()->add_flash("Password or email is incorrect");
					}
				}
			}

			if($redirect){
				if($_SESSION['redirect_to'] != ""){ 
					$to = $_SESSION['redirect_to'];
					\Session::remove('redirect_to');
					\Helper::Redirect()->to($to);
				}
				else { 
					\Helper::Redirect()->app();
				}
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
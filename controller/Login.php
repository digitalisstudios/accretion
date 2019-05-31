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
					$user 		= \Model::get($by->model_name)->where("`{$by->login_with}` = '{$email}' AND `{$by->login_pass}` = '{$password}'")->single()->load();					

					if($user->loaded()){

						//UPDATE THE SESSION
						\Session::set($by->session_name, \Auth::sessionFields($user));
						\Session::set('association_id', $user->association_id);

						$hour = time() + 3600;
						setcookie('ID_ARCCOZ', $email, $hour);

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

					return	\Helper::Redirect()->to(\Auth::user()->association->appUrl());
				}
			}
		}

		public function remote(){

			\Session::update(function(){
				session_destroy();
				session_start();
			});

			$credentials 	= json_decode(\Helper::Encryption()->decrypt(base64_decode(\Request::get(3))));
			$email 			= trim(\DB::escape($credentials->user_email));
			$password 		= \Helper::Encryption()->encrypt(trim(\DB::escape($credentials->user_password)));
			$by 			= \Auth::by();
			$user 			= \Model::get($by->model_name)->where("`{$by->login_with}` = '{$email}' AND `{$by->login_pass}` = '{$password}' AND `{$by->model_key}` = '{$credentials->user_id}'")->single()->load();


			if($user->loaded()){

				//UPDATE THE SESSION
				\Session::set($by->session_name, \Auth::sessionFields($user));
				\Session::set('association_id', $user->association_id);

				$hour = time() + 3600;
				setcookie('ID_ARCCOZ', $email, $hour);

				//SET TO REDIRECT
				$redirect = true;
			}

			if(isset($credentials->redirect)) return \Helper::Redirect()->to($credentials->redirect);

			//REDIRECT THE CONTACT
			return \Helper::Redirect()->app();
		}		

		public function logout(){
			\Session::update(function(){
				session_destroy();
				\Helper::Redirect()->app();
			});
		}

		public function forgot_password(){

			$this->disableHeaderFlash = true;

			if(\Request::post('_validate')){
				$email 		= trim(\DB::escape(\Request::post('email')));
				$user 		= \User::findBy('user_email', $email);

				if($user->loaded()){					

					$password_reset_hash = _disable_filter_access(function(){

						$password_reset_hash = substr(md5(mt_rand()), 0, 7);

						while(\User::where("password_reset_hash = '{$password_reset_hash}'")->count()->load()){
							$password_reset_hash = substr(md5(mt_rand()), 0, 7);
						}

						return $password_reset_hash;
					});

					$user->set(['password_reset_hash' => $password_reset_hash])->save();

					$link = $user->association->appUrl().'login/reset-password/'.$password_reset_hash;
					$user->sendEmail("Arccoz Forgot Password", ['link' => $link], 'user_password_reset');
					\Helper::Redirect()->local('forgot-password-sent');
				}
				\Helper::Flash()->add_flash("No active account with this email was found");
			}
		}

		public function forgot_password_sent(){

		}

		public function reset_password($hash = null){

			if(is_null($hash)){
				return \Request::error(404, "This reset link is no longer valid");
			}

			$user = \User::findBy('password_reset_hash', $hash);

			if(!$user->loaded()){
				return \Request::error(404, "This reset link is no longer valid");
			}

			$this->validate = \Helper::Validate();

			if(\Request::post('_validate')){

				$r['user_password_update'] = [
							'has_caps' 		=> "The password must contain at least one capital letter", 
							'has_symbol' 	=> 'The password must contain at least one symbol (e.g. !?#%)', 
							'has_letter' 	=> 'The password must contain at least one letter',
							'has_number' 	=> 'The password must contain at least one number',
							'min[8]' 		=> "The password must be at least 8 characters long",
						];

				$r = [
					'password' => [
						'reqd' 			=> "The password is required", 
						'has_caps' 		=> "The password must contain at least one capital letter", 
						'has_symbol' 	=> 'The password must contain at least one symbol (e.g. !?#%)', 
						'has_letter' 	=> 'The password must contain at least one letter',
						'has_number' 	=> 'The password must contain at least one number',
						'min[8]' 		=> "The password must be at least 8 characters long",
					],
					'password_conf' => [
						'reqd' => "This field is required",
						'match[password]' => "The passwords do not match"
					]
				];

				if($this->validate->run(\Request::post(), $r)){
					$user->set(['user_password' => \Request::post('password'), 'password_reset_hash' => null])->save();
					\Helper::Redirect()->flash("Your password has been updated")->local();
				}
			}
		}
	}
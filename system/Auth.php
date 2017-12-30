<?php	
	/**
	 * @file Auth.php
	 * @package    AccretionFramework
	 *
	 * @license    see LICENSE.txt
	 */

	/**
	 * Base Auth class
	 *
	 * This class manages the current logged in user.
	 *
	 * @package  AccretionFramework
	 * @since    1.0.0
	 */
	class Auth extends Accretion {

		public static $user = false;

		public function __construct(){

		}

		public static function __callStatic($name, $value = array()){

			$user = Auth::user();

			if($user){
				if(Reflect::reflectable_class($user, $name, $value)){
					return Reflect::reflect_class($user, $name, $value);
				}
				else{
					if(method_exists($user, $name)){
						return $user->$name();
					}
				}
			}

			return array();
		}	

		/**
		 * User Method
		 *
		 * This method calls the current logged in user
		 *
		 * @return mixed false when user is not logged in or a User Model when they are
		 */
		public static function user(){
			
			//IF THE USER EXISTS RETURN THE USER
			if(Auth::$user){
				return Auth::$user;
			}

			//USER DOES NOT EXITS
			else{

				//GET THE CREDENTIAL CONFIG
				$by = \Auth::by();

				//IF THE SESSION AND THE MODEL EXIST
				if(isset($_SESSION[$by->session_name][$by->model_key])){

					$model = \Model::get($by->model_name);

					if($model){
						Auth::$user = $model->load($_SESSION[$by->session_name][$by->model_key]);
					}
					else{
						Auth::$user = $_SESSION[$by->session_name];
					}		
					return Auth::$user;
				}
			}
			return false;
		}

		/**
		 * by Method
		 *
		 * This method determines how to authorize user credentials
		 *
		 * @return object stdClass containing the way to authorize a user
		 */
		public static function by(){

			$res 				= new stdClass;
			$auth 				= \Config::get('Auth');
			$res->model_name 	= isset($auth->model_name) 		? $auth->model_name 	: 'User';
			$res->session_name 	= isset($auth->session_name) 	? $auth->session_name 	: 'user';
			$res->model_key 	= isset($auth->model_key) 		? $auth->model_key 		: 'user_id';
			$res->login_with 	= isset($auth->login_with) 		? $auth->login_with 	: 'user_email';
			$res->login_pass 	= isset($auth->login_pass) 		? $auth->login_pass 	: 'user_password';

			return $res;
		}
	}
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
			if(Auth::$user){
				return Auth::$user;
			}
			else{
				if(isset($_SESSION['user']['user_id'])){
					Auth::$user = Model::get('User')->orm_load($_SESSION['user']['user_id']);					
					return Auth::$user;
				}
			}
			return false;
		}

		/**
		 * Users Method
		 *
		 * This method loads all of the users that are not deleted
		 *
		 * @return object ORM_Wrapper with all of the current active users
		 */
		public static function users(){
			return Model::get('User')->where("user_delete = 'false'")->order_by("user_fname ASC")->wrap(true)->load();
		}
	}
?>
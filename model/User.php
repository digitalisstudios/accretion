<?php

	//namespace App;

	//use Model;

	class User extends Model{
		
		public $structure = array(			
			'user_id' 					=> array('Type' => "int(11)",'Extra' => "auto_increment",),
			'user_first_name' 			=> array('Type' => "varchar(255)",),
			'user_last_name' 			=> array('Type' => "varchar(255)",),
			'user_email' 				=> array('Type' => "varchar(255)",),
			'user_password' 			=> array('Type' => "varchar(255)",),
			'user_role' 				=> array('Type' => "enum('admin','user')",'Default' => "user",),
			'company_id'				=> array('Type' => "int(11)",'Null' => "YES",),
			'user_create_time' 			=> array('Type' => "timestamp",'Default' => "CURRENT_TIMESTAMP",),
			'user_delete_time' 			=> array('Type' => "timestamp",'Null' => "YES",),
		);

		public $_encrypt 		= ['user_password'];
		public $_soft_delete 	= "user_delete_time";
		public $_validate 		= [
			'user_first_name' 	=> ['reqd' => "The first name is required"],
			'user_last_name' 	=> ['reqd' => "The last name is required"],
			'user_email' 		=> ['reqd' => "The email is required"],
			'user_name'			=> ['reqd' => "The name is required"],
		];
		
		public function __construct(){

		}

		public function _relationships(){
			
		}
	}
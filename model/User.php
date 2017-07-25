<?php

	class User extends Model{
		
		public $structure = array(			
			'user_id' 					=> array('Type' => "int(11)",'Extra' => "auto_increment",),
			'user_first_name' 			=> array('Type' => "varchar(255)",),
			'user_last_name' 			=> array('Type' => "varchar(255)",),
			'user_email' 				=> array('Type' => "varchar(255)",),
			'user_password' 			=> array('Type' => "varchar(255)",),
			'user_role' 				=> array('Type' => "enum('admin','user')",'Default' => "user",),
			'user_create_time' 			=> array('Type' => "timestamp",'Default' => "CURRENT_TIMESTAMP",),
			'user_delete' 				=> array('Type' => "enum('true','false')",'Default' => "false",),
			'user_delete_time' 			=> array('Type' => "timestamp",'Null' => "YES",),
		);
		
		public function __construct(){
			
		}
	}
?>

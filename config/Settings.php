<?php		

	//INIT THE CONFIG ARRAY
	$config = [];

	//SET THE DEFAULT CONTROLLER
	$config['default_controller'] = 'Home';

	$config['model_schema'] = true;

	//GLOBAL ENCRYPTION KEY
	$config['encryption_key'] = "COMPILE_ENCRYPTION_KEY";

	//DEFAULT CSS
	$config['css'] = array(
		'app',
		'https://code.jquery.com/ui/1.10.2/themes/smoothness/jquery-ui.css',
		'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css',
		'https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/3.3.4/css/inputmask.min.css',
		'https://use.fontawesome.com/releases/v5.7.2/css/all.css',
		'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css',
	);

	//DEFAULT JS FILES
	$config['js'] = array(
		//'app',
		'https://code.jquery.com/jquery-1.12.4.js',
		'https://code.jquery.com/ui/1.10.2/jquery-ui.js',
		'https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js',
		'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js',
		'https://cdn.datatables.net/1.10.18/js/dataTables.bootstrap4.min.js',
		'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js',		
	);

	//SET IP ADDRESSES TO IDENTIFY THE SERVER MODE
	$config['servers'] = array(
		'dev' 	=> '::1',
		'prod' 	=> '::1',
		//'rel'	=> '100.0.0.1',							//ADD OTHER SERVER MODES BY NAMING THEM AND SETTING THEIR IP ADDRESS
	);

	//DATABASE CREDENTIALS
	$config['database'] = array(
		
		//THE FIRST SET OF CREDENTIALS SHOULD ALWAYS BE THE APPLICATIONS DATABASE
		'main_by_server' => array(			

			//IF WE ARE ON THE DEV SERVER
			'dev' => array(
				'host' 		=> 'COMPILE_APP_DEV_DB_HOST',
				'database' 	=> 'COMPILE_APP_DEV_DB_NAME',
				'user'		=> 'COMPILE_APP_DEV_DB_USER',
				'password'	=> 'COMPILE_APP_DEV_DB_PASS'
			),

			//IF WE ARE ON THE PRODUCTION SERVER
			'prod' => array(
				'host' 		=> 'COMPILE_APP_PROD_DB_HOST',
				'database' 	=> 'COMPILE_APP_PROD_DB_NAME',
				'user'		=> 'COMPILE_APP_PROD_DB_USER',
				'password'	=> 'COMPILE_APP_PROD_DB_PASS'
			)
		),

		//ALLOW ACCESS TO OTHER DATABASES BY ADDING THEM TO THE ARRAY
		/*'other_by_server' => array(

			//IF WE ARE ON THE DEV SERVER
			'dev' => array(
				'host' 		=> 'localhost',
				'database' 	=> 'dbname',
				'user'		=> 'root',
				'pass'		=> ''
			),

			//IF WE ARE ON THE PRODUCTION SERVER
			'prod' => array(
				'host' 		=> 'localhost',
				'database' 	=> 'dbname',
				'user'		=> 'root',
				'pass'		=> ''
			)
		),*/
	);
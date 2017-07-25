<?php		

	//INIT THE CONFIG ARRAY
	$config = array();

	//SET THE DEFAULT CONTROLLER
	$config['default_controller'] = 'Home';

	$config['model_schema'] = false;

	//GLOBAL ENCRYPTION KEY
	$config['encryption_key'] = "fa9bd819327fe7b9bd5ca31453837962bb7aee24d3e6b281d2fe66c5acd74810";

	//DEFAULT CSS
	$config['css'] = array(
		'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css',
		'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css',
		'https://code.jquery.com/ui/1.10.2/themes/smoothness/jquery-ui.css',
		'https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css',
		'https://maxcdn.bootstrapcdn.com/css/ie10-viewport-bug-workaround.css',
	);

	//DEFAULT JS FILES
	$config['js'] = array(
		'https://code.jquery.com/jquery-1.12.4.js',
		'https://code.jquery.com/ui/1.10.2/jquery-ui.js',
		'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js',
		'https://cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.3/modernizr.min.js',
	);

	//SET IP ADDRESSES TO IDENTIFY THE SERVER MODE
	$config['servers'] = array(
		'dev' 	=> 'COMPILE_DEV_SERVER_IP',
		'prod' 	=> 'COMPILE_PROD_SERVER_IP',
		//'rel'	=> '100.0.0.1',							//ADD OTHER SERVER MODES BY NAMING THEM AND SETTING THEIR IP ADDRESS
	);

	//DATABASE CREDENTIALS
	$config['database'] = array(
		
		//THE FIRST SET OF CREDENTIALS SHOULD ALWAYS BE THE APPLICATIONS DATABASE
		'main' => array(			

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
		/*'other' => array(

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
?>
<?php

	ini_set('display_errors', '1');
	error_reporting(E_ALL);

	//GET THE LOADER FILE	
	require dirname(__FILE__).'/system/Accretion.php';

	//START THE FRAMEWORK
	new Accretion(true);
?>
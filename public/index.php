<?php

/**
 * Accretion
 *
 * @package	Accrection
 * @author 	Brandon Moore <brandon@digitalisstudios.com>
 */

	//LOAD THE MAIN ACCRETION FRAMEWORK FILE
	require __DIR__.'/../system/Accretion.php';

	//INSTANTIATE ACCRETION
	$app = new Accretion();

	//ROUTE ACCRETION
	$app->route();
?>
<?php

if(!defined('EQDKP_INC'))
{
	header('HTTP/1.0 404 Not Found');
	exit;
}

$new_version    = '0.4.2.1';
$updateFunction = false;

$updateDESC = array();

global $eqdkp;
$updateSQL = array();
if($eqdkp->config['default_game'] == 'WoW')
{
	$updateDESC = array(
		'',
		'Added Config Value: dep_match',
	);
	$reloadSETT = 'settings.php';
	$updateSQL = array(
		"INSERT INTO __raidlogimport_config (config_name, config_value) VALUES ('dep_match', '0');"
	);
}

?>
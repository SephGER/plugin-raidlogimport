<?php

if(!defined('EQDKP_INC'))
{
	header('HTTP/1.0 404 Not Found');
	exit;
}

$new_version    = '0.4.3.1';
$updateFunction = false;

$updateDESC = array();

global $eqdkp;
$updateSQL = array();
if($eqdkp->config['default_game'] == 'WoW')
{
	$updateDESC = array(
		'',
		'Deleted Config Value: conf_adjustment',
	);
	$reloadSETT = 'settings.php';
	$updateSQL = array(
		"DELETE FROM __raidlogimport_config WHERE config_name = 'conf_adjustment';"
	);
}

?>
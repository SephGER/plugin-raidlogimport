<?php

if(!defined('EQDKP_INC'))
{
	header('HTTP/1.0 404 Not Found');
	exit;
}

$new_version    = '0.4.4';
$updateFunction = false;

$updateDESC = array();

global $eqdkp;
$updateDESC = array(
	'',
	'Added Config Values: use_timedkp, use_bossdkp',
);
$reloadSETT = 'settings.php';
$updateSQL = array(
	"INSERT INTO __raidlogimport_config (config_name, config_value) VALUES ('use_timedkp', '1');",
	"INSERT INTO __raidlogimport_config (config_name, config_value) VALUES ('use_bossdkp', '1');"
);

?>
<?php

if(!defined('EQDKP_INC'))
{
	header('HTTP/1.0 404 Not Found');
	exit;
}

$new_version    = '0.5.1.3';
$updateFunction = false;

$updateDESC = array(
	'',
	'Added Config Value: Auto-Minus Value = Last Raids Value',
	'Added Config Value: Auto-Minus reset raidcounter if member gains minus?'
);
$reloadSETT = 'settings.php';

$updateSQL = array(
	"INSERT INTO __raidlogimport_config (config_name, config_value) VALUES ('am_value_raids', '0');",
	"INSERT INTO __raidlogimport_config (config_name, config_value) VALUES ('am_allxraids', '0');"
);

?>
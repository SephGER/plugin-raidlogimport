<?php

if(!defined('EQDKP_INC'))
{
	header('HTTP/1.0 404 Not Found');
	exit;
}

$new_version    = '0.4.6';
$updateFunction = false;

$updateDESC = array(
	'',
	'Added Config Value: Automatic Minus',
	'Added Config Value: Number after DKP are decreased',
	'Added Config Value: Amount of DKP lost'
);
$reloadSETT = 'settings.php';

$updateSQL = array(
	"INSERT INTO __raidlogimport_config (config_name, config_value) VALUES ('auto_minus', '0');",
	"INSERT INTO __raidlogimport_config (config_name, config_value) VALUES ('am_raidnum', '3');",
	"INSERT INTO __raidlogimport_config (config_name, config_value) VALUES ('am_value', '10');",	
);

?>
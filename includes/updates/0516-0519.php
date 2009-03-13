<?php

if(!defined('EQDKP_INC'))
{
	header('HTTP/1.0 404 Not Found');
	exit;
}

$new_version    = '0.5.1.9';
$updateFunction = false;

$updateDESC = array(
	'',
	'Set PRIMARY KEY for config_name',
);
$reloadSETT = 'settings.php';

$updateSQL = array(
	"ALTER TABLE __raidlogimport_config ADD PRIMARY KEY (`config_name`);",
);


?>
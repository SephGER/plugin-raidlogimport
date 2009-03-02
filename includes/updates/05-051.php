<?php

if(!defined('EQDKP_INC'))
{
	header('HTTP/1.0 404 Not Found');
	exit;
}

$new_version    = '0.5.1';
$updateFunction = false;

$updateDESC = array(
	'',
	'Added Config Value: Member miss time'
);
$reloadSETT = 'settings.php';

$updateSQL = array(
	"INSERT INTO __raidlogimport_config (config_name, config_value) VALUES ('member_miss_time', '300');"
);

?>
<?php

if(!defined('EQDKP_INC'))
{
	header('HTTP/1.0 404 Not Found');
	exit;
}

$new_version    = '0.5.4';
$updateFunction = false;

$updateDESC = array(
	'',
	'Change Config Value of Parser: ctrt to eqdkp'
);
$reloadSETT = 'settings.php';

$updateSQL = array(
	"UPDATE __raidlogimport_config SET config_value = 'eqdkp' WHERE config_name = 'parser' AND config_value = 'ctrt';"
);

?>
<?php

if(!defined('EQDKP_INC'))
{
	header('HTTP/1.0 404 Not Found');
	exit;
}

$new_version    = '0.4.0';
$updateFunction = false;

$updateDESC = array(
	'',
	'Added Config Value: MultiDKP-Support',
	'Added Config Value: Adjustment-Parse',
	'Added Config Value: Boss/Zone-Parse'
);
$reloadSETT = 'settings.php';

$updateSQL = array(
	"INSERT INTO __raidlogimport_config (config_name, config_value) VALUES ('conf_adjustment', '".$conf_plus['pk_multidkp']."');",
	"INSERT INTO __raidlogimport_config (config_name, config_value) VALUES ('adj_parse', ': ');",
	"INSERT INTO __raidlogimport_config (config_name, config_value) VALUES ('bz_parse', ',');",
);

?>
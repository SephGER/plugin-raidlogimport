<?php

if(!defined('EQDKP_INC'))
{
	header('HTTP/1.0 404 Not Found');
	exit;
}

$new_version    = '0.4.2';
$updateFunction = false;

$updateDESC = array();

global $eqdkp;
$updateSQL = array();
if($eqdkp->config['default_game'] == 'WoW')
{
	$updateDESC = array(
		'',
		'Added Config Value: Hero',
		'Added Config Value: Non-Hero'
	);
	$reloadSETT = 'settings.php';
	$updateSQL = array(
		"INSERT INTO ".$table_prefix."importk_config (config_name, config_value) VALUES ('hero', '_25');",
		"INSERT INTO ".$table_prefix."importk_config (config_name, config_value) VALUES ('non_hero', '_10');"
	);
}

?>
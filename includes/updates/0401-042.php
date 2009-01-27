<?php

if(!defined('EQDKP_INC'))
{
	header('HTTP/1.0 404 Not Found');
	exit;
}

$new_version    = '0.4.2';
$updateFunction = false;

$updateDESC = "Added Config Value.";

global $eqdkp;
$updateSQL = array();
if($eqdkp->config['default_game'] == 'WoW')
{
	$updateSQL = array(
		"INSERT INTO ".$table_prefix."importk_config (config_name, config_value) VALUES ('hero', '_25');",
		"INSERT INTO ".$table_prefix."importk_config (config_name, config_value) VALUES ('non_hero', '_10');"
	);
}

?>
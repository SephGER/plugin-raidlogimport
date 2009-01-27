<?php

if(!defined('EQDKP_INC'))
{
	header('HTTP/1.0 404 Not Found');
	exit;
}

$new_version    = '0.4.0';
$updateFunction = false;

$updateDESC = "Added Config Values.";

$updateSQL = array(
	"INSERT INTO ".$table_prefix."importk_config (config_name, config_value) VALUES ('conf_adjustment', '".$conf_plus['pk_multidkp']."');",
	"INSERT INTO ".$table_prefix."importk_config (config_name, config_value) VALUES ('adj_parse', ': ');",
	"INSERT INTO ".$table_prefix."importk_config (config_name, config_value) VALUES ('bz_parse', ',');",
);

?>
<?php

if(!defined('EQDKP_INC'))
{
	header('HTTP/1.0 404 Not Found');
	exit;
}

$new_version    = '0.4.0.1';
$updateFunction = false;

$updateDESC = "Added Config Value.";

$updateSQL = array(
	"INSERT INTO ".$table_prefix."importk_config (config_name, config_value) VALUES ('parser', 'ctrt');"
);

?>
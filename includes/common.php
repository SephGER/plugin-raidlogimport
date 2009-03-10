<?php
 /*
  * author: hoofy
  * copyright: 2009
  */

if(!defined('EQDKP_INC'))
{
	header('HTTP/1.0 Not Found');
	exit;
}


global $eqdkp_root_path;
include_once($eqdkp_root_path.'common.php');
if(!version_compare(phpversion(), '5.0.0', '>='))
{
    message_die('This Plugin needs at least PHP-Version 5. Your Version is: '.phpversion().'.');
}
if (!$pm->check(PLUGIN_INSTALLED, 'raidlogimport') )
{
    message_die('The Raid-Log-Import plugin is not installed.');
}
require_once($eqdkp_root_path.'plugins/raidlogimport/includes/functions.php');
require_once($eqdkp_root_path.'plugins/raidlogimport/includes/rli.class.php');
$rli = new rli;

$_COOKIE = stripslashes_array($_COOKIE);
$_FILES = stripslashes_array($_FILES);
$_GET = stripslashes_array($_GET);
$_POST = stripslashes_array($_POST);
$_REQUEST = stripslashes_array($_REQUEST);

//include library
require($eqdkp_root_path.'plugins/raidlogimport/includes/libloader.inc.php');
$pC->pluginCore();

$raidlogimport = $pm->get_plugin('raidlogimport');
?>
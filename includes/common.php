<?php
 /*
 * Project:     EQdkp-Plus Raidlogimport
 * License:     Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
 * Link:		http://creativecommons.org/licenses/by-nc-sa/3.0/
 * -----------------------------------------------------------------------
 * Began:       2008
 * Date:        $Date: 2009-05-07 17:52:03 +0200 (Do, 07 Mai 2009) $
 * -----------------------------------------------------------------------
 * @author      $Author: hoofy_leon $
 * @copyright   2008-2009 hoofy_leon
 * @link        http://eqdkp-plus.com
 * @package     raidlogimport
 * @version     $Rev: 4786 $
 *
 * $Id: common.php 4786 2009-05-07 15:52:03Z hoofy_leon $
 */

if(!defined('EQDKP_INC'))
{
	header('HTTP/1.0 Not Found');
	exit;
}


global $eqdkp_root_path, $libloader;

include_once($eqdkp_root_path.'common.php');
if(!version_compare(phpversion(), '5.1.2', '>='))
{
    message_die('This Plugin needs at least PHP-Version 5.1.2. Your Version is: '.phpversion().'.');
}
if (!$pm->check(PLUGIN_INSTALLED, 'raidlogimport') ) {
    message_die('The Raid-Log-Import plugin is not installed.');
}
require_once($eqdkp_root_path.'plugins/raidlogimport/includes/functions.php');
$rli = new rli;

$raidlogimport = $pm->get_plugin('raidlogimport');
?>
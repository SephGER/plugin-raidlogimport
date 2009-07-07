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
 * $Id: 0522-053.php 4786 2009-05-07 15:52:03Z hoofy_leon $
 */

if(!defined('EQDKP_INC'))
{
	header('HTTP/1.0 404 Not Found');
	exit;
}

$new_version    = '0.5.3';
$updateFunction = false;

$updateDESC = array(
	'',
	'Added Config Value: note for attendence_start-raid',
	'Added Config Value: note for attendence_end-raid',
	'Added Config Value: note for raid per hour',
	'Added Config Value: calculation of timedkp',
	'Added Config Value: display of member_list'
);
$reloadSETT = 'settings.php';

global $user;
$updateSQL = array(
	"INSERT INTO __raidlogimport_config (config_name, config_value) VALUES ('att_note_begin', '".$user->lang['rli_att']." ".$user->lang['rli_start']."');",
	"INSERT INTO __raidlogimport_config (config_name, config_value) VALUES ('att_note_end', '".$user->lang['rli_att']." ".$user->lang['rli_end']."');",
	"INSERT INTO __raidlogimport_config (config_name, config_value) VALUES ('raid_note_time', '0');",
	"INSERT INTO __raidlogimport_config (config_name, config_value) VALUES ('timedkp_handle', '0');",
	"INSERT INTO __raidlogimport_config (config_name, config_value) VALUES ('member_display', '0');"
);

?>

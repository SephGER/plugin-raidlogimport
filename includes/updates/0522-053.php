<?php

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
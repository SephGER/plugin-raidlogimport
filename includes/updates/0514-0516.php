<?php

if(!defined('EQDKP_INC'))
{
	header('HTTP/1.0 404 Not Found');
	exit;
}

$new_version    = '0.5.1.6';
$updateFunction = "rli0514_0516";

$updateDESC = array(
	'',
	'Added Config Value: Start-DKP for members',
	'Added Config Value: Start-DKP event'
);
$reloadSETT = 'settings.php';

$updateSQL = array(
	"INSERT INTO __raidlogimport_config (config_name, config_value) VALUES ('member_start', '0');",
	"INSERT INTO __raidlogimport_config (config_name, config_value) VALUES ('member_start_event', '0');"
);

function rli0514_0516()
{
	global $db;

	$sql = "SELECT config_name, config_value FROM __raidlogimport_config WHERE config_name = 'use_bossdkp' OR config_name = 'use_timedkp';";
	$result = $db->query($sql);
	while ( $row = $db->fetch_record($result) )
	{
		$config[$row['config_name']] = $row['config_value'];
	}
	$val = $config['use_bossdkp'] + (($config['use_timedkp']) ? 2 : 0);
	$val = ($val) ? $val : 4;
	$sql = "DELETE FROM __raidlogimport_config WHERE config_name = 'use_bossdkp' OR config_name = 'use_timedkp';";
	$db->query($sql);
	$sql = "INSERT INTO __raidlogimport_config (config_name, config_value) VALUES ('use_dkp', '".$val."');";
	$db->query($sql);
}

?>
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
 * $Id: 0514-0516.php 4786 2009-05-07 15:52:03Z hoofy_leon $
 */

if(!defined('EQDKP_INC'))
{
	header('HTTP/1.0 404 Not Found');
	exit;
}

$new_version    = '0.5.5';
$updateFunction = 'add_coliseum_boss_trigger';

$updateDESC = array(
	'',
	'Added Config Value: Zone-dependent Bosstriggers',
	'Add Coliseum Triggers'
);
$reloadSETT = false;

$updateSQL = array(
	"INSERT INTO __raidlogimport_config (config_name, config_value) VALUES ('bz_dep_match', '1');",
	"INSERT INTO __raidlogimport_bz
			(bz_type, bz_string, bz_note, bz_bonus, bz_tozone, bz_sort)
		VALUES
			('zone', 'Trial of the Crusader', 'Coliseum', '5', '0', '5'),
			('zone', 'Trial of the Grand Crusader', 'Coliseum (HM)', '5', '0', '6');",
);

function add_coliseum_boss_trigger()
{
	global $db;
	$result = $db->query("SELECT bz_id, bz_string FROM __raidlogimport_bz WHERE bz_type = 'zone';");
	while( $row =  $db->fetch_record($result) ) {
		if($row['bz_string'] == 'Trial of the Crusader') {
			$norm = $row['bz_id'];
		} elseif($row['bz_string'] == 'Trial of the Grand Crusader') {
			$hm = $row['bz_id'];
		} elseif($row['bz_string'] == 'Vault of Archavon') {
			$archa = $row['bz_id'];
		}
	}
	$db->query("INSERT INTO __raidlogimport_bz
					(bz_type, bz_string, bz_note, bz_bonus, bz_tozone, bz_sort)
				VALUES
            		('boss', 'Northrend Beasts', 'Beasts (HM)', '2', '".$hm."', '0'),
            		('boss', 'Lord Jaraxxus', 'Jaraxxus (HM)', '2', '".$hm."', '1'),
            		('boss', 'Faction Champions', 'Champions (HM)', '3', '".$hm."', '2'),
            		('boss', 'Twin Val\'kyr', 'Val\'kyr (HM)', '3', '".$hm."', '3'),
            		('boss', 'Anub\'arak', 'Anub\'arak (HM)', '4', '".$hm."', '4')
            		('boss', 'Northrend Beasts', 'Beasts', '2', '".$norm."', '0'),
            		('boss', 'Lord Jaraxxus', 'Jaraxxus', '2', '".$norm."', '1'),
            		('boss', 'Faction Champions', 'Champions', '3', '".$norm."', '2'),
            		('boss', 'Twin Val\'kyr', 'Val\'kyr', '3', '".$norm."', '3'),
            		('boss', 'Anub\'arak', 'Anub\'arak', '4', '".$norm."', '4'),
            		('boss', 'Koralon the Flame Watcher', 'Koralon', '2', '".$archa."', '2');");
}

?>
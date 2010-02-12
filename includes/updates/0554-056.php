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

$new_version    = '0.5.6.0';
$updateFunction = 'update_bosszone_list';

$updateDESC = array(
	'',
	'Add table-column for difficulty',
	'Change Sort-column to varchar',
	'Rename tozone-column to bonus per hour (bonusph)',
);
$reloadSETT = false;

$updateSQL = array(
	"ALTER TABLE __raidlogimport_bz ADD `bz_diff` INT NULL AFTER `bz_tozone`;",
	"ALTER TABLE __raidlogimport_bz MODIFY `bz_sort` VARCHAR(6) NOT NULL DEFAULT '0.0';",
	"ALTER TABLE __raidlogimport_bz CHANGE `bz_tozone` `bz_bonusph` INT NULL",
);

function update_bosszone_list() {
	global $db, $eqdkp;
	$result = $db->query("SELECT * FROM __raidlogimport_bz ORDER BY bz_sort ASC;");
	$sqls = array();
	$icc_bosses = array('Lord Marrowgar', 'Lady Deathwhisper', 'Gunship Battle', 'Deathbringer Saurfang', 'Festergut', 'Rotface', 'Professor Putricide', 'Blood Prince Council', 'Queen Lana\'thel', 'Blood-Queen Lana\'thel', 'Valithiria Dreamwalker', 'Sindragosa', 'The Lich King');
	$sort_index = 0;
	while ( $row = $db->fetch_record($result) ) {
		$add = '';
		$ph = $row['bz_bonusph'];
		$row['bz_sort'] = $row['bz_bonusph'].".".$row['bz_sort'];
		$row['bz_bonusph'] = ($row['bz_type'] == 'zone') ? $row['bz_bonus'] : 0;
		$row['bz_bonus']  = ($row['bz_type'] == 'boss') ? $row['bz_bonus'] : 0;
		if(in_array($row['bz_string'], $icc_bosses) AND $eqdkp->config['default_game'] == 'WoW') {
			$id = $row['bz_id'];
			unset($row['bz_id']);
			$row['bz_diff'] = 4;
			$row['bz_note'] .= ' HM';
			$row['bz_sort'] = $ph.".".$sort_index;
			$sort_index++;
			$ssql = "INSERT INTO __raidlogimport_bz (`".implode("`, `", array_keys($row))."`) VALUES (";
			$max = count($row);
			$i = 1;
			foreach($row as $rowv) {
				$ssql .= "'".$db->escape($rowv)."'";
				if($i < $max) {
					$ssql .= ", ";
				}
				$i++;
			}
			$sqls[] = $ssql.");";
			$row['bz_id'] = $id;
			unset($id);
			$add = ", bz_diff = '2'";
		} elseif($row['bz_string'] == 'Trial of the Crusader') {
			$add = ", bz_diff = '".$ph."'";
		}
        $sqls[] = "UPDATE __raidlogimport_bz SET bz_sort = '".$row['bz_sort']."', bz_bonusph = '".$row['bz_bonusph']."', bz_bonus = '".$row['bonus']."'".$add." WHERE bz_id = '".$row['bz_id']."';";
    }
    foreach($sqls as $sql) {
    	$db->query($sql);
    }
}
?>
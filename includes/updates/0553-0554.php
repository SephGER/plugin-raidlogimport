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

$new_version    = '0.5.5.4';
$updateFunction = 'add_icc_boss_trigger';

$updateDESC = array(
	'',
);
$reloadSETT = 'settings.php';

$updateSQL = array(
);

function add_icc_boss_trigger()
{
	global $db, $eqdkp;
	if(strtolower($eqdkp->config['default_game']) == 'wow' AND $eqdkp->config['game_language'] == 'en') {
		$db->query("INSERT INTO __raidlogimport_bz (bz_type, bz_string, bz_note, bz_bonus, bz_tozone, bz_sort) VALUES ('zone', 'Icecrown Citadel', 'Icecrown', '5', '2', '8');");
		$id = $db->sql_lastid();
		$db->query("INSERT INTO __raidlogimport_bz
						(bz_type, bz_string, bz_note, bz_bonus, bz_tozone, bz_sort)
					VALUES
						('boss', 'Lord Marrowgar', 'Marrowgar', '2', '".$id."', '0'),
						('boss', 'Lady Deathwhisper', 'Deathwhisper', '2', '".$id."', '1'),
						('boss', 'Gunship Battle', 'Gunship', '2', '".$id."', '2'),
						('boss', 'Deathbringer Saurfang', 'Saurfang', '2', '".$id."', '3'),
						('boss', 'Festergut', 'Festergut', '2', '".$id."', '4'),
						('boss', 'Rotface', 'Rotface', '2', '".$id."', '5'),
						('boss', 'Professor Putricide', 'Putricide', '2', '".$id."', '6'),
						('boss', 'Blood Prince Council', 'Blood Council', '2', '".$id."', '7'),
						('boss', 'Queen Lana\'thel', 'Lana\'thel', '2', '".$id."', '8'),
						('boss', 'Valithiria Dreamwalker', 'Dreamwalker', '2', '".$id."', '9'),
						('boss', 'Sindragosa', 'Sindragosa', '2', '".$id."', '10'),
						('boss', 'The Lich King', 'Arthas', '2', '".$id."', '11');");
	} elseif(strtolower($eqdkp->config['default_game']) == 'wow' AND $eqdkp->config['game_language'] == 'de') {
		$db->query("INSERT INTO __raidlogimport_bz (bz_type, bz_string, bz_note, bz_bonus, bz_tozone, bz_sort) VALUES ('zone', 'Icecrown Citadel', 'Eiskrone', '5', '2', '8');");
		$id = $db->sql_lastid();
		$db->query("INSERT INTO __raidlogimport_bz
						(bz_type, bz_string, bz_note, bz_bonus, bz_tozone, bz_sort)
					VALUES 
						('boss', 'Lord Marrowgar', 'Mark\'gar', '2', '".$id."', '0'),
						('boss', 'Lady Deathwhisper', 'Todeswisper', '2', '".$id."', '1'),
						('boss', 'Gunship Battle', 'Kanonenschiff', '2', '".$id."', '2'),
						('boss', 'Deathbringer Saurfang', 'Saurfang', '2', '".$id."', '3'),
						('boss', 'Festergut', 'Fauldarm', '2', '".$id."', '4'),
						('boss', 'Rotface', 'Modermiene', '2', '".$id."', '5'),
						('boss', 'Professor Putricide', 'Seuchenmord', '2', '".$id."', '6'),
						('boss', 'Blood Prince Council', 'Rat des Blutes', '2', '".$id."', '7'),
						('boss', 'Queen Lana\'thel', 'Lana\'thel', '2', '".$id."', '8'),
						('boss', 'Valithiria Dreamwalker', 'Traumwandler', '2', '".$id."', '9'),
						('boss', 'Sindragosa', 'Sindragosa', '2', '".$id."', '10'),
						('boss', 'The Lich King', 'Arthas', '2', '".$id."', '11');");
	}
}

?>
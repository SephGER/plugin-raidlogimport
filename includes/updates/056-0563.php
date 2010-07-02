<?php
 /*
 * Project:     EQdkp-Plus Raidlogimport
 * License:     Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
 * Link:		http://creativecommons.org/licenses/by-nc-sa/3.0/
 * -----------------------------------------------------------------------
 * Began:       2008-2010
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

$new_version    = '0.5.6.3';
$updateFunction = 'add_halion';

$updateDESC = array(
	'',
);
$reloadSETT = false;

$updateSQL = array(
);

function add_halion() {
	global $db, $eqdkp;
	$note = ($eqdkp->config['game_language'] == 'german') ? 'Rubin Sanktum' : 'Ruby Sanctum';
	$db->query("INSERT INTO __raidlogimport_bz (bz_type, bz_string, bz_note, bz_bonus, bz_bonusph, bz_diff, bz_sort) VALUES ('zone', 'Ruby Sanctum', '".$note."', '0', '4', '2', '10');");
	$normal_id = $db->insert_id();
	$db->query("INSERT INTO __raidlogimport_bz (bz_type, bz_string, bz_note, bz_bonus, bz_bonusph, bz_diff, bz_sort) VALUES ('zone', 'Ruby Sanctum', '".$note."', '0', '4', '4', '11');");
	$hm_id = $db->insert_id();
	$db->query("INSERT INTO __raidlogimport_bz (bz_type, bz_string, bz_note, bz_bonus, bz_bonusph, bz_diff, bz_sort) VALUES ('boss', 'Halion the Twilight Destroyer', 'Halion', '4', '0', '2', '".$normal_id.".0'), ('boss', 'Halion the Twilight Destroyer', 'Halion', '5', '0', '4', '".$hm_id.".0');");
}
?>
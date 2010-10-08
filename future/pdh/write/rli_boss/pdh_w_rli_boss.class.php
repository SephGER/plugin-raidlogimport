<?php
 /*
 * Project:     EQdkp-Plus Raidlogimport
 * License:     Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
 * Link:		http://creativecommons.org/licenses/by-nc-sa/3.0/
 * -----------------------------------------------------------------------
 * Began:       2009
 * Date:        $Date: 2009-06-09 17:20:27 +0200 (Di, 09 Jun 2009) $
 * -----------------------------------------------------------------------
 * @author      $Author: hoofy_leon $
 * @copyright   2008-2009 hoofy_leon
 * @link        http://eqdkp-plus.com
 * @package     raidlogimport
 * @version     $Rev: 5040 $
 *
 * $Id: rli.class.php 5040 2009-06-09 15:20:27Z hoofy_leon $
 */

if(!defined('EQDKP_INC')) {
	header('HTTP/1.0 Not Found');
	exit;
}
if(!class_exists('pdh_w_rli_boss')) {
class pdh_w_rli_boss extends pdh_w_generic {
	public function __construct() {
		parent::pdh_w_generic();
	}
	
	public function add($string, $note, $bonus=0.0, $timebonus=0.0, $diff=0, $tozone=0, $sort=0) {
		global $db, $pdh;
		if($db->query("INSERT INTO __raidlogimport_boss :params;", array(
						'boss_string'	=> $string,
						'boss_note'		=> $note,
						'boss_bonus'	=> $bonus,
						'boss_timebonus'=> $timebonus,
						'boss_diff'		=> $diff,
						'boss_tozone'	=> $tozone,
						'boss_sort'		=> $sort))) {
			$id = $db->insert_id();
			$pdh->enqueue_hook('rli_boss_update', array($id));
			return $id;
		}
		return false;
	}
	
	public function update($id, $string='', $note='', $bonus=false, $timebonus=false, $diff=false, $tozone=false, $sort=false) {
		global $db, $pdh;
		if($db->query("UPDATE __raidlogimport_boss SET :params WHERE boss_id = '".$id."';", array(
						'boss_string'	=> ($string == '') ? $pdh->get('rli_boss', 'string', array($id)) : $string,
						'boss_note'		=> ($note == '') ? $pdh->get('rli_boss', 'note', array($id)) : $note,
						'boss_timebonus'=> ($bonus === false) ? $pdh->get('rli_boss', 'bonus', array($id)) : $bonus,
						'boss_timebonus'=> ($timebonus === false) ? $pdh->get('rli_boss', 'timebonus', array($id)) : $timebonus,
						'boss_diff'		=> ($diff === false) ? $pdh->get('rli_boss', 'diff', array($id)) : $diff,
						'boss_diff'		=> ($tozone === false) ? $pdh->get('rli_boss', 'tozone', array($id)) : $tozone,
						'boss_sort'		=> ($sort === false) ? $pdh->get('rli_boss', 'sort', array($id)) : $sort))) {
			$pdh->enqueue_hook('rli_boss_update', array($id));
			return true;
		}
		return false;
	}
	
	public function del($id) {
		global $db, $pdh;
		if($db->query("DELETE FROM __raidlogimport_boss WHERE boss_id = '".$id."';")) {
			$pdh->enqueue_hook('rli_boss_update', array($id));
			return true;
		}
		return false;
	}
}
}
?>
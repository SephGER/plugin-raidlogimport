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
if(!class_exists('pdh_w_rli_zone')) {
class pdh_w_rli_zone extends pdh_w_generic {
	public function __construct() {
		parent::pdh_w_generic();
	}
	
	public function add($string, $note, $timebonus=0.0, $diff=0, $sort=0) {
		global $db, $pdh;
		if($db->query("INSERT INTO __raidlogimport_zone :params", array(
						'zone_string'	=> $string,
						'zone_note'		=> $note,
						'zone_timebonus'=> $timebonus,
						'zone_diff'		=> $diff,
						'zone_sort'		=> $sort))) {
			$id = $db->insert_id();
			$pdh->enqueue_hook('rli_zone_update', array($id));
			return $id;
		}
		return false;
	}
	
	public function update($id, $string='', $note='', $timebonus=false, $diff=false, $sort=false) {
		global $db, $pdh;
		if($db->query("UPDATE __raidlogimport_zone SET :params WHERE zone_id = '".$id."';", array(
						'zone_string'	=> ($string == '') ? $pdh->get('rli_zone', 'string', array($id)) : $string,
						'zone_note'		=> ($note == '') ? $pdh->get('rli_zone', 'note', array($id)) : $note,
						'zone_timebonus'=> ($timebonus === false) ? $pdh->get('rli_zone', 'timebonus', array($id)) : $timebonus,
						'zone_diff'		=> ($diff === false) ? $pdh->get('rli_zone', 'diff', array($id)) : $diff,
						'zone_sort'		=> ($sort === false) ? $pdh->get('rli_zone', 'sort', array($id)) : $sort))) {
			$pdh->enqueue_hook('rli_zone_update', array($id));
			return true;
		}
		return false;
	}
	
	public function del($id) {
		global $db, $pdh;
		if($db->query("DELETE FROM __raidlogimport_zone WHERE zone_id = '".$id."';")) {
			$pdh->enqueue_hook('rli_zone_update', array($id));
			return true;
		}
		return false;
	}
}
}
?>
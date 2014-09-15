<?php
 /*
 * Project:     EQdkp-Plus Raidlogimport
 * License:     Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
 * Link:		http://creativecommons.org/licenses/by-nc-sa/3.0/
 * -----------------------------------------------------------------------
 * Began:       2009
 * Date:        $Date$
 * -----------------------------------------------------------------------
 * @author      $Author$
 * @copyright   2008-2009 hoofy_leon
 * @link        http://eqdkp-plus.com
 * @package     raidlogimport
 * @version     $Rev$
 *
 * $Id$
 */

if(!defined('EQDKP_INC')) {
	header('HTTP/1.0 Not Found');
	exit;
}
if(!class_exists('pdh_w_rli_item')) {
class pdh_w_rli_item extends pdh_w_generic {
	public static function __shortcuts() {
		$shortcuts = array('pdh', 'db');
		return array_merge(parent::$shortcuts, $shortcuts);
	}
	
	public function add($item_id, $event_id, $itempool_id) {
		if($item_id <= 0 || $event_id <= 0 || $itempool_id <= 0) return false;
		if($this->pdh->get('rli_item', 'itempool', array($item_id, $event_id))) $this->delete($item_id, $event_id);
		$objQuery = $this->db->prepare("INSERT INTO __raidlogimport_item2itempool :p;")->set(array('item_id' => $item_id, 'event_id' => $event_id, 'itempool_id' => $itempool_id))->execute();
		
		if($objQuery) {
			$this->pdh->enqueue_hook('rli_item_update');
			return true;
		}
		return false;
	}
	
	public function delete($item_id, $event_id) {
		$objQuery = $this->db->prepare("DELETE FROM __raidlogimport_item2itempool WHERE event_id = ? AND item_id = ?;")->execute($event_id, $item_id);
		
		if($objQuery) {
			$this->pdh->enqueue_hook('rli_item_update');
			return true;
		}
		return false;
	}
}
}

?>
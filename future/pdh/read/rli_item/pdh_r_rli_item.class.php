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
if(!class_exists('pdh_r_rli_item')) {
class pdh_r_rli_item extends pdh_r_generic {
	private $data = array();
	public $hooks = array('rli_item_update');
	
	public function init() {
		global $pdc, $db, $core;
		$this->data = $pdc->get('pdh_rli_item');
		if(!$this->data) {
			$sql = "SELECT item_id, itempool_id, event_id FROM __raidlogimport_item2itempool;";
			if($result = $db->query($sql)) {
				while($row = $db->fetch_record($result)) {
					$this->data[$row['item_id']][$row['event_id']] = $row['itempool_id'];
				}
			} else {
				$this->data = array();
				return false;
			}
			$db->free_result($result);
			$pdc->put('pdh_rli_item', $this->data, null);
		}
		return true;
	}
	
	public function reset() {
		global $pdc;
		unset($this->data);
		$pdc->del('pdh_rli_item');
		$this->init();
	}
	
	public function get_id_list() {
		return array_keys($this->data);
	}
	
	public function get_itempool($item_id, $event_id) {
		if(!isset($this->data[$item_id]) && !isset($this->data[$item_id][$event_id])) return false;
		return $this->data[$item_id][$event_id];
	}
}
}
?>
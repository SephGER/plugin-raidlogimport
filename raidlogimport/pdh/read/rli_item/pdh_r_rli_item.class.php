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
if(!class_exists('pdh_r_rli_item')) {
class pdh_r_rli_item extends pdh_r_generic {
	public static function __shortcuts() {
		$shortcuts = array('pdc', 'db');
		return array_merge(parent::$shortcuts, $shortcuts);
	}

	private $data = array();
	public $hooks = array('rli_item_update');
	
	public function init() {
		$this->data = $this->pdc->get('pdh_rli_item');
		if(!$this->data) {
			$sql = "SELECT item_id, itempool_id, event_id FROM __raidlogimport_item2itempool;";
			$objQuery = $this->db->query($sql);
			if ($objQuery){
				while($row = $objQuery->fetchAssoc()) {
					$this->data[$row['item_id']][$row['event_id']] = $row['itempool_id'];
				}
			} else {
				$this->data = array();
				return false;
			}
			
			$this->pdc->put('pdh_rli_item', $this->data, null);
		}
		return true;
	}
	
	public function reset() {
		unset($this->data);
		$this->pdc->del('pdh_rli_item');
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
if(version_compare(PHP_VERSION, '5.3.0', '<')) registry::add_const('short_pdh_r_rli_item', pdh_r_rli_item::__shortcuts());
?>
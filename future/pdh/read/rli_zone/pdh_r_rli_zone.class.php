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
if(!class_exists('pdh_r_rli_zone')) {
class pdh_r_rli_zone extends pdh_r_generic {
	private $data = array();
	public $hooks = array('rli_zone_update');
	
	public function init() {
		global $pdc, $db, $core;
		$this->data = $pdc->get('pdh_rli_zone');
		if(!$this->data) {
			$sql = "SELECT zone_id, zone_string, zone_event, zone_timebonus, zone_diff, zone_sort FROM __raidlogimport_zone;";
			if($result = $db->query($sql)) {
				while($row = $db->fetch_record($result)) {
					$this->data[$row['zone_id']]['string'] = explode($core->config('bz_parse', 'raidlogimport'), $row['zone_string']);
					$this->data[$row['zone_id']]['event'] = $row['zone_event'];
					$this->data[$row['zone_id']]['timebonus'] = $row['zone_timebonus'];
					$this->data[$row['zone_id']]['diff'] = $row['zone_diff'];
					$this->data[$row['zone_id']]['sort'] = $row['zone_sort'];
				}
			} else {
				$this->data = array();
				return false;
			}
			$db->free_result($result);
			$pdc->put('pdh_rli_zone', $this->data, null);
		}
		return true;
	}
	
	public function reset() {
		global $pdc;
		unset($this->data);
		$pdc->del('pdh_rli_zone');
		$this->init();
	}
	
	public function get_id_list() {
		return array_keys($this->data);
	}
	
	public function get_id_string($string, $diff) {
		foreach($this->data as $id => $data) {
			if(in_array($string, $data['string']) AND ($diff == 0 OR $data['diff'] == 0 OR $diff == $data['diff'])) {
				return $id;
			}
		}
		return false;
	}
	
	public function get_string($id) {
		return $this->data[$id]['string'];
	}
	
	public function get_html_string($id) {
		return implode(', ', $this->data[$id]['string']).$this->get_html_diff($id);
	}
	
	public function get_event($id) {
		return $this->data[$id]['event'];
	}
	
	public function get_html_event($id, $with_icon=true) {
		global $pdh, $game;
		$icon = ($with_icon) ? $game->decorate('events', array($this->get_event($id))) : '';
		return $icon.$pdh->get('event', 'name', array($this->get_event($id)));
	}
	
	public function get_eventbystring($string) {
		foreach($this->data as $id => $data) {
			if(in_array($string, $data['string'])) {
				return $this->get_event($id);
			}
		}
		return false;
	}
	
	public function get_timebonus($id) {
		return $this->data[$id]['timebonus'];
	}
	
	public function get_diff($id) {
		return $this->data[$id]['diff'];
	}
	
	public function get_html_diff($id) {
		global $user;
		return ($this->get_diff($id)) ? ' &nbsp; ('.$user->lang('diff_'.$this->get_diff($id)).')' : '';
	}
	
	public function get_sort($id) {
		return $this->data[$id]['sort'];
	}
}
}
?>
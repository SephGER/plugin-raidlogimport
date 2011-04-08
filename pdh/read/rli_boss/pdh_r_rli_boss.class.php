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
if(!class_exists('pdh_r_rli_boss')) {
class pdh_r_rli_boss extends pdh_r_generic {
	private $data = array();
	public $hooks = array('rli_boss_update');
	
	public function init() {
		global $pdc, $db, $core;
		$this->data = $pdc->get('pdh_rli_boss');
		if(!$this->data) {
			$sql = "SELECT boss_id, boss_string, boss_note, boss_bonus, boss_timebonus, boss_diff, boss_tozone, boss_sort FROM __raidlogimport_boss;";
			if($result = $db->query($sql)) {
				while($row = $db->fetch_record($result)) {
					$this->data[$row['boss_id']]['string'] = explode($core->config('bz_parse', 'raidlogimport'), $row['boss_string']);
					$this->data[$row['boss_id']]['note'] = $row['boss_note'];
					$this->data[$row['boss_id']]['bonus'] = $row['boss_bonus'];
					$this->data[$row['boss_id']]['timebonus'] = $row['boss_timebonus'];
					$this->data[$row['boss_id']]['diff'] = $row['boss_diff'];
					$this->data[$row['boss_id']]['sort'] = $row['boss_sort'];
					$this->data[$row['boss_id']]['tozone'] = $row['boss_tozone'];
				}
			} else {
				$this->data = array();
				return false;
			}
			$db->free_result($result);
			$pdc->put('pdh_rli_boss', $this->data, null);
		}
		return true;
	}
	
	public function reset() {
		global $pdc;
		unset($this->data);
		$pdc->del('pdh_rli_boss');
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
		return implode(', ', $this->get_string($id)).$this->get_html_diff($id);
	}
	
	public function get_note($id) {
		return $this->data[$id]['note'];
	}
	
	public function get_html_note($id, $with_icon=true) {
		global $core, $pdh, $game;
		if($core->config('event_boss', 'raidlogimport') AND is_numeric($id)) {
			$icon = ($with_icon) ? $game->decorate('events', array($this->get_note($id))) : '';
			return $icon.$pdh->get('event', 'name', array($this->get_note($id)));
		}
		$suffix = ($this->get_diff($id) AND $core->config('dep_match', 'raidlogimport') AND $game->get_game() == 'wow') ? $core->config('diff_'.$this->get_diff($id), 'raidlogimport') : '';
		return $this->get_note($id).$suffix;
	}
	
	public function get_bonus($id) {
		return $this->data[$id]['bonus'];
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
	
	public function get_tozone($id) {
		return $this->data[$id]['tozone'];
	}
	
	public function get_sort($id) {
		return $this->data[$id]['sort'];
	}
	
	public function get_bosses2zone($zone_id) {
		$bosses = array();
		foreach($this->data as $id => $data) {
			if(intval($data['tozone']) === intval($zone_id)) {
				$bosses[] = $id;
			}
		}
		return $bosses;
	}
}
}
?>
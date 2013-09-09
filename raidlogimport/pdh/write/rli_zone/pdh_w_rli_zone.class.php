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
if(!class_exists('pdh_w_rli_zone')) {
class pdh_w_rli_zone extends pdh_w_generic {
	public static function __shortcuts() {
		$shortcuts = array('pdh', 'config', 'db2');
		return array_merge(parent::$shortcuts, $shortcuts);
	}
	
	public function add($string, $event, $timebonus=0.0, $diff=0, $sort=0) {
		if(!$string OR !$event) {
			return false;
		}
		$objQuery = $this->db2->prepare("INSERT INTO __raidlogimport_zone :p")->set(array(
						'zone_string'	=> $string,
						'zone_event'	=> $event,
						'zone_timebonus'=> $timebonus,
						'zone_diff'		=> $diff,
						'zone_sort'		=> $sort,
						'zone_active'	=> '1'))->execute();
		
		
		if($objQuery) {
			$id = $objQuery->insertId;
			$this->pdh->enqueue_hook('rli_zone_update', array($id));
			$log_action = array(
				'{L_ID}'			=> $id,
				'{L_BZ_TYPE}'   	=> '{L_BZ_ZONE_S}',
				'{L_BZ_STRING}'		=> $string,
				'{L_EVENT}'			=> $this->pdh->get('event', 'name', array($event)),
				'{L_BZ_TIMEBONUS}'	=> $timebonus,
				'{L_BZ_DIFF}' 		=> $diff
			);
			$this->log_insert('action_raidlogimport_bz_add', $log_action, true, 'raidlogimport' );
			return $id;
		}
		return false;
	}
	
	public function update($id, $string='', $event=false, $timebonus=false, $diff=false, $sort=false) {
		if(!$id) {
			return false;
		}
		$old = array(
			'string'	=> implode($this->config->get('bz_parse', 'raidlogimport'), $this->pdh->get('rli_zone', 'string', array($id))),
			'event'		=> $this->pdh->get('rli_zone', 'event', array($id)),
			'timebonus'	=> $this->pdh->get('rli_zone', 'timebonus', array($id)),
			'diff'		=> $this->pdh->get('rli_zone', 'diff', array($id)),
			'sort'		=> $this->pdh->get('rli_zone', 'sort', array($id))
		);
		$data = array(
			'zone_string'	=> ($string == '') ? $old['string'] : $string,
			'zone_event'	=> ($event === false) ? $old['event'] : $event,
			'zone_timebonus'=> ($timebonus === false) ? $old['timebonus'] : $timebonus,
			'zone_diff'		=> ($diff === false) ? $old['diff'] : $diff,
			'zone_sort'		=> ($sort === false) ? $old['sort'] : $sort
		);
		if($this->changed($old, $data)) {
			$objQuery = $this->db2->prepare("UPDATE __raidlogimport_zone :p WHERE zone_id = ?")->set($data)->execute($id);
				
			if($objQuery) {
				$this->pdh->enqueue_hook('rli_zone_update', array($id));
				$log_action = array(
					'{L_ID}'			=> $id,
					'{L_BZ_TYPE}'   	=> '{L_BZ_ZONE_S}',
					'{L_BZ_STRING}'		=> $old['string']." => ".$string,
					'{L_EVENT}'			=> $this->pdh->get('event', 'name', array($old['event']))." => ".$this->pdh->get('event', 'name', array($event)),
					'{L_BZ_TIMEBONUS}'	=> $old['timebonus']." => ".$timebonus,
					'{L_BZ_DIFF}' 		=> $old['diff']." => ".$diff,
				);
				$this->log_insert('action_raidlogimport_bz_upd', $log_action, true, 'raidlogimport' );
				return $id;
			}
		} else {
			return $id;
		}
		return false;
	}
	
	public function del($id) {
		if(!$id) {
			return false;
		}
		$old = array(
			'string'	=> implode(', ', $this->pdh->get('rli_zone', 'string', array($id))),
			'event'		=> $this->pdh->get('rli_zone', 'event', array($id)),
			'timebonus'	=> $this->pdh->get('rli_zone', 'timebonus', array($id)),
			'diff'		=> $this->pdh->get('rli_zone', 'diff', array($id)),
			'sort'		=> $this->pdh->get('rli_zone', 'sort', array($id))
		);
		$objQuery = $this->db2->prepare("DELETE FROM __raidlogimport_zone WHERE zone_id = ?;")->execute($id);
		
		if($objQuery) {
			$this->pdh->enqueue_hook('rli_zone_update', array($id));
			$log_action = array(
				'{L_ID}'			=> $id,
				'{L_BZ_TYPE}'   	=> '{L_BZ_ZONE_S}',
				'{L_BZ_STRING}'		=> $old['string'],
				'{L_EVENT}'			=> $this->pdh->get('event', 'name', array($old['event'])),
				'{L_BZ_TIMEBONUS}'	=> $old['timebonus'],
				'{L_BZ_DIFF}' 		=> $old['diff'],
			);
			$this->log_insert('action_raidlogimport_bz_del', $log_action, true, 'raidlogimport' );
			return $id;
		}
		return false;
	}
	
	public function switch_inactive($zone_id) {
		$active = ($this->pdh->get('rli_zone', 'active', array($zone_id))) ? '0' : '1';
		$objQuery = $this->db2->prepare("UPDATE __raidlogimport_zone SET zone_active = ? WHERE zone_id = ?")->execute($active, $zone_id);
		
		if($objQuery) {
			$bosses = $this->pdh->get('rli_boss', 'bosses2zone', array($zone_id));
			foreach($bosses as $boss_id) {
				$this->pdh->put('rli_boss', 'set_active', array($boss_id, $active));
			}
			$this->pdh->enqueue_hook('rli_zone_update', array($zone_id));
			return true;
		}
		return false;
	}
	
	private function changed($array1, $array2) {
		foreach($array1 as $val) {
			if(!in_array($val, $array2, true)) {
				return true;
			}
		}
		return false;
	}
}
}
if(version_compare(PHP_VERSION, '5.3.0', '<')) registry::add_const('short_pdh_w_rli_zone', pdh_w_rli_zone::__shortcuts());
?>
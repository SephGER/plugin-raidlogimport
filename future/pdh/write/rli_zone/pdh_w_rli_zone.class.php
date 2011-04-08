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
	
	public function add($string, $event, $timebonus=0.0, $diff=0, $sort=0) {
		global $db, $pdh, $user;
		if(!$string OR !$event) {
			return false;
		}
		if($db->query("INSERT INTO __raidlogimport_zone :params", array(
						'zone_string'	=> $string,
						'zone_event'	=> $event,
						'zone_timebonus'=> $timebonus,
						'zone_diff'		=> $diff,
						'zone_sort'		=> $sort))) {
			$id = $db->insert_id();
			$pdh->enqueue_hook('rli_zone_update', array($id));
			$log_action = array(
				'{L_ID}'			=> $id,
				'{L_BZ_TYPE}'   	=> '{L_BZ_ZONE_S}',
				'{L_BZ_STRING}'		=> $string,
				'{L_EVENT}'			=> $pdh->get('event', 'name', array($event)),
				'{L_BZ_TIMEBONUS}'	=> $timebonus,
				'{L_BZ_DIFF}' 		=> $diff
			);
			$this->log_insert('action_raidlogimport_bz_add', $log_action, true, 'raidlogimport' );
			return $id;
		}
		return false;
	}
	
	public function update($id, $string='', $event=false, $timebonus=false, $diff=false, $sort=false) {
		global $db, $pdh, $core;
		if(!$id) {
			return false;
		}
		$old = array(
			'string'	=> implode($core->config('bz_parse', 'raidlogimport'), $pdh->get('rli_zone', 'string', array($id))),
			'event'		=> $pdh->get('rli_zone', 'event', array($id)),
			'timebonus'	=> $pdh->get('rli_zone', 'timebonus', array($id)),
			'diff'		=> $pdh->get('rli_zone', 'diff', array($id)),
			'sort'		=> $pdh->get('rli_zone', 'sort', array($id))
		);
		$data = array(
			'zone_string'	=> ($string == '') ? $old['string'] : $string,
			'zone_event'	=> ($event === false) ? $old['event'] : $event,
			'zone_timebonus'=> ($timebonus === false) ? $old['timebonus'] : $timebonus,
			'zone_diff'		=> ($diff === false) ? $old['diff'] : $diff,
			'zone_sort'		=> ($sort === false) ? $old['sort'] : $sort
		);
		if($this->changed($old, $data)) {
			if($db->query("UPDATE __raidlogimport_zone SET :params WHERE zone_id = '".$id."';", $data)) {
				$pdh->enqueue_hook('rli_zone_update', array($id));
				$log_action = array(
					'{L_ID}'			=> $id,
					'{L_BZ_TYPE}'   	=> '{L_BZ_ZONE_S}',
					'{L_BZ_STRING}'		=> $old['string']." => ".$string,
					'{L_EVENT}'			=> $pdh->get('event', 'name', array($old['event']))." => ".$pdh->get('event', 'name', array($event)),
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
		global $db, $pdh;
		if(!$id) {
			return false;
		}
		$old = array(
			'string'	=> implode(', ', $pdh->get('rli_zone', 'string', array($id))),
			'event'		=> $pdh->get('rli_zone', 'event', array($id)),
			'timebonus'	=> $pdh->get('rli_zone', 'timebonus', array($id)),
			'diff'		=> $pdh->get('rli_zone', 'diff', array($id)),
			'sort'		=> $pdh->get('rli_zone', 'sort', array($id))
		);
		if($db->query("DELETE FROM __raidlogimport_zone WHERE zone_id = '".$id."';")) {
			$pdh->enqueue_hook('rli_zone_update', array($id));
			$log_action = array(
				'{L_ID}'			=> $id,
				'{L_BZ_TYPE}'   	=> '{L_BZ_ZONE_S}',
				'{L_BZ_STRING}'		=> $old['string'],
				'{L_EVENT}'			=> $pdh->get('event', 'name', array($old['event'])),
				'{L_BZ_TIMEBONUS}'	=> $old['timebonus'],
				'{L_BZ_DIFF}' 		=> $old['diff'],
			);
			$this->log_insert('action_raidlogimport_bz_del', $log_action, true, 'raidlogimport' );
			return $id;
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
?>
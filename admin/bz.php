<?php
/*
* Project:     EQdkp-Plus Raidlogimport
* License:     Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
* Link:		http://creativecommons.org/licenses/by-nc-sa/3.0/
* -----------------------------------------------------------------------
* Began:       2008
* Date:        $Date: 2009-05-15 18:51:52 +0200 (Fr, 15 Mai 2009) $
* -----------------------------------------------------------------------
* @author      $Author: hoofy_leon $
* @copyright   2008-2009 hoofy_leon
* @link        http://eqdkp-plus.com
* @package     raidlogimport
* @version     $Rev: 4868 $
*
* $Id: bz.php 4868 2009-05-15 16:51:52Z hoofy_leon $
*/

// EQdkp required files/vars
define('EQDKP_INC', true);
define('IN_ADMIN', true);

$eqdkp_root_path = './../../../';

include_once('./../includes/common.php');

class Bz extends page_generic {

	public function __construct() {
		global $user, $in;
		$user->check_auth('a_raidlogimport_bz');
		
		$handler = array(
			'save' => array('process' => 'save', 'session_key' => true),
			'copy' => array('process' => 'copy', 'session_key' => true)
		);
		parent::__construct(false, $handler, false, null, 'bz_ids[]');
		$this->process();
	}
	
	private function prepare_data($type, $id, $method='add') {
		global $in, $core;
		$data = array();
		if($type == 'zone') {
			$data = array(
				$in->get('string:'.$id, ''),
				$in->get('event:'.$id, 0),
				runden(floatvalue($in->get('timebonus:'.$id, '0.0'))),
				$in->get('diff:'.$id, 0),
				$in->get('sort:'.$id, 0));
		} else {
			$data = array(
				$in->get('string:'.$id, ''),
				(($core->config('event_boss', 'raidlogimport')) ? $in->get('event:'.$id, 0) : $in->get('note:'.$id, '')),
				runden(floatvalue($in->get('bonus:'.$id, '0.0'))),
				runden(floatvalue($in->get('timebonus:'.$id, '0.0'))),
				$in->get('diff:'.$id, 0),
				$in->get('tozone:'.$id, 0),
				$in->get('sort:'.$id, 0));
		}
		if($method == 'update') {
			list($type, $id) = explode('_', $id);
			$data = array_merge(array($id), $data);
		}
		return $data;
	}

	public function save() {
		global $user, $pdh, $in;
		$message = array('bz_no_save' => array(), 'bz_save_suc' => array());
		if($in->get('save') == $user->lang('bz_save')) {
			$data = $in->getArray('type', 'string');
			foreach($data as $id => $type) {
				$method = ($id == 'neu') ? 'add' : 'update';
				list($old_type, $iid) = explode('_', $id);
				if($old_type == $type OR $method == 'add') {
					$save = $pdh->put('rli_'.$type, $method, $this->prepare_data($type, $id, $method));
				} else {
					//type changed: remove and add
					$save = $pdh->put('rli_'.$old_type, 'del', array($iid));
					if($save) $save = $pdh->put('rli_'.$type, 'add', $this->prepare_data($type, $id, 'add'));
				}
				if($save) {
					$message['bz_save_suc'][] = $in->get('string:'.$id, '');
				} else {
					$message['bz_no_save'][] = $in->get('string:'.$id, '');
				}
			}
			$pdh->process_hook_queue();
		}
		$this->display($message);
	}
	
	public function copy() {
		global $user, $pdh, $in, $core;
		$zones = $in->getArray('zone_id', 'int');
		foreach($zones as $id) {
			$data = array(
				implode($core->config('bz_parse', 'raidlogimport'), $pdh->get('rli_zone', 'string', array($id))),
				$pdh->get('rli_zone', 'event', array($id)),
				$pdh->get('rli_zone', 'timebonus', array($id)),
				$in->get('diff', 0),
				$pdh->get('rli_zone', 'sort', array($id)));
			$new_id = $pdh->put('rli_zone', 'add', $data);
			if($new_id) {
				$bosses = $pdh->get('rli_boss', 'bosses2zone', array($id));
				foreach($bosses as $bid) {
					$boss_diff = $pdh->get('rli_boss', 'diff', array($bid));
					$data = array(
						implode($core->config('bz_parse', 'raidlogimport'), $pdh->get('rli_boss', 'string', array($bid))),
						$pdh->get('rli_boss', 'note', array($bid)),
						$pdh->get('rli_boss', 'bonus', array($bid)),
						$pdh->get('rli_boss', 'timebonus', array($bid)),
						($boss_diff) ? $in->get('diff', 0) : 0,
						$new_id,
						$pdh->get('rli_boss', 'sort', array($bid)));
					$pdh->put('rli_boss', 'add', $data);
				}
				$message['bz_copy_suc'][] = $pdh->geth('rli_zone', 'event', array($id, false));
			} else {
				$message['bz_no_copy'][] = $pdh->geth('rli_zone', 'event', array($id, false));
			}
		}
		$pdh->process_hook_queue();
		$this->display($message);
	}

	public function delete() {
		global $core, $user, $tpl, $pm, $in, $pdh;
		if($in->exists('bz_ids')) {
			$bz_ids = $in->getArray('bz_ids', 'string');
			foreach($bz_ids as $id) {
				if(strpos($id, 'b') !== false) {
					$id = substr($id, 1);
					$note = $pdh->get('rli_boss', 'note', array($id));
					if($pdh->put('rli_boss', 'del', array($id))) {
						$message['bz_save_suc'][] = $note;
					} else {
						$message['bz_no_save'][] = $note;
					}
				} else {
					$id = substr($id, 1);
					$event = $pdh->get('rli_zone', 'event', array($id, false));
					if($pdh->put('rli_zone', 'del', array($id))) {
						$message['bz_save_suc'][] = $event;
					} else {
						$message['bz_no_save'][] = $event;
					}
				}
			}
		} else {
			$message['bz_no_save'][] = $user->lang('bz_no_id');
		}
		$pdh->process_hook_queue();
		$this->display($message);
	}
	
	private function get_upd_data($type, $id) {
		global $html, $pdh, $core;
		return array(
				'ID'			=> $type.'_'.$id,
				'STRING'		=> implode($core->config('bz_parse', 'raidlogimport'), $pdh->get('rli_'.$type, 'string', array($id))),
				'NOTE'			=> ($type == 'boss') ? $pdh->get('rli_boss', 'note', array($id)) : '',
				'BONUS'			=> ($type == 'boss') ? $pdh->get('rli_boss', 'bonus', array($id)) : '',
				'TIMEBONUS'		=> $pdh->get('rli_'.$type, 'timebonus', array($id)),
				'DIFF'			=> $pdh->get('rli_'.$type, 'diff', array($id)),
				'SORT'			=> $pdh->get('rli_'.$type, 'sort', array($id)),
				'BSELECTED'		=> ($type == 'boss') ? 'selected="selected"' : '',
				'ZSELECTED'		=> ($type == 'zone') ? 'selected="selected"' : '',
				'DIFF_ARRAY'	=> $html->DropDown("diff[".$type."_".$id."]", $this->diff_drop, $pdh->get('rli_'.$type, 'diff', array($id))),
				'ZONE_ARRAY'	=> $html->DropDown("tozone[".$type."_".$id."]", $this->zone_drop, (($type == 'boss') ? $pdh->get('rli_boss', 'tozone', array($id)) : $id)),
				'EVENTS'		=> $html->DropDown("event[".$type."_".$id."]", $this->event_drop, (($type == 'zone') ? $pdh->get('rli_zone', 'event', array($id)) : $pdh->get('rli_boss', 'note', array($id))))
		);
	}
	
	private function prepare_diff_drop() {
		global $user;
		if(!isset($this->diff_drop)) $this->diff_drop = array($user->lang('diff_0'), $user->lang('diff_1'), $user->lang('diff_2'), $user->lang('diff_3'), $user->lang('diff_4'));
	}
	
	public function update() {
		global $core, $user, $tpl, $pm, $html, $in, $pdh, $game;
		if(!$this->zone_drop) {
			$this->zone_drop = $pdh->aget('rli_zone', 'html_string', 0, array($pdh->get('rli_zone', 'id_list')));
			$this->zone_drop[0] = $user->lang('bz_no_zone');
			ksort($this->zone_drop);
		}
		if(!$this->event_drop) $this->event_drop = $pdh->aget('event', 'name', 0, array($pdh->get('event', 'id_list')));
		$this->prepare_diff_drop();
		if($in->exists('bz_ids')) {
			$bz_ids = $in->getArray('bz_ids', 'string');
			foreach($bz_ids as $id) {
				if(strpos($id, 'b') !== false) {
					$tpl->assign_block_vars('upd_list', $this->get_upd_data('boss', substr($id, 1)));
				} else {
					$tpl->assign_block_vars('upd_list', $this->get_upd_data('zone', substr($id, 1)));
				}
			}
		} else {
			$tpl->assign_block_vars('upd_list', array(
				'ID'		=> 'neu',
				'STRING'	=> $in->get('string'),
				'NOTE'		=> $in->get('note'),
				'BONUS'		=> $in->get('bonus'),
				'TIMEBONUS'	=> $in->get('timebonus'),
				'SORT'		=> '',
				'BSELECTED'	=> 'true',
				'ZSELECTED'	=> '',
				'DIFF_ARRAY' => $html->DropDown("diff[neu]", $this->diff_drop, $in->get('diff')),
				'ZONE_ARRAY' => $html->DropDown("tozone[neu]", $this->zone_drop, $in->get('zone_id')),
				'EVENTS'	=> $html->DropDown("event[neu]", $this->event_drop, '')
			));
		}

		$tpl->assign_vars(array(
			'S_DIFF'		=> ($game->get_game() == 'wow') ? true : false,
			'S_BOSSEVENT'	=> ($core->config('event_boss', 'raidlogimport')) ? true : false,
			'L_BZ_UPD'		=> $user->lang('bz_upd'),
			'L_TYPE'		=> $user->lang('bz_type'),
			'L_STRING'		=> $user->lang('bz_string'),
			'L_NOTE_EVENT'	=> $user->lang('bz_note_event'),
			'L_BONUS'		=> $user->lang('bz_bonus'),
			'L_TIMEBONUS'	=> $user->lang('bz_timebonus'),
			'L_DIFF'		=> $user->lang('difficulty'),
			'L_SAVE'		=> $user->lang('bz_save'),
			'L_ZONE'		=> $user->lang('bz_zone_s'),
			'L_BOSS'		=> $user->lang('bz_boss_s'),
			'L_TOZONE'		=> $user->lang('bz_tozone'),
			'L_SORT'		=> $user->lang('bz_sort'))
		);
		$core->set_vars(array(
			'page_title'        => sprintf($user->lang('admin_title_prefix'), $core->config('guildtag'), $core->config('dkp_name')).': '.$user->lang('rli_bz_bz'),
			'template_path'     => $pm->get_data('raidlogimport', 'template_path'),
			'template_file'     => 'bz_upd.html',
			'header_format'		=> $this->simple_head,
			'display'           => true,
			)
		);
	}

	public function display($messages=array()) {
		global $tpl, $core, $pm, $user, $pdh, $html;

		if($messages) {
			$type = 'green';
			foreach($messages as $title => $mess) {
				if(strpos('no', $title) !== false) {
					$type = 'red';
				}
				if($mess) {
					$core->message(implode(', ', $mess), $user->lang($title), $type);
				}
			}
		}
		$bosses = $pdh->get('rli_boss', 'id_list');
		$tozone = array();
		$sorting = array();
		$zones = $pdh->get('rli_zone', 'id_list');
		foreach($bosses as $boss_id) {
			$sorting['boss'][$boss_id] = $pdh->get('rli_boss', 'sort', array($boss_id));
			$tozone[$pdh->get('rli_boss', 'tozone', array($boss_id))][] = $boss_id;
		}
		foreach($zones as $id) {
			$sorting['zone'][$id] = $pdh->get('rli_zone', 'sort', array($id));
			if(!in_array($id, array_keys($tozone))) {
				$tozone[$id] = array();
			}
		}
		asort($sorting['boss']);
		asort($sorting['zone']);
		foreach($sorting['zone'] as $zone_id => $zsort) {
			$this->assign2tpl($zone_id, $sorting, $tozone);
		}
		if(isset($tozone[0]) AND count($tozone[0]) > 0) {
			$this->assign2tpl(0, $sorting, $tozone);
		}
		$this->prepare_diff_drop();
		$this->confirm_delete();
		$tpl->assign_vars(array(
			'S_DIFF'		=> ($core->config('default_game') == 'wow') ? true : false,
			'DIFF_DROP'		=> $html->DropDown('diff', $this->diff_drop, ''),
			'L_BZ'			=> $user->lang('rli_bz_bz'),
			'L_STRING'		=> $user->lang('bz_string'),
			'L_NOTE'		=> $user->lang('bz_note_event'),
			'L_BONUS'		=> $user->lang('bz_bonus'),
			'L_TIMEBONUS'	=> $user->lang('bz_timebonus'),
			'L_UPDATE'		=> $user->lang('bz_update'),
			'L_DELETE'		=> $user->lang('bz_delete'),
			'L_COPY_ZONE'	=> $user->lang('bz_copy_zone'))
		);

		$core->set_vars(array(
			'page_title'        => sprintf($user->lang('admin_title_prefix'), $core->config('guildtag'), $core->config('dkp_name')).': '.$user->lang('rli_bz_bz'),
			'template_path'     => $pm->get_data('raidlogimport', 'template_path'),
			'template_file'     => 'bz.html',
			'header_format'		=> $this->simple_head,
			'display'           => true,
			)
		);
	}
	
	private function assign2tpl($zone_id, $sorting, $tozone) {
		global $tpl, $pdh, $user, $core, $jquery;
		$jquery->Collapse('#zone_'.$zone_id);
		$tpl->assign_block_vars('zone_list', array(
			'ZID'		=> $zone_id,
			'ZSTRING'	=> ($zone_id) ? $pdh->geth('rli_zone', 'string', array($zone_id)) : $user->lang('bz_boss_oz'),
			'ZTIMEBONUS'=> ($zone_id) ? $pdh->geth('rli_zone', 'timebonus', array($zone_id)) : '',
			'ZNOTE'		=> ($zone_id) ? $pdh->geth('rli_zone', 'event', array($zone_id)) : '')
		);
		foreach($sorting['boss'] as $boss_id => $bsort) {
			if(in_array($boss_id, $tozone[$zone_id])) {
				$tpl->assign_block_vars('zone_list.boss_list', array(
					'BID'		=> $boss_id,
					'BSTRING'	=> $pdh->geth('rli_boss', 'string', array($boss_id)),
					'BNOTE'		=> $pdh->geth('rli_boss', 'note', array($boss_id)),
					'BBONUS'	=> $pdh->get('rli_boss', 'bonus', array($boss_id)),
					'BTIMEBONUS'=> $pdh->get('rli_boss', 'timebonus', array($boss_id))
				));
			}
		}
	}
}
$bosszone = new Bz;
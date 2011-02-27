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

if(!class_exists('rli_adjustment')) {
  class rli_adjustment {
	public function __construct() {
		global $rli;
		$this->adjs = $rli->get_cache_data('adj');
	}

	private function config($name) {
		global $rli;
		return $rli->config($name);
	}
	
	public function reset() {
		$this->adjs = array();
	}

	public function add($reason, $member, $value, $event, $date=0, $raid=0) {
		$this->adjs[] = array('reason' => $reason, 'member' => $member, 'value' => runden($value), 'date' => $date, 'raid' => $raid);
	}

	public function add_new($num) {
		while($num > 0) {
			$this->adjs[] = array('reason' => '');
			$num--;
		}
	}
	
	public function update($key, $values) {
		if(is_array($values)) {
			foreach($values as $type => $data) {
				$this->adjs[$key][$type] = $data;
			}
			return true;
		}
		return false;
	}
	
	public function load_adjs() {
		global $in;
		$this->adjs = array();
		foreach($_POST['adjs'] as $a => $adj) {
			if(!(isset($adj['delete']) AND $adj['delete'])) {
				$this->adjs[$a] = $in->getArray('adjs:'.$a, '');
				$this->adjs[$a]['value'] = runden(floatvalue($adj['value']));
			}
		}
	}
	
	public function display($with_form=false) {
		global $rli, $core, $html, $tpl, $pdh, $user;
		$members = $rli->member->get_for_dropdown(4);
		$events = $pdh->aget('event', 'name', 0, array($pdh->get('event', 'id_list')));
		$raid_select = array_merge(array($user->lang('none')), $rli->raid->raidlist());
		if(is_array($this->adjs)) {
			foreach($this->adjs as $a => $adj) {
				$ev_sel = (isset($adj['event'])) ? $adj['event'] : 0;
				if(runden($adj['value']) == '0' || runden($adj['value']) == '-0') {
					unset($data['adjs'][$a]);
					continue;
				}
				$tpl->assign_block_vars('adjs', array(
					'MEMBER'	=> $html->DropDown('adjs['.$a.'][member]', $members, $adj['member']),
					'EVENT'		=> $html->DropDown('adjs['.$a.'][event]', $events, $ev_sel),
					'NOTE'		=> $adj['reason'],
					'VALUE'		=> $adj['value'],
					'RAID'		=> $html->DropDown('adjs['.$a.'][raid]', $raid_select, $adj['raid']),
					'KEY'		=> $a,
				));
			}
		}
		$tpl->assign_block_vars('adjs', array(
			'KEY'		=> 999,
			'MEMBER'	=> $html->DropDown('adjs[999][member]', $members, 0),
			'EVENT'		=> $html->DropDown('adjs[999][event]', $events, 0),
			'RAID'		=> $html->DropDown('adjs[999][raid]', $raid_select, 0),
			'DISPLAY'	=> 'style="display: none;"',
			'DELCHK'	=> 'checked="checked"',
		));
		$tpl->add_js(
"$('#rli_select_all').click(function() {
	if($('.rli_select_me').attr('checked')) {
		$('.rli_select_me').removeAttr('checked');
	} else {
		$('.rli_select_me').attr('checked', 'checked');
	}
});
var rli_key = ".($a+1).";
$('#add_adj_button').click(function() {
	var adj = $('#adj_999').clone(true);
	adj.find('.rli_select_me').removeAttr('checked');
	adj.html(adj.html().replace(/999/g, rli_key));
	adj.attr('id', 'adj_'+rli_key);
	adj.removeAttr('style');
	$('#adj_'+(rli_key-1)).after(adj);
	rli_key++;
});", 'docready');
	}
	
	public function check_adj_exists($member, $reason, $raid_id=0) {
		if(is_array($this->adjs)) {
			foreach($this->adjs as $key => $adj) {
				if($adj['member'] == $member AND $adj['reason'] == $reason AND (!$raid_id OR $adj['raid'] == $raid_id)) {
					return $key;
				}
			}
		}
		return false;
	}

	public function check($bools) {
		if(is_array($this->adjs)) {
			foreach($this->adjs as $key => $adj) {
				if(!$adj['event'] OR !$adj['member'] OR !$adj['reason'] OR !$adj['value']) {
					$bools['false']['adj'] = false;
				}
			}
		} else {
			$bools['false']['adj'] = 'miss';
		}
	}
	
	public function insert() {
		global $pdh, $rli;
		foreach($this->adjs as $adj) {
			if(!$pdh->put('adjustment', 'add_adjustment', array($adj['value'], $adj['reason'], array($rli->member->name_ids[$adj['member']]), $adj['event'], $rli->raid->real_ids[$adj['raid']], $adj['time']))) {
				return false;
			}
		}
		return true;
	}
	
	public function __destruct() {
		global $rli;
		$rli->add_cache_data('adj', $this->adjs);
	}
  }
}
?>
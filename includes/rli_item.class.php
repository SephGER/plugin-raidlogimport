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

if(!class_exists('rli_item')) {
class rli_item {
	private $items = array();

	public function __construct() {
		global $rli;
		$this->items = $rli->get_cache_data('item');
	}

	private function config($name) {
		global $rli;
		return $rli->config($name);
	}
	
	public function add($name, $member, $value, $id=0, $time=0, $raid=0) {
		$this->items[] = array('name' => $name, 'member' => $member, 'value' => $value, 'game_id' => $id, 'time' => $time, 'raid' => $raid);
	}
	
	public function add_new($num) {
		while($num > 0) {
			$this->items[] = array('name' => '');
			$num--;
		}
	}
	
	public function load_items() {
		global $in;
		foreach($_POST['loots'] as $k => $loot) {
			if(is_array($this->items)) {
				foreach($this->items as $key => $item) {
					if($k == $key) {
						if(isset($loot['delete']) AND $loot['delete']) {
							unset($this->items[$key]);
							continue;
						}
						$this->items[$key] = $in->getArray('loots:'.$key, '');
						$this->items[$key]['value'] = floatvalue($in->get('loots:'.$key.':value', '0.0'));
						$this->items[$key]['time'] = $item['time'];
					}
				}
			} else {
				$this->items[$k] = $in->getArray('loots:'.$k, '');
				$this->items[$k]['value'] = floatvalue($in->get('loots:'.$k.':value', '0.0'));
			}
		}
	}
	
	public function display($with_form=false) {
		global $rli, $html, $pdh, $tpl, $user, $core, $in;
		if(is_array($this->items)) {
			$p = count($this->items);
			$start = 0;
			$end = $p+1;
			if($vars = ini_get('suhosin.post.max_vars')) {
				$vars = $vars - 5;
				$dic = $vars/6;
				settype($dic, 'int');
				$page = 1;

				if(!(strpos($in->get('checkitem'), $user->lang('rli_itempage')) === false)) {
					$page = str_replace($user->lang('rli_itempage'), '', $in->get('checkitem'));
				}
				if($page >= 1) {
					$start = ($page-1)*$dic;
					$page++;
				}
				$end = $start+$dic;
			}
			$members = $rli->member->get_for_dropdown(2);
			//add disenchanted and bank
			$members['disenchanted'] = 'disenchanted';
			$members['bank'] = 'bank';
			ksort($members);
			$itempools = $pdh->aget('itempool', 'name', 0, array($pdh->get('itempool', 'id_list')));
			//maybe add "saving" for itempool
			foreach($this->items as $key => $item) {
				if(($start <= $key AND $key < $end) OR !$with_form) {
					if($with_form) {
						$member_select = "<select size='1' name='loots[".$key."][member]'>";
						$member_select .= "<option disabled='disabled' ".((in_array($item['member'], $members)) ? "" : "selected='selected'").">".$user->lang('rli_choose_mem')."</option>";
						foreach($members as $mn => $mem) {
							$member_select .= "<option value='".$mn."' ".(($mn == $item['member']) ? "selected='selected'" : "").">".$mem."</option>";
						}
						
						$raid_select = "<select size='1' name='loots[".$key."][raid]'>";
						$att_raids = $rli->raid->get_attendance_raids();
						$rli->raid->raidlist();
						foreach($rli->raid->raidlist as $i => $note) {
							if(!(in_array($i, $att_raids) AND $this->config['attendence_raid'])) {
								$raid_select .= "<option value='".$i."'";
								if($rli->raid->item_in_raid($i, $item['time'])) {
									$raid_select .= ' selected="selected"';
								}
								$raid_select .= ">".$i."</option>";
							}
						}
					}
					$tpl->assign_block_vars('loots', array(
						'LOOTNAME'  => $item['name'],
						'ITEMID'    => (isset($item['game_id'])) ? $item['game_id'] : '',
						'LOOTER'    => ($with_form) ? $member_select."</select>" : $item['member'],
						'RAID'      => ($with_form) ? $raid_select."</select>" : $item['raid'],
						'ITEMPOOL'	=> ($with_form) ? $html->DropDown('loots['.$key.'][itempool]', $itempools, $item['itempool']) : $pdh->get('itempool', 'name', array($item['itempool'])),
						'LOOTDKP'   => runden($item['value']),
						'KEY'       => $key,
						'CLASS'     => $core->switch_row_class())
					);
				}
			}
		}
		if($end <= $p AND $end) {
			$next_button = '<input type="submit" name="checkitem" value="'.$user->lang('rli_itempage').(($page) ? $page : 2).'" class="mainoption" />';
		} elseif(isset($dic) AND $end+$dic >= $p) {
			$next_button .= ' <input type="submit" name="checkitem" value="'.$user->lang('rli_itempage').(($page) ? $page : 2).'" class="mainoption" />';
		} elseif($rli->config('deactivate_adj')) {
			$next_button = '<input type="submit" name="insert" value="'.$user->lang('rli_go_on').' ('.$user->lang('rli_insert').')" class="mainoption" />';
		} else {
			$next_button = '<input type="submit" name="checkadj" value="'.$user->lang('rli_go_on').' ('.$user->lang('rli_checkadj').')" class="mainoption" />';
		}
		$tpl->assign_var('NEXT_BUTTON', $next_button);
	}

	public function check($bools) {
		if(is_array($this->items)) {
			foreach($this->items as $key => $item) {
				if(!$item['name'] OR !$item['raid'] OR !$item['itempool']) {
					$bools['false']['item'] = false;
				}
			}
		} else {
			$bools['false']['item'] = 'miss';
		}
	}
	
	public function insert() {
		global $pdh, $rli;
		foreach($this->items as $item) {
			if(!$pdh->put('item', 'add_item', array($item['name'], array($rli->member->name_ids[$item['member']]), $rli->raid->real_ids[$item['raid']], $item['game_id'], $item['value'], $item['itempool'], $item['time']))) {
				return false;
			}
		}
		return true;
	}
	
	public function __destruct() {
		global $rli;
		$rli->add_cache_data('item', $this->items);
	}
}
}
?>
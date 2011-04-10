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

if(!defined('EQDKP_INC'))
{
	header('HTTP/1.0 Not Found');
	exit;
}

if(!class_exists('rli_member')) {
  class rli_member {
  	private $members = array();
  	private $timebar_created = false;
	private $positions = array('up', 'middle', 'down');
	private $rpos = array();
	public $raid_members = array();

	public function __construct() {
		global $rli;
		$this->members = $rli->get_cache_data('member');
	}
	
	public function reset() {
		$this->members = array();
	}

	private function config($name) {
		global $rli;
		return $rli->config($name);
	}

	public function add($name, $class=0, $race=0, $lvl=0, $note='') {
		if($race == 'Scourge' || $race == 'SCOURGE') {
			$race = 'Undead';
		}
		if($race == 'BloodElf' || $race == 'BLOODELF') {
			$race = 'Blood Elf';
		}
		if($race == 'DEATHKNIGHT' || $race == 'DeathKnight') {
			$race = 'Death Knight';
		}
		if(!deep_in_array($name, $this->members)) $this->members[] = array('name' => $name, 'class' => $class, 'race' => $race, 'level' => $lvl, 'note' => $note);
	}

	public function add_time($name, $time, $type, $extra=0) {
		settype($time, 'int');
		foreach($this->members as $key => &$mem) {
			if(isset($mem['name']) AND $mem['name'] == $name) {
				if(isset($this->members['times'][$key]) AND is_array($this->members['times'][$key]) AND array_key_exists($time, $this->members['times'][$key])) {
					unset($this->members['times'][$key][$time]);
				} else {
					$this->members['times'][$key][$time] = (string) $type;
					if($extra) {
						$this->members['times'][$key][$time] .= '_'.$extra;
					}
				}
				break;
			}
		}
	}
		
	public function load_members() {
		global $rli, $in, $user;
		$globalattraids = $rli->raid->get_attendance_raids();
		foreach($_POST['members'] as $k => $mem) {
			if(!(is_array($this->members) AND in_array($k, array_keys($this->members)))) {
				$this->members[$k] = array();
			}
			foreach($this->members as $key => &$member) {
				if($k == $key) {
					if(isset($mem['delete']) AND $mem['delete']) {
						unset($this->members[$key]);
						continue;
					}
					$member['name'] = $in->get('members:'.$key.':name', '');
					if($rli->config('member_display') == 2) {
						$times = array();
						foreach($mem['times'] as $tk => $time) {
							$times[$tk]['join'] = $in->get('members:'.$key.':times:'.$tk.':join', 0);
							$times[$tk]['leave'] = $in->get('members:'.$key.':times:'.$tk.':leave', 0);
							$extra = $in->get('members:'.$key.':times:'.$tk.':extra', '');
							if($extra) $times[$tk][$extra] = 1;
						}
						$member['times'] = $times;
						$member['raid_list'] = $rli->raid->get_memberraids($member['times']);
						$a = $rli->raid->get_attendance($member['times']);
						$member['att_begin'] = $a['begin'];
						$member['att_end'] = $a['end'];
						unset($a);
					} else {
						$member['raid_list'] = $in->getArray('members:'.$key.':raid_list', 'int');
						$member['att_begin'] = (isset($mem['att_begin'])) ? true : false;
						$member['att_end'] = (isset($mem['att_end'])) ? true : false;
					}
					if($member['raid_list']) {
						foreach($member['raid_list'] as $raid_id) {
							if(!$rli->config('attendence_raid') OR ($raid_id != $globalattraids['begin'] AND $raid_id != $globalattraids['end'])) {
								$dkp = $rli->raid->get_value($raid_id, $member['times'], array($member['att_begin'], $member['att_end']));
								$dkp = runden($dkp, 2);
								$raid = $rli->raid->get($raid_id);
								if($dkp <  $raid['value']) {
									//add an adjustment
									$dkp -= $raid['value'];
									$akey = $rli->adj->check_adj_exists($member['name'], $user->lang('rli_partial_raid'), $raid_id);
									if($akey !== false) {
										$rli->adj->update($akey, array('value' => $dkp));
									} else {
										$rli->adj->add($user->lang('rli_partial_raid'), $member['name'], $dkp, $raid['event'], $raid['begin'], $raid_id);
									}
								}
							}
						}
					}
				}
			}
		}
	}

	public function finish() {
		global $rli;
		$begin = $rli->raid->get_start_end();
		$end = $begin['end'];
		$begin = $begin['begin'];
		$error = '';
		foreach($this->members['times'] as $key => $times) {
			ksort($times);
			$count = 1;
			$size =  count($times);
        	$lasttype = false;
        	$lasttime = false;
			foreach($times as $time => $type) {
  				if($type == $lasttype) {
					$error .= '<br />Wrong Member: '.$this->members[$key]['name'].', '.$type.'-times: '.date('H:i:s', $time).' and '.date('H:i:s', $lasttime);
				} elseif($type == 'join' AND $lasttype == 'join_standby') {
					$new_time = $time-1;
					$times[$new_time] = 'leave_standby';
	      		} else {
        	  	  	if($begin AND $type == 'join' AND ($begin + $this->config('member_miss_time')) > $time AND $count == 1) {
          		      	unset($times[$time]);
          		      	$times[$begin] = 'join';
         		   	}
         		   	if($end AND $type == 'leave' AND ($end - $this->config('member_miss_time')) < $time AND $count == $size) {
         		       	unset($times[$time]);
         		       	$times[$end] = 'leave';
         		   	}
	     	 		if($type == 'join' AND ($time - $this->config('member_miss_time')) < $lasttime) {
	     	 			unset($times[$time]);
	     	 			unset($times[$lasttime]);
	    	  		}
	    	  	}
	    	  	$lasttype = $type;
	    	  	$lasttime = $time;
	    	  	$count++;
	      	}
	      	ksort($times);
	      	$tkey = 0;
        	$new_times = array();
	      	foreach($times as $time => $type) {
				$extra = '';
	      		if(strpos($type, '_') !== false) list($type, $extra) = explode('_', $type);
	      		if($type == 'join') {
	      			$new_times[$tkey] = array($type => $time);
	      			if($extra) {
	      				$new_times[$tkey][$extra] = true;
	      			}
	      		}
	      		if($type == 'leave') {
	      			$new_times[$tkey][$type] = $time;
	      			$tkey++;
	      		}
	      	}
	      	$this->members[$key]['times'] = $new_times;
	    }
	    unset($this->members['times']);
	    if($error != '') {
	    	message_die($error);
	    }
  	}

  	public function add_new($num) {
  		for($i=1; $i<=$num; $i++) {
  			$this->members[] = array('name' => '', 'times' => array());
  		}
  	}

  	public function display($with_form=false) {
  		global $tpl, $jquery, $rli, $core, $user, $in;
		$globalattraids = $rli->raid->get_attendance_raids();
		$key = 0;
		foreach($this->members as $key => $member) {
			if($with_form) {
				if($this->config('s_member_rank') & 1) {
					$member['rank'] = $this->rank_suffix($member['name']);
				}
				if($in->get('checkmem') == $user->lang('rli_go_on').' ('.$user->lang('rli_checkmem').')') {
					$mraids = $rli->raid->get_memberraids($member['times']);
					$a = $rli->raid->get_attendance($member['times']);
					if(isset($a['begin']) AND !in_array($globalattraids['begin'], $mraids)) {
						$mraids[] = $globalattraids['begin'];
					}
					if(isset($a['end']) AND !in_array($globalattraids['end'], $mraids)) {
						$mraids[] = $globalattraids['end'];
					}
				} else {
					$mraids = $member['raid_list'];
				}
				if($this->config('member_display') == 1 AND extension_loaded('gd')) {
					$raid_list = $rli->raid->get_checkraidlist($mraids, $key);
				}
				elseif($this->config('member_display') == 2 AND extension_loaded('gd')) {
					$raid_list = '';
					$detail_raid_list = true;
				} else {
					$raid_list = '<td>'.$jquery->MultiSelect('members['.$key.'][raid_list]', $rli->raid->raidlist(), $mraids, '200', '200', array('id' => 'members_'.$key.'_raidlist')).'</td>';
				}
				$att_begin = ((isset($member['att_begin']) AND $member['att_begin']) OR (!isset($member['att_begin']) AND $a['begin'])) ? 'checked="checked"' : '';
				$att_end = ((isset($member['att_end']) AND $member['att_end']) OR (!isset($member['att_end']) AND $a['end'])) ? 'checked="checked"' : '';
				//js deletion
				$options = array(
					'custom_js' => "$('#'+del_id).css('display', 'none'); $('#'+del_id+'submit').removeAttr('disabled');",
					'withid' => 'del_id',
					'message' => $user->lang('rli_delete_members_warning')
				);
				$jquery->Dialog('delete_warning', $user->lang('confirm_deletion'), $options, 'confirm');
			} else {
				$att_begin = (isset($member['att_begin']) AND $member['att_begin']) ? $user->lang('yes') : $user->lang('no');
				$att_end = (isset($member['att_end']) AND $member['att_end']) ? $user->lang('yes') : $user->lang('no');
				$raid_list = array();
				if(is_array($member['raid_list'])) {
					$rli->raid->raidlist();
					foreach($member['raid_list'] as $rkey) {
						$raid_list[] = $rli->raid->raidlist[$rkey];
					}
				}
			}
           	$tpl->assign_block_vars('player', array(
               	'MITGLIED' => ($with_form) ? $member['name'] : (($key < 9) ? '&nbsp;&nbsp;' : '').($key+1).'&nbsp;'.$member['name'],
                'RAID_LIST'=> ($with_form) ? $raid_list : implode('; ', $raid_list),
                'ATT_BEGIN'=> $att_begin,
                'ATT_END'  => $att_end,
                'KEY'	   => $key,
                'NR'	   => $key +1,
                'RANK'	   => ($this->config('s_member_rank') & 1) ? $this->rank_suffix($member['name']) : '',
				'DELDIS'	=> 'disabled="disabled"',
           	));
			if(isset($detail_raid_list)) $this->detailed_times_list($key, $mraids);
        }//foreach members
		//a member to copy from for js-addition
		if($with_form) {
			if(!isset($detail_raid_list)) {
				if($this->config('member_display') == 1 AND extension_loaded('gd')) {
					$raid_list = $rli->raid->get_checkraidlist(array(), 999);
				} else {
					$raid_list = '<td>'.$jquery->MultiSelect('members[999][raid_list]', $rli->raid->raidlist, array(), '200', '200', array('id' => 'members_999_raidlist')).'</td>';
				}
			}
			$tpl->assign_block_vars('player', array(
                'RAID_LIST'	=> (!isset($detai_raid_list)) ? $raid_list : '',
                'KEY'		=> 999,
				'DISPLAY'	=> 'style="display: none;"',
			));
			$this->members[999]['times'] = array();
			if(isset($detail_raid_list)) $this->detailed_times_list(999, array());
			unset($this->members[999]);
			$tpl->add_js(
"var rli_key = ".($key+1).";
$('.del_mem').click(function() {
	$(this).removeClass('del_mem');
	delete_warning($(this).attr('class'));
});
$('#add_mem_button').click(function() {
	var mem = $('#memberrow_999').clone(true);
	mem.find('#memberrow_999submit').attr('disabled', 'disabled');
	mem.html(mem.html().replace(/999/g, rli_key));
	mem.attr('id', 'memberrow_'+rli_key);
	mem.removeAttr('style');
	mem.find('td:first').html((rli_key+1)+$.trim(mem.find('td:first').html()));
	mem.find('.add_time').".$this->rightclick_js.";
	$('#memberrow_'+(rli_key-1)).after(mem);
	$('#memberrow_'+rli_key+'submit').prev().click(function() {
		$(this).removeClass('del_mem');
		delete_warning($(this).attr('class'));
	});
	rli_key++;
});", 'docready');
		}
  	}

    public function rank_suffix($mname) {
        $this->get_member_ranks();
        $rank = (isset($this->member_ranks[$mname])) ? $this->member_ranks[$mname] : $this->member_ranks['new'];
        return ' ('.$rank.')';
    }
	
	public function get_for_dropdown($rank_page) {
		$members = array();
		foreach($this->members as $member) {
			$members[$member['name']] = $member['name'];
			if($this->config('s_member_rank') & $rank_page)
				$members[$member['name']] .= $this->rank_suffix($member['name']);
		}
		return $members;
	}

	public function check($bools) {
		if(is_array($this->members)) {
			foreach($this->members as $key => $member) {
				if(!$member['name']) {
					$bools['false']['mem'] = false;
				}
			}
		} else {
			$bools['false']['mem'] = 'miss';
		}
	}
	
	public function insert() {
		global $pdh;
		$members = $pdh->aget('member', 'name', 0, array($pdh->get('member', 'id_list')));
		foreach($this->members as $member) {
			if(!($id = array_search($member['name'], $members))) {
				$id = $pdh->put('member', 'add_member', array($member['name'], $member['level'], $member['race'], $member['class'], $this->config('new_member_rank'), 0));
				if(!$id) return false;
			}
			$this->raid_members[$id] = $member['raid_list'];
			$this->name_ids[$member['name']] = $id;
		}
		return true;
	}
	
	private function raid_positions($raids, $begin) {
		global $rli;
		if(isset($this->raids_positioned) AND $this->raids_positioned) return true;
		$suf = '';
		if($this->updown[0] !== $this->updown[1]) {
			$suf = ' half';
			if($this->updown[0]) {
				$pos = 0;
			} else {
				$pos = 2;
			}
		} else {
			$pos = 1;
		}
		foreach($raids as $rkey => $raid) {
			if($rli->raid->get_standby_raid() == $rkey) {
				$pos = 2;
			} elseif($this->config('raidcount') & 2 AND count($raid['bosskills']) == 1) {
				$pos = 0;
			}
			$this->rpos[$rkey] = $this->positions[$pos].$suf;
		}
		$this->raids_positioned = true;
		return true;
	}
	
	private function init_times_list($width) {
		if(!isset($this->px_time)) {
			$this->px_time = (($width['end'] - $width['begin']) / 20);
			settype($px_time, 'int');
			$bars = 1;
			$this->updown = array(false, false);
			if($this->config('standby_raid') == 1) {
				$bars++;
				$this->updown[0] = true;
			}
			if($this->config('raidcount') & 1 AND $this->config('raidcount') & 2) {
				$bars++;
				$this->updown[1] = true;
			}
			$this->height = 11 + $bars*14;
		}
	}

    private function detailed_times_list($key, $mraids) {
    	global $rli, $tpl, $html, $eqdkp_root_path, $jquery, $user, $pdh, $time;

    	$width = $rli->raid->get_start_end();
		$this->init_times_list($width);

        $raids = $rli->raid->get_data();
		$this->raid_positions($raids, $width['begin']);
        foreach($raids as $rkey => $raid) {
        	$w = ($raid['end']-$raid['begin'])/20;
        	$m = ($raid['begin']-$width['begin'])/20;
        	settype($w, 'int');
        	settype($m, 'int');
			$w--;
			$disabled = (in_array($rkey, $mraids)) ? "" : " disabled='disabled'";
			$active = (in_array($rkey, $mraids)) ? " active" : "";
			$tpl->assign_block_vars('player.member_raids', array(
				'KEY'	=> $rkey,
				'RPOS'	=> $this->rpos[$rkey],
				'ACTIVE' => $active,
				'DISABLED' => $disabled,
				'WIDTH' => $w,
				'LEFT' => $m)
			);
			if(!isset($this->bosses_done)) {
				foreach($raid['bosskills'] as $bkey => $boss) {
					$m = ($boss['time']-$width['begin'])/20 - 4;
					settype($m, 'int');
					$jquery->qtip('.rli_boss', 'return $(".rli_boss_c", this).html();', array('contfunc' => true));
					$this->boss_data[] = array(
						'KEY' => $bkey,
						'LEFT' => $m,
						'NAME' => (is_numeric($boss['id'])) ? $pdh->get('rli_boss', 'note', array($boss['id'])) : $boss['id'],
						'TIME' => $time->user_date($boss['time'], false, true),
						'VALUE'	=> $boss['bonus']
					);
				}
				$this->bosses_done = true;
        	}
			foreach($this->boss_data as $boss_data) {
				$tpl->assign_block_vars('player.bosses', $boss_data);
			}
        }
        $tkey = 0;
        foreach($this->members[$key]['times'] as $mtime) {
        	$s = (isset($mtime['standby']) AND $mtime['standby']) ? 'standby' : '';
        	$w = ($mtime['leave']-$mtime['join'])/20;
        	$ml = ($mtime['join']-$width['begin'])/20;
        	settype($w, 'int');
        	settype($ml, 'int');
			$tpl->assign_block_vars('player.times', array(
				'KEY'		=> $tkey,
				'STANDBY'	=> $s,
				'EXTRA'		=> (!$s) ? '0' : 'standby',
				'WIDTH'		=> $w,
				'LEFT'		=> $ml,
				'JOIN'		=> $mtime['join'],
				'LEAVE'		=> $mtime['leave']
			));
			$tkey++;
        }
        $this->create_timebar($width['begin'], $width['end']);

    	//only do this once
    	if(!isset($this->tpl_assignments)) {
			$rightc_menu = array(
				'rli_add_dmem' => array('image' => $eqdkp_root_path.'images/menues/add.png', 'name' => $user->lang('rli_add_time'), 'jscode' => 'add_timeframe();'),
				'rli_del_dmem' => array('image' => $eqdkp_root_path.'images/menues/delete.png', 'name' => $user->lang('rli_del_time'), 'jscode' => 'remove_timeframe();'),
				'rli_swi_dmem' => array('image' => $eqdkp_root_path.'images/menues/update.png', 'name' => $user->lang('rli_standby_switch'), 'jscode' => 'change_standby();')
			);
			$tpl->assign_vars(array(
				'CONTEXT_MENU' => $jquery->RightClickMenu('_rli_dmem', '.add_time', $rightc_menu),
				'PXTIME' => $this->px_time,
				'HEIGHT' => $this->height)
			);
			$this->rightclick_js = $jquery->RightClickMenu('_rli_dmem', '.add_time', $rightc_menu, '170px', true);
    		$tpl->js_file($eqdkp_root_path.'plugins/raidlogimport/templates/dmem.js');
    		$tpl->css_file($eqdkp_root_path.'plugins/raidlogimport/templates/base_template/dmem.css');
    		$tpl->add_css(".time_scale {
								position: absolute;
								background-image: url(./../../../plugins/raidlogimport/images/time_scale.png);
								background-repeat: repeat-x;
								width: ".$this->px_time."px;
								height: 18px;
								margin-top: 10px;
								z-index: 16;
							}");
    		$tpl->add_js("$(document).ready(function() {
                            $('#member_form').data('raid_start', ".$width['begin'].");
							$('.add_time').live('mouseenter', function() {
								$('#time_scale_' + member_id).attr('class', 'time_scale');
							});
							$('.add_time').live('mouseleave', function() {
								$('#time_scale_' + member_id).attr('class', 'time_scale_hide');
							});
                        });");
    		$this->tpl_assignments = true;
    	}
    }

	private function create_timebar($start, $end) {
		if(!$this->timebar_created) {
			$px_time = ($this->px_time > 10000) ? 10000 : $this->px_time; //prevent very big images (although 10000 is quite big)
			$im = imagecreate($px_time, 18);
			$black = imagecolorallocate($im, 0,0,0);
			$white = imagecolorallocate($im, 255,255,255);
			imagefill($im, 0, 0, $white);
			imageline($im, 0,0,$px_time, 0, $black);
			$c = 2;
			for($i=0; $i<=$px_time;) {
				$y = 3;
				$c++;
				if($c == 3) {
					$y = 5;
					$c = 0;
				}
				imageline($im, $i, 1, $i, $y, $black);
                $i = $i+15;
			}
			$start += 900;
			$counter = 1;
			for($i=$start; $i < $end;) {
				$x = $counter*45 - 14;
                imagestring($im, 2, $x, 5, date('H:i', $i), $black);
				$i += 900;
				$counter++;
			}
			#$imagefile = $eqdkp_root_path.$pcache->FileLink('time_scale.png', 'raidlogimport');
			imagepng($im, './../images/time_scale.png');
			imagedestroy($im);
			$this->timebar_created = true;
		}
	}

	private function get_member_ranks() {
		global $pdh;
		if(!$this->member_ranks) {
			$member_id_rank = $pdh->aget('member', 'rank_name', 0, array($pdh->get('member', 'id_list')));
			foreach($member_id_rank as $id => $rank) {
				$this->member_ranks[$pdh->get('member', 'name', array($id))] = $rank;
			}
			$this->member_ranks['new'] = $pdh->get('rank', 'name', array($this->config('new_member_rank')));
		}
	}

	public function __destruct() {
		global $rli;
		$rli->add_cache_data('member', $this->members);
	}
  }
}
?>
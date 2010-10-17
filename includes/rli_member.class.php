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
		$this->members[] = array('name' => $name, 'class' => $class, 'race' => $race, 'level' => $lvl, 'note' => $note);
	}

	public function add_time($name, $time, $type, $extra=0) {
		settype($time, 'int');
		foreach($this->members as $key => &$mem) {
			if($mem['name'] == $name) {
				if(is_array($this->members['times'][$key]) AND array_key_exists($time, $this->members['times'][$key])) {
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
		foreach($_POST['members'] as $k => $mem) {
			foreach($this->members as $key => &$member) {
				if($k == $key) {
					if($mem['delete']) {
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
							if(($raid_id != $rli->add_data['att_begin_raid'] AND $raid_id != $rli->add_data['att_end_raid']) OR !$rli->config('attendence_raid')) {
								$dkp = $rli->raid->get_value($raid_id, $member['times'], array($member['att_begin'], $member['att_end']));
								$dkp = runden($dkp, 2);
								$raid = $rli->raid->get($raid_id);
								if($dkp <  $raid['value']) {
									//add an adjustment
									$dkp -= $raid['value'];
									if($akey = $rli->adj->check_adj_exists($member['name'], $user->lang['rli_partial_raid'], $raid_id)) {
										$rli->adj->update($akey, array('value' => $dkp));
									} else {
										$rli->adj->add($user->lang['rli_partial_raid'], $member['name'], $dkp, $raid['event'], $raid['begin'], $raid_id);
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
	      		list($type, $extra) = explode('_', $type);
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
  		global $tpl, $jquery, $rli, $core, $user;

		foreach($this->members as $key => $member) {
			if($with_form) {
				if($this->config['s_member_rank'] & 1) {
					$member['rank'] = $this->rank_suffix($member['name']);
				}
				if($_POST['checkmem'] == $user->lang['rli_go_on'].' ('.$user->lang['rli_checkmem'].')') {
					$mraids = $rli->raid->get_memberraids($member['times']);
					$a = $rli->raid->get_attendance($member['times']);
					if($a['att_dkp_begin'] AND !in_array($this->add_data['att_begin_raid'], $mraids)) {
						$mraids[] = $rli->add_data['att_begin_raid'];
					}
					if($a['att_dkp_end'] AND !in_array($this->add_data['att_end_raid'], $mraids)) {
						$mraids[] = $rli->add_data['att_end_raid'];
					}
				}
				if($this->config('member_display') == 1 AND extension_loaded('gd')) {
					$raid_list = $rli->raid->get_checkraidlist($mraids, $key);
				}
				elseif($this->config('member_display') == 2 AND extension_loaded('gd')) {
					$raid_list = $this->detailed_times_list($key, $mraids);
				} else {
					$raid_list = '<td>'.$jquery->MultiSelect('members['.$key.'][raid_list]', $rli->raid->raidlist(), $mraids, '200', '200', false, 'members_'.$key.'_raidlist').'</td>';
				}
				$att_begin = ($a['att_dkp_begin']) ? 'checked="checked"' : '';
				$att_end = ($a['att_dkp_end']) ? 'checked="checked"' : '';
			} else {
				$att_begin = ($member['att_dkp_begin']) ? $user->lang['yes'] : $user->lang['no'];
				$att_end = ($member['att_dkp_end']) ? $user->lang['yes'] : $user->lang['no'];
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
                'ZAHL'     => $core->switch_row_class(),
                'KEY'	   => $key,
                'NR'	   => $key +1,
                'RANK'	   => ($this->config['s_member_rank'] & 1) ? $this->rank_suffix($member['name']) : '')
           	);
        }//foreach members
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
	
	private function raid_positions($raids, $begin, $updown) {
		global $rli;
		if($this->raids_positioned) return true;
		$suf = '';
		if($updown[0] !== $updown[1]) {
			$suf = ' half';
			if($updown[0]) {
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

    private function detailed_times_list($key, $mraids) {
    	global $rli, $tpl, $html, $eqdkp_root_path, $jquery, $user, $pdh;

    	$width = $rli->raid->get_start_end();
    	$px_time = (($width['end'] - $width['begin']) / 20);
    	settype($px_time, 'int');
		$bars = 1;
		$updown = array(false, false);
		if($this->config('standby_raid') == 1) {
			$bars++;
			$updown[0] = true;
		}
		if($this->config('raidcount') & 1 AND $this->config('raidcount') & 2) {
			$bars++;
			$updown[1] = true;
		}
		$height = 11 + $bars*14;

    	$out = "<td id='member_".$key."' class='add_time' onmouseover='set_member(\"".$key."\", \"".$px_time."\")' style='height: ".$height."px;'>";
        $raids = $rli->raid->get_data();
		$this->raid_positions($raids, $width['begin'], $updown);

        $this->raid_div = '';
		if(!$this->members[$key]['raid_list']) $this->members[$key]['raid_list'] = $rli->raid->get_memberraids($this->members[$key]['times']);
        foreach($raids as $rkey => $raid) {
        	$w = ($raid['end']-$raid['begin'])/20;
        	$m = ($raid['begin']-$width['begin'])/20;
        	settype($w, 'int');
        	settype($m, 'int');
			$w--;
			$disabled = (in_array($rkey, $this->members[$key]['raid_list'])) ? "" : " disabled='disabled'";
			$active = (in_array($rkey, $this->members[$key]['raid_list'])) ? " active" : "";
        	$out .= "<div id='raid_".$key."_".$rkey."' class='raid ".$this->rpos[$rkey].$active."' style='width:".$w."px; margin-left: ".$m."px;'><div class='raid_left'></div><div class='raid_middle'><input type='hidden' name='members[".$key."][raid_list][]' value='".$rkey."'".$disabled." /></div><div class='raid_right'></div></div>";
        	foreach($raid['bosskills'] as $bkey => $boss) {
        		$m = ($boss['time']-$width['begin'])/20 - 4;
        		settype($m, 'int');
        		$bossinfo = "<table><tr><td>".$user->lang['rli_bossname']." </td><td>".$pdh->get('rli_boss', 'note', array($boss['id']))."</td></tr><tr><td>".$user->lang['rli_bosstime']."</td><td>".date('H:i:s', $boss['time'])."</td></tr><tr><td>".$user->lang['rli_bossvalue']."</td><td>".$boss['bonus']."</td></tr></table>";
				$tt_out = $jquery->tooltip('#boss_'.$key.'_'.$bkey.'_'.$key, 'boss', $bossinfo);
        		$out .= "<div id='boss_".$key."_".$bkey."_".$key."' ".$tt_out." style='margin-left: ".$m."px;'></div>";
        	}
        }
        $out .= "<div id='times_".$key."'>";
        $tkey = 0;
        foreach($this->members[$key]['times'] as $time) {
        	$s = ($time['standby']) ? 'standby' : '';
			$ev = ($time['standby']) ? 'standby' : '0';
        	$w = ($time['leave']-$time['join'])/20;
        	$ml = ($time['join']-$width['begin'])/20;
        	settype($w, 'int');
        	settype($ml, 'int');
        	$out .= "<div id='times_".$key."_".$tkey."' class='time".$s."' style='width:".$w."px; margin-left: ".$ml."px;' onmouseover='set_time_key(this.id)'>";
        	$out .= "<div class='time_left' onmousedown='scale_start(\"left\")'></div>";
        	$out .= "<div class='time_middle' onmousedown='scale_start(\"middle\")'>";
        	$out .= "<input type='hidden' name='members[".$key."][times][".$tkey."][join]' value='".$time['join']."' id='times_".$key."_".$tkey."j' />";
        	$out .= "<input type='hidden' name='members[".$key."][times][".$tkey."][leave]' value='".$time['leave']."' id='times_".$key."_".$tkey."l' />";
        	$out .= "<input type='hidden' name='members[".$key."][times][".$tkey."][extra]' value='".$ev."' id='times_".$key."_".$tkey."s' />";
        	$out .= "</div><div class='time_right' onmousedown='scale_start(\"right\")'></div></div>";
        	$tkey++;
        }
        $out .= "<div style='display:none;'><div id='times_".$key."_99' class='time' style='width:0px; margin-left:0px;' onmouseover='set_time_key(this.id)'>";
        $out .= "<div class='time_left' onmousedown='scale_start(\"left\")'></div>";
        $out .= "<div class='time_middle' onmousedown='scale_start(\"middle\")'>";
        $out .= "<input type='hidden' name='members[".$key."][times][99][join]' value='0' id='times_".$key."_99j' disabled='disabled' />";
        $out .= "<input type='hidden' name='members[".$key."][times][99][leave]' value='0' id='times_".$key."_99l' disabled='disabled' />";
        $out .= "<input type='hidden' name='members[".$key."][times][99][extra]' value='0' id='times_".$key."_99s' disabled='disabled' />";
        $out .= "</div><div class='time_right' onmousedown='scale_start(\"right\")'></div></div></div>";

        $this->create_timebar($width['begin'], $width['end'], $px_time);
        $out .= "<div><div id='time_scale_".$key."' class='time_scale_hide'></div></div></div></td>";

    	//only do this once
    	if(!$this->tpl_assignments) {
			$rightc_menu = array(
				'rli_add_dmem' => array('image' => $eqdkp_root_path.'images/menues/add.png', 'name' => $user->lang['rli_add_time'], 'jscode' => 'add_timeframe();'),
				'rli_del_dmem' => array('image' => $eqdkp_root_path.'images/menues/delete.png', 'name' => $user->lang['rli_del_time'], 'jscode' => 'remove_timeframe();'),
				'rli_swi_dmem' => array('image' => $eqdkp_root_path.'images/menues/update.png', 'name' => $user->lang['rli_standby_switch'], 'jscode' => 'change_standby();')
			);
			$tpl->assign_vars(array(
				'CONTEXT_MENU' => $jquery->RightClickMenu('_rli_dmem', '.add_time', $rightc_menu),
				'PXTIME' => $px_time)
			);
    		$tpl->js_file($eqdkp_root_path.'plugins/raidlogimport/templates/dmem.js');
    		$tpl->css_file($eqdkp_root_path.'plugins/raidlogimport/templates/dmem.css');
    		$tpl->add_css(".time_scale {
								position: absolute;
								background-image: url(./../../../plugins/raidlogimport/images/time_scale.png);
								background-repeat: repeat-x;
								width: ".$px_time."px;
								height: 18px;
								margin-top: 10px;
								z-index: 16;
							}");
    		$tpl->add_js("$(document).ready(function() {
                            $('#member_form').data('raid_start', ".$width['begin'].");
							$('.add_time').mouseover(function() {
								$('#time_scale_' + member_id).attr('class', 'time_scale');
							});
							$('.add_time').mouseout(function() {
								$('#time_scale_' + member_id).attr('class', 'time_scale_hide');
							});
                        });");
    		$this->tpl_assignments = true;
    	}
    	return $out;
    }

	private function create_timebar($start, $end, $px_time) {
		if(!$this->timebar_created) {
			$px_time = ($px_time > 10000) ? 10000 : $px_time; //prevent very big images (although 10000 is quite big)
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
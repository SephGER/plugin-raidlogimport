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

if(!class_exists('rli_member'))
{
  class rli_member
  {
  	private $members = array();
  	private $timebar_created = false;
  	private $raid_div = '';

	public function __construct()
	{
		global $rli;
		$this->members = $rli->get_cache_data('member');
	}

	private function config($name)
	{
		global $rli;
		return $rli->config($name);
	}

	public function add($name, $class=0, $race=0, $lvl=0, $note='')
	{
		$this->members[] = array('name' => $name, 'class' => $class, 'race' => $race, 'level' => $lvl, 'note' => $note);
	}

	public function add_time($name, $time, $type, $extra=0)
	{
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

	public function finish()
	{
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
			foreach($times as $time => $type)
	    	{
  				if($type == $lasttype) {
					$error .= '<br />Wrong Member: '.$this->members[$key]['name'].', '.$type.'-times: '.date('H:i:s', $time).' and '.date('H:i:s', $lasttime);
				} elseif($type == 'join' AND $lasttype == 'join_standby') {
					$new_time = $time-1;
					$times[$new_time] = 'leave_standby';
	      		} else {
        	  	  	if($begin AND $type == 'join' AND ($begin + $this->config('member_miss_time')) > $time AND $count == 1)
         	 	  	{
          		      	unset($times[$time]);
          		      	$times[$begin] = 'join';
         		   	}
         		   	if($end AND $type == 'leave' AND ($end - $this->config('member_miss_time')) < $time AND $count == $size)
         		   	{
         		       	unset($times[$time]);
         		       	$times[$end] = 'leave';
         		   	}
	     	 		if($type == 'join' AND ($time - $this->config('member_miss_time')) < $lasttime)
	     	 		{
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

  	public function add_new($num)
  	{
  		for($i=1; $i<=$num; $i++) {
  			$this->members[] = array('name' => '', 'times' => array());
  		}
  	}

  	public function display($with_form=false)
  	{
  		global $tpl, $jquery, $rli, $core, $user;

		foreach($this->members as $key => $member) {
			if($this->config['s_member_rank'] & 1) {
				$member['rank'] = $this->rank_suffix($member['name']);
			}
            if($_POST['checkmem'] == $user->lang['rli_go_on'].' ('.$user->lang['rli_checkmem'].')') {
            	$mraids = $rli->raid->get_memberraids($member['times']);
            	$a = $rli->raid->calc_att($member['times']);
            	if($a['att_dkp_begin'] AND !in_array($this->add_data['att_begin_raid'], $mraids)) {
            		$mraids[] = $rli->add_data['att_begin_raid'];
            	}
            	if($a['att_dkp_end'] AND !in_array($this->add_data['att_end_raid'], $mraids)) {
            		$mraids[] = $rli->add_data['att_end_raid'];
            	}
	        }
	        if($this->config('member_display') == 1 AND extension_loaded('gd')) {
	        	$raid_list = $this->get_checkraidlist($mraids, $key);
	        }
	        elseif($this->config('member_display') == 2) {
	        	$raid_list = $this->detailed_times_list($key, $mraids);
	        } else {
	        	$raid_list = '<td>'.$jquery->MultiSelect('members['.$key.'][raid_list]', $rli->raid->raidlist(), $mraids, '200', '200', false, 'members_'.$key.'_raidlist').'</td>';
	        }
           	$tpl->assign_block_vars('player', array(
               	'MITGLIED' => $member['name'],
                'ALIAS'    => $alias,
                'RAID_LIST'=> $raid_list,
                'ATT_BEGIN'=> ($a['att_dkp_begin']) ? 'checked="checked"' : '',
                'ATT_END'  => ($a['att_dkp_end']) ? 'checked="checked"' : '',
                'ZAHL'     => $core->switch_row_class(),
                'KEY'	   => $key,
                'NR'	   => $key +1,
                'RANK'	   => ($this->config['s_member_rank'] & 1) ? $this->rank_suffix($member['name']) : '')
           	);
        }//foreach members
  	}

    public function rank_suffix($mname)
    {
        $this->get_member_ranks();
        $rank = (isset($this->member_ranks[$mname])) ? $this->member_ranks[$mname] : $this->member_ranks['new'];
        return ' ('.$rank.')';
    }

    private function detailed_times_list($key)
    {
    	global $rli, $tpl, $html, $eqdkp_root_path, $jquery;

    	$width = $rli->raid->get_start_end();
    	$px_time = (($width['end'] - $width['begin']) / 20);
    	settype($px_time, 'int');

    	$out = "<td id='member_".$key."' class='add_time' onmouseover='set_member(\"".$key."\", \"".$px_time."\")'>";
        #$out .= $jquery->RightClickMenu('right_click_'.$key, 'member_'.$key, array('rc_'.$key.'_0' => array('name' => 'Time hinzufügen', 'jscode' => 'alert("bla")')));
        $raids = $rli->raid->get_data();

        if(!$this->raid_div) {
          $this->raid_div = '';
          foreach($raids as $rkey => $raid) {
        	$w = ($raid['end']-$raid['begin'])/20;
        	$m = ($raid['begin']-$width['begin'])/20;
        	settype($w, 'int');
        	settype($m, 'int');
        	$this->raid_div .= "<div id='raid_".$key."_".$rkey."' class='raid' style='width:".$w."px; margin-left: ".$m."px;'><div class='raid_left'></div><div class='raid_middle'><input type='hidden' name='members[".$key."][raid_list][]' value='".$rkey."' /></div><div class='raid_right'></div></div>";
        	foreach($raid['bosskills'] as $bkey => $boss) {
        		$m = ($boss['time']-$width['begin'])/20 - 4;
        		settype($m, 'int');
        		$bossinfo = "<table><tr><td style='width:80px;'>bossname:</td><td>".$boss['name']."</td></tr><tr><td>killtime:</td><td>".date('H:i:s', $boss['time'])."</td></tr><tr><td>bossvalue:</td><td>".$boss['bonus']."</td></tr></table>";
        		$this->raid_div .= "<div id='boss_".$key."_".$bkey."' class='boss' style='margin-left: ".$m."px;' ".substr($html->Tooltip($bossinfo,''),5,-8)."></div>";
        	}
          }
        }
        $out .= $this->raid_div."<div id='times_".$key."'>";
        $tkey = 0;
        foreach($this->members[$key]['times'] as $time) {
        	$s = ($time['standby']) ? '_standby' : '';
        	$w = ($time['leave']-$time['join'])/20;
        	$ml = ($time['join']-$width['begin'])/20;
        	settype($w, 'int');
        	settype($ml, 'int');
        	$out .= "<div id='times_".$key."_".$tkey."' class='time".$s."' style='width:".$w."px; margin-left: ".$ml."px;' onmouseover='set_time_key(this.id)'>";
        	$out .= "<div class='time_left' onmousedown='scale_start(\"left\")'></div>";
        	$out .= "<div class='time_middle' onmousedown='scale_start(\"middle\")'>";
        	$out .= "<input type='hidden' name='members[".$key."][times][".$tkey."][join]' value='".$time['join']."' id='times_".$key."_".$tkey."j' />";
        	$out .= "<input type='hidden' name='members[".$key."][times][".$tkey."][leave]' value='".$time['leave']."' id='times_".$key."_".$tkey."l' />";
        	if($time['standby']) {
        		$out .= "<input type='hidden' name='members[".$key."][times][".$tkey."][extra]' value='standby' id='times_".key."_".$tkey."s' />";
        	}
        	$out .= "</div><div class='time_right' onmousedown='scale_start(\"right\")'></div></div>";
        	$tkey++;
        }
        $out .= "<div style='display:none;'><div id='times_".$key."_99' class='time' style='width:0px; margin-left:0px;' onmouseover='set_time_key(this.id)'>";
        $out .= "<div class='time_left' onmousedown='scale_start(\"left\")'></div>";
        $out .= "<div class='time_middle' onmousedown='scale_start(\"middle\")'>";
        $out .= "<input type='hidden' name='members[".$key."][times][99][join]' value='0' id='times_".$key."_99j' />";
        $out .= "<input type='hidden' name='members[".$key."][times][99][leave]' value='0' id='times_".$key."_99l' />";
        $out .= "</div><div class='time_right' onmousedown='scale_start(\"right\")'></div></div></div>";

        $this->create_timebar($width['begin'], $width['end'], $px_time);
        $out .= "<div><div id='time_scale_".$key."' class='time_scale_hide'></div></div></div></td>";

    	//only do this once
    	if(!$this->tpl_assignments) {
    		$tpl->assign_var('PXTIME', $px_time);
    		$tpl->add_css(".time_scale {
								position: absolute;
								background-image: url(./../../../plugins/raidlogimport/images/time_scale.png);
								background-repeat: repeat-x;
								width: ".$px_time."px;
								height: 18px;
								margin-top: 10px;
								z-index: 13;
							}");
    		$tpl->add_js("$(document).ready(function() {
                            $('#member_form').data('raid_start', ".$width['begin'].");
                            $('.time_middle').dblclick(function() {
								var change_id = $('#times_' + member_id + '_' + time_id + ' ~ div');
								$('#times_' + member_id + '_' + time_id).remove();
								var lgth = 'times_' + member_id + '_';
								for(var i=0; i < change_id.length; i++) {
									if(!isNaN(parseInt(change_id[i].id.substr(lgth.length)))) {
    									change_id_of_input(change_id[i].id, (parseInt(change_id[i].id.substr(lgth.length)) -1));
										change_id[i].id = \"times_\" + member_id + \"_\" + (parseInt(change_id[i].id.substr(lgth.length)) -1);
									}
								}
							});
							$('.time,time_standby').mouseout(function() {
								time_id = null;
							});
							$('.add_time').mouseover(function() {
								$('#time_scale_' + member_id).attr('class', 'time_scale');
							});
							$('.add_time').mouseout(function() {
								$('#time_scale_' + member_id).attr('class', 'time_scale_hide');
								member_id = null;
							});
							$('.add_time').dblclick(function() {
								//between which times did the user click?
								var all_times = $('#times_' + member_id + ' > div');
								var left = new Array(0,0);
								var right = new Array(0,0);
								for(var i=0; i < all_times.length; i++) {
									var current = new Array();
									current.offset = $(all_times[i]).offset();
									current.right_edge = (current.offset.left + $(all_times[i]).width());
									if(current.right_edge < posx && current.right_edge >left[1]) {
										left[0] = all_times[i].id;
										left[1] = current.right_edge;
									}
									if(current.offset.left > posx && current.offset.left < right[1]) {
										right[0] = all_times[i].id;
										right[1] = current.offset.left;
									}
								}
								var change_id = '';
                                var lgth = 'times_' + member_id + '_';
                                var object_to_add = $('#times_' + member_id + '_99').clone(true);
                                var selector = '';
                                var type = 'after';
                                var new_time_key = 0;
								//is there an element left of mouse?
								if(left[0]) {
                                    new_time_key = parseInt(left[0].substr(lgth.length)) + 1;
                                	object_to_add.attr('id', 'times_' + member_id + '_' + new_time_key);
									change_id = $('#' + left[0] + ' ~ div');
									selector = '#' + left[0];
                                } else { //its not so change all elements
                                	object_to_add.attr('id', 'times_' + member_id + '_0');
                                    change_id = $('#times_' + member_id + '_0, #times_' + member_id + '_0 ~ div');
                                    selector = '#times_' + member_id + '_1';
                                    type = 'before';
                                }
								for(i=(change_id.length -1); i>=0; i--) {
									if(!isNaN(parseInt(change_id[i].id.substr(lgth.length)))) {
										change_id_of_input(change_id[i].id, (parseInt(change_id[i].id.substr(lgth.length)) + 1));
        								change_id[i].id = 'times_' + member_id + '_' + (parseInt(change_id[i].id.substr(lgth.length)) + 1);
        							}
								}
								if(type == 'before') {
									$(selector).before(object_to_add);
								} else {
									$(selector).after(object_to_add);
								}
								if(left[1]) {
									left[1] = left[1] - posi_null;
								}
								object_to_add.css('margin-left', (left[1] + 2) + 'px');
								if(!right[1]) {
									right[1] = 12;
								}
								object_to_add.css('width', (right[1] - 2) + 'px');
								change_id_of_input('times_' + member_id + '_99', new_time_key);                             
                                $('#times_' + member_id + '_' + new_time_key + 'j').attr('value', $('#member_form').data('raid_start') + (left[1]+2)*20);
                                $('#times_' + member_id + '_' + new_time_key + 'l').attr('value', $('#member_form').data('raid_start') + (left[1] + right[1])*20);
							});
                        });");
    		$tpl->js_file($eqdkp_root_path.'plugins/raidlogimport/templates/dmem.js');
    		$tpl->css_file($eqdkp_root_path.'plugins/raidlogimport/templates/dmem.css');
    		$this->tpl_assignments = true;
    	}
    	return $out;
    }

	private function create_timebar($start, $end, $px_time)
	{
		if(!$this->timebar_created) {
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

	private function get_member_ranks()
	{
		global $db;
		if(!$this->member_ranks)
		{
			$sql = "SELECT m.member_name, r.rank_name FROM __members m, __member_ranks r WHERE m.member_rank_id = r.rank_id;";
			$result = $db->query($sql);
			while ($row = $db->fetch_record($result))
			{
				$this->member_ranks[$row['member_name']] = $row['rank_name'];
			}
			$ssql = "SELECT rank_name FROM __member_ranks WHERE rank_id = '".$this->config['new_member_rank']."';";
			$this->member_ranks['new'] = $db->query_first($ssql);
		}
	}

	public function delete($key)
	{
	}

	public function __destruct()
	{
		global $rli;
		$rli->add_cache_data('member', $this->members);
	}
  }
}
?>
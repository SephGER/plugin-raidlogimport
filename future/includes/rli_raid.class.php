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

if(!class_exists('rli_raid')) {
class rli_raid {
	private $data = array();
	private $raids = array();
	private $hour_count = 0;
	public $real_ids = array();

	public function __construct() {
		global $rli;
		$this->raids = $rli->get_cache_data('raid');
		$this->data = $rli->get_cache_data('data_raid');
	}
	
	public function reset() {
		$this->raids = array();
		$this->data = array();
	}

	private function config($name) {
		global $rli;
		return $rli->config($name);
	}
	
	public function flush_data() {
		$this->data = array();
	}

	public function add_zone($name, $enter, $leave, $diff=0) {
		$this->data['zones'][] = array('name' => $name, 'enter' => (int) $enter, 'leave' => (int) $leave, 'diff' => $diff);
	}

	public function add_bosskill($name, $time, $diff=0) {
		$this->data['bosskills'][] = array('name' => $name, 'time' => (int) $time, 'diff' => $diff);
	}

	public function load_raids() {
		global $in, $pdh, $time;
		$this->raids = array();
		foreach($_POST['raids'] as $key => $raid) {
			if(!isset($raid['delete'])) {
				$this->raids[$key]['begin'] = $time->fromformat($in->get('raids:'.$key.':start_date'), 1);
				$this->raids[$key]['end'] = $time->fromformat($in->get('raids:'.$key.':end_date'), 1);
				$this->raids[$key]['note'] = $in->get('raids:'.$key.':note');
				$this->raids[$key]['value'] = runden(floatvalue($in->get('raids:'.$key.':value', '0.0')));
				$this->raids[$key]['timebonus'] = runden(floatvalue($in->get('raids:'.$key.':timebonus', '0.0')));
				$this->raids[$key]['event'] = $in->get('raids:'.$key.':event');
				$this->raids[$key]['bosskill_add'] = $in->get('raids:'.$key.':bosskill_add', 0);
				$this->raids[$key]['diff'] = $in->get('raids:'.$key.':diff', 0);
				$bosskills = array();
				if(is_array($raid['bosskills'])) {
					foreach($raid['bosskills'] as $u => $bk) {
						if(!isset($bk['delete'])) {
							$bosskills[$u]['time'] = $time->fromformat($in->get('raids:'.$key.':bosskills:'.$u.':date'), 1);
							$bosskills[$u]['bonus'] = runden(floatvalue($in->get('raids:'.$key.':bosskills:'.$u.':bonus', '0.0')));
							$bosskills[$u]['timebonus'] = runden(floatvalue($in->get('raids:'.$key.':bosskills:'.$u.':timebonus', '0.0')));
							$bosskills[$u]['id'] = $in->get('raids:'.$key.':bosskills:'.$u.':id');
							$bosskills[$u]['diff'] = $in->get('raids:'.$key.':bosskills:'.$u.':diff');
							if(!is_numeric($bosskills[$u]['id'])) {
								$id = $pdh->get('rli_boss', 'id_string', array($bosskills[$u]['id'], $bosskills[$u]['diff']));
								if($id) $bosskills[$u]['id'] = $id;
							}
						}
					}
				}
				$this->raids[$key]['bosskills'] = $bosskills;
				$this->raids[$key]['timebonus'] = floatvalue($in->get('raids:'.$key.':timebonus', '0.0'));
			}
		}
	}

	public function create() {
		global $pdh;
		$key = 1;
		foreach( $this->data['zones'] as $zone ) {
			if( $this->config('raidcount') == 0 ) {
				$this->raids[$key]['begin'] = $zone['enter'];
				$this->raids[$key]['end'] = $zone['leave'];
				$this->raids[$key]['zone'] = $zone['name'];
				$this->raids[$key]['diff'] = $zone['diff'];
				$key++;
			}
			if( $this->config('raidcount') & 1 ) {
				for($i = $zone['enter']; $i<=$zone['leave']; $i+=3600)
				{
					$this->raids[$key]['begin'] = $i;
					$this->raids[$key]['end'] = (($i+3600) > $zone['leave']) ? $zone['leave'] : $i+3600;
					$this->raids[$key]['zone'] = $zone['name'];
					$this->raids[$key]['diff'] = $zone['diff'];
					$key++;
				}
			}
			if( $this->config('raidcount') & 2) {
				foreach($this->data['bosskills'] as $b => $bosskill) {
					$temp = $this->get_bosskill_raidtime($zone['enter'], $zone['leave'], $bosskill['time'], @$this->data['bosskills'][$b-1]['time'], @$this->data['bosskills'][$b+1]['time']);
					$this->raids[$key]['begin'] = $temp['begin'];
					$this->raids[$key]['end'] = $temp['end'];
					$this->raids[$key]['zone'] = $zone['name'];
					$this->raids[$key]['diff'] = $zone['diff'];
					$this->raids[$key]['bosskills'][$b] = $bosskill['name'];
					$key++;
				}
			}
		}
		$this->data['add']['att_begin_raid'] = 1;
		$this->data['add']['att_end_raid'] = $key-1;
		if($this->config('attendence_raid')) {
			if($this->config('attendence_begin') > 0) {
				$this->raids[0]['begin'] = $this->raids[1]['begin'];
				$this->raids[0]['end'] = $this->raids[1]['begin'] + $this->config('attendence_time');
				$this->raids[0]['event'] = $pdh->get('rli_zone', 'eventbystring', array($this->raids[1]['zone']));
				$this->raids[0]['note'] = $this->config('att_note_begin');
				$this->raids[0]['value'] = $this->config('attendence_begin');
				$this->data['add']['att_begin_raid'] = 0;
			}
			if($this->config('attendence_end') > 0) {
				$this->raids[$key]['begin'] = $this->raids[$key-1]['end'] - $this->config('attendence_time');
				$this->raids[$key]['end'] = $this->raids[$key-1]['end'];
				$this->raids[$key]['event'] = $pdh->get('rli_zone', 'eventbystring', array($this->raids[$key-1]['zone']));
				$this->raids[$key]['note'] = $this->config('att_note_end');
				$this->raids[$key]['value'] = $this->config('attendence_end');
				$this->data['add']['att_end_raid'] = $key;
				$key++;
			}
		}
		$this->data['add']['standby_raid'] = -1;
		if($this->config('standby_raid') <= 1) {
			$this->raids[$key]['begin'] = $this->raids[1]['begin'];
			$this->raids[$key]['end'] = $this->raids[$key-1]['end'];
			$this->raids[$key]['diff'] = $this->raids[1]['diff'];
			$this->raids[$key]['zone'] = $this->raids[1]['zone'];
			$this->data['add']['standby_raid'] = $key;
		}
	}

	public function add_new($number) {
		for($i=1; $i<=$number; $i++) {
			$this->raids[] = array();
		}
	}

	public function new_bosskill($raidkey, $number) {
		for($i=1; $i<=$number; $i++) {
			$this->raids[$raidkey]['bosskills'][] = array();
		}
	}

	public function recalc($first=false) {
		$ignore = $this->get_attendance_raids(true);
		foreach( $this->raids as $key => $raid ) {
			if(!in_array($key, $ignore)) {
				$this->diff = $raid['diff'];
				if( (!($this->config('raidcount') & 1 AND $this->config('raidcount') & 2) OR count($this->raids[$key]['bosskills']) == 1) AND $first) {
					$bosskills = $this->get_bosskills($raid['begin'], $raid['end']);
					$this->raids[$key]['bosskills'] = $bosskills;
					$this->raids[$key]['event'] = $this->get_event($key);
				}
				$this->raids[$key]['note'] = ($key == $this->data['add']['standby_raid']) ? $this->config('standby_raidnote') : $this->get_note($key);
				$this->raids[$key]['value'] = runden($this->get_value($key, false));
			}
		}
	}

	public function delete($key) {
		unset($this->raids[$key]);
	}

	public function get_value($key, $times=false, $attdkp_force=array(-1,-1)) {
		if($key == $this->data['add']['standby_raid'] AND $this->config('standby_absolute')) {
			return $this->config('standby_value');
		}
		$timedkp = $this->get_timedkp($key, $times);
		$bossdkp = $this->get_bossdkp($key, $times);
		$eventdkp = $this->get_eventdkp($key, $times);
		#$itemdkp = $this->get_itemdkp($key, $times);
		$attdkp = $this->get_attdkp($key, $times, $attdkp_force);
		$dkp = $timedkp + $bossdkp + $eventdkp + $attdkp;
		return $dkp;
	}

	public function display($with_form=false) {
		global $tpl, $html, $core, $rli, $pdh, $user, $jquery, $time;
		
		if(!isset($this->event_drop)) {
			$this->event_drop = $pdh->aget('event', 'name', 0, array($pdh->get('event', 'id_list')));
			asort($this->event_drop);
		}
		if(!isset($this->diff_drop)) $this->diff_drop = array($user->lang('diff_0'), $user->lang('diff_1'), $user->lang('diff_2'), $user->lang('diff_3'), $user->lang('diff_4'));
		if(!isset($this->bk_list)) {
			$this->bk_list = $pdh->aget('rli_boss', 'html_note', 0, array($pdh->get('rli_boss', 'id_list'), false));
			asort($this->bk_list);
		}
		$last_key = 0;
		ksort($this->raids);
		$tpl->add_js("var boss_keys = new Array();", 'docready');
		foreach($this->raids as $ky => $rai) {
			if($ky == $this->data['add']['standby_raid'] AND $this->config('standby_raid') == 0) {
				continue;
			}
			$bosskills = '';
			if(!$with_form) {
				foreach($rai['bosskills'] as $bk) {
					$note = (!is_numeric($bk['id'])) ? $bk['id'] : $pdh->geth('rli_boss', 'note', array($bk['id']));
					$bosskills .= '<tr><td>'.$note.'</td><td colspan="2">'.date('H:i:s',$bk['time']).'</td><td>'.$bk['bonus'].'</td></tr>';
				}
			}
			if(isset($rai['bosskill_add'])) {
				$this->new_bosskill($ky, $rai['bosskill_add']);
			}
			$begin = $time->user_date($rai['begin'], true, false, false, function_exists('date_create_from_format'));
			$end = $time->user_date($rai['end'], true, false, false, function_exists('date_create_from_format'));
			$tpl->assign_block_vars('raids', array(
				'COUNT'     => $ky,
				'START_DATE'=> ($with_form) ? $jquery->Calendar("raids[".$ky."][start_date]", $begin, '', array('id' => 'raids_'.$ky.'_start_date', 'timepicker' => true, 'class' => 'class="input"')) : $begin,
				'END_DATE'	=> ($with_form) ? $jquery->Calendar("raids[".$ky."][end_date]", $end, '', array('id' => 'raids_'.$ky.'_end_date', 'timepicker' => true, 'class' => 'class="input"')) : $end,
				'EVENT'		=> ($with_form) ? $html->DropDown('raids['.$ky.'][event]', $this->event_drop, $rai['event'], '', '', 'input', 'event_raid'.$ky) : $pdh->get('event', 'name', array($rai['event'])),
				'TIMEBONUS'	=> $rai['timebonus'],
				'VALUE'		=> $rai['value'],
				'NOTE'		=> $rai['note'],
				'DIFF'		=> ($with_form) ? $html->DropDown('raids['.$ky.'][diff]', $this->diff_drop, $rai['diff'], '', '', 'input', 'diff_raid'.$ky) : $user->lang('diff_'.$rai['diff']),
				'BOSSKILLS' => $bosskills,
				'DELDIS'	=> 'disabled="disabled"')
			);
			$last_key = $ky;
			if($with_form) {
				//js deletion
				$options = array(
					'custom_js' => "$('#'+del_id).css('display', 'none'); $('#'+del_id+'submit').removeAttr('disabled');",
					'withid' => 'del_id',
					'message' => $user->lang('rli_delete_raids_warning')
				);
				$jquery->Dialog('delete_warning', $user->lang('confirm_deletion'), $options, 'confirm');

				if(is_array($rai['bosskills'])) {
					foreach($rai['bosskills'] as $xy => $bk) {
						$import = false;
						$html_id = 'raid'.$ky.'_boss'.$xy;
						$name_field = '';
						if(is_numeric($bk['id'])) {
							$name_field = $html->DropDown('raids['.$ky.'][bosskills]['.$xy.'][id]', $this->bk_list, $bk['id'], '', '', 'input', 'a'.uniqid());
						} else {
							$name_field = $bk['id'];
							$params = "&string=' + $('#id_".$html_id."').val() + '&bonus=' + $('#bonus_".$html_id."').val() + '&timebonus=' + $('#timebonus_".$html_id."').val() + '&diff=' + $('#diff_".$html_id."').val()";
							$params .= " + '&note=' + $('#id_".$html_id."').val()";
							$onclosejs = "$('#onclose_submit').removeAttr('disabled'); $('form:first').submit();";
							$jquery->Dialog($html_id, $user->lang('bz_import_boss'), array('url' => "bz.php?simple_head=simple&upd=true".$params." + '&", 'width' => 1200, 'onclosejs' => $onclosejs));
							$import = true;
						}
						$tpl->assign_block_vars('raids.bosskills', array(
							'BK_SELECT' => $name_field,
							'BK_DATE'   => $jquery->Calendar("raids[".$ky."][bosskills][".$xy."][date]", $time->user_date($bk['time'], true, false, false, function_exists('date_create_from_format')), '', array('id' => 'raids_'.$ky.'_boss_'.$xy.'_date', 'timepicker' => true, 'class' => 'class="input"')),
							'BK_BONUS'  => $bk['bonus'],
							'BK_TIMEBONUS' => $bk['timebonus'],
							'BK_DIFF'	=> $html->DropDown('raids['.$ky.'][bosskills]['.$xy.'][diff]', $this->diff_drop, $bk['diff'], '', '', 'input', 'diff_'.$html_id),
							'BK_KEY'    => $xy,
							'IMPORT'	=> ($import) ? $html_id : 0,
							'DELDIS'	=> 'disabled="disabled"')
						);
					}
				}
				$tpl->add_js("boss_keys[".$ky."] = ".($xy+1).";", 'docready');
				$tpl->assign_block_vars('raids.bosskills', array(
					'BK_SELECT'	=> $html->DropDown('raids['.$ky.'][bosskills][99][id]', $this->bk_list, 0, '', '', 'input', 'a'.uniqid()),
					'BK_DATE'	=> '<input type="text" name="raids['.$ky.'][bosskills][99][date]" id="raids_'.$ky.'_boss_99_date" size="15" />',
					'BK_DIFF'	=> $html->DropDown('raids['.$ky.'][bosskills][99][diff]', $this->diff_drop, 0, '', '', 'input', 'diff_raid'.$ky.'_boss99'),
					'BK_KEY'	=> 99,
					'DISPLAY'	=> 'style="display: none;"'
				));
			}
		}
		if($with_form) {
			$tpl->assign_block_vars('raids', array(
				'COUNT'     => 999,
				'START_DATE'=> '<input type="text" name="raids[999][start_date]" id="raids_999_start_date" size="15" />',
				'END_DATE'	=> '<input type="text" name="raids[999][end_date]" id="raids_999_end_date" size="15" />',
				'EVENT'		=> $html->DropDown('raids[999][event]', $this->event_drop, $rai['event'], '', '', 'input', 'a'.uniqid()),
				'DIFF'		=> $html->DropDown('raids[999][diff]', $this->diff_drop, $rai['diff'], '', '', 'input', 'a'.uniqid()),
				'DISPLAY'	=> 'style="display: none;"'
			));
			$tpl->assign_block_vars('raids.bosskills', array(
				'BK_SELECT'	=> $html->widget(array('type' => 'dropdown', 'name' => 'raids[999][bosskills][99][id]', 'options' => $this->bk_list, 'selected' => 0, 'id' => 'a'.uniqid(), 'no_lang' => true)),
				'BK_DATE'	=> '<input type="text" name="raids[999][bosskills][99][date]" id="raids_999_boss_99_date" size="15" />',
				'BK_DIFF'	=> $html->widget(array('type' => 'dropdown', 'name' => 'raids[999][bosskills][99][diff]', 'options' => $this->diff_drop, 'selected' => 0, 'id' => 'diff_raid999_boss99', 'no_lang' => true)),
				'BK_KEY'	=> 99,
				'DISPLAY'	=> 'style="display: none;"'
			));
			$functioncall = $jquery->Calendar('n', 0, '', array('timepicker' => true, 'return_function' => true));
			$tpl->add_js(
"var rli_rkey = ".($last_key+1).";
$('.del_boss').live('click', function() {
	$(this).removeClass('del_boss');
	delete_warning($(this).attr('class'));
});
$('.del_raid').live('click', function() {
	$(this).removeClass('del_raid');
	delete_warning($(this).attr('class'));
});
$('#add_raid_button').click(function() {
	var raid = $('#raid_999').clone(true);
	raid.find('#raid_999submit').attr('disabled', 'disabled');
	raid.html(raid.html().replace(/999/g, rli_rkey));
	raid.attr('id', 'raid_'+rli_rkey);
	raid.removeAttr('style');
	$('#raid_999').before(raid);
	$('#raids_'+rli_rkey+'_end_date').".$functioncall.";
	$('#raids_'+rli_rkey+'_start_date').".$functioncall.";
	boss_keys[rli_rkey] = 0;
	rli_rkey++;
});
$('input[name=\"add_boss_button[]\"]').live('click', function() {
	var raid_key = $(this).attr('id').substr(-1);
	var boss = $('#raid_'+raid_key+'_boss_99').clone(true);
	boss.find('#raid_'+raid_key+'_boss_99submit').attr('disabled', 'disabled');
	boss.html(boss.html().replace(/99/g, boss_keys[raid_key]));
	boss.attr('id', 'raid_'+raid_key+'_boss_'+boss_keys[raid_key]);
	boss.removeAttr('style');
	$('#raid_'+raid_key+'_boss_99').before(boss);
	$('#raids_'+raid_key+'_boss_'+boss_keys[raid_key]+'_date').".$functioncall.";
	boss_keys[raid_key]++;
});", 'docready');
		}
	}

	public function get_start_end() {
		if($this->raids) {
			return array('begin' => $this->raids[1]['begin'], 'end' => $this->raids[max(array_keys($this->raids))]['end']);
		}
		return false;
	}

	public function get_data() {
		return $this->raids;
	}
	
	public function get($raid_key) {
		return $this->raids[$raid_key];
	}

	public function check($bools) {
		if(is_array($this->raids)) {
			foreach($this->raids as $key => $raid) {
				if(!$raid['begin'] OR !$raid['event'] OR !$raid['note']) {
					$bools['false']['raid'] = false;
				}
			}
		} else {
			$bools['false']['raid'] = 'miss';
		}
	}
	
	public function insert() {
		global $rli, $pdh;
		$raid_attendees = array();
		foreach($rli->member->raid_members as $member_id => $raid_keys) {
			foreach($raid_keys as $raid_key) {
				$raid_attendees[$raid_key][] = $member_id;
			}
		}
		foreach($this->raids as $key => $raid) {
			$this->real_ids[$key] = $pdh->put('raid', 'add_raid', array($raid['begin'], $raid_attendees[$key], $raid['event'], $raid['note'], $raid['value']));
		}
		if(in_array(false, $this->real_ids)) {
			return false;
		}
		return true;
	}
	
	/*
	 * get seconds the member was in the raid
	 * @int $key: key of the raid
	 * @array $times: array of join/leave times
	 * @int $standby: 0: time in raid regardless of standbystatus; 1: time in raid without standby; 2: time in raid being standby
	 * return @int
	 */
	public function in_raid($key, $times=false, $standby=0) {
		$in_raid = 0;
		if(!is_numeric($key)) {
			$this->raids['temp'] = $key;
			$key = 'temp';
		}
		if(is_array($times)) {
			foreach ($times as $time) {
				if(!$standby OR ($standby == 1 AND (!isset($time['standby']) OR !$time['standby'])) OR ($standby == 2 AND $time['standby'])) {
					if($time['join'] < $this->raids[$key]['end'] AND $time['leave'] > $this->raids[$key]['begin']) {
						if($time['leave'] > $this->raids[$key]['end']) {
							$in_raid += $this->raids[$key]['end'];
						} else {
							$in_raid += $time['leave'];
						}
						if($time['join'] < $this->raids[$key]['begin']) {
							$in_raid -= $this->raids[$key]['begin'];
						} else {
							$in_raid -= $time['join'];
						}
					}
				}
			}
		} else {
			$in_raid = $this->raids[$key]['end'] - $this->raids[$key]['begin'];
		}
		if($key == 'temp') unset($this->raids['temp']);
		return $in_raid;
	}

	public function get_memberraids($times) {
		$raid_list = array();
		$att_raids = $this->get_attendance_raids();
		foreach($this->raids as $key => $rdata) {
			if($key == $att_raids['begin']) {
				$att = $this->get_attendance($times);
				if($att['begin']) {
					$raid_list[] = $key;
					continue;
				}
			}
			if($key == $att_raids['end']) {
				$att = $this->get_attendance($times);
				if($att['end']) {
					$raid_list[] = $key;
					continue;
				}
			}
			if($this->config('attendance_raids') AND in_array($key, $att_raids)) {
				continue;
			}
			$standby = 1;
			if($key == $this->data['add']['standby_raid'] AND $this->config('standby_raid') <= 1) {
				$standby = 2;
			} elseif($this->config('standby_raid') == 2) {
				$standby = 0;
			}
			if(($this->in_raid($key, $times, $standby)/$this->in_raid($key)) >= ($this->config('member_raid') / 100)) {
				$raid_list[] = $key;
			}
		}
		return $raid_list;
	}

	public function get_checkraidlist($memberraids, $mkey) {
		global $eqdkp_root_path, $pcache, $user;

		$td = '';
		if(!$this->th_raidlist) {
			$pcache->CheckCreateFolder($pcache->FolderPath('raidlogimport'));
			foreach($this->raids as $rkey => $raid) {
				$imagefile = $eqdkp_root_path.$pcache->FileLink('image'.$rkey.'.png', 'raidlogimport');
				if(!$pcache->CheckCreateFile($imagefile, true)) {
					$this->th_raidlist = '<td colspan="20">'.$user->lang('rli_error_imagecreate').'</td>';
				}
				$image = imagecreate(20, 150);
				$weiss = imagecolorallocate($image, 0xFF, 0xFF, 0xFF);
				$schwarz = imagecolorallocate($image, 0x00, 0x00, 0x00);
				imagefill($image, 0, 0, $weiss);
				imagestringup($image, 2, 2, 148, $raid['note'], $schwarz);
				imagepng($image, $imagefile);
				$this->th_raidlist .= '<td width="20px"><img src="'.$imagefile.'" title="'.$raid['note'].'" alt="'.$rkey.'" /></td>';
				imagedestroy($image);
			}
		}
		foreach($this->raids as $rkey => $raid) {
			$td .= '<td><input type="checkbox" name="members['.$mkey.'][raid_list][]" value="'.$rkey.'" title="'.$raid['note'].'" '.((in_array($rkey, $memberraids)) ? 'checked="checked"' : '').' /></td>';
		}
		return $td;
	}

	public function raidlist($with_event=false) {
		if(!isset($this->raidlist) OR ($with_event AND !isset($this->raidevents))) {
			foreach($this->raids as $key => $raid) {
				$this->raidlist[$key] = $raid['note'];
				if($with_event) $this->raidevents[$key] = $raid['event'];
			}
		}
		return $this->raidlist;
	}

	public function count() {
		return count($this->raids);
	}

	public function get_attendance($times) {
		$attendance = array('begin' => false, 'end' => false);
		foreach($this->raids as $key => $raid) {
			if($this->calc_attdkp($key, 'begin', $times))
				$attendance['begin'] = true;
			if($this->calc_attdkp($key, 'end', $times))
				$attendance['end'] = true;
			if($attendance['begin'] AND $attendance['end'])
				break;
		}
		return $attendance;
	}
	
	public function item_in_raid($key, $time) {
		if($this->raids[$key]['begin'] < $time AND $this->raids[$key]['end'] > $time) {
			return true;
		}
		return false;
	}

	public function get_attendance_raids($strict=false) {
		$att_ra = array();
		if($this->config('attendance_raid')) {
			$att_ra['begin'] = $this->data['add']['att_begin_raid'];
			$att_ra['end'] = $this->data['add']['att_end_raid'];
		} elseif(!$strict) {
			$att_ra['begin'] = ($this->config('attendance_begin')) ? $this->data['add']['att_begin_raid'] : 0;
			$att_ra['end'] = ($this->config('attendance_end')) ? $this->data['add']['att_end_raid'] : 0;
		}
		return $att_ra;
	}
	
	public function get_standby_raid() {
		return $this->data['add']['standby_raid'];
	}
	
	private function calc_timedkp($key, $in_raid) {
		$timedkp = $in_raid['hours'] * $this->raids[$key]['timebonus'];
		if($this->config('timedkp_handle')) {
			$timedkp += ($in_raid['minutes'] >= $this->config('timedkp_handle')) ? $this->raids[$key]['timebonus'] : 0;
		} else {
			$timedkp += $this->raids[$key]['timebonus'] * ($in_raid['minutes']/60);
		}
		return $timedkp;
	}

	private function get_timedkp($key, $times) {
		$timedkp = 0;
		if(	$this->config('standby_raid') <= 1 AND (
				($this->config('standby_dkptype') & 2 AND $key == $this->data['add']['standby_raid']) OR 
				($this->config('use_dkp') & 2 AND $key != $this->data['add']['standby_raid'])
			)) {
			$standby = ($key == $this->data['add']['standby_raid']) ? 2 : 1;
			$in_raid = format_duration($this->in_raid($key, $times, $standby));
			$timedkp = ($standby == 2) ? $this->calc_timedkp($key, $in_raid)*$this->config('standby_value')/100 : $this->calc_timedkp($key, $in_raid);
		} elseif($this->config('standby_raid') == 2) {
			$in_raid = array(0, 0);
			if($this->config('use_dkp') & 2) {
				$in_raid[0] = format_duration($this->in_raid($key, $times, 1));
				$in_raid[0] = $this->calc_timedkp($key, $in_raid[0]);
			}
			if($this->config('standby_dkptype') & 2) {
				$in_raid[1] = format_duration($this->in_raid($key, $times, 2));
				$in_raid[1] = $this->calc_timedkp($key, $in_raid[1]);
			}
			$timedkp = $in_raid[0] + $in_raid[1]*$this->config('standby_value')/100;
		}
		return $timedkp;
	}
	
	private function calc_timebossdkp($bonus, $in_raid) {
		$timedkp = $in_raid['hours'] * $bonus;
		if($this->config('timedkp_handle')) {
			$timedkp += ($in_raid['minutes'] >= $this->config('timedkp_handle')) ? $bonus : 0;
		} else {
			$timedkp += $bonus * ($in_raid['minutes']/60);
		}
		return $timedkp;
	}
	
	private function calc_bossdkp($key, $times, $standby, $standby1=0) {
		$bossdkp = 0;
		foreach ($this->raids[$key]['bosskills'] as $b => $bosskill) {
			//absolute bossdkp
			if($times !== false) {
				foreach ($times as $time) {
					if(isset($time['standby']) AND $standby == $time['standby']) {
						if($time['join'] < $bosskill['time'] AND $time['leave'] > $bosskill['time']) {
							$bossdkp += $bosskill['bonus'];
							break;
						}
					}
				}
			} else {
				$bossdkp += $bosskill['bonus'];
			}
			//timed bossdkp
			$kill_before = (isset($this->data['bosskills'][$b-1]['time'])) ? $this->data['bosskills'][$b-1]['time'] : NULL;
			$kill_after = (isset($this->data['bosskills'][$b+1]['time'])) ? $this->data['bosskills'][$b+1]['time'] : NULL;
			$temp = $this->get_bosskill_raidtime($this->raids[$key]['begin'], $this->raids[$key]['end'], $bosskill['time'], $kill_before, $kill_after);
			$in_boss = format_duration($this->in_raid($temp, $times, $standby1));
			$bossdkp += $this->calc_timebossdkp($bosskill['timebonus'], $in_boss);
		}
		return $bossdkp;
	}

	private function get_bossdkp($key, $times) {
		$bossdkp = 0;
		if(	$this->config('standby_raid') <= 1 AND (
				($this->config('standby_dkptype') & 1 AND $key == $this->data['add']['standby_raid']) OR 
				($this->config('use_dkp') & 1 AND $key != $this->data['add']['standby_raid'])
			)) {
			$standby = ($key == $this->data['add']['standby_raid']) ? true : false;
			$standby1 = ($key == $this->data['add']['standby_raid']) ? 2 : 1;
			$bossdkp = ($standby) ? $this->calc_bossdkp($key, $times, $standby, $standby1)*$this->config('standby_value')/100 : $this->calc_bossdkp($key, $times, $standby, $standby1);
		} elseif($this->config('standby_raid') == 2) {
			if($this->config('use_dkp') & 1) {
				$bossdkp += $this->calc_bossdkp($key, $times, false, 1);
			}
			if($this->config('standby_dkptype') & 1) {
				$bossdkp += $this->calc_bossdkp($key, $times, true, 2)*$this->config('standby_value')/100;
			}
		}
		return $bossdkp;
	}

	private function get_eventdkp($key) {
		global $pdh;
		$eventdkp = 0;
		if($this->config('use_dkp') & 4 AND $key != $this->data['add']['standby_raid']) {
			$eventdkp = $pdh->get('event', 'value', array($this->raids[$key]['event']));
		} elseif($this->config('standby_dkptype') & 4 AND $key == $this->data['add']['standby_raid']) {
			$eventdkp = $pdh->get('event', 'value', array($this->raids[$key]['event']))*$this->config('standby_value')/100;
		}
		return $eventdkp;
	}
	
	private function get_attdkp($key, $times=false, $force=array(-1,-1)) {
		return $this->calc_attdkp($key, 'begin', $times, $force) + $this->calc_attdkp($key, 'end', $times, $force);
	}
	
	private function calc_attdkp($key, $type, $times=false, $force=array(-1,-1)) {
		$att_raids = $this->get_attendance_raids(true);
		if($this->config('attendance_'.$type) && $key == $att_raids[$type]) {
			if($times !== false) {
				if($type == 'begin') {
					$ct = $this->config('attendence_time') + $this->raids[$key]['begin'];
					foreach($times as $time) {
						if($force[0] > 0 OR ($force[0] < 0 AND ($time['join'] < $ct AND (($time['standby'] AND $this->config('standby_att')) OR !$time['standby']))))
							return $this->config('attendance_begin');
					}	
				} elseif($type == 'end') {
					$ct = $this->raids[$key]['end'] - $this->config('attendence_time');
					foreach($times as $time) {
						if($force[1] > 0 OR ($force[1] < 0 AND ($time['leave'] > $ct AND (($time['standby'] AND $this->config('standby_att')) OR !$time['standby']))))
							return $this->config('attendance_end');
					}
				}
			} else {
				return $this->config('attendance_'.$type);
			}
		}
	}

	private function get_bosskills($begin, $end) {
		global $pdh;
		$bosskills = array();
		foreach ($this->data['bosskills'] as $b => $bosskill) {
			if($begin <= $bosskill['time'] AND $bosskill['time'] <= $end) {
				$id = $pdh->get('rli_boss', 'id_string', array($bosskill['name'], $bosskill['diff']));
				if($id) {
					$bosskills[$b]['id'] = $id;
					$bosskills[$b]['bonus'] = $pdh->get('rli_boss', 'bonus', array($id));
					$bosskills[$b]['timebonus'] = $pdh->get('rli_boss', 'timebonus', array($id));
				} else {
					$bosskills[$b]['id'] = $bosskill['name'];
					$bosskills[$b]['bonus'] = 0;
					$bosskills[$b]['timebonus'] = 0;
				}
				$bosskills[$b]['time'] = $bosskill['time'];
				$bosskills[$b]['diff'] = $bosskill['diff'];
			}
		}
		return $bosskills;
	}

	private function get_bosskill_raidtime($begin, $end, $bosskill, $bosskill_before, $bosskill_after) {
		if(isset($bosskill_before))	{
			if(($bosskill_before + $this->config('loottime')) > $bosskill) {
				$r['begin'] = $bosskill -1;
			} elseif(($bosskill_before + $this->config('loottime')) < $begin) {
				$r['begin'] = $begin;
			} else {
				$r['begin'] = $bosskill_before + $this->config('loottime');
			}
		} else {
			$r['begin'] = $begin;
		}
		if(isset($bosskill_after)) {
			if(($bosskill + $this->config('loottime')) > $bosskill_after) {
				$r['end'] = $bosskill_after -1;
			} elseif(($bosskill + $this->config('loottime')) > $end) {
				$r['end'] = $end;
			} else {
				$r['end'] = $bosskill + $this->config('loottime');
			}
		} else {
			$r['end'] = $end;
		}
		return $r;
	}

	private function get_event($key) {
		global $pdh, $rli;
		if($this->config('event_boss') & 1 AND count($this->raids[$key]['bosskills']) == 1 AND $this->config('raidcount') & 2) {
			$id = $pdh->get('rli_boss', 'id_string', array(trim($this->raids[$key]['bosskills'][0]), $this->raids[$key]['diff']));
			$event = $pdh->get('rli_boss', 'note', array($id));
			if($this->config('raidcount') & 1) {
				$this->raids[$key]['timebonus'] = 0;
			} else {
				$this->raids[$key]['timebonus'] = $pdh->get('rli_zone', 'timebonus', array($pdh->get('rli_boss', 'tozone', array($id))));
			}
		} else {
			$id = $pdh->get('rli_zone', 'id_string', array(trim($this->raids[$key]['zone']), $this->raids[$key]['diff']));
			if(!$id) return false;
			$event = $pdh->get('rli_zone', 'event', array($id));
			if($this->config('raidcount') & 1 AND $this->config('raidcount') & 2 AND count($this->raids[$key]['bosskills']) == 1) {
				$this->raids[$key]['timebonus'] = 0;
			} else {
				$this->raids[$key]['timebonus'] = $pdh->get('rli_zone', 'timebonus', array($id));
			}
		}
		return $event;
	}

	private function get_note($key) {
		global $user, $rli, $pdh;
		if($this->config('event_boss') == 1 OR count($this->raids[$key]['bosskills']) == 0) {
			if(count($this->raids[$key]['bosskills']) == 1 OR !$this->config('raid_note_time')) {
				return date('H:i', $this->raids[$key]['begin']).' - '.date('H:i', $this->raids[$key]['end']);
			} else {
				$this->hour_count++;
				return $this->hour_count.'. '.$user->lang('rli_hour');
			}
		} else {
			foreach ($this->raids[$key]['bosskills'] as $bosskill) {
				if(!is_numeric($bosskill['id'])) {
					$bosss[] = $rli->suffix($bosskill['id'], $this->config('dep_match'), $bosskill['diff']);
				} else {
					$bosss[] = $rli->suffix($pdh->get('rli_boss', 'note', array($bosskill['id'])), $this->config('dep_match'), $bosskill['diff']);
				}
			}
			return implode(', ', $bosss);
		}
	}

	public function __destruct() {
		global $rli;
		$rli->add_cache_data('raid', $this->raids);
		$rli->add_cache_data('data_raid', $this->data);
	}
}
}
?>
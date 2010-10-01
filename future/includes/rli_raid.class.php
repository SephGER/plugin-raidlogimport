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

if(!class_exists('rli_raid')) {
class rli_raid {
	private $data = array();
	private $raids = array();
	private $hour_count = 0;
	private $add_data = array();

	public function __construct() {
		global $rli;
		$this->raids = $rli->get_cache_data('raid');
		$this->add_data = &$this->add_data;
	}

	private function config($name) {
		global $rli;
		return $rli->config($name);
	}

	public function add_zone($name, $enter, $leave, $diff=0) {
		$this->data['zones'][] = array('name' => $name, 'enter' => (int) $enter, 'leave' => (int) $leave, 'diff' => $diff);
	}

	public function add_bosskill($name, $time, $diff=0) {
		$this->data['bosskills'][] = array('name' => $name, 'time' => (int) $time, 'diff' => $diff);
	}

	public function load_raids() {
		global $in;
		$this->raids = array();
		foreach($_POST['raids'] as $key => $raid) {
			if(!isset($raid['delete'])) {
				list($day, $month, $year) = explode('.', $in->get('raids:'.$key.':start_date','1.1.1970'), 3);
				list($hour, $min, $sec) = explode(':', $in->get('raids:'.$key.':start_time','00:00:00'), 3);
				$raids[$key]['begin'] = mktime($hour, $min, $sec, $month, $day, $year);
				list($day, $month, $year) = explode('.', $in->get('raids:'.$key.':end_date','1.1.1970'), 3);
				list($hour, $min, $sec) = explode(':', $in->get('raids:'.$key.':end_time','00:00:00'), 3);
				$this->raids[$key]['end'] = mktime($hour, $min, $sec, $month, $day, $year);
				$this->raids[$key]['note'] = $in->get('raids:'.$key.':note');
				$this->raids[$key]['value'] = floatvalue($in->get('raids:'.$key.':value', 0.0));
				$this->raids[$key]['event'] = $in->get('raids:'.$key.':event');
				$this->raids[$key]['bosskill_add'] = $in->get('raids:'.$key.':bosskill_add', 0);
				$this->raids[$key]['diff'] = $in->get('raids:'.$key.':diff', 0);
				$bosskills = array();
				if(is_array($raid['bosskills'])) {
					foreach($raid['bosskills'] as $u => $bk) {
						if(!isset($bk['delete'])) {
							list($hour, $min, $sec) = explode(':', $in->get('raids:'.$key.':bosskills:'.$u.':time', '00:00:00'), 3);
							list($day, $month, $year) = explode('.', $in->get('raids:'.$key.':bosskills:'.$u.':date', '1.1.1970'), 3);
							$bosskills[$u]['time'] = mktime($hour, $min, $sec, $month, $day, $year);
							$bosskills[$u]['bonus'] = floatvalue($in->get('raids:'.$key.':bosskills:'.$u.':bonus', 0.0));
							$bosskills[$u]['name'] = $in->get('raids:'.$key.':bosskills:'.$u.':name');
							$bosskills[$u]['diff'] = $in->get('raids:'.$key.':bosskills:'.$u.':diff');
						}
					}
				}
				$this->raids[$key]['bosskills'] = $bosskills;
				$this->raids[$key]['timebonus'] = floatvalue($in->get('raids:'.$key.':timebonus', 0.0));
			}
		}
	}

	public function create() {
		$key = 1;
		foreach( $this->data['zones'] as $zone ) {
			if( $this->config('raidcount') == 0 ) {
				$this->raids[$key]['begin'] = $zone['enter'];
				$this->raids[$key]['end'] = $zone['leave'];
				$this->raids[$key]['zone'] = $zone['name'];
				$this->raids[$key]['diff'] = $zone['diff'];
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
					$temp = $this->get_bosskill_raidtime($zone['begin'], $zone['end'], $bosskill['time'], @$this->data['bosskills'][$b-1]['time'], @$this->data['bosskills'][$b+1]['time']);
					$this->raids[$key]['begin'] = $temp['begin'];
					$this->raids[$key]['end'] = $temp['end'];
					$this->raids[$key]['zone'] = $zone['name'];
					$this->raids[$key]['diff'] = $zone['diff'];
					$this->raids[$key]['bosskills'][] = $bosskill['name'];
					$key++;
				}
			}
		}
		$this->add_data['att_begin_raid'] = 1;
		$this->add_data['att_end_raid'] = $key-1;
		if($this->config('attendence_raid')) {
			if($this->config('attendence_begin') > 0) {
				$this->raids[0]['begin'] = $this->raids[1]['begin'];
				$this->raids[0]['end'] = $this->raids[1]['begin'] + $this->config('attendence_time');
				$this->raids[0]['event'] = $this->get_event($this->raids[1]['zone'], true);
				$this->raids[0]['note'] = $this->config('att_note_begin');
				$this->raids[0]['value'] = $this->config('attendence_begin');
				$this->add_data['att_begin_raid'] = 0;
			}
			if($this->config('attendence_end') > 0) {
				$this->raids[$key]['begin'] = $this->raids[$key-1]['end'] - $this->config('attendence_time');
				$this->raids[$key]['end'] = $this->raids[$key-1]['end'];
				$this->raids[$key]['event'] = $this->get_event($this->raids[$key-1]['zone'], true);
				$this->raids[$key]['note'] = $this->config('att_note_end');
				$this->raids[$key]['value'] = $this->config('attendence_end');
				$this->add_data['att_end_raid'] = $key;
				$key++;
			}
		}
		$this->add_data['standby_raid'] = -1;
		if($this->config('standby_raid') <= 1) {
			$this->raids[$key]['begin'] = $this->raids[1]['begin'];
			$this->raids[$key]['end'] = $this->raids[$key-1]['end'];
			$this->raids[$key]['diff'] = $this->raids[1]['diff'];
			$this->raids[$key]['zone'] = $this->raids[1]['zone'];
			$this->add_data['standby_raid'] = $key;
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
				$this->raids[$key]['event'] = $this->get_event($key);
				if( (!($this->config('raidcount') & 1 AND $this->config('raidcount') & 2) OR count($this->raids[$key]['bosskills']) == 1) AND $first) {
					$bosskills = $this->get_bosskills($raid['begin'], $raid['end']);
					$this->raids[$key]['bosskills'] = $bosskills;
				}
				$this->raids[$key]['note'] = ($key == $this->add_data['standby_raid']) ? $this->config('standby_raidnote') : $this->get_note($key);
				$this->raids[$key]['value'] = $this->get_value($key, false);
			}
		}
	}

	public function delete($key) {
		unset($this->raids[$key]);
	}

	public function get_value($key, $times=false, $attdkp_force=array(-1,-1)) {
		if($key == $this->add_data['standby_raid'] AND $this->config('standby_absolute')) {
			return $this->config('standby_value');
		}
		$timedkp = $this->get_timedkp($key, $times);
		$bossdkp = $this->get_bossdkp($key, $times);
		$eventdkp = $this->get_eventdkp($key, $times);
		$attdkp = $this->get_attdkp($key, $times, $attdkp_force);
		$dkp = $timedkp + $bossdkp + $eventdkp + $attdkp;
		return $dkp;
	}

	public function display($with_form=false) {
		global $tpl, $html, $core, $rli;

		foreach($this->raids as $ky => $rai) {
			if($ky == $this->add_data['standby_raid'] AND $this->config('standby_raid') != 1) {
				continue;
			}
			$bosskills = '';
			if(!$with_form) {
				foreach($rai['bosskills'] as $bk) {
					$bosskills .= '<tr class="'.$core->switch_row_class().'"><td>'.$bk['name'].'</td><td colspan="2">'.date('H:i:s',$bk['time']).'</td><td>'.$bk['bonus'].'</td></tr>';
				}
			}
			if(isset($rai['bosskill_add'])) {
				$this->new_bosskill($ky, $rai['bosskill_add']);
			}
			$tpl->assign_block_vars('raids', array(
				'COUNT'     => $ky,
				'START_DATE'=> date('d.m.y', $rai['begin']),
				'START_TIME'=> date('H:i:s', $rai['begin']),
				'END_DATE'	=> date('d.m.y', $rai['end']),
				'END_TIME'	=> date('H:i:s', $rai['end']),
				'EVENT'		=> ($with_form) ? $html->DropDown('raids['.$ky.'][event]', $rli->get_events('name'), $rai['event']) : $rai['event'],
				'TIMEBONUS'	=> $rai['timebonus'],
				'VALUE'		=> $rai['value'],
				'NOTE'		=> $rai['note'],
				'HEROIC'	=> ($rai['diff'] == 2) ? TRUE : FALSE,
				'BOSSKILLS' => $bosskills)
			);
			if($with_form) {
				if(is_array($rai['bosskills'])) {
					foreach($rai['bosskills'] as $xy => $bk) {
						$tpl->assign_block_vars('raids.bosskills', array(
							'BK_SELECT' => $rli->boss_dropdown($bk['name'], $ky, $xy),
							'BK_TIME'   => date('H:i:s', $bk['time']),
							'BK_DATE'   => date('d.m.y', $bk['time']),
							'BK_VALUE'  => $bk['bonus'],
							'BK_KEY'    => $xy)
						);
					}
				}
			}
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
	
	/*
	 * get seconds the member was in the raid
	 * @int $key: key of the raid
	 * @array $times: array of join/leave times
	 * @int $standby: 0: time in raid regardless of standbystatus; 1: time in raid without standby; 2: time in raid being standby
	 * return @int
	 */
	public function in_raid($key, $times=false, $standby=0) {
		$in_raid = 0;
		if(is_array($times)) {
			foreach ($times as $time) {
				if(!$standby OR ($standby == 1 AND !$time['standby']) OR ($standby == 2 AND $time['standby'])) {
					$in_raid += (($time['leave'] > $this->raids[$key]['end']) ? $this->raids[$key]['end'] : $time['leave']) - (($time['join'] < $this->raids[$key]['begin']) ? $this->raids[$key]['begin'] : $time['join']);
				}
			}
		} else {
			$in_raid = $this->raids[$key]['end'] - $this->raids[$key]['begin'];
		}
		return $in_raid;
	}

	public function get_memberraids($times) {
		$raid_list = array();
		$att_raids = $this->get_attendance_raids();
		foreach($this->raids as $key => $rdata) {
			if($key == $att_raids['begin']) {
				$att = $this->get_attendance($times);
				if($att['begin'])
					$raid_list[] = $key;
				continue;
			}
			if($key == $att_raids['end']) {
				$att = $this->get_attendance($times);
				if($att['end'])
					$raid_list[] = $key;
				continue;
			}
			$standby = 1;
			if($key == $this->add_data['standby_raid'] AND $this->config('standby_raid') <= 1) {
				$standby = 2;
			} elseif($this->config('standby_raid') == 2) {
				$standby = 0;
			}
			$in_raid = $this->in_raid($key, $times, $standby);
			$raid_time = $this->in_raid($key);
			if(($in_raid/$raid_time) >= ($this->config('member_raid') / 100)) {
				$raid_list[] = $key;
			}
		}
		return $raid_list;
	}

	public function get_checkraidlist($memberraids, $mkey) {
		global $eqdkp_root_path, $pcache;

		$td = '';
		if(!$this->th_raidlist) {
			$pcache->CheckCreateFolder($pcache->CacheFolder.'raidlogimport');
			foreach($this->raids as $rkey => $raid) {
				$imagefile = $eqdkp_root_path.$pcache->FileLink('image'.$rkey.'.png', 'raidlogimport');
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

	public function raidlist() {
		if(!$this->raidlist) {
			foreach($this->raids as $key => $raid) {
				$this->raidlist[$key] = $raid['note'];
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
				($this->config('standby_dkptype') & 2 AND $key == $this->add_data['standby_raid']) OR 
				($this->config('use_dkp') & 2 AND $key != $this->add_data['standby_raid'])
			)) {
			$standby = ($key == $this->add_data['standby_raid']) ? 2 : 1;
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
	
	private function calc_bossdkp($key, $times, $standby) {
		$bossdkp = 0;
		foreach ($this->raids[$key]['bosskills'] as $bosskill) {
			if($times !== false) {
				foreach ($times as $time) {
					if($standby == $time['standby']) {
						if($time['join'] < $bosskill['time'] AND $time['leave'] > $bosskill['time']) {
							$bossdkp += $bosskill['bonus'];
							break;
						}
					}
				}
			} else {
				$bossdkp += $bosskill['bonus'];
			}
		}
		return $bossdkp;
	}

	private function get_bossdkp($key, $times) {
		$bossdkp = 0;
		if(	$this->config('standby_raid') <= 1 AND (
				($this->config('standby_dkptype') & 1 AND $key == $this->add_data['standby_raid']) OR 
				($this->config('use_dkp') & 1 AND $key != $this->add_data['standby_raid'])
			)) {
			$standby = ($key == $this->add_data['standby_raid']) ? true : false;
			$bossdkp = ($standby) ? $this->calc_bossdkp($key, $times, $standby)*$this->config('standby_value')/100 : $this->calc_bossdkp($key, $times, $standby);
		} elseif($this->config('standby_raid') == 2) {
			if($this->config('use_dkp') & 1) {
				$bossdkp += $this->calc_bossdkp($key, $times, false);
			}
			if($this->config('standby_dkptype') & 1) {
				$bossdkp += $this->calc_bossdkp($key, $times, true)*$this->config('standby_value')/100;
			}
		}
		return $bossdkp;
	}

	private function get_eventdkp($key) {
		global $rli;
		$eventdkp = 0;
		if($this->config('use_dkp') & 4 AND $key != $this->add_data['standby_raid']) {
			$eventdkp = $rli->get_events('value', $this->raids[$key]['event']);
		} elseif($this->config('standby_dkptype') & 4 AND $key == $this->add_data['standby_raid']) {
			$eventdkp = $rli->get_events('value', $this->raids[$key]['event'])*$this->config('standby_value')/100;
		}
		return $eventdkp;
	}
	
	private function get_attdkp($key, $times=false, $force=array(-1,-1)) {
		return $this->calc_attdkp($key, 'begin', $times, $force) + $this->calc_attdkp($key, 'end', $times, $force);
	}
	
	private function calc_attdkp($key, $type, $times=false, $force=array(-1,-1)) {
		$att_raids = $this->get_attendance_raids(true);
		if($key == $att_raids[$type] && $this->config('attendance_'.$type)) {
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
		global $rli;
		$bosskills = array();
		$bonus = $rli->get_bonus();
		$key = 1;
		foreach ($this->data['bosskills'] as $bosskill)
		{
			if($begin <= $bosskill['time'] AND $bosskill['time'] <= $end)
			{
				foreach($bonus['boss'] as $bon)
				{
					if(in_array($bosskill['name'], $bon['string']))
					{
						$bosskills[$key]['name'] = $bosskill['name'];
						$bosskills[$key]['bonus'] = $bon['bonus'];
						$bosskills[$key]['note'] = $bon['note'];
						$bosskills[$key]['time'] = $bosskill['time'];
					}
				}
			}
			$key++;
		}
		return $bosskills;
	}

	private function get_bosskill_raidtime($begin, $end, $bosskill, $bosskill_before, $bosskill_after) {
		if(isset($bosskill_before))	{
			if(($bosskill_before + $this->config['loottime']) > $bosskill) {
				$r['begin'] = $bosskill -1;
			} else {
				$r['begin'] = $bosskill_before + $this->config['loottime'];
			}
		} else {
			$r['begin'] = $begin;
		}
		if(isset($bosskill_after)) {
			if(($bosskill + $this->config['loottime']) > $bosskill_after) {
				$r['end'] = $bosskill_after -1;
			} else {
				$r['end'] = $bosskill + $this->config['loottime'];
			}
		} else {
			$r['end'] = $end;
		}
		return $r;
	}

	private function get_attendance_raids($strict=false) {
		$att_ra = array();
		if($this->config('attendance_raid')) {
			$att_ra['begin'] = $this->add_data['att_begin_raid'];
			$att_ra['end'] = $this->add_data['att_end_raid'];
		} elseif(!$strict) {
			$att_ra['begin'] = ($this->config('attendance_begin')) ? $this->add_data['att_begin_raid'] : 0;
			$att_ra['end'] = ($this->config('attendance_end')) ? $this->add_data['att_end_raid'] : 0;
		}
		return $att_ra;
	}

	private function get_event($key, $nokey=false) {
		global $rli;
		$bonus = $rli->get_bonus();
		if($nokey) {
			foreach ($bonus['zone'] as $zone) {
				if(in_array(trim($key), $zone['string'])) {
					return $zone['note'];
				}
			}
		}
		if($this->config('event_boss') AND count($this->raids[$key]['bosskills']) == 1) {
			foreach($bonus['boss'] as $boss) {
				if (in_array(trim($this->raids[$key]['bosskills'][0]), $boss['string'])) {
					$event = $boss['note'];
					$this->raids[$key]['timebonus'] = $bonus['zone'][$boss['tozone']]['bonus'];
				}
			}
		} else {
			foreach ($bonus['zone'] as $zone) {
				if (in_array(trim($this->raids[$key]['zone']), $zone['string'])) {
					$event = $zone['note'];
					$this->raids[$key]['timebonus'] = $zone['bonus'];
				}
			}
		}
		return $this->suffix($event, true, true);
	}

	private function suffix($string, $append, $recalc=false) {
		global $core;
		if($core->config['default_game'] == 'WoW') {
			if($recalc) {
				$string = str_replace($this->config('hero'), '', $string);
				$string = str_replace($this->config('non_hero'), '', $string);
			}
			if($append)	{
				return $string.(($this->diff == 2) ? $this->config('hero') : $this->config('non_hero'));
			} else {
				return $string;
			}
		} else {
			return $string;
		}
	}

	private function get_note($key) {
		global $user;
		if($this->config('event_boss') == 1 OR count($this->raids[$key]['bosskills']) == 0) {
			if(count($this->raids[$key]['bosskills']) == 1 OR !$this->config('raid_note_time')) {
				return date('H:i', $this->raids[$key]['begin']).' - '.date('H:i', $this->raids[$key]['end']);
			} else {
				$this->hour_count++;
				return $this->hour_count.'. '.$user->lang['rli_hour'];
			}
		} else {
			foreach ($this->raids[$key]['bosskills'] as $bosskill)
			{
				$bosss[] = $this->suffix($bosskill['note'], $this->config('dep_match'));
			}
			return implode(', ', $bosss);
		}
	}

	public function __destruct() {
		global $rli;
		$rli->add_cache_data('raid', $this->raids);
	}
}
}
?>
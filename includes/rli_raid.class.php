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

if(!class_exists('rli_raid'))
{
  class rli_raid
  {
  	private $data = array();
  	private $raids = array();
  	private $hour_count = 0;

	public function __construct()
	{
		global $rli;
		$this->raids = get_cache_data('raid');
	}

	public function add_zone($name, $enter, $leave, $diff=0)
	{
		$this->data['zones'][] = array('name' => $name, 'enter' => (int) $enter, 'leave' => (int) $leave, 'diff' => (($diff == 2) ? true : false));
	}

	public function add_bosskill($name, $time)
	{
		$this->data['bosskills'][] = array('name' => $name, 'time' => (int) $time);
	}
	
	public function give($data)
	{
		$this->raids = $data;
	}

	public function create()
	{
		global $rli;

		$key = 1;
	 	foreach( $this->data['zones'] as $zone )
	 	{
	 		if( $rli->config('raidcount') == 0 )
	 		{
 				$this->raids[$key]['begin'] = $zone['enter'];
 				$this->raids[$key]['end'] = $zone['leave'];
 				$this->raids[$key]['diff'] = $zone['diff'];
 				$this->raids[$key]['zone'] = $zone['name'];
 			}
 			if( $rli->config('raidcount') & 1 )
 			{
				for($i = $zone['enter']; $i<=$zone['leave']; $i+=3600)
				{
					$this->raids[$key]['begin'] = $i;
					$this->raids[$key]['end'] = (($i+3600) > $zone['leave']) ? $zone['leave'] : $i+3600;
					$this->raids[$key]['diff'] = $zone['diff'];
	 				$this->raids[$key]['zone'] = $zone['name'];
	 				$key++;
				}
			}
			if( $rli->config('raidcount') & 2)
			{
				foreach($this->data['bosskills'] as $b => $bosskill)
				{
					$temp = $this->get_bosskill_raidtime($zone['begin'], $zone['end'], $bosskill['time'], @$this->data['bosskills'][$b-1]['time'], @$this->data['bosskills'][$b+1]['time']);
					$this->raids[$key]['begin'] = $temp['begin'];
					$this->raids[$key]['end'] = $temp['end'];
					$this->raids[$key]['diff'] = $zone['diff'];
					$this->raids[$key]['zone'] = $zone['name'];
					$this->raids[$key]['bosskills'][] = $bosskill['name'];
					$key++;
				}
			}
		}
		if($this->config('attendence_raid'))
		{
			if($this->config('attendence_begin') > 0)
			{
				$this->raids[0]['begin'] = $this->raids[1]['begin'];
				$this->raids[0]['end'] = $this->raids[1]['begin'] + $this->config('attendence_time');
				$this->raids[0]['event'] = $this->get_event($this->raids[1]['zone'], true);
				$this->raids[0]['note'] = $this->config('att_note_begin');
				$this->raids[0]['value'] = $this->config('attendence_begin');
			}
			if($this->config('attendence_end') > 0)
			{
				$this->raids[$key]['begin'] = $this->raids[$key-1]['end'] - $this->config('attendence_time');
				$this->raids[$key]['end'] = $this->raids[$key-1]['end'];
				$this->raids[$key]['event'] = $this->get_event($this->raids[$key-1]['zone'], true);
				$this->raids[$key]['note'] = $this->config('att_note_end');
				$this->raids[$key]['value'] = $this->config('attendence_end');
			}
		}
	}

	public function add_new($number)
	{
		for($i=1; $i<=$number; $i++) {
			$this->raids[] = array();
		}
	}
	
	public function add_bosskill($raidkey, $number)
	{
		for($i=1; $i<=$number; $i++) {
			$this->raids[$raidkey]['bosskills'][] = array();
		}
	}

	public function recalc()
	{
        global $rli;

        $ignore = $this->get_attendance_raids(true);
        foreach( $this->raids as $key => $raid )
        {
          if(!in_array($key, $ignore))
          {
          	$this->diff = $raid['diff'];
            $this->raids[$key]['event'] = $this->get_event($key);
            $this->raids[$key]['bosskills'] = array();
            if( !($rli->config('raidcount') & 1 AND $rli->config('raidcount') & 2) OR count($this->raids[$key]['bosskills']) == 1)
            {
            	$bosskills = $this->get_bosskills($raid['begin'], $raid['end']);
            	$this->raids[$key]['bosskills'] = $bosskills;
            }
            $this->raids[$key]['note'] = $this->get_note($key);
            $this->raids[$key]['value'] = $this->get_value($key);
          }
        }
	}

	public function delete($key)
	{
		unset($this->raids[$key]);
	}

	public function get_value($key, $times=false)
	{
  		$timedkp = $this->get_timedkp($key, $times);
  		$bossdkp = $this->get_bossdkp($key, $times);
  		$eventdkp = $this->get_eventdkp($key, $times);
  		$attdkp = $this->get_attdkp($key, $times);
  		return $timedkp + $bossdkp + $eventdkp + $attdkp;
	}

	private function get_timedkp($key, $times)
	{
		global $rli;
		$timedkp = 0;
		if($rli->config('use_dkp') & 2)	{
            $in_raid = 0;
			if($times !== false) {
				foreach ($times as $time) {
					$in_raid += ($time['leave'] - $time['join']);
				}
			} else {
				$in_raid = $this->raids[$key]['end'] - $this->raids[$key]['begin'];
			}
			$in_raid = format_duration($in_raid);
			$timedkp = $in_raid['hours'] * $this->raids[$key]['timebonus'];
			if($this->config('timedkp_handle')) {
				$timedkp += (($times['minutes'] >= $this->config('timedkp_handle') ? $timebonus : 0);
			} else {
				$timedkp += $timebonus * ($times['minutes']/60);
			}
		}
		return $timedkp;
	}

	private function get_bossdkp($key, $times)
	{
		global $rli;
		$bossdkp = 0;
		if($rli->config('use_dkp') & 1) {
			foreach ($this->raids[$key]['bosskills'] as $bosskill) {
				if($times !== false) {
					foreach ($times as $time) {
						if($time['join'] < $bosskill['time'] AND $time['leave'] > $bosskill['time']) {
							$bossdkp += $bosskill['bonus'];
							break;
						}
					}
				} else {
					$bossdkp += $bosskill['bonus'];
				}
			}
		}
		return $bossdkp;
	}

	private function get_eventdkp($key)
	{
		global $rli;
		$eventdkp = 0;
		if($rli->config['use_dkp'] & 4)
		{
			$eventdkp = $rli->get_events('value', $this->raids[$key]['event']);
		}
		return $eventdkp;
	}

	private function get_attdkp($key, $times)
	{
		global $rli;
		$attdkp = 0;
		$att_raids = $this->get_attendance_raids();
		if($key == $att_raids['start']) {
			if($times !== false) {
				$ct = $rli->config('attendence_time') + $this->raids[$key]['begin'];
				foreach ($times as $time) {
					if($time['join'] < $ct) {
						$attdkp += $rli->config('attendence_begin');
						break;
					}
				}
			} else {
				$attdkp += $rli->config('attendence_begin');
			}
		}
		if($key == $att_raids['end']) {
			if($times !== false) {
				$ct = $this->raids[$key]['end'] - $rli->config('attendence_time');
				foreach ($times as $time) {
					if(time['leave'] > $ct) {
						$attdkp += $rli->config('attendence_end');
						break;
					}
				}
			} else {
				$attdkp += $rli->config('attendence_end');
			}
		}
		return $attdkp;
	}

	private function get_bosskills($begin, $end)
	{
		global $rli;

		$bosskills = array();
		$bonus = $rli->get_bonus();
		foreach ($this->data['bosskills'] as $bosskill)
		{
			if($begin <= $bosskill['time'] AND $bosskill['time'] <= $end)
			{
				foreach($bonus as $bon)
				{
					if(in_array($bosskill['name'], $bon['string']))
					{
						$bosskills['name'] = $bosskill['name'];
						$bosskills['bonus'] = $bon['bonus'];
						$bosskills['note'] = $bon['note'];
						$bosskills['time'] = $bosskill['time'];
					}
				}
			}
		}
		return $bosskills;
	}

	private function get_bosskill_raidtime($begin, $end, $bosskill, $bosskill_before, $bosskill_after)
	{
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

	private function get_attendance_raids($strict=false)
	{
		global $rli;
		$att_ra = array();
		if($rli->config('attendence_raid'))
		{
			$att_ra['start'] = 0;
			if($rli->config('attendence_end'))
			{
				$att_ra['end'] = max(array_keys($this->raids));
			}
		}
		elseif(!$strict)
		{
			$att_ra['start'] = ($rli->config('attendence_begin')) ? 1 : 0;
			$att_ra['end'] = ($rli->config('attendence_end')) ? max(array_keys($this->raids)) : 0;
		}
		return $att_ra;
	}

	private function get_event($key, $nokey=false)
	{
		global $rli;
		$bonus = $rli->get_bonus();
		if($nokey) {
			foreach ($bonus['zone'] as $zone) {
				if(in_array(trim($key), $zone['string'])) {
					return $zone['note'];
				}
			}
		}
		if($rli->config('event_boss') AND count($this->raids[$key]['bosskills']) == 1) {
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

    private function suffix($string, $append, $recalc=false)
    {
    	global $eqdkp;
      	if($eqdkp->config['default_game'] == 'WoW') {
      		if($recalc) {
    			$string = str_replace($this->config['hero'], '', $string);
    			$string = str_replace($this->config['non_hero'], '', $string);
    		}
    		if($append)	{
      			return $string.(($this->diff == 2) ? $this->config['hero'] : $this->config['non_hero']);
      		} else {
      			return $string;
      		}
      	} else {
      		return $string;
      	}
    }

	private function get_note($key)
	{
		global $rli, $user;
		if($rli->config('event_boss') == 1 OR count($this->raids[$key]['bosskills']) == 0) {
			if(count($this->raids[$key]['bosskills'] == 1 OR !$rli->config('raid_note_time')) {
				return date('H:i', $this->raids[$key]['begin']).' - '.date('H:i', $this->raids[$key]['end']);
			} else {
            	$this->hour_count++;
				return $this->hour_count.'. '.$user->lang['rli_hour'];
			}
		} else {
			foreach ($this->raids[$key]['bosskills'] as $bosskill)
			{
				$bosss[] = $this->suffix($bosskill['note'], $rli->config('dep_match'));
			}
			return implode(', ', $bosss)
		}
	}

	public function __destruct()
	{
		global $rli;
		$rli->add_cache_data('raid', $this->raids);
	}
  }
}
?>
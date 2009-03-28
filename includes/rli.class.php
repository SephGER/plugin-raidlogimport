<?php
	/*
	 * @author: hoofy
	 * @copyright: 2009
	 */

if(!defined('EQDKP_INC'))
{
	header('HTTP/1.0 Not Found');
	exit;
}
if(!class_exists('rli'))
{
  class rli
  {
  	var $bonus = array();
  	var $config = array();
  	var $bk_list = array();
  	var $events = array();
  	var $member_ranks = array();
  	var $data = array();

  	function rli()
  	{
		global $db;
	    $sql = "SELECT * FROM __raidlogimport_config;";
		$result = $db->query($sql);
		while ( $row = $db->fetch_record($result) )
		{
			$this->config[$row['config_name']] = $row['config_value'];
		}
		$db->free_result();
  	}

  	function get_bonus()
  	{
  		global $db;

  		if(!$this->bonus)
  		{
  		  $sql = "SELECT bz_id, bz_string, bz_note, bz_bonus, bz_type, bz_tozone FROM __raidlogimport_bz;";
		  if($result = $db->query($sql))
		  {
			while($row = $db->fetch_record($result))
			{
				if($row['bz_type'] == 'boss')
				{
					$this->bonus['boss'][$row['bz_id']]['string'] = explode($this->config['bz_parse'], $row['bz_string']);
					$this->bonus['boss'][$row['bz_id']]['note'] = $row['bz_note'];
					$this->bonus['boss'][$row['bz_id']]['bonus'] = $row['bz_bonus'];
					$this->bonus['boss'][$row['bz_id']]['tozone'] = $row['bz_tozone'];
				}
				else
				{
					$this->bonus['zone'][$row['bz_id']]['string'] = explode($this->config['bz_parse'], $row['bz_string']);
					$this->bonus['zone'][$row['bz_id']]['note'] = $row['bz_note'];
					$this->bonus['zone'][$row['bz_id']]['bonus'] = $row['bz_bonus'];
				}
			}
		  }
		  else
		  {
			message_die('SQL-Error! Query:<br />'.$sql);
		  }
		}
	}

	function get_events()
	{
		global $db;
		if(!$this->events)
		{
			$sql = "SELECT event_name, event_value FROM __events;";
			$result = $db->query($sql);
			while ( $row = $db->fetch_record($result) )
			{
				$this->events['name'][$row['event_name']] = $row['event_name'];
				$this->events['values'][$row['event_name']] = $row['event_value'];
			}
		}
	}

	function get_event($zone, $bosskill=false)
	{
	  $retu = false;
	  $this->get_bonus();
	  if(!$bosskill)
	  {
        foreach ($this->bonus['zone'] as $zo)
        {
            if (in_array(trim($zone), $zo['string']))
            {
                $retu['event'] = trim($zo['note']);
                $retu['timebonus'] = $zo['bonus'];
                break;
            }
        }
      }
      else
      {
      	foreach($this->bonus['boss'] as $bo)
      	{
      		if(in_array(trim($zone), $bo['string']))
      		{
      			$retu['event'] = trim($bo['note']);
      			$retu['timebonus'] = $this->bonus['zone'][$bo['tozone']]['bonus'];
      			break;
      		}
      	}
      }
      $retu['event'] .= $this->suffix(true);
      return $retu;
    }

	function get_bosskills($bosskills, $begin, $end)
	{
		$rbosskills = array();
		$i = 0;
		$this->get_bonus();
		foreach($bosskills as $bosskill)
		{
			foreach($this->bonus['boss'] as $boss)
			{
				if(in_array($bosskill['name'], $boss['string']) AND $bosskill['time'] >= $begin AND $bosskill['time'] < $end)
				{
					$rbosskills[$i]['name'] = $bosskill['name'];
					$rbosskills[$i]['bonus'] = $boss['bonus'];
					$rbosskills[$i]['note'] = $boss['note'];
					$rbosskills[$i]['time'] = $bosskill['time'];
					break;
				}
			}
			$i++;
		}
		return $rbosskills;
	}

	function suffix($append)
	{
	  global $eqdkp;
	  if($eqdkp->config['default_game'] == 'WoW' AND $append) {
		return ($this->diff == 2) ? $this->config['hero'] : $this->config['non_hero'];
	  } else {
	  	return '';
	  }
	}

	function get_note($bosskills, $nonote=false)
	{
		$bosss = array();
		foreach($bosskills as $bosskill)
		{
			if($nonote)
			{
				$this->get_bonus();
				foreach($this->bonus['boss'] as $boss)
				{
					if(in_array($bosskill['name'], $boss['string']))
					{
						$bosskill['note'] = $boss['note'];
					}
				}
			}
			$bosss[] = $bosskill['note'].$this->suffix($this->config['dep_match']);
		}
		return implode(', ', $bosss);
	}

	function get_member_times($member, $begin=false, $end=false)
	{
		$time = array();
		foreach($member['join'] as $tj)
		{
			if(array_key_exists($tj, $time))
			{
				unset($time[$tj]);
			}
			else
			{
				$time[$tj] = 'join';
			}
		}
		foreach($member['leave'] as $tl)
		{
			if(array_key_exists($tl, $time))
			{
				unset($time[$tl]);
			}
			else
			{
				$time[$tl] = 'leave';
			}
		}
		ksort($time);
		$times = array();
		foreach($time as $ti => $ty)
		{
			$times[][$ty] = $ti;
		}
        return $this->check_times($times);
    }

    function check_times($times)
    {
		$n = count($times)-1;
	    for($i=0; $i<$n; $i++)
	    {
	      $k = $i+1;
	      if(key($times[$i]) == key($times[$k]))
	      {
			message_die($user->lang['parse_error'].' '.$user->lang[$this->config['parser'].'_format'].' <img src="'.$eqdkp_root_path.'plugins/raidlogimport/images/'.$this->config['parser'].'_options.png"><br />'.$user->lang['rli_lgaobk']);
	      }
	      else
	      {
            if($begin AND key($times[$i]) == 'join' AND ($begin + $this->config['member_miss_time']) > $times[$i]['join'])
            {
                $times[$i]['join'] = $begin;
            }
            if($end AND key($times[$i]) == 'leave' AND ($end - $this->config['member_miss_time']) < $times[$i]['leave'])
            {
                $times[$i]['leave'] = $end;
            }
	      	if(key($times[$i]) == 'leave' AND ($times[$i]['leave'] + $this->config['member_miss_time']) > $times[$k]['join'])
	      	{
	      		unset($times[$i]);
	      		unset($times[$k]);
	      		$i++;
	      	}
	      }
	    }
	    return $times;
	}

	function get_member_secs_inraid($times, $begin, $end)
	{
		$tim = 0;
        $nu = max(array_keys($times));
		foreach($times as $i => $time)
		{
		  if(key($time) == 'join')
		  {
			$k = $i+1;
			$j = '';
			for($k; $k<=$nu; $k++)
			{
				if(isset($times[$k]) AND key($times[$k]) == 'leave')
				{
					$j = $k;
					break;
				}
			}
            if($times[$j]['leave'] > $begin AND $time['join'] < $end)
            {
                $tim = $tim + ((($times[$j]['leave'] > $end) ? $end : $times[$j]['leave']) - (($time['join'] < $begin) ? $begin : $time['join']));
            }
          }
        }
        return $tim;
    }

	function calc_timedkp($begin, $end, $player, $timebonus)
	{
      $timedkp = 0;
	  if($this->get_multiconfig($this->config['use_dkp'], 2))
	  {
		$times = format_duration($this->get_member_secs_inraid($player['times'], $begin, $end));
		$timedkp = $times['hours'] * $timebonus;
		if($this->config['timedkp_handle'])
		{
			$timedkp = $timedkp + (($times['minutes'] >= $this->config['timedkp_handle']) ? $timebonus : 0);
		}
		else
		{
			$timedkp = $timedkp + $timebonus * ($times['minutes']/60);
		}
	  }
	  return $timedkp;
	}

	function calc_bossdkp($kills, $player)
	{
	  $bossdkp = 0;
	  if($this->get_multiconfig($this->config['use_dkp'], 1))
	  {
		foreach($kills as $kill)
		{
		  foreach($player['times'] as $i => $time)
		  {
			$nu = max(array_keys($player['times']));
			$k = $i+1;
			$j = '';
			for($k; $k<=$nu; $k++)
			{
				if(isset($player['times'][$k]) AND key($player['times'][$k]) == 'leave')
				{
					$j = $k;
					break;
				}
			}
			if(key($time) == 'join')
			{
				if($time['join'] < $kill['time'] AND $player['times'][$j]['leave'] > $kill['time'])
				{
					$bossdkp = $bossdkp + $kill['bonus'];
				}
			}
		  }
		}
	  }
	  return $bossdkp;
	}

	function calc_eventdkp($event)
	{
		$eventdkp = 0;
		if($this->config['use_dkp'] > 3)
		{
			$this->get_events();
			$eventdkp = $this->events['values'][$event];
		}
		return $eventdkp;
	}

	function get_raidvalue($begin, $end, $bosskills, $timebonus, $event)
	{
		$dkp = 0;
		$tempmem['times'][1]['join'] = $begin;
		$tempmem['times'][2]['leave'] = $end;
		$timedkp = $this->calc_timedkp($begin, $end, $tempmem, $timebonus);
		$bossdkp = $this->calc_bossdkp($bosskills, $tempmem);
		$eventdkp = $this->calc_eventdkp($event);
		$tempattdkp = $this->calculate_attendence($tempmem['times'], $begin, $end);
		$attdkp = 0;
		if(!$this->config['attendence_raid'])
		{
			$attdkp = $tempattdkp['begin'] + $tempattedkp['end'];
			unset($tempattdkp);
		}
		$dkp = $timedkp + $bossdkp + $eventdkp + $attdkp;
		return round($dkp,2);
	}

	function get_bosskill_raidtime($begin, $end, $bosskill, $bosskill_before, $bosskill_after)
	{
		if(isset($bosskill_before))
		{
			if(($bosskill_before + $this->config['loottime']) > $bosskill)
			{
				$r['begin'] = $bosskill -1;
			}
			else
			{
				$r['begin'] = $bosskill_before + $this->config['loottime'];
			}
		}
		else
		{
			$r['begin'] = $begin;
		}
		if(isset($bosskill_after))
		{
			if(($bosskill + $this->config['loottime']) > $bosskill_after)
			{
				$r['end'] = $bosskill_after -1;
			}
			else
			{
			$r['end'] = $bosskill + $this->config['loottime'];
			}
		}
		else
		{
			$r['end'] = $end;
		}
		return $r;
	}

	function create_raids($raidxml)
	{
		global $user;

		$this->parse_string($raidxml);

		$key = 1;
		foreach($this->data['zones'] as $zkey => $zone)
		{
			$this->diff = $zone['diff'];
		  switch($this->config['raidcount'])
		  {
			case "0": //one raid for everything
			{
				//time
				$raids[$key]['begin'] = $zone['enter'];
				$raids[$key]['end'] = $zone['leave'];

				//event
				$temp = $this->get_event($zone['name']);
				$raids[$key]['event'] = $temp['event'];
				$raids[$key]['timebonus'] = $temp['timebonus'];

				//bosskills
				$raids[$key]['bosskills'] = array();
				if(is_array($this->data['bosskills']))
				{
					$raids[$key]['bosskills'] = $this->get_bosskills($this->data['bosskills'], $raids[$key]['begin'], $raids[$key]['end']);
				}

                //note
                $raids[$key]['note'] = $this->get_note($raids[$key]['bosskills']);

				//value
				$raids[$key]['value'] = $this->get_raidvalue($raids[$key]['begin'], $raids[$key]['end'], $raids[$key]['bosskills'], $raids[$key]['timebonus'], $raids[$key]['event']);
				$key++;
				break;
			}
			case "1": //one raid per hour
			{
				for($i = $zone['enter']; $i<=$zone['leave']; $i+=3600)
				{
                	//time
					$raids[$key]['begin'] = $i;
					$raids[$key]['end'] = (($i+3600) > $zone['leave']) ? $zone['leave'] : $i+3600;

                    //event
					$temp = $this->get_event($zone['name']);
					$raids[$key]['event'] = $temp['event'];
					$raids[$key]['timebonus'] = $temp['timebonus'];

					//bosskills
					$raids[$key]['bosskills'] = array();
					if(is_array($this->data['bosskills']))
					{
						$raids[$key]['bosskills'] = $this->get_bosskills($this->data['bosskills'], $raids[$key]['begin'], $raids[$key]['end']);
					}

	                //note
                    $raids[$key]['note'] = $this->get_note($raids[$key]['bosskills']);

					//value
					$raids[$key]['value'] = $this->get_raidvalue($raids[$key]['begin'], $raids[$key]['end'], $raids[$key]['bosskills'], $raids[$key]['timebonus'], $raids[$key]['event']);
					$key++;
				}
				break;

			}
			case "2": //one raid per bosskill
			{
				if(is_array($this->data['bosskills']))
				{
					foreach($this->data['bosskills'] as $b => $bosskill)
					{
						//time
						$temp = $this->get_bosskill_raidtime($zone['enter'], $zone['leave'], $bosskill['time'], $this->data['bosskills'][$b-1]['time'], $this->data['bosskills'][$b+1]['time']);
						$raids[$key]['begin'] = $temp['begin'];
						$raids[$key]['end'] = $temp['end'];

						//bosskills
						$raids[$key]['bosskills'] = $this->get_bosskills($this->data['bosskills'], $raids[$key]['begin'], $raids[$key]['end']);

						$temp = array();
						//event+note
						if($this->config['event_boss'] == 1)
						{
							$temp = $this->get_event($bosskill['name'], true);
							$raids[$key]['note'] = date('H:i:s', $raids[$key]['begin']).' - '.date('H:i:s', $raids[$key]['end']).' '.$user->lang['rli_clock'];
						}
						else
						{
							$temp = $this->get_event($zone['name']);
                        	$raids[$key]['note'] = $this->get_note($raids[$key]['bosskills']);
						}
						$raids[$key]['event'] = $temp['event'];
						$raids[$key]['timebonus'] = $temp['timebonus'];

						//value
						$raids[$key]['value'] = $this->get_raidvalue($raids[$key]['begin'], $raids[$key]['end'], $raids[$key]['bosskills'], $raids[$key]['timebonus'], $raids[$key]['event']);
						$key++;
				  }
			}
			break;

			}
			case "3": //one raid per hour and one per boss
			{
				$tc = 1;
				for($i = $zone['enter']; $i<=($zone['leave']); $i+=3600)
				{
                   	//time
					$raids[$key]['begin'] = $i;
					$raids[$key]['end'] = (($i+3600) > $zone['leave']) ? $zone['leave'] : $i+3600;

                    //event
                   	$temp = $this->get_event($zone['name']);
                   	$raids[$key]['event'] = $temp['event'];
					$raids[$key]['timebonus'] = $temp['timebonus'];

					//bosskills
					$raids[$key]['bosskills'] = array();

	                //note
	                if($this->config['raid_note_time'])
	                {
	                  	$raid[$key]['note'] = $tc.'. '.$user->lang['rli_hour'];
	                  	$tc++;
	                }
	                else
	                {
						$raids[$key]['note'] = date('H:i:s', $i).' - '.date('H:i:s', $raids[$key]['end']).' '.$user->lang['rli_clock'];
					}

					//value
					$raids[$key]['value'] = $this->get_raidvalue($raids[$key]['begin'], $raids[$key]['end'], $raids[$key]['bosskills'], $raids[$key]['timebonus'], $raids[$key]['event']);
					$key++;
				}
				if(is_array($this->data['bosskills']))
				{
					foreach($this->data['bosskills'] as $b => $bosskill)
					{
						//time
						$temp = $this->get_bosskill_raidtime($zone['enter'], $zone['leave'], $bosskill['time'], $this->data['bosskills'][$b-1]['time'], $this->data['bosskills'][$b+1]['time']);
						$raids[$key]['begin'] = $temp['begin'];
						$raids[$key]['end'] = $temp['end'];

						//bosskills
						$raids[$key]['bosskills'] = $this->get_bosskills($this->data['bosskills'], $raids[$key]['begin'], $raids[$key]['end']);

						//note
						$raids[$key]['note'] = $this->get_note($raids[$key]['bosskills']);

						//event
						if($this->config['event_boss'] == 1)
						{
							$temp = $this->get_event($bosskill['name'], true);
						}
						else
						{
							$temp = $this->get_event($zone['name']);
						}
						$raids[$key]['event'] = $temp['event'];
						$raids[$key]['timebonus'] = 0;  //timedkp are calculated in raids per hour

						//value
						$raids[$key]['value'] = $raids[$key]['bosskills'][$b]['bonus'];
						$key++;
					}
				}
				break;

			}
		  }//switch
		}//foreach zones
		if($this->config['attendence_raid'])
		{
			if($this->config['attendence_begin'] > 0)
			{
				$raids[0]['begin'] = $raids[1]['begin'];
				$raids[0]['end'] = $raids[1]['begin'] + $this->config['attendence_time'];
				$raids[0]['event'] = $raids[1]['event'];
				$raids[0]['note'] = $this->config['att_note_begin'];
				$raids[0]['value'] = $this->config['attendence_begin'];
			}
			if($this->config['attendence_end'] > 0)
			{
				$raids[$key]['begin'] = $raids[$key-1]['end'] - $this->config['attendence_time'];
				$raids[$key]['end'] = $raids[$key-1]['end'];
				$raids[$key]['event'] = $raids[$key-1]['event'];
				$raids[$key]['note'] = $this->config['att_note_end'];
				$raids[$key]['value'] = $this->config['attendence_end'];
			}
		}
		else
		{
		  $rmaxkey = max(array_keys($raids));
		  foreach($raids as $r)
		  {
			if($this->config['attendence_begin'] > 0 OR $this->config['attendence_end'] > 0)
			{
				$raids[1]['value'] = $r['value'] + $this->config['attendence_begin'];
				$raids[$rmaxkey]['value'] = $r['value'] + $this->config['attendence_end'];
			}
		  }
		}
		ksort($raids);
		$this->data['raids'] = $raids;
		unset($raids);
		unset($this->data['bosskills']);
		unset($this->data['zones']);
	}

	function boss_dropdown($bossname, $raid_key, $key)
	{
		global $myHtml;
		$this->get_bonus();
		if(!$this->bk_list)
		{
		  foreach($this->bonus['boss'] as $boss)
		  {
			$this->bk_list[htmlspecialchars($boss['string'][0], ENT_QUOTES)] = htmlentities($boss['note'], ENT_QUOTES);
			if($this->get_multiconfig($this->config['use_dkp'], 1))
			{
				$this->bk_list[htmlspecialchars($boss['string'][0], ENT_QUOTES)] .= ' ('.$boss['bonus'].')';
			}
		  }
		}
		foreach($this->bonus['boss'] as $boss)
		{
			if(in_array($bossname, $boss['string']))
			{
				$sel = htmlspecialchars($boss['string'][0], ENT_QUOTES);
			}
		}
		return $myHtml->DropDown('raids['.$raid_key.'][bosskills]['.$key.'][name]', $this->bk_list, $sel);
	}

	function calculate_attendence($times, $begin, $end)
	{
		$dkp['begin'] = false;
		$dkp['end'] = false;
		foreach($times as $time)
		{
			if(key($time) == 'join')
			{
            	if(($begin + $this->config['attendence_time']) > $time['join'])
            	{
                	$dkp['begin'] = $this->config['attendence_begin'];
           		}
			}
			elseif(key($time) == 'leave')
			{
				if(($end - $this->config['attendence_time']) < $time['leave'])
				{
					$dkp['end'] = $this->config['attendence_end'];
				}
			}
		}
		return $dkp;
	}

	function raidlist()
	{
		$list = array();
		foreach($this->data['raids'] as $key => $raid)
		{
			$list[$key] = $raid['note'];
		}
		return $list;
	}

	function auto_minus_ra($actualraidvalue)
	{
		global $db, $user;
		if($this->config['auto_minus'])
		{
        	$raid_attendees = array();
			$sql = "SELECT raid_id, raid_value, raid_date FROM __raids ORDER BY raid_date DESC LIMIT ".($this->config['am_raidnum']-1).";";
			$res = $db->query($sql);
			$raid_value = 0;
			$raid_date = 9996191683;			//date in the far, far, far future
			while ($row = $db->fetch_record($res))
			{
				$raid_ids[] = $row['raid_id'];
				$raid_date = ($raid_date > $row['raid_date']) ? $row['raid_date'] : $raid_date;
				$raid_value += $row['raid_value'];
			}
			if($this->config['am_value_raids'])
			{
				$raid_value += $actualraidvalue;
			}
			$raidid = implode("' OR raid_id = '", $raid_ids);
            if($this->config['am_allxraids'])
            {
                if($this->config['null_sum'])
                {
                	$sql = "SELECT item_date AS date, item_buyer AS member_name FROM __items WHERE item_name = '".$user->lang['am_name']."';";
                }
                else
                {
                	$sql = "SELECT adjustment_date AS date, member_name FROM __adjustments WHERE adjustment_reason = '".$user->lang['am_name']."';";
                }
                $res = $db->query($sql);
                while ($row = $db->fetch_record($res))
                {
                	if($row['date'] >= $raid_date)
                	{
                		$raid_attendees[$row['member_name']] = true;
                	}
                }
            }
			$sql = "SELECT member_name FROM __raid_attendees WHERE raid_id = '".$raidid."';";
			$res = $db->query($sql);
			while ($row = $db->fetch_record($res))
			{
				$raid_attendees[$row['member_name']] = true;
			}
			$raid_attendees['raids_value'] = $raid_value;
			$db->free_result($res);
			return $raid_attendees;
		}
	}

	function auto_minus($raid_attendees)
	{
		global $db, $user;
        if($this->config['auto_minus'])
        {
        	$maxkey = 0;
    		if($this->config['null_sum'])
    		{
    		  if(is_array($this->data['loots']))
    		  {
        		foreach($this->data['loots'] as $key => $loot)
	        	{
	            	$maxkey = ($maxkey < $key) ? $key : $maxkey;
	            }
	          }
	        }
	        else
	        {
	          if(is_array($this->data['adjs']))
	          {
	            foreach($this->data['adjs'] as $key => $adj)
	            {
	            	$maxkey = ($maxkey < $key) ? $key : $maxkey;
	            }
	          }
	        }
        	$sql = "SELECT member_name FROM __members WHERE member_status = '1';";
        	$res = $db->query($sql);
        	while ($row = $db->fetch_record($res))
        	{
        		if(!$raid_attendees[$row['member_name']])
        		{
                	$maxkey++;
                    if($tempkey = $this->check_adj_exists($row['member_name'], $user->lang['am_name']))
                    {
                      if($this->config['null_sum'])
                      {
                      	$this->data['loots'][$tempkey]['dkp'] = ($this->config['am_value_raids']) ? $raid_attendees['raids_value'] : $this->config['am_value'];
                      }
                      else
                      {
                      	$this->data['adjs'][$tempkey]['value'] = -(($this->config['am_value_raids']) ? $raid_attendees['raids_value'] : $this->config['am_value']);
                      }
                    }
                    else
                    {
        			  if($this->config['null_sum'])
        			  {
        				$this->data['loots'][$maxkey]['name'] = $user->lang['am_name'];
        				$this->data['loots'][$maxkey]['time'] = $this->data['raids'][1]['begin'] +1;
        				$this->data['loots'][$maxkey]['dkp'] = ($this->config['am_value_raids']) ? $raid_attendees['raids_value'] : $this->config['am_value'];
        				$this->data['loots'][$maxkey]['player'] = $row['member_name'];
        			  }
        			  else
        			  {
        				$this->data['adjs'][$maxkey]['reason'] = $user->lang['am_name'];
        				$this->data['adjs'][$maxkey]['value'] = -(($this->config['am_value_raids']) ? $raid_attendees['raids_value'] : $this->config['am_value']);
        				$this->data['adjs'][$maxkey]['event'] = $this->data['raids'][1]['event'];
        				$this->data['adjs'][$maxkey]['member'] = $row['member_name'];
        			  }
        			}
        		}
        	}
        }
    }

	function parse_raids()
	{
	 foreach($_POST['raids'] as $key => $raid)
	 {
	  if(!isset($raid['delete']))
	  {
      	list($day, $month, $year) = explode('.', $raid['start_date'], 3);
      	list($hour, $min, $sec) = explode(':', $raid['start_time'], 3);
      	$raids[$key]['begin'] = mktime($hour, $min, $sec, $month, $day, $year);
      	list($day, $month, $year) = explode('.', $raid['end_date'], 3);
      	list($hour, $min, $sec) = explode(':', $raid['end_time'], 3);
      	$raids[$key]['end'] = mktime($hour, $min, $sec, $month, $day, $year);
      	$raids[$key]['note'] = $raid['note'];
      	$raids[$key]['value'] = floatvalue($raid['value']);
      	$raids[$key]['event'] = $raid['event'];
      	$raids[$key]['bosskill_add'] = $raid['bosskill_add'];
      	$bosskills = array();
      	if(is_array($raid['bosskills']))
      	{
      	  foreach($raid['bosskills'] as $u => $bk)
      	  {
      		if(!isset($bk['delete']))
      		{
    	  		list($hour, $min, $sec) = explode(':', $bk['time']);
	      		list($day, $month, $year) = explode('.', $bk['date']);
      			$bosskills[$u]['time'] = mktime($hour, $min, $sec, $month, $day, $year);
      			$bosskills[$u]['bonus'] = floatvalue($bk['bonus']);
      			$bosskills[$u]['name'] = $bk['name'];
      		}
      	  }
      	}
      	$raids[$key]['bosskills'] = $bosskills;
      	$raids[$key]['timebonus'] = floatvalue($raid['timebonus']);
      }
	 }
	 $this->data['raids'] = $raids;
	}

	function parse_members()
	{
	  global $user;
	  $rv = 0;
	  if($this->config['null_sum'])
	  {
	  	$rv = $this->get_nsr_value();
	  }
	  else
	  {
	  	foreach($this->data['raids'] as $ra)
	  	{
	  		$rv += $ra['value'];
	  	}
	  }
	  $raid_attendees = $this->auto_minus_ra($rv);
      $members = array();

	  //check which raids, are for the adjustment
	  if($this->config['attendence_raid'])
	  {
	  	$adj_ra['start'] = 0;
	  	if($this->config['attendence_end'])
	  	{
	  		$adj_ra['end'] = count($this->data['raids']);
	    }
	    $adj_ra['end'] = ($this->config['attendence_begin']) ? $adj_ra['end']-1 : $adj_ra['end'];
	  }
	  else
	  {
	  	  $adj_ra['start'] = ($this->config['attendence_begin']) ? 1 : 0;
	  	  $adj_ra['end'] = ($this->config['attendence_end']) ? count($this->data['raids']) : 0;
	  }

	  foreach($_POST['members'] as $k => $mem)
	  {
        $i = count($this->data['adjs'])+1;
		foreach($this->data['members'] as $key => $member)
		{
			if($k == $key)
			{
			  if(!$mem['delete'])
			  {
			  	$members[$key] = $member;
			  	$members[$key]['name'] = $mem['name'];
				$members[$key]['raid_list'] = $mem['raid_list'];
				$members[$key]['att_dkp_begin'] = (isset($mem['att_begin'])) ? true : false;
				$members[$key]['att_dkp_end'] = (isset($mem['att_end'])) ? true : false;
				if(isset($mem['alias']))
				{
	                $members[$key]['alias'] = $mem['alias'];
	            }
				if($mem['raid_list'])
				{
					$raids = $mem['raid_list'];
	           		$raid_attendees[$mem['name']] = true;

					foreach($raids as $raid_id)
					{
					  if(($raid_id != $adj_ra['start'] AND $raid_id != $adj_ra['end']) OR !$this->config['attendence_raid'])
					  {
                        $dkp = 0;
						$raid = $this->data['raids'][$raid_id];
						if($this->get_multiconfig($this->config['use_dkp'], 2))
						{
							$dkp = $dkp + $this->calc_timedkp($raid['begin'], $raid['end'], $member, $raid['timebonus']);
						}
						if($this->get_multiconfig($this->config['use_dkp'], 1))
						{
							$dkp = $dkp + $this->calc_bossdkp($raid['bosskills'], $member);
						}
						if($this->get_multiconfig($this->config['use_dkp'], 4))
						{
							$dkp = $dkp + $this->calc_eventdkp($raid['event']);
						}
						if($this->config['attendence_begin'] AND $raid_id == $adj_ra['start'])
						{
							$dkp = $dkp + (($mem['att_begin']) ? $this->config['attendence_begin'] : 0);
						}
						if($this->config['attendence_end'] AND $raid_id == $adj_ra['end'])
						{
							$dkp = $dkp + (($mem['att_end']) ? $this->config['attendence_end'] : 0);
						}
						$dkp = round($dkp, 2);
						if($dkp <  $raid['value'])
						{	//add an adjustment
                          $dkp -= $raid['value'];
						  if($tempkey = $this->check_adj_exists($mem['name'], $user->lang['rli_partial_raid']." ".date('d.m.y H:i:s', $raid['begin'])))
						  {
						  	$this->data['adjs'][$tempkey]['value'] = $dkp;
						  }
						  else
						  {
							$this->data['adjs'][$i]['member'] = $mem['name'];
							$this->data['adjs'][$i]['reason'] = $user->lang['rli_partial_raid']." ".date('d.m.y H:i:s', $raid['begin']);
							$this->data['adjs'][$i]['value'] = $dkp;
							$this->data['adjs'][$i]['event'] = $raid['event'];
							$i++;
						  }
						}
					  }
					}
				}
			  } //delete
			}
		}
	  }

	  $this->auto_minus($raid_attendees);
	  $this->data['members'] = $members;
	}

	function parse_items()
	{
	  $loot_sum = 0;
	  foreach($_POST['loots'] as $k => $loot)
	  {
		if(is_array($this->data['loots']))
		{
    	  foreach($this->data['loots'] as $key => $item)
    	  {
			if($k == $key)
			{
			  if(!$loot['delete'])
			  {
				$this->data['loots'][$key] = $loot;
				$this->data['loots'][$key]['dkp'] = floatvalue($loot['dkp']);
				$this->data['loots'][$key]['time'] = $item['time'];
			  }
			  else
			  {
			  	unset($this->data['loots'][$key]);
			  }
			}
		  }
		}
	  }
	}

	function parse_adjs()
	{
	  $adjs = array();
	  foreach($_POST['adjs'] as $f => $adj)
	  {
		if(!$adj['delete'])
		{
			$adjs[$f] = $adj;
			$adjs[$f]['value'] = floatvalue($adj['value']);
		}
	  }
	  $this->data['adjs'] = $adjs;
	}

	function parse_post()
	{
		$this->data = unserialize($_POST['rest']);
		if(isset($_POST['adjs']))
		{
			$this->parse_adjs();
		}
		if(isset($_POST['loots']))
		{
			$this->parse_items();
		}
		if(isset($_POST['members']))
		{
			$this->parse_members();
		}
		if(isset($_POST['raids']))
		{
			if(!isset($_POST['ns']))
			{
				$this->parse_raids();
			}
			else
			{
				foreach($this->data['raids'] as $key => $raid)
				{
					foreach($_POST['raids'] as $k => $r)
					{
						if($k == $key)
						{
							$this->data['raids'][$k]['value'] = $r['value'];
						}
					}
				}
			}
		}
	}

	function member_in_raid($mkey, $begin, $end)
	{
		$time = $end - $begin;
		$member_raidtime = $this->get_member_secs_inraid($this->data['members'][$mkey]['times'], $begin, $end);
		if($member_raidtime > $time/2)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	function check_data()
	{
		$bools = array();
		if($this->data['raids'])
		{
			foreach($this->data['raids'] as $raid)
			{
				if(!($raid['event'] AND $raid['note'] AND $raid['begin'] AND $raid['end']) AND $raid['value'] == '' AND $raid['event'] == '')
				{
					$bools['false']['raid'] = FALSE;
				}
			}
		}
		else
		{
			$bools['false']['raid'] = 'miss';
		}
		if(!isset($this->data['members']))
		{
			$bools['false']['mem'] = 'miss';
		}
		if(isset($this->data['loots']))
		{
			foreach($this->data['loots'] as $loot)
			{
				if(!($loot['name'] AND $loot['player'] AND $loot['raid']) AND $loot['dkp'] == '')
				{
					$bools['false']['item'] = FALSE;
				}
			}
		}
		if(isset($this->data['adjs']))
		{
			foreach($this->data['adjs'] as $adj)
			{
				if(!($adj['member'] AND $adj['event'] AND $adj['reason'] AND $adj['value']))
				{
					$bools['false']['adj'] = FALSE;
				}
			}
		}
		return $bools;
	}

	/**
	 *	checks wether all nodes are available (if not optional) and complete.
	 *	returns an array(1 => bool, 2 => array( contains strings of missing/wrong nodes ))
	 *	params: xml => xml to check
	 *			xml_form => array, which describes the xml: array(node => array(node => ''));
	 *					if prefix is "optional:", the node is only checked for completion
	 *					if prefix is "multiple:", all occuring nodes are checked
	 */
	function check_xml_format($xml, $xml_form, $back=array(1 => true), $pre='')
	{
		foreach($xml_form as $name => $val)
		{
			$optional = false;
			if(strpos($name, 'optional:') !== false)
			{
				$name = str_replace('optional:', '', $name);
				$optional = true;
			}
			$multiple = false;
			if(strpos($name, 'multiple:') !== false)
			{
				$name = str_replace('multiple:', '', $name);
				$multiple = true;
			}
			if($multiple)
			{
				$pre .= $name.'->';
				foreach($val as $nname => $vval)
				{
                	$optional = false;
                    if(strpos($nname, 'optional:') !== false)
                    {
                        $nname = str_replace('optional:', '', $nname);
                        $optional = true;
                    }
                    $pre .= $nname.'->';
                    if((!isset($xml->$name->$nname)) AND !$optional)
                    {
                    	$back[1] = false;
                    	$back[2][] = $pre.$name;
                    }
                    else
                    {
					  if(is_array($vval))
					  {
						foreach($xml->$name->children() as $child)
						{
							$back = $this->check_xml_format($child, $vval, $back, $pre);
						}
                    	$pre = substr($pre, 0, -(strlen($nname)+2));
					  }
					  else
					  {
                    	foreach($xml->$name->children() as $child)
                    	{
                        	if((!isset($child) OR trim($child) == '') AND !$optional)
                        	{
                            	$back[1] = false;
                            	$back[2][] = $pre.$name;
                        	}
                    	}
                      }
					}
					$pre = '';
				}
			}
			else
			{
				if((!isset($xml->$name) OR (trim($xml->$name) == '') AND !is_array($val)) AND !$optional)
				{
					$back[1] = false;
					$back[2][] = $pre.$name;
				}
				else
				{
					if(is_array($val))
					{
						$pre .= $name.'->';
						$back = $this->check_xml_format($xml->$name, $val, $back, $pre);
						$pre = '';
					}
				}
			}
			if(strpos($val, 'function:') !== false)
			{
				$func = str_replace('function:', '', $val);
				$back = call_user_func($func, $xml->name, $back);
			}
		}
		return $back;
	}

	function check_plus_format($xml)
	{
		$xml = $xml->raiddata;
		$xml_form = array(
			'multiple:zones' => array(
				'zone' => array(
					'enter'	=> '',
					'leave' => '',
					'name'	=> ''
				)
			),
			'multiple:bosskills' => array(
				'optional:bosskill' => array(
					'name'	=> '',
					'time'	=> ''
				)
			),
			'multiple:members' => array(
				'member' => array(
					'name'	=> '',
					'multiple:times' => array('time' => '')
				)
			),
			'multiple:items' => array(
				'optional:item'	=> array(
					'name'		=> '',
					'time'		=> '',
					'member'	=> ''
				)
			)
		);
		return $this->check_xml_format($xml, $xml_form);
	}

	function parse_plus_string($xml)
	{
		global $eqdkp, $user;

        $this->data = array();
		if((trim($xml->head->gameinfo->game) == 'Runes of Magic' AND strtolower($eqdkp->config['default_game']) != 'runesofmagic') OR
		   (trim($xml->head->gameinfo->game) == 'World of Warcraft' AND strtolower($eqdkp->config['default_game']) != 'wow'))
		{
			message_die($user->lang['wrong_game']);
		}
		$lang = trim($xml->head->gameinfo->language);
		$this->data['log_lang'] = substr($lang, 0, 2);
		$xml = $xml->raiddata;
		$key = 1;
		foreach($xml->zones->children() as $zone)
		{
			$this->data['zones'][$key] = array(
				'enter'	=> (int) trim($zone->enter),
				'leave'	=> (int) trim($zone->leave),
				'name'	=> trim(utf8_decode($zone->name)),
				'diff'	=> (int) trim($zone->difficulty)
			);
			$key++;
		}
		$key = 1;
		foreach($xml->bosskills->children() as $bosskill)
		{
			$this->data['bosskills'][$key] = array(
				'name'	=> trim(utf8_decode($bosskill->name)),
				'time'	=> (int) trim($bosskill->time)
			);
			$key++;
		}
		$key = 1;
		foreach($xml->members->children() as $member)
		{
			$times = array();
			$type = '';
			foreach($member->times->children() as $time)
			{
				foreach($time->attributes() as $type);
				{
					$type = (string) $type[0];
					$time = (int) $time;
					$times[] = array($type => $time);
				}
			}
            $times = $this->check_times($times);
			$note = (isset($member->note)) ? trim(utf8_decode($member->note)) : '';
			$this->data['members'][$key] = array(
				'name'	=> trim(utf8_decode($member->name)),
				'race'	=> trim(utf8_decode($member->race)),
				'class'	=> trim(utf8_decode($member->class)),
				'level'	=> trim($member->level),
				'note'	=> $note,
				'times'	=> $times
			);
			$key++;
		}
		$key = 1;
		foreach($xml->items->children() as $item)
		{
			$cost = (isset($item->costs)) ? trim($item->costs) : '';
			$id = (isset($item->itemid)) ? trim($item->itemid) : '';
			$this->data['loots'][$key] = array(
				'name'		=> trim(utf8_decode($item->name)),
				'time'		=> (int) trim($item->time),
				'player'	=> trim(utf8_decode($item->member)),
				'dkp'		=> (int) $cost,
				'id'		=> (int) $id
			);
			$key++;
		}
	}

	function check_eqdkp_format($xml)
	{
		$back[1] = true;
		if(!isset($xml->start))
		{
			$back[1] = false;
			$back[2][] = 'start';
		}
		else
		{
			if(!(stristr($xml->start, ':')))
			{
				$back[1] = false;
				$back[2][] = 'start in format: MM/DD/YY HH:MM:SS';
			}
		}
		if(!isset($xml->end))
		{
		 	$back[1] = false;
		 	$back[2][] = 'end';
		}
		else
		{
			if(!(stristr($xml->start, ':')))
			{
				$back[1] = false;
				$back[2][] = 'end in format: MM/DD/YY HH:MM:SS';
			}
		}
		if(!isset($xml->BossKills))
		{
		  	$back[1] = false;
		  	$back[2][] = 'BossKills';
		}
		else
		{
			foreach($xml->BossKills->children() as $bosskill)
			{
			  if($bosskill)
			  {
				if(!isset($bosskill->name))
				{
					$back[1] = false;
					$back[2][] = 'BossKills->name';
				}
				if(!isset($bosskill->time))
				{
					$back[1] = false;
					$back[2][] = 'BossKills->time';
				}
			  }
			}
		}
		if(!isset($xml->Loot))
		{
		   	$back[1] = false;
		   	$back[2][] = 'Loot';
		}
		else
		{
			foreach($xml->Loot->children() as $loot)
			{
			  if($loot)
			  {
				if(!isset($loot->ItemName))
				{
					$back[1] = false;
					$back[2][] = 'Loot->ItemName';
				}
				if(!isset($loot->Player))
				{
					$back[1] = false;
					$back[2][] = 'Loot->Player';
				}
				if(!isset($loot->Time))
				{
					$back[1] = false;
					$back[2] = 'Loot->Time';
				}
			  }
			}
		}
		if(!isset($xml->PlayerInfos))
		{
			$back[1] = false;
			$back[2][] = 'PlayerInfos';
		}
		else
		{
			foreach($xml->PlayerInfos->children() as $mem)
			{
				if(!isset($mem->name))
				{
					$back[1] = false;
					$back[2][] = 'PlayerInfos->name';
				}
			}
		}
		if(!isset($xml->Join))
		{
			$back[1] = false;
			$back[2][] = 'Join';
		}
		else
		{
			foreach($xml->Join->children() as $join)
			{
				if(!isset($join->player))
				{
					$back[1] = false;
					$back[2][] = 'Join->player';
				}
				if(!isset($join->time))
				{
					$back[1] = false;
					$back[2][] = 'Join->time';
				}
			}
		}
		if(!isset($xml->Leave))
		{
			$back[1] = false;
			$back[2][] = 'Leave';
		}
		else
		{
			foreach($xml->Leave->children() as $leave)
			{
				if(!isset($leave->player))
				{
					$back[1] = false;
					$back[2][] = 'Leave->player';
				}
				if(!isset($leave->time))
				{
					$back[1] = false;
					$back[2][] = 'Leave->time';
				}
			}
		}
		return $back;
	}

	function parse_eqdkp_string($xml)
	{
		$this->data = array();
		$this->data['zones'][1]['enter'] = strtotime($xml->start);
		$this->data['zones'][1]['leave'] = strtotime($xml->end);
		$this->data['zones'][1]['name']  = trim($xml->zone);
		$this->data['zones'][1]['difficulty'] = trim($xml->difficulty);
		$i = 0;
		foreach ($xml->BossKills->children() as $bosskill)
		{
			$this->data['bosskills'][$i]['name'] = trim($bosskill->name);
			$this->data['bosskills'][$i]['time'] = strtotime($bosskill->time);
			$i++;
		}
		$i = 0;
		foreach($xml->Loot->children() as $loot)
		{
			$this->data['loots'][$i]['name'] 	 = utf8_decode(trim($loot->ItemName));
			$this->data['loots'][$i]['id']     = substr(trim($loot->ItemID), 0, 5);
			$this->data['loots'][$i]['player'] = utf8_decode(trim($loot->Player));
			$this->data['loots'][$i]['boss']    = trim($loot->Boss);
			$this->data['loots'][$i]['time']    = strtotime($loot->Time);
			if (array_key_exists('Costs',$loot))
			{
				$this->data['loots'][$i]['dkp'] = (int)$loot->Costs;
			}
			else
			{
				$this->data['loots'][$i]['dkp'] = (int)$loot->Note;
			}
			if((($this->config['ignore_dissed'] == 1 OR $this->config['ignore_dissed'] == 3) AND $this->data['loots'][$i]['player'] == 'disenchanted') OR
			   (($this->config['ignore_dissed'] == 2 OR $this->config['ignore_dissed'] == 3) AND $this->data['loots'][$i]['player'] == 'bank'))
			{
				unset($this->data['loots'][$i]);
				$i--;
			}
			$i++;
		}
		$i = 0;
		$a = 0;
		foreach($xml->PlayerInfos->children() as $member)
		{
			$this->data['members'][$i]['name']  = utf8_decode($member->name);
			$this->data['members'][$i]['race']  = utf8_decode($member->race);
			$this->data['members'][$i]['class'] = utf8_decode($member->class);
			$this->data['members'][$i]['level'] = utf8_decode($member->level);
			if(isset($member->note))
			{
				$this->data['adjs'][$a]['member'] = utf8_decode($member->name);
				list($reason, $value) = explode($this->config['adj_parse'], utf8_decode($member->note));
				$this->data['adjs'][$a]['reason'] = $reason;
				$this->data['adjs'][$a]['value'] = $value;
				$a++;
			}
			$i++;
		}
		foreach ($xml->Join->children() as $joiner)
		{
			$search = utf8_decode(trim($joiner->player));
			$key = fktMultiArraySearch($this->data['members'],$search);
		    if ($key)
	    	{
				$this->data['members'][$key[0]]['jl']['join'][] = strtotime($joiner->time);
			}
		}
		foreach ($xml->Leave->children() as $leaver)
		{
			$search = utf8_decode(trim($leaver->player));
			$key = fktMultiArraySearch($this->data['members'],$search);
		    if ($key)
		    {
			   $this->data['members'][$key[0]]['jl']['leave'][] = strtotime($leaver->time);
			}
		}
		foreach($this->data['members'] as $key => $member)
		{
			$times = $this->get_member_times($member['jl'], $this->data['zones'][1]['enter'], $this->data['zones'][1]['leave']);
			unset($this->data['members'][$key]['jl']);
			$this->data['members'][$key]['times'] = $times;
		}
	}
	
	function check_magicdkp_format($xml)
	{
		$back[1] = true;
		if(!isset($xml->start))
		{
			$back[1] = false;
			$back[2][] = 'start';
		}
		else
		{
			if(!(stristr($xml->start, ':')))
			{
				$back[1] = false;
				$back[2][] = 'start in format: MM/DD/YY HH:MM:SS';
			}
		}
		if(!isset($xml->end))
		{
		 	$back[1] = false;
		 	$back[2][] = 'end';
		}
		else
		{
			if(!(stristr($xml->start, ':')))
			{
				$back[1] = false;
				$back[2][] = 'end in format: MM/DD/YY HH:MM:SS';
			}
		}
		if(!isset($xml->BossKills))
		{
		  	$back[1] = false;
		  	$back[2][] = 'BossKills';
		}
		else
		{
			foreach($xml->BossKills->children() as $bosskill)
			{
			  if($bosskill)
			  {
				if(!isset($bosskill->name))
				{
					$back[1] = false;
					$back[2][] = 'BossKills->name';
				}
				if(!isset($bosskill->time))
				{
					$back[1] = false;
					$back[2][] = 'BossKills->time';
				}
			  }
			}
		}
		if(!isset($xml->Loot))
		{
		   	$back[1] = false;
		   	$back[2][] = 'Loot';
		}
		else
		{
			foreach($xml->Loot->children() as $loot)
			{
			  if($loot)
			  {
				if(!isset($loot->ItemName))
				{
					$back[1] = false;
					$back[2][] = 'Loot->ItemName';
				}
				if(!isset($loot->Player))
				{
					$back[1] = false;
					$back[2][] = 'Loot->Player';
				}
				if(!isset($loot->Time))
				{
					$back[1] = false;
					$back[2] = 'Loot->Time';
				}
			  }
			}
		}
		if(!isset($xml->Join))
		{
			$back[1] = false;
			$back[2][] = 'Join';
		}
		else
		{
			foreach($xml->Join->children() as $join)
			{
				if(!isset($join->player))
				{
					$back[1] = false;
					$back[2][] = 'Join->player';
				}
				if(!isset($join->time))
				{
					$back[1] = false;
					$back[2][] = 'Join->time';
				}
			}
		}
		if(!isset($xml->Leave))
		{
			$back[1] = false;
			$back[2][] = 'Leave';
		}
		else
		{
			foreach($xml->Leave->children() as $leave)
			{
				if(!isset($leave->player))
				{
					$back[1] = false;
					$back[2][] = 'Leave->player';
				}
				if(!isset($leave->time))
				{
					$back[1] = false;
					$back[2][] = 'Leave->time';
				}
			}
		}
		return $back;
	}

	function parse_magicdkp_string($xml)
	{
		$this->data = array();
		$this->data['zones'][1]['enter'] = strtotime($xml->start);
		$this->data['zones'][1]['leave'] = strtotime($xml->end);
		$this->data['zones'][1]['name']  = trim($xml->zone);
		$this->data['zones'][1]['difficulty'] = trim($xml->difficulty);
		$i = 0;
		foreach ($xml->BossKills->children() as $bosskill)
		{
			$this->data['bosskills'][$i]['name'] = trim($bosskill->name);
			$this->data['bosskills'][$i]['time'] = strtotime($bosskill->time);
			$i++;
		}
		$i = 0;
		foreach($xml->Loot->children() as $loot)
		{
			$this->data['loots'][$i]['name'] 	 = utf8_decode(trim($loot->ItemName));
			$this->data['loots'][$i]['id']     = substr(trim($loot->ItemID), 0, 5);
			$this->data['loots'][$i]['player'] = utf8_decode(trim($loot->Player));
			$this->data['loots'][$i]['boss']    = trim($loot->Boss);
			$this->data['loots'][$i]['time']    = strtotime($loot->Time);
			if (array_key_exists('Costs',$loot))
			{
				$this->data['loots'][$i]['dkp'] = (int)$loot->Costs;
			}
			else
			{
				$this->data['loots'][$i]['dkp'] = (int)$loot->Note;
			}
			if((($this->config['ignore_dissed'] == 1 OR $this->config['ignore_dissed'] == 3) AND $this->data['loots'][$i]['player'] == 'disenchanted') OR
			   (($this->config['ignore_dissed'] == 2 OR $this->config['ignore_dissed'] == 3) AND $this->data['loots'][$i]['player'] == 'bank'))
			{
				unset($this->data['loots'][$i]);
				$i--;
			}
			$i++;
		}
		foreach ($xml->Join->children() as $joiner)
		{
			$this->data['members'] = array(
				'jl' => array(
					'join' => array(
						strtotime($joiner->time)
					)
				),
				'name' => utf8_decode(trim($joiner->player)),
				'race' => utf8_decode(trim($joiner->race)),
				'level' => (int) trim($joiner->level),
				'class' => utf8_decode(trim($joiner->class))
			);
		}
		foreach ($xml->Leave->children() as $leaver)
		{
			$search = utf8_decode(trim($leaver->player));
			$key = fktMultiArraySearch($this->data['members'],$search);
		    if ($key)
		    {
			   $this->data['members'][$key[0]]['jl']['leave'][] = strtotime($leaver->time);
			}
		}
		foreach($this->data['members'] as $key => $member)
		{
			$times = $this->get_member_times($member['jl'], $this->data['zones'][1]['enter'], $this->data['zones'][1]['leave']);
			unset($this->data['members'][$key]['jl']);
			$this->data['members'][$key]['times'] = $times;
		}
	}

	function parse_string($xml)
	{
		global $user, $eqdkp_root_path;

		if(method_exists($this, 'parse_'.$this->config['parser'].'_string'))
		{
			$back = call_user_func(array($this, 'check_'.$this->config['parser'].'_format'), $xml);
			if($back[1])
			{
				call_user_func(array($this, 'parse_'.$this->config['parser'].'_string'), $xml);
			}
			else
			{
			  message_die($user->lang['wrong_format'].' '.$user->lang[$this->config['parser'].'_format'].'<br />'.$user->lang['rli_miss'].implode(', ', $back[2]));
			}
		}
		else
		{
			message_die($user->lang['no_parser']);
		}
	}

	function iteminput2tpl($loot_cache, $start, $end, $members, $aliase)
	{
		global $db, $tpl, $myHtml, $eqdkp, $user;

		if($this->get_multiconfig($this->config['s_member_rank'], 2))
		{
			foreach($members['name'] as $kex => $member)
			{
				$members['name'][$kex] .= $this->rank_suffix($kex);
			}
		}
		foreach ($this->data['loots'] as $key => $loot)
        {
          if($start <= $key AND $key < $end)
          {
            if($user->lang['am_minus'] != $loot['name'])
            {
        		$bla = false;
        		if($loot_cache[$key]['trans'])
        		{
        			$loot['name'] = $loot_cache[$key]['trans'];
        			$loot['id'] = $loot_cache[$key]['itemid'];
   		  	   		$bla = true;
  		      	}
       	 		elseif($loot_cache[$key]['name'])
    	    	{
        			$bla = true;
        		}
        		if(!$bla)
        		{
        			$sql = "INSERT INTO item_rename (id, item_name, item_id) VALUES ('".$key."', '".mysql_real_escape_string($loot['name'])."', '".$loot['id']."');";
        			$db->query($sql);
        		}
        	}
        	if(isset($aliase[$loot['player']]))
        	{
        		$loot['player'] = $aliase[$loot['player']];
        	}
        	$loot_select = "<select size='1' name='loots[".$key."][raid]'>";
          	foreach($this->data['raids'] as $i => $ra)
           	{
           		$loot_select .= "<option value='".$i."'";
           		if($this->loot_in_raid($ra['begin'], $ra['end'], $loot['time']))
           		{
           			$loot_select .= ' selected="selected"';
           		}
           		$loot_select .= ">".$i."</option>";
           	}
           	$lm_s = "<select size='1' name='loots[".$key."][player]'>";
           	$lm_s .= "<option disabled ".((in_array($loot['player'], $members['name'])) ? "" : "selected='selected'").">".$user->lang['rli_choose_mem']."</option>";
           	foreach($members['name'] as $mn => $mem)
           	{
           		$lm_s .= "<option value='".$mn."' ".(($mn == $loot['player']) ? "selected='selected'" : "").">".$mem."</option>";
           	}
           	$lm_s .= "</select>";
			$tpl->assign_block_vars('loots', array(
                'LOOTNAME'  => $loot['name'],
                'ITEMID'    => $loot['id'],
                'LOOTER'    => $lm_s,#$myHtml->DropDown("loots[".$key."][player]", $members['name'], $loot['player'], '', '', true),
                'RAID'      => $loot_select."</select>",
                'LOOTDKP'   => round($loot['dkp'], 2),
                'KEY'       => $key,
                'CLASS'     => $eqdkp->switch_row_class(),
                'READONLY'	=> ($loot['name'] == $user->lang['am_name']) ? 'readonly="readonly"' : '')
            );
		  }
		}
	}

	function loot_in_raid($begin, $end, $time)
	{
		if($begin < $time AND $end > $time)
		{
			return true;
		}
		return false;
	}

	function get_nsr_value($raid_key=false, $returncount=false, $without_am=false)
	{
		global $db, $user;
		$value = 0;
		foreach($this->data['raids'] as $key => $raid)
		{
			$raid['value'] = 0;
			foreach($this->data['loots'] as $loot)
			{
				if($this->loot_in_raid($raid['begin'], $raid['end'], $loot['time']))
				{
					if(!($without_am AND $loot['name'] == $user->lang['am_name']))
					{
            			$loot['dkp'] = $loot['dkp'];
						$raid['value'] = $raid['value'] + $loot['dkp'];
					}
				}
			}
			$count = 0;
			if($this->config['null_sum'] == 2)
			{
				$count = $db->query_first("SELECT COUNT(member_id) FROM __members;");
			}
			else
			{
				foreach($this->data['members'] as $member)
				{
					if($member['raid_list'] AND in_array($key, $member['raid_list']))
					{
						$count++;
					}
				}
			}
			$count = ($count) ? $count : 1; //prevent zero-division
			$pre = (float) $raid['value'];
			$raid['value'] = $raid['value']/$count;
			$raid['value'] = round($raid['value'], 2);
			if($raid_key AND $key == $raid_key)
			{
				if($returncount)
				{
					return array('v' => $raid['value'], 'c' => $count, 'p' => $pre);
				}
				return $raid['value'];
			}
			$value += $raid['value'];
		}
		if($returncount)
		{
			return array('v' => $value, 'c' => $count);
		}
		return $value;
	}

	function get_member_ranks()
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

	function rank_suffix($mname)
	{
		$this->get_member_ranks();
		$rank = (isset($this->member_ranks[$mname])) ? $this->member_ranks[$mname] : $this->member_ranks['new'];
		return ' ('.$rank.')';
	}

	function check_adj_exists($memname, $adjreason)
	{
		if($this->config['null_sum'])
		{
		  if(is_array($this->data['loots']))
		  {
			foreach($this->data['loots'] as $key => $loot)
			{
				if($loot['player'] == $memname AND $loot['name'] == $adjreason)
				{
					return $key;
				}
			}
		  }
  		}
  		else
  		{
  		  if(is_array($this->data['adjs']))
  		  {
  			foreach($this->data['adjs'] as $key => $adj)
  			{
  				if($adj['member'] == $memname AND $adj['reason'] == $adjreason)
  				{
  					return $key;
  				}
  			}
  		  }
  		}
		return false;
	}

	function get_checkraidlist($memberraids, $mkey)
	{
		global $eqdkp_root_path, $pcache;

		$td = '';
		if(!$this->th_raidlist)
		{
			$pcache->CheckCreateFolder($pcache->CacheFolder.'raidlogimport');
            foreach($this->data['raids'] as $rkey => $raid)
            {
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
		foreach($this->data['raids'] as $rkey => $raid)
		{
		$td .= '<td><input type="checkbox" name="members['.$mkey.'][raid_list][]" value="'.$rkey.'" title="'.$raid['note'].'" '.((in_array($rkey, $memberraids)) ? 'checked="checked"' : '').' /></td>';
		}
		return $td;
  	}

  	function get_multiconfig($v, $p)
  	{
		if($v == 7)
		{
			return true;
		}
		if($p == 1 AND ($v == 1 || $v == 3 || $v == 5))
		{
			return true;
		}
		if($p == 2 AND ($v == 2 || $v == 3 || $v == 6))
		{
			return true;
		}
		if($p == 4 AND ($v == 4 || $v == 5 || $v == 6))
		{
			return true;
		}
		return false;
	}
  }//class
}//class exist
?>
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
  	var $diff = '';
  	var $bk_list = array();
  	var $events = array();
  	var $member_ranks = array();

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
		global $user;

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
		foreach($times as $i => $time)
		{
		  if(key($time) == 'join')
		  {
			$nu = max(array_keys($times));
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
	  if($this->config['use_dkp'] == 2 || $this->config['use_dkp'] == 3 || $this->config['use_dkp'] == 6 || $this->config['use_dkp'] == 7)
	  {
		$times = format_duration($this->get_member_secs_inraid($this->get_member_times($player, $begin, $end), $begin, $end));
		$timedkp = $times['hours'] * $timebonus;
		$timedkp = $timedkp + $timebonus * ($times['minutes']/60);
	  }
	  return $timedkp;
	}

	function calc_bossdkp($kills, $player)
	{
	  $bossdkp = 0;
	  if($this->config['use_dkp'] == 1 || $this->config['use_dkp'] == 3 || $this->config['use_dkp'] == 5 || $this->config['use_dkp'] == 7)
	  {
		$times = $this->get_member_times($player);
		foreach($kills as $kill)
		{
		  foreach($times as $i => $time)
		  {
			$nu = max(array_keys($times));
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
			if(key($time) == 'join')
			{
				if($time['join'] < $kill['time'] AND $times[$j]['leave'] > $kill['time'])
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
		$tempmem['join'][1] = $begin;
		$tempmem['leave'][1] = $end;
		$timedkp = $this->calc_timedkp($begin, $end, $tempmem, $timebonus);
		$bossdkp = $this->calc_bossdkp($bosskills, $tempmem);
		$eventdkp = $this->calc_eventdkp($event);
		$tempattdkp = $this->calculate_attendence($tempmem, $begin, $end);
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

		$raid = $this->parse_string($raidxml);
		$this->diff = $raid['difficulty'];
	  	$data['members'] = $raid['members'];
	  	$data['loots'] = $raid['loots'];
	  	$data['adjs'] = $raid['adjs'];
		$raids = array();

		$key = 1;
		switch($this->config['raidcount'])
		{
			case "0": //one raid for everything
			{
				//time
				$raids[$key]['begin'] = $raid['begin'];
				$raids[$key]['end'] = $raid['end'];

				//event
				$temp = $this->get_event($raid['zone']);
				$raids[$key]['event'] = $temp['event'];
				$raids[$key]['timebonus'] = $temp['timebonus'];

				//bosskills
				$raids[$key]['bosskills'] = array();
				if(is_array($raid['bosskills']))
				{
					$raids[$key]['bosskills'] = $this->get_bosskills($raid['bosskills'], $raids[$key]['begin'], $raids[$key]['end']);
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
				if($this->config['use_dkp'] > 1)
				{
					for($i = $raid['begin']; $i<=($raid['end']); $i+=3600)
					{
                    	//time
						$raids[$key]['begin'] = $i;
						$raids[$key]['end'] = $i+3600;

                    	//event
						$temp = $this->get_event($raid['zone']);
						$raids[$key]['event'] = $temp['event'];
						$raids[$key]['timebonus'] = $temp['timebonus'];

						//bosskills
						$raids[$key]['bosskills'] = array();
						if(is_array($raid['bosskills']))
						{
							$raids[$key]['bosskills'] = $this->get_bosskills($raid['bosskills'], $raids[$key]['begin'], $raids[$key]['end']);
						}

	                    //note
	                    $raids[$key]['note'] = $this->get_note($raids[$key]['bosskills']);

						//value
						$raids[$key]['value'] = $this->get_raidvalue($raids[$key]['begin'], $raids[$key]['end'], $raids[$key]['bosskills'], $raids[$key]['timebonus'], $raids[$key]['event']);
						$key++;
					}
					break;

				}
				else
				{
				  	message_die($user->lang['wrong_settings_1']);
				}
			}
			case "2": //one raid per bosskill
			{
				if($this->config['use_dkp'] != 2)
				{
					if(is_array($raid['bosskills']))
					{
					  foreach($raid['bosskills'] as $b => $bosskill)
					  {
						//time
						$temp = $this->get_bosskill_raidtime($raid['begin'], $raid['end'], $bosskill['time'], $raid['bosskills'][$b-1]['time'], $raid['bosskills'][$b+1]['time']);
						$raids[$key]['begin'] = $temp['begin'];
						$raids[$key]['end'] = $temp['end'];

						//bosskills
						$raids[$key]['bosskills'] = $this->get_bosskills($raid['bosskills'], $raids[$key]['begin'], $raids[$key]['end']);

						$temp = array();
						//event+note
						if($this->config['event_boss'] == 1)
						{
							$temp = $this->get_event($bosskill['name'], true);
							$raids[$key]['note'] = date('H:i:s', $raids[$key]['begin']).' - '.date('H:i:s', $raids[$key]['end']).' '.$user->lang['rli_clock'];
						}
						else
						{
							$temp = $this->get_event($raid['zone']);
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
				else
				{
				  	message_die($user->lang['wrong_settings_2']);
				}
			}
			case "3": //one raid per hour and one per boss
			{
				if($this->config['use_dkp'] > 2)
				{
					for($i = $raid['begin']; $i<=($raid['end']); $i+=3600)
					{
                    	//time
						$raids[$key]['begin'] = $i;
						$raids[$key]['end'] = (($i+3600) > $raid['end']) ? $raid['end'] : $i+3600;

                    	//event
                    	$temp = $this->get_event($raid['zone']);
                    	$raids[$key]['event'] = $temp['event'];
						$raids[$key]['timebonus'] = $temp['timebonus'];

						//bosskills
						$raids[$key]['bosskills'] = array();

	                    //note
						$raids[$key]['note'] = date('H:i:s', $i).' - '.date('H:i:s', $raids[$key]['end']).' '.$user->lang['rli_clock'];

						//value
						$raids[$key]['value'] = $this->get_raidvalue($raids[$key]['begin'], $raids[$key]['end'], $raids[$key]['bosskills'], $raids[$key]['timebonus'], $raids[$key]['event']);
						$key++;
					}
					if(is_array($raid['bosskills']))
					{
					  foreach($raid['bosskills'] as $b => $bosskill)
					  {
						//time
						$temp = $this->get_bosskill_raidtime($raid['begin'], $raid['end'], $bosskill['time'], $raid['bosskills'][$b-1]['time'], $raid['bosskills'][$b+1]['time']);
						$raids[$key]['begin'] = $temp['begin'];
						$raids[$key]['end'] = $temp['end'];

						//bosskills
						$raids[$key]['bosskills'] = $this->get_bosskills($raid['bosskills'], $raids[$key]['begin'], $raids[$key]['end']);

						//note
						$raids[$key]['note'] = $this->get_note($raids[$key]['bosskills']);

						//event
						if($this->config['event_boss'] == 1)
						{
							$temp = $this->get_event($bosskill['name'], true);
						}
						else
						{
							$temp = $this->get_event($raid['zone']);
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
				else
				{
				  	message_die($user->lang['wrong_settings_3']);
				}
			}
		}//switch
		if($this->config['attendence_raid'])
		{
			if($this->config['attendence_begin'] > 0)
			{
				$raids[0]['begin'] = $raids[1]['begin'];
				$raids[0]['end'] = $raids[1]['begin'] + $this->config['attendence_time'];
				$raids[0]['event'] = $raids[1]['event'];
				$raids[0]['note'] = $user->lang['rli_att']." ".$user->lang['rli_start'];
				$raids[0]['value'] = $this->config['attendence_begin'];
			}
			if($this->config['attendence_end'] > 0)
			{
				$raids[$key]['begin'] = $raids[$key-1]['end'] - $this->config['attendence_time'];
				$raids[$key]['end'] = $raids[$key-1]['end'];
				$raids[$key]['event'] = $raids[$key-1]['event'];
				$raids[$key]['note'] = $user->lang['rli_att']." ".$user->lang['rli_end'];
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
		$data['raids'] = $raids;
		return $data;
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
			if($this->config['use_dkp'] == 1 || $this->config['use_dkp'] == 3 || $this->config['use_dkp'] == 5 || $this->config['use_dkp'] == 7)
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

	function calculate_attendence($member, $begin, $end)
	{
		$dkp['begin'] = 0;
		$dkp['end'] = 0;
		foreach($member['join'] as $jt)
		{
			if(($begin + $this->config['attendence_time']) > $jt)
			{
				$dkp['begin'] = $this->config['attendence_begin'];
				break;
			}
		}
		foreach($member['leave'] as $lt)
		{
			if(($end - $this->config['attendence_time']) < $lt)
			{
				$dkp['end'] = $this->config['attendence_end'];
				break;
			}
		}
		return $dkp;
	}

	function raidlist($raids)
	{
		$list = array();
		foreach($raids as $key => $raid)
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
			$raid_date = 9996191683;
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

	function auto_minus($data, $raid_attendees)
	{
		global $db, $user;
        if($this->config['auto_minus'])
        {
        	$maxkey = 0;
    		if($this->config['null_sum'])
    		{
    		  if(is_array($data['loots']))
    		  {
        		foreach($data['loots'] as $key => $loot)
	        	{
	            	$maxkey = ($maxkey < $key) ? $key : $maxkey;
	            }
	          }
	        }
	        else
	        {
	          if(is_array($data['adjs']))
	          {
	            foreach($data['adjs'] as $key => $adj)
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
                    if($tempkey = $this->check_adj_exists($row['member_name'], $user->lang['am_name'], $data))
                    {
                      if($this->config['null_sum'])
                      {
                      	$data['loots'][$tempkey]['dkp'] = ($this->config['am_value_raids']) ? $raid_attendees['raids_value'] : $this->config['am_value'];
                      }
                      else
                      {
                      	$data['adjs'][$tempkey]['value'] = -(($this->config['am_value_raids']) ? $raid_attendees['raids_value'] : $this->config['am_value']);
                      }
                    }
                    else
                    {
        			  if($this->config['null_sum'])
        			  {
        				$data['loots'][$maxkey]['name'] = $user->lang['am_name'];
        				$data['loots'][$maxkey]['time'] = $data['raids'][1]['begin'] +1;
        				$data['loots'][$maxkey]['dkp'] = ($this->config['am_value_raids']) ? $raid_attendees['raids_value'] : $this->config['am_value'];
        				$data['loots'][$maxkey]['player'] = $row['member_name'];
        			  }
        			  else
        			  {
        				$data['adjs'][$maxkey]['reason'] = $user->lang['am_name'];
        				$data['adjs'][$maxkey]['value'] = -(($this->config['am_value_raids']) ? $raid_attendees['raids_value'] : $this->config['am_value']);
        				$data['adjs'][$maxkey]['event'] = $data['raids'][1]['event'];
        				$data['adjs'][$maxkey]['member'] = $row['member_name'];
        			  }
        			}
        			unset($raid_attendees[$row['member_name']]);
        		}
        	}
        }
        return $data;
    }

	function parse_raids($post, $data)
	{
	 foreach($post as $key => $raid)
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
	 $data['raids'] = $raids;
	 return $data;
	}

	function parse_members($post, $data)
	{
	  global $user;
	  $rv = 0;
	  if($this->config['null_sum'])
	  {
	  	$rv = $this->get_nsr_value($data);
	  }
	  else
	  {
	  	foreach($data['raids'] as $ra)
	  	{
	  		$rv += $ra['value'];
	  	}
	  }
	  $raid_attendees = $this->auto_minus_ra($rv);
      $members = array();
	  foreach($post as $k => $mem)
	  {
        $i = count($data['adjs'])+1;
		foreach($data['members'] as $key => $member)
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
	           		
	           		//check which raids, are for the adjustment
	           		if($this->config['attendence_raid'])
	           		{
	           			$adj_ra['start'] = 0;
	           			if($this->config['attendence_end'])
	           			{
	           				$adj_ra['end'] = count($data['raids']);
	           			}
	           			$adj_ra['end'] = ($this->config['attendence_start']) ? $adj_ra['end']-1 : $adj_ra['end'];
	           		}
	           		else
	           		{
	           			$adj_ra['start'] = 1;
	           			$adj_ra['end'] = count($data['raids']);
	           		}
	           			
					foreach($raids as $raid_id)
					{
                        $dkp = 0;
						$raid = $data['raids'][$raid_id];
						if($this->config['use_dkp'] == 2 || $this->config['use_dkp'] == 3 || $this->config['use_dkp'] == 6 || $this->config['use_dkp'] == 7)
						{
							$dkp = $dkp + $this->calc_timedkp($raid['begin'], $raid['end'], $member, $raid['timebonus']);
						}
						if($this->config['use_dkp'] == 1 || $this->config['use_dkp'] == 3 || $this->config['use_dkp'] == 5 || $this->config['use_dkp'] == 7)
						{
							$dkp = $dkp + $this->calc_bossdkp($raid['bosskills'], $member);
						}
						if($this->config['use_dkp'] > 3)
						{
							$dkp = $dkp + $this->calc_eventdkp($raid['event']);
						}
						if($adj_ra['start'] == $raid_id)
						{
							$dkp = $dkp + (($mem['att_begin']) ? $this->config['attendence_begin'] : 0);
						}
						if($adj_ra['end'] == $raid_id)
						{
							$dkp = $dkp + (($mem['att_end']) ? $this->config['attendence_end'] : 0);
						}
						$dkp = round($dkp, 2);
						if($dkp <  $raid['value'])
						{	//add an adjustment
                          $dkp -= $raid['value'];
						  if($tempkey = $this->check_adj_exists($mem['name'], $user->lang['rli_partial_raid']." ".date('d.m.y H:i:s', $raid['begin']), $data))
						  {
						  	$data['adjs'][$tempkey]['value'] = $dkp;
						  }
						  else
						  {
							$data['adjs'][$i]['member'] = $mem['name'];
							$data['adjs'][$i]['reason'] = $user->lang['rli_partial_raid']." ".date('d.m.y H:i:s', $raid['begin']);
							$data['adjs'][$i]['value'] = $dkp;
							$data['adjs'][$i]['event'] = $raid['event'];
							$i++;
						  }
						}
					}
				}
			  } //delete
			}
		}
	  }

	  $data = $this->auto_minus($data, $raid_attendees);
	  $data['members'] = $members;
	  return $data;
	}

	function parse_items($post, $data)
	{
	  $loot_sum = 0;
	  foreach($post as $k => $loot)
	  {
		if(is_array($data['loots']))
		{
    	  foreach($data['loots'] as $key => $item)
    	  {
			if($k == $key)
			{
			  if(!$loot['delete'])
			  {
				$data['loots'][$key] = $loot;
				$data['loots'][$key]['dkp'] = floatvalue($loot['dkp']);
				$data['loots'][$key]['time'] = $item['time'];
			  }
			  else
			  {
			  	unset($data['loots'][$key]);
			  }
			}
		  }
		}
	  }
	  return $data;
	}

	function parse_adjs($post, $data)
	{
	  $adjs = array();
	  foreach($post as $f => $adj)
	  {
		if(!$adj['delete'])
		{
			$adjs[$f] = $adj;
			$adjs[$f]['value'] = floatvalue($adj['value']);
		}
	  }
	  $data['adjs'] = $adjs;
	  return $data;
	}

	function parse_post($post, $data)
	{
		$data = unserialize($post['rest']);
		if(isset($post['adjs']))
		{
			$data = $this->parse_adjs($post['adjs'], $data);
		}
		if(isset($post['loots']))
		{
			$data = $this->parse_items($post['loots'], $data);
		}
		if(isset($post['members']))
		{
			$data = $this->parse_members($post['members'], $data);
		}
		if(isset($post['raids']))
		{
			if(!isset($post['ns']))
			{
				$data = $this->parse_raids($post['raids'], $data);
			}
			else
			{
				foreach($data['raids'] as $key => $raid)
				{
					foreach($post['raids'] as $k => $r)
					{
						if($k == $key)
						{
							$data['raids'][$k]['value'] = $r['value'];
						}
					}
				}
			}
		}
		return $data;
	}

	function member_in_raid($member, $raid)
	{
		$raid['time'] = $raid['end'] - $raid['begin'];
		$member_raidtime = $this->get_member_secs_inraid($this->get_member_times($member), $raid['begin'], $raid['end']);
		if($member_raidtime > $raid['time']/2)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	function check_data($data)
	{
		$bools = array();
		if($data['raids'])
		{
			foreach($data['raids'] as $raid)
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
		if(!isset($data['members']))
		{
			$bools['false']['mem'] = 'miss';
		}
		if(isset($data['loots']))
		{
			foreach($data['loots'] as $loot)
			{
				if(!($loot['name'] AND $loot['player'] AND $loot['raid']) AND $loot['dkp'] == '')
				{
					$bools['false']['item'] = FALSE;
				}
			}
		}
		if(isset($data['adjs']))
		{
			foreach($data['adjs'] as $adj)
			{
				if(!($adj['member'] AND $adj['event'] AND $adj['reason'] AND $adj['value']))
				{
					$bools['false']['adj'] = FALSE;
				}
			}
		}
		return $bools;
	}

	function check_ctrt_format($xml)
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

	function parse_ctrt_string($xml)
	{
		$raid = array();
		$raid['begin'] = strtotime($xml->start);
		$raid['end']   = strtotime($xml->end);
		$raid['zone']  = trim($xml->zone);
		$raid['difficulty'] = trim($xml->difficulty);
		$i = 0;
		foreach ($xml->BossKills->children() as $bosskill)
		{
			$raid['bosskills'][$i]['name'] = trim($bosskill->name);
			$raid['bosskills'][$i]['time'] = strtotime($bosskill->time);
			$i++;
		}
		$i = 0;
		foreach($xml->Loot->children() as $loot)
		{
			$raid['loots'][$i]['name'] 	 = utf8_decode(trim($loot->ItemName));
			$raid['loots'][$i]['id']     = substr(trim($loot->ItemID), 0, 5);
			$raid['loots'][$i]['player'] = utf8_decode(trim($loot->Player));
			$raid['loots'][$i]['boss']    = trim($loot->Boss);
			$raid['loots'][$i]['time']    = strtotime($loot->Time);
			if (array_key_exists('Costs',$loot))
			{
				$raid['loots'][$i]['dkp'] = (int)$loot->Costs;
				$raid['lootdkp'][$raid['loots'][$i]['player']] = $raid['lootdkp'][$raid['loots'][$i]['player']] + (int)$loot->Costs;
			}
			else
			{
				$raid['loots'][$i]['dkp'] = (int)$loot->Note;
			}
			if((($this->config['ignore_dissed'] == 1 OR $this->config['ignore_dissed'] == 3) AND $raid['loots'][$i]['player'] == 'disenchanted') OR
			   (($this->config['ignore_dissed'] == 2 OR $this->config['ignore_dissed'] == 3) AND $raid['loots'][$i]['player'] == 'bank'))
			{
				unset($raid['loots'][$i]);
				$i--;
			}
			$i++;
		}
		$i = 0;
		$a = 0;
		foreach($xml->PlayerInfos->children() as $member)
		{
			$raid['members'][$i]['name']  = utf8_decode($member->name);
			$raid['members'][$i]['race']  = utf8_decode($member->race);
			$raid['members'][$i]['class'] = utf8_decode($member->class);
			$raid['members'][$i]['level'] = utf8_decode($member->level);
			if(isset($member->note))
			{
				$raid['adjs'][$a]['member'] = utf8_decode($member->name);
				list($reason, $value) = explode($this->config['adj_parse'], utf8_decode($member->note));
				$raid['adjs'][$a]['reason'] = $reason;
				$raid['adjs'][$a]['value'] = $value;
				$a++;
			}
			$i++;
		}
		foreach ($xml->Join->children() as $joiner)
		{
			$search = utf8_decode(trim($joiner->player));
			$key = fktMultiArraySearch($raid['members'],$search);
		    if ($key)
	    	{
	    	if (array_key_exists('join', $raid['members'][$key[0]])){
					$acount = count($raid['members'][$key[0]]['join']) + 1;
					$raid['members'][$key[0]]['join'][$acount] = strtotime($joiner->time);
				}
				else {
					$raid['members'][$key[0]]['join'][1] = strtotime($joiner->time);
				}
			}
		}
		foreach ($xml->Leave->children() as $leaver) {
			$search = utf8_decode(trim($leaver->player));
			$key = fktMultiArraySearch($raid['members'],$search);
		    if ($key){
		    	if (array_key_exists('leave', $raid['members'][$key[0]])){
					$acount = count($raid['members'][$key[0]]['leave']) + 1;
					$raid['members'][$key[0]]['leave'][$acount] = strtotime($leaver->time);
				}
				else
				{
					$raid['members'][$key[0]]['leave'][1] = strtotime($leaver->time);
				}
			}
		}
		return $raid;
	}

	function parse_string($xml)
	{
		global $user, $eqdkp_root_path;

		if(method_exists($this, 'parse_'.$this->config['parser'].'_string'))
		{
			$back = call_user_func(array($this, 'check_'.$this->config['parser'].'_format'), $xml);
			if($back[1])
			{
				$raid = call_user_func(array($this, 'parse_'.$this->config['parser'].'_string'), $xml);
			}
			else
			{
			  message_die($user->lang['wrong_format'].' '.$user->lang[$this->config['parser'].'_format'].' <img src="'.$eqdkp_root_path.'plugins/raidlogimport/images/'.$this->config['parser'].'_options.png"><br />'.$user->lang['rli_miss'].implode(', ', $back[2]));
			}
		}
		else
		{
			message_die($user->lang['no_parser']);
		}
		return $raid;
	}

	function iteminput2tpl($data, $loot_cache, $start, $end, $members, $aliase)
	{
		global $db, $tpl, $myHtml, $eqdkp, $user;

		if($this->display_rank('loot'))
		{
			foreach($members['name'] as $kex => $member)
			{
				$members['name'][$kex] .= $this->rank_suffix($kex);
			}
		}
		foreach ($data['loots'] as $key => $loot)
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
          	foreach($data['raids'] as $i => $ra)
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

	function get_nsr_value($data, $raid_key=false, $returncount=false, $without_am=false)
	{
		global $db, $user;
		$value = 0;
		foreach($data['raids'] as $key => $raid)
		{
			$raid['value'] = 0;
			foreach($data['loots'] as $loot)
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
				foreach($data['members'] as $member)
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

	function display_rank($page)
	{
		$v = $this->config['s_member_rank'];
		if($v == 7)
		{
			return true;
		}
		if($page == 'member' AND ($v == 1 || $v == 3 || $v == 5))
		{
			return true;
		}
		if($page == 'loot' AND ($v == 2 || $v == 3 || $v == 6))
		{
			return true;
		}
		if($page == 'adj' AND ($v == 4 || $v == 5 || $v == 6))
		{
			return true;
		}
		return false;
	}

	function rank_suffix($mname)
	{
		$this->get_member_ranks();
		$rank = (isset($this->member_ranks[$mname])) ? $this->member_ranks[$mname] : $this->member_ranks['new'];
		return ' ('.$rank.')';
	}

	function check_adj_exists($memname, $adjreason, $data)
	{
		if($this->config['null_sum'])
		{
			foreach($data['loots'] as $key => $loot)
			{
				if($loot['player'] == $memname AND $loot['name'] == $adjreason)
				{
					return $key;
				}
			}
  		}
  		else
  		{
  		  if(is_array($data['adjs']))
  		  {
  			foreach($data['adjs'] as $key => $adj)
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
  }//class
}//class exist
?>
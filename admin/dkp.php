<?php
	/*
	 * original version by kokson
	 * further development by hoofy
	 *
	 */
// EQdkp required files/vars
define('EQDKP_INC', true);
define('IN_ADMIN', true);

$eqdkp_root_path = './../../../';
include_once('./../includes/common.php');

class raidlogimport extends EQdkp_Admin
{
	var $bonus = array();

	function raidlogimport()
    {
        global $db, $eqdkp, $user, $tpl, $pm;
        global $SID, $rli_config;

        parent::eqdkp_admin();

        $this->assoc_buttons(array(
            'checkraid' => array(
                'name'    => 'checkraid',
                'process' => 'process_raids',
                'check'   => 'a_raidlogimport_dkp'),
            'checkmem' => array(
            	'name'	  => 'checkmem',
            	'process' => 'process_members',
            	'check'	  => 'a_raidlogimport_dkp'),
            'checkitem' => array(
            	'name'	  => 'checkitem',
            	'process' => 'process_items',
            	'check'   => 'a_raidlogimport_dkp'),
            'checkadj' => array(
            	'name'	  => 'checkadj',
            	'process' => 'process_adjustments',
            	'check'	  => 'a_raidlogimport_dkp'),
            'viewall' => array(
            	'name'    => 'viewall',
            	'process' => 'process_view',
            	'check'	  => 'a_raidlogimport_dkp'),
            'form' => array(
                'name'    => '',
                'process' => 'display_form',
                'check'   => 'a_raidlogimport_dkp'),
            'insert' => array(
                'name'    => 'insert',
                'process' => 'insert_log',
                'check'   => 'a_raidlogimport_dkp')
                )
        );

        if (get_magic_quotes_gpc()) {
            function stripslashes_array($array) {
                return is_array($array) ? array_map('stripslashes_array', $array) : stripslashes($array);
            }

            $_COOKIE = stripslashes_array($_COOKIE);
            $_FILES = stripslashes_array($_FILES);
            $_GET = stripslashes_array($_GET);
            $_POST = stripslashes_array($_POST);
            $_REQUEST = stripslashes_array($_REQUEST);
        }

		$sql = "SELECT bz_id, bz_string, bz_note, bz_bonus, bz_type FROM __raidlogimport_bz;";
		if($result = $db->query($sql))
		{
			while($row = $db->fetch_record($result))
			{
				if($row['bz_type'] == 'boss')
				{
					$this->bonus['boss'][$row['bz_id']]['string'] = explode($rli_config['bz_parse'], $row['bz_string']);
					$this->bonus['boss'][$row['bz_id']]['note'] = $row['bz_note'];
					$this->bonus['boss'][$row['bz_id']]['bonus'] = $row['bz_bonus'];
				}
				else
				{
					$this->bonus['zone'][$row['bz_id']]['string'] = explode($rli_config['bz_parse'], $row['bz_string']);
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

	function process_raids()
	{
		global $db, $eqdkp, $user, $tpl, $pm;
		global $myHtml, $rli_config, $eqdkp_root_path;

        if(isset($_POST['rest']))
        {
        	$data = unserialize($_POST['rest']);
        	$raids = $data['raids'];
        }
        if(isset($_POST['raids']))
        {
            $data['raids'] = parse_post($_POST['raids']);
            $raids = $data['raids'];
        }
        if(isset($_POST['log']))
        {
          $_POST['log'] = trim(str_replace("&", "and", html_entity_decode($_POST['log'])));
          $dkpstring   = utf8_encode($_POST['log']);
          $raidxml     = simplexml_load_string($dkpstring);
		  if (!$raidxml)
		  {
			  message_die($user->lang['xml_error']);
		  }
		  else
		  {
		  	$raid = parse_string($raidxml);
		  	$data['members'] = $raid['members'];
		  	$data['loots'] = $raid['loots'];
		  	$data['adjs'] = $raid['adjs'];
            $raids = array();
		  	// Raids
			switch($rli_config['raidcount'])
			{
				case "0": //one raid for everything
				{
					$key = 1;
					//time
					$raids[1]['begin'] = $raid['begin'];
					$raids[1]['end'] = $raid['end'];

					//event
					foreach ($this->bonus['zone'] as $zone)
					{
						if (in_array(trim($raid['zone']), $zone['string'])) {
							$raids[1]['event'] = trim($zone['note']);
							$raids[1]['timebonus'] = $zone['bonus'];
							break;
						}
					}

					//Note
					$i = 1;
					foreach($raid['bosskills'] as $b => $bosskill)
					{
						foreach($this->bonus['boss'] as $boss) {
							if (in_array($bosskill['name'], $boss['string'])) {
								if ($i == 1) {
									$raids[1]['note'] = trim($boss['note']);
									if($rli_config['dep_match'])
									{
										$raids[$key]['note'] .= ($raid['difficulty'] == '2') ? $rli_config['hero'] : $rli_config['non_hero'];
									}
								} else {
									$raids[1]['note'] .= ", ".trim($boss['note']);
									if($rli_config['dep_match'])
									{
										$raids[$key]['note'] .= ($raid['difficulty'] == '2') ? $rli_config['hero'] : $rli_config['non_hero'];
									}
								}
								$raids[1]['bosskills'][$b]['name'] = $bosskill['name'];
								$raids[1]['bosskills'][$b]['bonus'] = $boss['bonus'];
								$raids[1]['bosskills'][$b]['time'] = $bosskill['time'];
								break;
							}
						}
						$i++;
					}

					//Value
					$max['join'][1] = $raids[1]['begin']+1;
					$max['leave'][1] = $raids[1]['end']-1;
					$max['timedkp'] = calculate_timedkp($raids[1]['timebonus'], calculate_time($max, $raids[1]['end'], $raids[1]['begin']));
					$max['bossdkp'] = calculate_bossdkp($raids[1]['bosskills'], $max);
					$raids[1]['value'] = $max['timedkp'] + $max['bossdkp'];
					unset($max);
					$key++;
					break;
				}
				case "1": //one raid per hour
				{
					//time
					$key = 1;
					for($i = $raid['begin']; $i<=($raid['end']); $i+=3600)
					{
						$raids[$key]['begin'] = $i;
						$raids[$key]['end'] = $i+3600;
                    	//event
	                    foreach ($this->bonus['zone'] as $zone)
	                    {
	                        if (in_array(trim($raid['zone']), $zone['string'])) {
	                            $raids[$key]['event'] = trim($zone['note']);
								$raids[$key]['timebonus'] = $zone['bonus'];
	                            break;
	                        }
	                    }
	                    //note
						$a = 1;
						foreach($raid['bosskills'] as $b => $bosskill)
						{
							foreach($this->bonus['boss'] as $boss) {
								if (in_array($bosskill['name'], $boss['string']) AND $bosskill['time'] >= $i AND $bosskill['time'] < $i+3600) {
									if ($a == 1) {
										$raids[$key]['note'] = trim($boss['note']);
										if($rli_config['dep_match'])
										{
											$raids[$key]['note'] .= ($raid['difficulty'] == '2') ? $rli_config['hero'] : $rli_config['non_hero'];
										}
                            			$a++;
									} else {
										$raids[$key]['note'] .= ", ".trim($boss['note']);
										if($rli_config['dep_match'])
										{
											$raids[$key]['note'] .= ($raid['difficulty'] == '2') ? $rli_config['hero'] : $rli_config['non_hero'];
										}
									}
									$raids[1]['bosskills'][$b]['name'] = $bossname;
									$raids[1]['bosskills'][$b]['bonus'] = $boss['bonus'];
									$raids[1]['bosskills'][$b]['time'] = $bosskill['time'];
									break;
								}
							}
						}
						//value
						$max['join'][1] = $i;
						$max['leave'][1] = $i+3600;
						$max['time'] = calculate_time($max, $raid[$key]['end'], $raid[$key]['begin']);
						$max['timedkp'] = calculate_timedkp($raids[$key]['timebonus'], $max['time']);
						$max['bossdkp'] = calculate_bossdkp($raids[$key]['bosskills'], $max);
						$raids[$key]['value'] = $max['timedkp'] + $max['bossdkp'];
						unset($max);
						$key++;
					}
					break;
				}
				case "2": //one raid per bosskill
				{
					$key = 1;
					foreach($raid['bosskills'] as $b => $bosskill)
					{
						//time
						$raids[$key]['begin'] = (isset($raid['bosskills'][$b-1]['time'])) ? $raid['bosskills'][$b-1]['time']+$rli_config['loottime'] +1 : $raid['begin'];
						$raids[$key]['end'] = $bosskill['time']+$rli_config['loottime'];
						//event+note
						if($rli_config['event_boss'] == 1)
						{
							foreach($this->bonus['boss'] as $boss)
							{
								if (in_array($bosskill['name'], $boss['string']))
								{
									$raids[$key]['event'] = trim($boss['note']);
									$raids[$key]['bosskills'][$b]['name'] = $bossname;
									$raids[$key]['bosskills'][$b]['bonus'] = $boss['bonus'];
									$raids[$key]['bosskills'][$b]['time'] = $bosskill['time'];
									break;
								}
							}
							$raids[$key]['note'] = time('h:i:s', $raids[$key]['begin']).' - '.time('h:i:s', $raids[$key]['end']);
						}
						else
						{
	                    	foreach ($this->bonus['zone'] as $zone)
	                    	{
	                        	if (in_array(trim($raid['zone']), $zone['string'])) {
	                            	$raids[$key]['event'] = trim($zone['note']);
									$raids[$key]['timebonus'] = $zone['bonus'];
	                            	break;
	                        	}
	                    	}
							foreach($this->bonus['boss'] as $boss)
							{
								if (in_array($bosskill['name'], $boss['string']))
								{
									$raids[$key]['note'] = trim($boss['note']);
									if($rli_config['dep_match'])
									{
										$raids[$key]['note'] .= ($raid['difficulty'] == '2') ? $rli_config['hero'] : $rli_config['non_hero'];
									}
									$raids[$key]['bosskills'][$b]['name'] = $bossname;
									$raids[$key]['bosskills'][$b]['bonus'] = $boss['bonus'];
									$raids[$key]['bosskills'][$b]['times'] = $bosskill['time'];
									break;
								}
							}
						}
						//value
						$max['join'][1] = $raids[$key]['begin'];
						$max['leave'][1] = $raids[$key]['end'];
						$max['timedkp'] = calculate_timedkp($raid['timebonus'], calculate_time($max, $raid['end'], $raid['begin']));
						$max['bossdkp'] = calculate_bossdkp($raid['bosskills'], $max);
						$raids[$key]['value'] = $max['timedkp'] + $max['bossdkp'];
						unset($max);
						$key++;
					}
					break;
				}
				case "3": //one raid per hour and one per boss
				{
					//time
					$key = 1;
					for($i = $raid['begin']; $i<=($raid['end']); $i+=3600)
					{
						$raids[$key]['begin'] = $i;
						$raids[$key]['end'] = (($i+3600) > $raid['end']) ? $raid['end'] : $i+3600;
                    	//event
	                    foreach ($this->bonus['zone'] as $zone)
	                    {
	                        if (in_array(trim($raid['zone']), $zone['string'])) {
	                            $raids[$key]['event'] = trim($zone['note']);
								$raids[$key]['timebonus'] = $zone['bonus'];
	                            break;
	                        }
	                    }
	                    //note
						$raids[$key]['note'] = date('H:i', $i).' - '.date('H:i', $raids[$key]['end']).' '.$user->lang['rli_clock'];
						//value
						$max['join'][1] = $i;
						$max['leave'][1] = $i+3600;
						$raids[$key]['value'] = calculate_timedkp($raid['timebonus'], calculate_time($max, $raid['end'], $raid['begin']));
						unset($max);
						$key++;
					}
					foreach($raid['bosskills'] as $b => $bosskill)
					{
						//time
						$raids[$key]['begin'] = (isset($raid['bosskills'][$b-1]['time'])) ? $raid['bosskills'][$b-1]['time']+$rli_config['loottime'] +1 :$raid['begin'];
						$raids[$key]['end'] = $bosskill['time']+$rli_config['loottime'];
						//event+note
						if($rli_config['event_boss'] == 1)
						{
							foreach($this->bonus['boss'] as $boss)
							{
								if (in_array($bosskill['name'], $boss['string']))
								{
									$raids[$key]['event'] = trim($boss['note']);
									$raids[$key]['bosskills'][$b]['name'] = $bossname;
									$raids[$key]['bosskills'][$b]['bonus'] = $boss['bonus'];
									$raids[$key]['bosskills'][$b]['time'] = $bosskill['time'];
									break;
								}
							}
							$raids[$key]['note'] = time('H:i:s', $raids[$key]['begin']).' - '.time('H:i:s', $raids[$key]['end']);
						}
						else
						{
	                    	foreach ($this->bonus['zone'] as $zone)
	                    	{
	                        	if (in_array(trim($raid['zone']), $zone['string'])) {
	                            	$raids[$key]['event'] = trim($zone['note']);
	                            	break;
	                        	}
	                    	}
							foreach($this->bonus['boss'] as $boss)
							{
								if (in_array($bosskill['name'], $boss['string']))
								{
									$raids[$key]['note'] = trim($boss['note']);
									if($rli_config['dep_match'])
									{
										$raids[$key]['note'] .= ($raid['difficulty'] == '2') ? $rli_config['hero'] : $rli_config['non_hero'];
									}
									$raids[$key]['bosskills'][$b]['name'] = $bossname;
									$raids[$key]['bosskills'][$b]['bonus'] = $boss['bonus'];
									$raids[$key]['bosskills'][$b]['time'] = $bosskill['time'];
									break;
								}
							}
						}
						//value
						$raids[$key]['value'] = $raid['bosskills'][$b]['bonus'];
						$key++;
					}
					break;
				}
			}//switch
			if($rli_config['attendence_raid'])
			{
				if($rli_config['attendence_begin'] > 0)
				{
					$raids[0]['begin'] = $raids[1]['begin'];
					$raids[0]['end'] = $raids[1]['begin'] + $rli_config['attendence_time'];
					$raids[0]['event'] = $raids[1]['event'];
					$raids[0]['note'] = $user->lang['rli_att']." ".$user->lang['rli_start'];
					$raids[0]['value'] = $rli_config['attendence_begin'];
				}
				if($rli_config['attendence_end'] > 0)
				{
					$raids[$key]['begin'] = $raids[$key-1]['end'] - $rli_config['attendence_time'];
					$raids[$key]['end'] = $raids[$key-1]['end'];
					$raids[$key]['event'] = $raids[$key-1]['event'];
					$raids[$key]['note'] = $user->lang['rli_att']." ".$user->lang['rli_end'];
					$raids[$key]['value'] = $rli_config['attendence_end'];
				}
			}
			else
			{
			  foreach($raids as $k => $r)
			  {
				if($rli_config['attendence_begin'] > 0 OR $rli_config['attendence_end'] > 0)
				{
					$raids[$k]['value'] = $r['value'] + $rli_config['attendence_begin'] + $rli_config['attendence_end'];
				}
			  }
			}
			ksort($raids);
		  }
        }//post or string

        //get events
        $eventqry = "SELECT event_name FROM __events ORDER BY event_name ASC;";
        $eventres = $db->query($eventqry);
        while ($ev = $db->fetch_record($eventres))
        {
          $events[$ev['event_name']] = $ev['event_name'];
        }
        $db->free_result();

		if(isset($_POST['raid_add']))
		{
			for($i=1; $i<=$_POST['raid_add']; $i++)
			{
				$raids[] = '';
			}
		}
		foreach($raids as $ky => $rai)
		{
			if(isset($rai['bosskill_add']))
			{
				for($i=1; $i<=$rai['bosskill_add']; $i++)
				{
					$rai['bosskills'][] = '';
				}
			}
			$bk_string = '';
			$list = array();
			foreach($this->bonus['boss'] as $boss)
			{
				$list[htmlspecialchars($boss['string'][0], ENT_QUOTES)] = htmlentities($boss['note'], ENT_QUOTES).' ('.$boss['bonus'].')';
			}
			foreach($rai['bosskills'] as $xy => $bk)
			{
				$sel = '';
				foreach($this->bonus['boss'] as $boss)
				{
					if(in_array($bk['name'], $boss['string']))
					{
						$sel = htmlspecialchars($boss['string'][0], ENT_QUOTES);
					}
				}
				$bk_string .= $myHtml->DropDown('raids['.$ky.'][bosskills]['.$xy.'][name]', $list, $sel);
				$bk_string .= '&nbsp;&nbsp;&nbsp;&nbsp;'.$user->lang['time'].': <input type="text" name="raids['.$ky.'][bosskills]['.$xy.'][time]" value="'.date('H:i:s', $bk['time']).'" size="9" />';
				$bk_string .= '&nbsp;&nbsp;&nbsp;&nbsp;'.$user->lang['date'].': <input type="text" name="raids['.$ky.'][bosskills]['.$xy.'][date]" value="'.date('d.m.y', $bk['time']).'" size="9" />';
				$bk_string .= '&nbsp;&nbsp;&nbsp;&nbsp;'.$user->lang['value'].': <input type="text" name="raids['.$ky.'][bosskills]['.$xy.'][bonus]" value="'.$bk['bonus'].'" size="5" />';
				$bk_string .= '&nbsp;&nbsp;&nbsp;&nbsp;<img src="'.$eqdkp_root_path.'images/global/delete.png" alt="'.$user->lang['delete'].'"><input type="checkbox" name="raids['.$ky;
				$bk_string .= '][bosskills]['.$xy.'][delete]" value="true" title="'.$user->lang['delete'].'" /><br />';
			}
			if($eqdkp->config['default_game'] == 'WoW')
			{
				if($raid['difficulty'] == '2')
				{
					$rai['event'] .= $rli_config['hero'];
				}
				else
				{
					$rai['event'] .= $rli_config['non_hero'];
				}
			}
			$tpl->assign_block_vars('raids', array(
                'COUNT'     => $ky,
                'START_DATE'=> date('d.m.y', $rai['begin']),
                'START_TIME'=> date('H:i:s', $rai['begin']),
                'END_DATE'	=> date('d.m.y', $rai['end']),
                'END_TIME'	=> date('H:i:s', $rai['end']),
				'BOSSKILLS'	=> $bk_string,
				'EVENT'		=> $myHtml->DropDown('raids['.$ky.'][event]', $events, $rai['event']),
				'TIMEBONUS'	=> $rai['timebonus'],
				'VALUE'		=> $rai['value'],
				'NOTE'		=> $rai['note']
				)
			);
		}
		$tpl->assign_vars(array(
			'DATA' => htmlspecialchars(serialize($data), ENT_QUOTES))
		);
		//language
		$tpl->assign_vars(lang2tpl());

		$eqdkp->set_vars(array(
        	'page_title'        => sprintf($user->lang['admin_title_prefix'], $eqdkp->config['guildtag'], $eqdkp->config['dkp_name']).': Daten prüfen',
            'template_path'     => $pm->get_data('raidlogimport', 'template_path'),
            'template_file'     => 'rli_step2raids.html',
            'display'           => true)
        );
	}

	function process_members()
	{
		global $db, $eqdkp, $user, $tpl, $pm;
		global $myHtml, $rli_config;

		$data = unserialize($_POST['rest']);
		if(isset($_POST['raids']))
		{
			$data['raids'] = parse_post($_POST['raids']);
		}
		elseif(isset($_POST['members']))
		{
			$data = parse_members($_POST['members'], $data);
		}
		if(isset($_POST['members_add']))
		{
			for($i=1; $i<=$_POST['members_add']; $i++)
			{
				$data['members'][] = '';
			}
		}

		foreach($data['members'] as $key => $member)
		{
			//load aliase
			$sql = "SELECT m.member_name FROM __raidlogimport_aliases a, __members m WHERE a.alias_member_id = m.member_id AND a.alias_name = '".$member['name']."';";
			$result = $db->query($sql);
			if($result)
			{
				if($db->num_rows($result) != 0)
				{
					$row = $db->fetch_record($result);
                    $data['members'][$key]['alias'] = $member['name'];
                    $member['alias'] = $member['name'];
                    $data['members'][$key]['name'] = $row['member_name'];
                    $member['name'] = $row['member_name'];
                }
            }
            else
            {
             	echo "SQL-Error: Query:<br />".$sql;
            }
         	//Ursprungsname hinter das Mitglied schreiben
          	$alias = "";
               if(isset($member['alias'])) {
               $alias = "(".$member['alias'].")";
               $alias .= '<input type="hidden" name="members['.$key.'][alias]" value="'.$member['alias'].'" />';
            }
            if($_POST['checkmem'] == $user->lang['rli_checkmem'])
            {
            	$member['raid_list'] = '';
	           	foreach($data['raids'] as $u => $ra)
	           	{
	           		//check events
	           		if(member_in_raid($member, $ra))
	           		{
	           			$member['raid_list'] .= $u.',';
	           		}
		           	//calc dkp
		            $member['time'] = calculate_time($member, $ra['end'], $ra['begin']);
		            $member['timedkp'] += calculate_timedkp($ra['timebonus'], $member['time']);
		            $member['bossdkp'] += calculate_bossdkp($ra['bosskills'], $member);
		            $end = $ra['end'];
		        }
		        $begin = $data['raids'][1]['begin'];
	            $att_dkp = calculate_attendence($member, $rli_config['attendence_begin'], $rli_config['attendence_end'], $rli_config['attendence_time'], $begin, $end);
	            $member['att_dkp_begin'] = $att_dkp['begin'];
	            $member['att_dkp_end'] = $att_dkp['end'];
	        }

           	$tpl->assign_block_vars('player', array(
               	'MITGLIED' => $member['name'],
                'ALIAS'    => $alias,
                'RAID_LIST'=> $member['raid_list'],
                'ZEITDKP'  => $member['timedkp'],
                'BOSSDKP'  => $member['bossdkp'],
                'ATT_BEGIN'=> $member['att_dkp_begin'],
                'ATT_END'  => $member['att_dkp_end'],
                'ZAHL'     => $eqdkp->switch_row_class(),
                'KEY'	   => $key)
           	);
        }//foreach members

		//show raids
		foreach($data['raids'] as $key => $raid)
		{
			$tpl->assign_block_vars('raids', raids2tpl($key, $raid));
		}

		$tpl->assign_vars(array(
			'DATA'			=> htmlspecialchars(serialize($data), ENT_QUOTES),
			'S_ATT_BEGIN'	=> ($rli_config['attendence_begin'] > 0) ? TRUE : FALSE,
			'S_ATT_END'		=> ($rli_config['attendence_end'] > 0) ? TRUE : FALSE,
			'S_CONF_ADJ'	=> ($rli_config['conf_adjustment']) ? FALSE : TRUE)
		);

		//language
		$tpl->assign_vars(lang2tpl());
		$eqdkp->set_vars(array(
        	'page_title'        => sprintf($user->lang['admin_title_prefix'], $eqdkp->config['guildtag'], $eqdkp->config['dkp_name']).': Daten prüfen',
            'template_path'     => $pm->get_data('raidlogimport', 'template_path'),
            'template_file'     => 'rli_step2mems.html',
            'display'           => true)
        );
	}

	function process_items()
	{
		global $db, $eqdkp, $user, $tpl, $pm;
		global $myHtml, $rli_config;

		$data = unserialize($_POST['rest']);
		if(isset($_POST['members']))
		{
			$data = parse_members($_POST['members'], $data);
		}
		elseif(isset($_POST['loots']))
		{
			$data['loots'] = parse_items($_POST['loots'], $data['loots']);
		}
		if(isset($_POST['items_add']))
		{
			for($i=1; $i<=$_POST['items_add']; $i++)
			{
				$data['loots'][]['input'] = TRUE;
			}
		}

		//show raids&members
		foreach($data['raids'] as $key => $raid)
		{
			$tpl->assign_block_vars('raids', raids2tpl($key, $raid));
		}

		$members = array(); //for select in loots
		foreach($data['members'] as $key => $member)
		{
			$tpl->assign_block_vars('player', mems2tpl($key, $member));
            $members['name'][$member['name']] = $member['name'];
            if(isset($member['alias']))
            {
            	$aliase[$member['alias']] = $member['name'];
            }
		}

		//add disenchanted and bank
        $members['name']['disenchanted'] = 'disenchanted';
        $members['name']['bank'] = 'bank';
        foreach ($data['loots'] as $key => $loot)
        {
        	if(isset($aliase[$loot['player']]))
        	{
        		$loot['player'] = $aliase[$loot['player']];
        	}
        	$loot_select = "<select size='1' name='loots[".$key."][raid]'>";
          	foreach($data['raids'] as $i => $ra)
           	{
           		$loot_select .= "<option value='".$i."'";
           		if($ra['begin'] < $loot['time'] AND $ra['end'] > $loot['time'])
           		{
           			$loot_select .= ' selected="selected"';
           		}
           		$loot_select .= ">".$i."</option>";
           	}
			$tpl->assign_block_vars('loots', array(
				'LOOTNAME'  => $loot['name'],
				'LOOTER'	=> $myHtml->DropDown("loots[".$key."][player]", $members['name'], $loot['player'], '', '', true),
				'RAID'		=> $loot_select."</select>",
				'LOOTDKP'	=> $loot['dkp'],
				'KEY'		=> $key,
				'CLASS'		=> $eqdkp->switch_row_class(),
				'INPUT_ITEMNAME' => ($loot['input']) ? TRUE : FALSE)
			);
		}

		$tpl->assign_vars(array(
			'DATA'			=> htmlspecialchars(serialize($data), ENT_QUOTES),
			'S_ATT_BEGIN'	=> ($rli_config['attendence_begin'] > 0) ? TRUE : FALSE,
			'S_ATT_END'		=> ($rli_config['attendence_end'] > 0) ? TRUE : FALSE,
			'S_CONF_ADJ'	=> ($rli_config['conf_adjustment']) ? FALSE : TRUE)
		);

		//language
		$tpl->assign_vars(lang2tpl());
		$eqdkp->set_vars(array(
        	'page_title'        => sprintf($user->lang['admin_title_prefix'], $eqdkp->config['guildtag'], $eqdkp->config['dkp_name']).': Daten prüfen',
            'template_path'     => $pm->get_data('raidlogimport', 'template_path'),
            'template_file'     => 'rli_step2items.html',
            'display'           => true)
        );
	}

	function process_adjustments()
	{
		global $db, $eqdkp, $tpl, $user, $pm;
		global $myHtml, $rli_config;

		$data = unserialize($_POST['rest']);
		if(isset($_POST['loots']))
		{
			$data['loots'] = parse_items($_POST['loots'], $data['loots']);
		}
		elseif(isset($_POST['adjs']))
		{
			$data['adjs'] = parse_adjs($_POST['adjs']);
		}
		if(isset($_POST['adjs_add']))
		{
			for($i=1; $i<=$_POST['adjs_add']; $i++)
			{
				$data['adjs'][] = '';
			}
		}

		//show raids, members & items
		foreach($data['raids'] as $key => $raid)
		{
			$tpl->assign_block_vars('raids', raids2tpl($key, $raid));
		}
		foreach($data['members'] as $key => $member)
		{
			$tpl->assign_block_vars('player', mems2tpl($key, $member));
		}
		foreach($data['loots'] as $loot)
		{
			$tpl->assign_block_vars('loots', items2tpl($loot));
		}

        //get events
        $eventqry = "SELECT event_name FROM __events ORDER BY event_name ASC;";
        $eventres = $db->query($eventqry);
        while ($ev = $db->fetch_record($eventres))
        {
          $events[$ev['event_name']] = $ev['event_name'];
        }
        $db->free_result();
		if(isset($data['adjs']))
		{
			$sql = "SELECT member_name FROM __members ORDER BY member_name ASC;";
			$res = $db->query($sql);
			$members = array();
			while ($row = $db->fetch_record($res))
			{
				$members[$row['member_name']] = $row['member_name'];
			}
			foreach($data['members'] as $member)
			{
				$members_r[$member['name']] = $member['name'];
				if(isset($member['alias']))
				{
					$adj_alias[$member['alias']] = $member['name'];
				}
			}
			$members = array_merge($members_r, $members);
			foreach($data['adjs'] as $a => $adj)
			{
				if(isset($adj_alias[$adj['member']]))
				{
					$adj['member'] = $adj_alias[$adj['member']];
				}
				$ev_sel = (isset($adj['event'])) ? $adj['event'] : '';
				$tpl->assign_block_vars('adjs', array(
					'MEMBER'	=> $myHtml->DropDown('adjs['.$a.'][member]', $members, $adj['member'], '', '', true),
					'EVENT'		=> $myHtml->DropDown('adjs['.$a.'][event]', $events, $ev_sel, '', '', true),
					'NOTE'		=> $adj['reason'],
					'VALUE'		=> $adj['value'],
					'CLASS'		=> $eqdkp->switch_row_class(),
					'KEY'		=> $a)
				);
			}
		}

		$tpl->assign_vars(array(
			'DATA'			=> htmlspecialchars(serialize($data), ENT_QUOTES),
			'S_ATT_BEGIN'	=> ($rli_config['attendence_begin'] > 0) ? TRUE : FALSE,
			'S_ATT_END'		=> ($rli_config['attendence_end'] > 0) ? TRUE : FALSE,
			'S_CONF_ADJ'	=> ($rli_config['conf_adjustment']) ? FALSE : TRUE)
		);

		//language
		$tpl->assign_vars(lang2tpl());
		$eqdkp->set_vars(array(
        	'page_title'        => sprintf($user->lang['admin_title_prefix'], $eqdkp->config['guildtag'], $eqdkp->config['dkp_name']).': Daten prüfen',
            'template_path'     => $pm->get_data('raidlogimport', 'template_path'),
            'template_file'     => 'rli_step2adj.html',
            'display'           => true)
        );
	}

	function insert_log()
	{
		global $db, $eqdkp, $user, $tpl, $pm;
		global $SID, $rli_config, $conf_plus;

		$data = unserialize($_POST['rest']);
		$data['adjs'] = parse_adjs($_POST['adjs']);
		$isok = true;

		$bools = check_data($data);
		if(!isset($bools['false']))
		{
		  $db->query("START TRANSACTION");
		  $newraidid = 0;
		  $newraidid = $db->query_first("SELECT MAX(`raid_id`) FROM __raids;");

		  foreach($data['raids'] as $raid_key => $raid)
		  {
		   if($raid['key'] != '')
		   {
			$newraidid++;
			$data['raids'][$raid_key]['id'] = $newraidid;
			$sql = "INSERT INTO __raids
        		      (`raid_id`, `raid_name`, `raid_date`, `raid_note`, `raid_value`, `raid_added_by`)
        		    VALUES
        		      ('".$newraidid."', '".$raid['event']."', '".$raid['begin']."', '".mysql_real_escape_string($raid['note'])."', '".$raid['value']."', 'Raid-Log-Import (by ".$user->data['username'].")');";
        	if(!$db->query($sql))
        	{
        		echo "raids_table: <br />".$sql."<br />";
        		$isok = false;
        		break;
        	}
           }
		  }

		  if ($isok)
		  {
	        $sql = "SHOW COLUMNS
	                        FROM __items
	                        LIKE 'game_itemid';";
	        $result = $db->query_first($sql);
	        if ($db->num_rows($result) > 0) {
	            $item_gameidExists = TRUE;
	        }
       		$lootdkp = array();
       		if(is_array($data['loots']))
       		{
	          foreach($data['loots'] as $key => $loot)
	          {
	        	$lootdkp[$loot['player']] = $lootdkp[$loot['player']] + $loot['dkp'];
				$sql = "INSERT INTO __items
	                        (`item_name`,
	                         `item_buyer`,
	                         `raid_id`,
	                         `item_value`,
	                         `item_date`,
	                         `item_added_by`,
	                         `item_group_key`";
	            if ($item_gameidExists) {
	                $sql .= ",`item_gameid`";
	            }
	            $sql .= ") VALUES
	                     ('".mysql_real_escape_string($loot['name'])."',
	                      '".mysql_real_escape_string($loot['player'])."',
	                      '".mysql_real_escape_string($data['raids'][$loot['raid']]['id'])."',
	                      '".mysql_real_escape_string($loot['dkp'])."',
	                      '".mysql_real_escape_string($loot['time'])."',
	                      'DKP Import(by ".$user->data['username'].")',
	                      '".mysql_real_escape_string($this->gen_group_key($loot['name'], $loot['time'], $data['raids'][$loot['raid']]['id']))."'";
	            if ($item_gameidExists) {
	                $sql .= ",'".mysql_real_escape_string($loot['id'])."'";
	            }
	            $sql .= ");";
	            if(!$db->query($sql)) {
					echo "items_table: <br />".$sql."<br />";
		   			$isok = false;
					break;
				}
			  }
	        }
          }

		  $adj_dkp = array();
		  if($isok)
		  {
		  	if(is_array($data['adjs']))
		  	{
			  	foreach($data['adjs'] as $adj)
			  	{
					$group_key = $this->gen_group_key($this->time, stripslashes($adj['reason']), $adj['value'], $adj['event']);
					$sql = "INSERT INTO __adjustments
								(`adjustment_value`, `adjustment_date`, `member_name`, `adjustment_reason`, `adjustment_added_by`, `adjustment_group_key`, `raid_name`)
						    VALUES
						    	('".$adj['value']."', '".$data['raids'][1]['begin']."', '".$adj['member']."', '".$adj['reason']."', 'Raid-Log-Import (by ".$user->data['username'].")', '".$group_key."', '".$adj['event']."');";
					$adj_dkp[$adj['member']] += $adj['value'];
					if(!$db->query($sql))
					{
						echo "adjustment_table: <br />".$sql."<br />";
						$isok = false;
						break;
					}
				}
		  	}
		  }

		  if($isok)
		  {
			foreach ($data['members'] as $key => $member)
			{
			  if($member['name'] != '')
			  {
                if($isok)
                {
					//memberexistscheck
					$sql = "SELECT `member_id` FROM __members WHERE `member_name` = '".mysql_real_escape_string($member['name'])."' LIMIT 1;";
					$resul = $db->query($sql);
            		if($db->num_rows($resul) == 0 AND !isset($member['alias']))
            		{
            			$answer = create_member($member, $rli_config['new_member_rank']);
		            		$this->log_insert(array(
		            		'log_type'	 => $answer[1]['header'],
		            		'log_action' => $answer[1])
		            	);
		            	$success[] = $answer[2];
					}
					//raid_attendence
					$member['raids'] = explode(',', $member['raid_list']);
					foreach($data['raids'] as $raid_key => $raid)
					{
						if(in_array($raid_key, $member['raids']) AND $isok)
						{
							$sql = "INSERT INTO __raid_attendees
										(`raid_id`, `member_name`)
								    VALUES
								    	('".$raid['id']."', '".$member['name']."');";
							if(!$db->query($sql))
							{
								echo "raid_attendees_table: <br />".$sql."<br />";
								$isok = false;
								break;
							}
						}
					}
					//dkp
					if(!$conf_plus['pk_multidkp'])
					{
						$dkp = 0;
						if($rli_config['conf_adjustment'])
						{
							foreach($data['raids'] as $raid)
							{
								$dkp = $dkp + $raid['value'];
							}
						}
						else
						{
			                $dkp = $member['timedkp'] + $member['bossdkp'];
							if($rli_config['attendence_begin'] > 0 AND isset($member['att_dkp_begin']))
							{
								$dkp += $member['att_dkp_begin'];
							}
							if($rli_config['attendence_end'] > 0 AND isset($member['att_dkp_end']))
							{
								$dkp += $member['att_dkp_end'];
							}
						}
						$sql = "UPDATE __members SET
									member_earned = member_earned + '".$dkp."',
									member_spent = member_spent + '".$lootdkp[$member['name']]."',
									member_adjustment = member_adjustment + '".$adj_dkp[$member['name']]."'";
						$keys = array_keys($member['raids']);
						krsort($keys);
						if(isset($member['raids']))
						{
							$sql .= ", member_lastraid = '".$data['raids'][$member['raids'][$keys[0]]]['begin']."'";
							if($a = $db->query_first("SELECT member_firstraid FROM ".MEMBERS_TABLE." WHERE member_name = '".$member['name']."';") == 0)
							{
								$sql .= ", member_firstraid = '".$data['raids'][$member['raids'][$keys[0]]]['begin']."'";
							}
						}
						$sql .= " WHERE
						   			member_name = '".$member['name']."' LIMIT 1;";
						if(!$db->query($sql))
						{
							echo "members_table: <br />".$sql."<br />";
							$isok = false;
							break;
						}
					}
				}
				else
				{
					break;
				}
			  }
			}
		  }

		  if ($isok)
		  {
			$db->query("COMMIT;");
			$message = $user->lang['bz_save_suc'];

			//logging
			//raids
            $log_actions['btdkp'] = array(
                'header'  => '{L_ACTION_RAIDLOGIMPORT_DKP}'
            );
            $num = count($data['members'])-1;
            $member_str = $data['members'][0]['name'];
            for ( $i=1; $i<=$num; $i++ )
            {
                $member_str .= ", ".$data['members'][$i]['name'];
            }
			foreach($data['raids'] as $key => $raid)
			{
				$log_actions[] = array(
					'header'		=> '{L_ACTION_RAID_ADDED}',
					'id'			=> $raid['id'],
					'{L_EVENT}' 	=> $raid['event'],
					'{L_ATTENDEES}' => $member_str,
					'{L_NOTE}'		=> $raid['note'],
					'{L_VALUE}'		=> $raid['value'],
					'{L_ADDED_BY}'	=> 'Raid-Log-Import (by '.$user->data['username'].')'
				);
                $log_actions['btdkp']['Raid('.$key.')'] = sprintf($user->lang['rli_raid_to'], $raid['event'], date($user->style['date_notime_short'], $raid['begin']));
                $log_actions['btdkp']['raid_id('.$key.')'] = $raid['id'];
			}

            //boss and time dkp
            if(!$rli_config['conf_adjustment'])
            {
	            foreach ($data['members'] as $member)
	            {
	                //Ursprungsname hinter das Mitglied schreiben
	                $alias = "";
	                if(isset($member['alias'])) {
	                    $alias = "(".$member['alias'].")";
	                }
	                $att_dkp = 0;
	                $att_dkp = $member['att_dkp_begin'] + $member['att_dkp_end'];
	                $log_actions['btdkp'][$member['name'].$alias] = $user->lang['rli_t_dkp'].": ".$member['timedkp'].", ".$user->lang['rli_b_dkp'];
	                $log_actions['btdkp'][$member['name'].$alias] .= ": ".$member['bossdkp'].", ".$user->lang['rli_att']." :".$att_dkp;
	            }
			}

            //items
            if(is_array($data['loots']))
            {
              	foreach ($data['loots'] as $loot)
              	{
	            	$log_actions[] = array(
    	        		'header' 		=> '{L_ACTION_ITEM_ADDED}',
    	        		'{L_NAME}'		=> $loot['name'],
            			'{L_BUYERS}'	=> $loot['player'],
            			'{L_RAID_ID}'	=> $newraidid,
            			'{L_VALUE}'		=> $loot['dkp'],
	            		'{L_ADDED_BY}'	=> 'Raid-Log-Import (by '.$user->data['username'].')'
    	        	);
              	}
            }

            //adjs
            if(is_array($data['adjs']))
            {
              	foreach($data['adjs'] as $adj)
              	{
              		$log_actions[] = array(
            			'header'			=> '{L_ACTION_INDIVADJ_ADDED}',
            			'{L_ADJUSTMENT}'	=> $adj['value'],
            			'{L_REASON}'		=> $adj['reason'],
            			'{L_MEMBER}'		=> $adj['member'],
            			'{L_EVENT}'			=> $adj['event'],
            			'{L_ADDED_BY}'		=> 'Raid-Log-Import (by '.$user->data['username'].')'
            		);
              	}
            }
            foreach($log_actions as $log_action)
            {
            	$this->log_insert(array(
            		'log_type'	 => $log_action['header'],
            		'log_action' => $log_action)
            	);
            }
		  }
		  else
		  {
			$db->query("ROLLBACK;");
			$message = $user->lang['rli_error'];
		  }

		  $success[] = $message;
		  foreach($success as $answer)
		  {
			$tpl->assign_block_vars('sucs', array(
				'PART1'	=> $answer,
				'CLASS'	=> $eqdkp->switch_row_class())
			);
		  }
          $tpl->assign_vars(array(
            'L_SUCCESS' => $user->lang['rli_success'],
            'L_LINKS'	=> $user->lang['links'])
          );

		  $eqdkp->set_vars(array(
            'page_title'        => sprintf($user->lang['admin_title_prefix'], $eqdkp->config['guildtag'], $eqdkp->config['dkp_name']).': '.$user->lang['rli_imp_suc'],
            'template_path'     => $pm->get_data('raidlogimport', 'template_path'),
            'template_file'     => 'success.html',
            'display'           => true,
            )
          );
        }
        else
        {
          unset($_POST);
          $check = $user->lang['rli_missing_values'].'<br />';
          foreach($bools['false'] as $loc => $la)
          {
          	if($la == 'miss')
          	{
          		$check .= $user->lang['rli_'.$loc.'_needed'];
          	}
          	$check .= '<input type="submit" name="check'.$loc.'" value="'.$user->lang['rli_check'.$loc].'" class="mainoption" /><br />';
          }
		  $tpl->assign_vars(array(
		  	'L_NO_IMP_SUC'	=> $user->lang['rli_imp_no_suc'],
		  	'CHECK'			=> $check,
		  	'DATA'			=> htmlspecialchars(serialize($data), ENT_QUOTES))
		  );
    	  $eqdkp->set_vars(array(
    	  	'page_title'		=> sprintf($user->lang['admin_title_prefix'], $eqdkp->config['guildtag'], $eqdkp->config['dkp_name']).': '.$user->lang['rli_imp_no_suc'],
    	  	'template_path'		=> $pm->get_data('raidlogimport', 'template_path'),
    	  	'template_file'		=> 'check_input.html',
    	  	'display'			=> true,
    	    )
    	  );
    	}
	}

	function display_form()
    {
        global $db, $eqdkp, $user, $tpl, $pm;
        global $SID;

        $tpl->assign_vars(array(
            'F_PARSE_LOG'    => 'dkp.php' . $SID,
            'L_INSERT'		 => $user->lang['rli_dkp_insert'],
            'L_SEND'		 => $user->lang['rli_send'],
            'S_STEP1'        => true)
        );

        $eqdkp->set_vars(array(
            'page_title'        => sprintf($user->lang['admin_title_prefix'], $eqdkp->config['guildtag'], $eqdkp->config['dkp_name']).': '."DKP String",
            'template_path'     => $pm->get_data('raidlogimport', 'template_path'),
            'template_file'     => 'rli_step1.html',
            'display'           => true,
            )
        );
    }
}

$raidlogimport = new raidlogimport;
$raidlogimport->process();
?>
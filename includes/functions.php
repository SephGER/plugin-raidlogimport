<?php

/**
 * @original author kokson
 * @copyright 2007
 *
 * @further development by hoofy
 * @copyright 2008
 */

if(!defined('EQDKP_INC'))
{
	header('HTTP/1.0 Not Found');
	exit;
}
function stripslashes_array($array) {
    return is_array($array) ? array_map('stripslashes_array', $array) : stripslashes($array);
}

function calculate_time($player, $endraid, $raidstart) {
	$time=0;
	asort($player['join']);
	asort($player['leave']);

	$join  = count($player['join']);
	$leave = count($player['leave']);

    if ($join == $leave) {
		for ($i = 1; $i <= $join; $i++) {
			if ($player['join'][$i] < $raidstart)
			{
				$jointime = $raidstart;
			}
			elseif($player['join'][$i] > $endraid)
			{
				$jointime = false;
			}
			else
			{
				$jointime = $player['join'][$i];
			}
			if ($player['leave'][$i] > $endraid)
			{
			   $leavetime = $endraid;
			}
			elseif($player['leave'][$i] < $raidstart)
			{
				$leavetime = false;
			}
			else
			{
			   $leavetime = $player['leave'][$i];
			}
			if($leavetime AND $jointime)
			{
				$time = $time + ($leavetime - $jointime);
			}
	    }
    }
    else
    {
		echo "Error! Mismatch of Join and Leave";
	}
	$timearray = format_duration($time);
	return $timearray;
}

function calculate_timedkp($dkpperhour, $timearray) {
	$timedkp = 0;
	$hours   = $timearray['hours'];
	$minutes = $timearray['minutes'];

	$timedkp = $hours * $dkpperhour;
	if($minutes > 15 AND $minutes < 45)
	{
    	$timedkp = $timedkp + ($dkpperhour/2);
	}
	elseif($minutes >= 45)
	{
		$timedkp = $timedkp + $dkpperhour;
	}
	return $timedkp;
}

function calculate_bossdkp($kills, $player) {
	$bossdkp = 0;
	foreach($kills as $kill)
	{
		$count = count($player['join']);
		for($i = 1;$i <= $count;$i++) {
			$join  = $player['join'][$i];
			$leave = $player['leave'][$i];
			if ($join < $kill['time'] and $leave > $kill['time']) {
				$bossdkp = $bossdkp + $kill['bonus'];
			}
		}
	}
	return $bossdkp;
}

function get_lootdkp($player, $loots) {
	$lootdkp = 0;
	foreach ($loots as $loot) {
		if (trim($loot['player']) == trim($player['name'])) {
			$lootdkp = $lootdkp + $loot['dkp'];
		}
	}
	return $lootdkp;
}


function format_duration($seconds) {

    $periods = array(
        'centuries' => 3155692600,
        'decades' => 315569260,
        'years' => 31556926,
        'months' => 2629743,
        'weeks' => 604800,
        'days' => 86400,
        'hours' => 3600,
        'minutes' => 60,
        'seconds' => 1
    );

    $durations = array();
    $durations['hours'] = 0;
    $durations['minutes'] = 0;


    foreach ($periods as $period => $seconds_in_period) {
        if ($seconds >= $seconds_in_period) {
            $durations[$period] = floor($seconds / $seconds_in_period);
            $seconds -= $durations[$period] * $seconds_in_period;
        }
    }

    return $durations;

}


function fktMultiArraySearch($arrInArray,$varSearchValue){

    foreach ($arrInArray as $key => $row){
        $ergebnis = array_search($varSearchValue, $row);

        if ($ergebnis){
            $arrReturnValue[0] = $key;
            $arrReturnValue[1] = $ergebnis;
            return $arrReturnValue;
        }
    }
}

/**
 * @author hoofy
 * @copyright 2008
 */

function create_member($member, $rank)
{
	global $db, $user, $eqdkp, $tpl;
	$sql = "SELECT config_value FROM ".CONFIG_TABLE." WHERE config_name = 'game_language';";
	$gl = $db->query_first($sql);
	if($gl == "en")
	{
		switch($member['class'])
		{
			case "DRUID": 	$member['class'] = "Druid";
				break;
			case "HUNTER":	$member['class'] = "Hunter";
				break;
			case "MAGE":	$member['class'] = "Mage";
				break;
			case "PALADIN":	$member['class'] = "Paladin";
				break;
			case "PRIEST":	$member['class'] = "Priest";
				break;
			case "ROGUE":	$member['class'] = "Rogue";
				break;
			case "SHAMAN":	$member['class'] = "Shaman";
				break;
			case "WARLOCK":	$member['class'] = "Warlock";
				break;
			case "WARRIOR":	$member['class'] = "Warrior";
				break;
			case "DEATHKNIGHT": $member['class'] = "Death Knight";
				break;
			default: 		$member['class'] = "Unknown";
				break;
		}
		switch($member['race'])
		{
			case "Scourge": 	$member['race'] = "Undead";
				break;
			case "BloodElf":	$member['race'] = "Blood Elf";
				break;
			case "NightElf":	$member['race'] = "Night Elf";
		}
	}
	elseif($gl == "de")
	{
		switch($member['class'])
		{
			case "DRUID": 	$member['class'] = "Druide";
				break;
			case "HUNTER":	$member['class'] = "Jäger";
				break;
			case "MAGE":	$member['class'] = "Magier";
				break;
			case "PALADIN":	$member['class'] = "Paladin";
				break;
			case "PRIEST":	$member['class'] = "Priester";
				break;
			case "ROGUE":	$member['class'] = "Schurke";
				break;
			case "SHAMAN":	$member['class'] = "Schamane";
				break;
			case "WARLOCK":	$member['class'] = "Hexenmeister";
				break;
			case "WARRIOR":	$member['class'] = "Krieger";
				break;
			case "DEATHKNIGHT": $member['class'] = "Todesritter";
				break;
			default: 		$member['class'] = "Unknown";
				break;
		}
		switch($member['race'])
		{
			case "BloodElf":	$member['race'] = "Blutelf";
				break;
			case "Draenai":		$member['race'] = "Draenai";
				break;
			case "Gnome":		$member['race'] = "Gnom";
				break;
			case "Human":		$member['race'] = "Mensch";
				break;
			case "NightElf":	$member['race'] = "Nachtelf";
				break;
			case "Orc":			$member['race'] = "Ork";
				break;
			case "Tauren":		$member['race'] = "Taure";
				break;
			case "Scourge":		$member['race'] = "Untoter";
				break;
			case "Dwarf":		$member['race'] = "Zwerg";
				break;
		}
	}
	//get member_id, race_id and rank_id
	$sql = "SELECT race_id FROM __races WHERE race_name = '".$member['race']."';";
	$r_i = $db->query_first($sql);
	$sql = "SELECT class_id FROM __classes WHERE class_name = '".$member['class']."';";
	$c_i = $db->query_first($sql);

	//insert member into database, create log
	$success = "";
	$sql = "INSERT INTO __members
				(member_name, member_level, member_race_id, member_class_id, member_rank_id)
		   VALUES
		   		('".$member['name']."', '".$member['level']."', '".$r_i."', '".$c_i."', '".$rank."');";
	$result = $db->query($sql);
	if(!$result)
	{
		$success = $user->lang['member']." ".$member['name']." ".$user->lang['rli_no_mem_create'];
	}
	else
	{
		$success = $user->lang['member']." ".$member['name']." ".$user->lang['rli_mem_auto'];
		$log_action = array(
            'header'         => '{L_ACTION_MEMBER_ADDED}',
            '{L_NAME}'       => $member['name'],
            '{L_EARNED}'     => '0',
            '{L_SPENT}'      => '0',
            '{L_ADJUSTMENT}' => '0',
            '{L_LEVEL}'      => $member['level'],
            '{L_RACE}'       => $member['race'],
            '{L_CLASS}'      => $member['class']
        );
	}
	$retu[1] = $log_action;
	$retu[2] = $success;
	return $retu;
}

function rli_get_config()
{
	global $db;
	$data = array();
	$sql = "SELECT * FROM __raidlogimport_config;";
	$result = $db->query($sql);
	while ( $row = $db->fetch_record($result) )
	{
		$data[$row['config_name']] = $row['config_value'];
	}
	$db->free_result();
	return $data;
}

function check_data($data)
{
	$bools = array();
	if($data['raids'])
	{
		foreach($data['raids'] as $raid)
		{
			if($raid['key'] != '')
			{
				if(!($raid['event'] AND $raid['value'] AND $raid['note'] AND $raid['begin'] AND $raid['end']))
				{
					$bools['false']['raid'] = FALSE;
				}
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
			if($loot['key'] != '')
			{
				if(!($loot['name'] AND $loot['player'] AND $loot['raid'] AND $loot['dkp'] != ''))
				{
					$bools['false']['item'] = FALSE;
				}
			}
		}
	}
	if(isset($data['adjs']))
	{
		foreach($data['adjs'] as $adj)
		{
			if(isset($adj['do']))
			{
				if(!($adj['member'] AND $adj['event'] AND $adj['reason'] AND $adj['value']))
				{
					$bools['false']['adj'] = FALSE;
				}
			}
		}
	}
	return $bools;
}

function check_ctrt_format($xml)
{
	if(isset($xml->start, $xml->end, $xml->zone, $xml->BossKills, $xml->Loot, $xml->PlayerInfos, $xml->Join, $xml->Leave))
	{
		foreach($xml->BossKills->children() as $bosskill)
		{
		  if($bosskill)
		  {
			if(!isset($bosskill->name, $bosskill->time))
			{
				var_dump($bosskill);
				return false;
			}
		  }
		}
		foreach($xml->Loot->children() as $loot)
		{
		  if($loot)
		  {
			if(!isset($loot->ItemName, $loot->Player, $loot->Time))
			{
				var_dump($loot);
				return false;
			}
		  }
		}
		foreach($xml->PlayerInfos->children() as $mem)
		{
			if(!isset($mem->name))
			{
				return false;
			}
		}
		foreach($xml->Join->children() as $join)
		{
			if(!isset($join->player, $join->time))
			{
				return false;
			}
		}
		foreach($xml->Leave->children() as $leave)
		{
			if(!isset($leave->player, $leave->time))
			{
				return false;
			}
		}
		return true;
	}
	else
	{
		return false;
	}
}

function parse_ctrt_string($xml)
{
	global $rli_config;

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
			$note = $loot->Note;
			$dpos = strpos($note,' DKP');
			if (!$dpos)
			{
				$raid['loots'][$i]['dkp'] = 0;
			}
			else
			{
				$sub  = substr($note,0,$dpos);
				$spos = strrpos($sub," ");
				$dkp  = substr($sub,$spos+1);
				$raid['loots'][$i]['dkp'] = $dkp;
			}
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
			list($reason, $value) = explode($rli_config['adj_parse'], utf8_decode($member->note));
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
	global $rli_config, $user;

	if(function_exists('parse_'.$rli_config['parser'].'_string'))
	{
		if(call_user_func('check_'.$rli_config['parser'].'_format', $xml))
		{
			$raid = call_user_func('parse_'.$rli_config['parser'].'_string', $xml);
		}
		else
		{
			message_die($user->lang['wrong_format'].' '.$user->lang[$rli_config['parser'].'_format']);
		}
	}
	else
	{
		message_die($user->lang['no_parser']);
	}
	return $raid;
}

function member_in_raid($member, $raid)
{
	global $user;
	$raid['time'] = $raid['end'] - $raid['begin'];
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
    for($i=0; $i<count($times)-1; $i++)
    {
    	$k = $i+1;
    	if(key($times[$i]) == key($times[$k]))
    	{
    		message_die($user->lang['xml_error']);
    	}
    	else
    	{
    		if(key($times[$i]) == 'join')
    		{
    			if($raid['begin'] < $times[$k]['leave'] AND $times[$i]['join'] < $raid['end'])
    			{
    				$tim = $tim + $times[$k]['leave']-$times[$i]['join'];
    				if($tim > $raid['time']/2)
    				{
    					$retur = TRUE;
    					break;
    				}
    				else
    				{
    					$retur = FALSE;
    				}
    			}
    			else
    			{
    				$retur = FALSE;
    			}
    		}
    	}
    }
	return $retur;
}

function calculate_attendence($member, $begin, $end, $time, $beg, $en)
{
	$dkp['begin'] = 0;
	$dkp['end'] = 0;
	foreach($member['join'] as $jt)
	{
		if(($beg + $time) > $jt)
		{
			$dkp['begin'] = $begin;
			break;
		}
	}
	foreach($member['leave'] as $lt)
	{
		if(($en - $time) < $lt)
		{
			$dkp['end'] = $end;
			break;
		}
	}
	return $dkp;
}

function lang2tpl()
{
	global $tpl, $user;
	$la_ar = array(
		'L_ADJ_ADD'		=> $user->lang['rli_add_adj'],
		'L_ADJS_ADD'	=> $user->lang['rli_add_adjs'],
		'L_ADJS'		=> $user->lang['rli_adjs'],
        'L_ATT'         => $user->lang['rli_att'],
        'L_B_DKP'       => $user->lang['rli_b_dkp'],
        'L_BACK2ITEM'	=> $user->lang['rli_back2item'],
        'L_BACK2MEM'    => $user->lang['rli_back2mem'],
        'L_BACK2RAID'   => $user->lang['rli_back2raid'],
        'L_BK_ADD'		=> $user->lang['rli_add_bk'],
        'L_BKS_ADD'		=> $user->lang['rli_add_bks'],
        'L_BOSSKILLS'   => $user->lang['rli_bosskills'],
        'L_CHECKADJS'	=> $user->lang['rli_checkadj'],
        'L_CHECKITEMS'  => $user->lang['rli_checkitem'],
        'L_CHECKMEM'    => $user->lang['rli_checkmem'],
        'L_CHECK_RAIDVAL' => $user->lang['check_raidval'],
        'L_COST'		=> $user->lang['rli_cost'],
        'L_DELETE'		=> $user->lang['delete'],
        'L_END'         => $user->lang['rli_end'],
        'L_EVENT'       => $user->lang['event'],
        'L_INSERT'		=> $user->lang['rli_insert'],
        'L_ITEM'		=> $user->lang['item'],
        'L_ITEM_ADD'	=> $user->lang['rli_add_item'],
        'L_ITEM_ID'		=> $user->lang['rli_item_id'],
        'L_ITEMS_ADD'	=> $user->lang['rli_add_items'],
        'L_LOOTER'		=> $user->lang['rli_looter'],
        'L_MEM_ADD'     => $user->lang['rli_add_mem'],
        'L_MEMS_ADD'	=> $user->lang['rli_add_mems'],
        'L_MEMBER'      => $user->lang['member'],
        'L_MEMBERS'     => $user->lang['members'],
        'L_NAME'		=> $user->lang['name'],
        'L_NOTE'        => $user->lang['note'],
        'L_PROCESS'		=> $user->lang['rli_process'],
        'L_RAID'        => $user->lang['raid'],
        'L_RAID_ADD'    => $user->lang['rli_add_raid'],
        'L_RAIDS_ADD'	=> $user->lang['rli_add_raids'],
        'L_RAIDS'       => $user->lang['raids'],
		'L_START'		=> $user->lang['rli_start'],
		'L_T_DKP'		=> $user->lang['rli_t_dkp'],
		'L_TIME'		=> $user->lang['time'],
		'L_TRANSLATE_ITEMS' => $user->lang['translate_items'],
		'L_TRANSLATE_ITEMS_TIP' => $user->lang['translate_items_tip'],
		'L_UPD'			=> $user->lang['update'],
        'L_VALUE'       => $user->lang['value']
	);
	return $la_ar;
}

function raids2tpl($key, $raid)
{
	$bosskills = '';
	$l = 2;
	foreach($raid['bosskills'] as $bk)
	{
		$bosskills .= '<tr class="row'.$l.'"><td>'.$bk['name'].'</td><td colspan="2">'.date('H:i:s',$bk['time']).'</td><td>'.$bk['bonus'].'</td></tr>';
		if($l != 1) {$l--;} else {$l++;}
	}
	return array(
		'COUNT'			=> $key,
		'START_DATE'	=> date('d.m.y', $raid['begin']),
		'START_TIME'	=> date('H:i:s', $raid['begin']),
		'END_DATE'		=> date('d.m.y', $raid['end']),
		'END_TIME'		=> date('H:i:s', $raid['end']),
		'BOSSKILLS'		=> $bosskills,
		'EVENT'			=> $raid['event'],
		'VALUE'			=> $raid['value'],
		'NOTE'			=> $raid['note']
	);
}

function mems2tpl($key, $member)
{
    global $eqdkp;
	if(isset($member['alias']))
	{
		$member['alias'] = '('.$member['alias'].')';
	}
	return array(
       	'MITGLIED' => $member['name'],
        'ALIAS'    => $member['alias'],
        'RAID_LIST'=> $member['raid_list'],
        'ATT_BEGIN'=> $member['att_dkp_begin'],
        'ATT_END'  => $member['att_dkp_end'],
        'ZAHL'     => $eqdkp->switch_row_class(),
        'KEY'	   => $key
   	);
}

function items2tpl($item)
{
	global $eqdkp;
	return array(
		'LOOTNAME'	=> $item['name'],
		'LOOTER'	=> $item['player'],
		'RAID'		=> $item['raid'],
		'LOOTDKP'	=> $item['dkp'],
		'ITEMID'	=> $item['id'],
		'CLASS'		=> $eqdkp->switch_row_class()
	);
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
      	$raids[$key]['key'] = $raid['key'];
      	$raids[$key]['end'] = mktime($hour, $min, $sec, $month, $day, $year);
      	$raids[$key]['note'] = $raid['note'];
      	$raids[$key]['value'] = $raid['value'];
      	$raids[$key]['event'] = $raid['event'];
      	$raids[$key]['bosskill_add'] = $raid['bosskill_add'];
      	$bosskills = array();
      	foreach($raid['bosskills'] as $u => $bk)
      	{
      		if(!$bk['delete'])
      		{
    	  		list($hour, $min, $sec) = explode(':', $bk['time']);
	      		list($day, $month, $year) = explode('.', $bk['date']);
      			$bosskills[$u]['time'] = mktime($hour, $min, $sec, $month, $day, $year);
      			$bosskills[$u]['bonus'] = $bk['bonus'];
      			$bosskills[$u]['name'] = $bk['name'];
      		}
      	}
      	$raids[$key]['bosskills'] = $bosskills;
      	$raids[$key]['timebonus'] = $raid['timebonus'];
      }
	}
	$data['raids'] = $raids;
	return $data;
}

function parse_members($post, $data)
{
	global $rli_config, $user;
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
				$members[$key]['raid_list'] = $mem['raid_list'];
				$members[$key]['att_dkp_begin'] = $mem['att_begin'];
				$members[$key]['att_dkp_end'] = $mem['att_end'];
				if(isset($mem['alias']))
				{
	                $members[$key]['alias'] = $mem['alias'];
	            }
				if($mem['raid_list'] != '')
				{
					$raids = explode(',', $mem['raid_list']);
					$dkp = 0;
					foreach($raids as $raid_id)
					{
						if($raid_id)
						{
							$raid = $data['raids'][$raid_id];
							if($rli_config['use_timedkp'])
							{
								$time = calculate_time($member, $raid['end'], $raid['begin']);
								$dkp = calculate_timedkp($raid['timebonus'], $time);
							}
							if($rli_config['use_bossdkp'])
							{
								$dkp = $dkp + calculate_bossdkp($raid['bosskills'], $member);
							}
                            $dkp = $dkp + $mem['att_begin'] + $mem['att_end'];
							if($dkp <  $raid['value'])
							{	//add an adjustment
								$dkp -= $raid['value'];
								$data['adjs'][$i]['member'] = $member['name'];
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

	$data['members'] = $members;
	return $data;
}

function parse_items($post, $data)
{
	$loot_sum = 0;
	foreach($post as $k => $loot)
	{
    	foreach($data['loots'] as $key => $item)
    	{
			if($k == $key)
			{
			  if(!$loot['delete'])
			  {
				$tdata[$key] = $loot;
				$tdata[$key]['time'] = $item['time'];
			  }
			}
		}
	}
	$data['loots'] = "";
	$data['loots'] = $tdata;
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
		}
	}
	$data['adjs'] = $adjs;
	return $data;
}

function parse_post($post, $data)
{
	$data = unserialize($_POST['rest']);
	if(isset($post['adjs']))
	{
		return parse_adjs($post['adjs'], $data);
	}
	if(isset($post['loots']))
	{
		return parse_items($post['loots'], $data);
	}
	if(isset($post['members']))
	{
		return parse_members($post['members'], $data);
	}
	if(isset($post['raids']))
	{
		return parse_raids($post['raids'], $data);
	}
	return $data;
}

?>
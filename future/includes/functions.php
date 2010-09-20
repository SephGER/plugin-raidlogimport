<?php
 /*
 * Project:     EQdkp-Plus Raidlogimport
 * License:     Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
 * Link:		http://creativecommons.org/licenses/by-nc-sa/3.0/
 * -----------------------------------------------------------------------
 * Began:       2008
 * Date:        $Date: 2009-06-09 17:20:27 +0200 (Di, 09 Jun 2009) $
 * -----------------------------------------------------------------------
 * @author      $Author: hoofy_leon $
 * @copyright   2008-2009 hoofy_leon
 * @link        http://eqdkp-plus.com
 * @package     raidlogimport
 * @version     $Rev: 5040 $
 *
 * $Id: functions.php 5040 2009-06-09 15:20:27Z hoofy_leon $
 */

if(!defined('EQDKP_INC'))
{
	header('HTTP/1.0 Not Found');
	exit;
}

if(!function_exists('floatvalue'))
{
    function floatvalue($value) {
        return floatval(preg_replace('#^([-]*[0-9\.,]+?)((\.|,)([0-9-]+))*$#e', "str_replace(array('.', ','), '', '\\1') . '.\\4'", $value));
    }
}

function __rli_autoload($name)
{
	global $eqdkp_root_path;
	if(file_exists($eqdkp_root_path.'plugins/raidlogimport/includes/'.$name.'.class.php'))
	{
		require_once($eqdkp_root_path.'plugins/raidlogimport/includes/'.$name.'.class.php');
	}
}
spl_autoload_register('__rli_autoload');

function stripslashes_array($array) {
    return is_array($array) ? array_map('stripslashes_array', $array) : stripslashes($array);
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

function deep_in_array($search, $array) {
	foreach($array as $value) {
		if(!is_array($value)) {
			if($search === $value) return true;
		} else {
			if(deep_in_array($search, $value)) return true;
		}
	}
	return false;
}

function create_member($member, $rank)
{
	global $db, $user, $core, $tpl, $rli, $raidlogimport, $pconvertion;
	if(strtolower($core->config['default_game']) == 'wow')
	{
	  if($core->config['game_language'] == "en")
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
	  elseif($core->config['game_language'] == "de")
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
	}
	else
	{
        if($core->config['game_language'] != $rli->data['log_lang'])
        {
			if($core->config['game_language'] == 'de')
			{
				$tolang = 'german';
			}
			else
			{
				$tolang = '';
			}
			if($rli->data['log_lang'] != 'en')
			{
				$member['class'] = $pconvertion->classname($member['class']);
			}
			$member['class'] = $pconvertion->classname($member['class'], $tolang);
		}
	}
	//get member_id, race_id and rank_id
	$sql = "SELECT race_id FROM __races WHERE race_name = '".$member['race']."';";
	$r_i = $db->query_first($sql);
	$sql = "SELECT class_id FROM __classes WHERE class_name = '".$member['class']."';";
	$c_i = $db->query_first($sql);

	//insert member into database, create log
	$sql = "INSERT INTO __members
				(member_name, member_level, member_race_id, member_class_id, member_rank_id, member_adjustment)
		   VALUES
		   		('".$member['name']."', '".$member['level']."', '".$r_i."', '".$c_i."', '".$rank."', '".$rli->config['member_start']."');";
	$result = $db->query($sql);
	if(!$result)
	{
		$retu[2] = $user->lang['member'].' '.$member['name'].' '.$user->lang['rli_no_mem_create'];
		$retu[1] = FALSE;
	}
	else
	{
		if($rli->config['member_start'])
		{
          $group_key = $raidlogimport->gen_group_key(time(), stripslashes($user->lang['member_start_name']), $rli->config['member_start'], $rli->config['member_start_event']);
		  $sql = "INSERT INTO __adjustments
			       (adjustment_value,
			        adjustment_date,
			        adjustment_reason,
			        adjustment_added_by,
			        adjustment_group_key,
			        member_name,
			        raid_name)
				  VALUES
				   ('".$rli->config['member_start']."',
				    '".time()."',
				    '".$user->lang['member_start_name']."',
				    'RaidLogImport (by ".$user->data['username'].")',
				    '".$group_key."',
				    '".$member['name']."',
				    '".$rli->config['member_start_event']."');";
		  if($db->query($sql))
		  {
        	$log_action = array(
            	'header'         => '{L_ACTION_INDIVADJ_ADDED}',
            	'{L_ADJUSTMENT}' => $rli->config['member_start'],
            	'{L_REASON}'     => stripslashes($rli->config['member_start_name']),
            	'{L_MEMBER}'	 => $member['name'],
				'{L_EVENT}'      => $rli->config['member_start_event'],
            	'{L_ADDED_BY}'   => $raidlogimport->admin_user
            );
            $raidlogimport->log_insert(array(
            	'log_type'	 => $log_action['header'],
            	'log_action' => $log_action)
            );
          }
          else
          {
          	$adj_null = true;
          }
        }
		$retu[2] = $user->lang['member'].' '.$member['name'].' '.$user->lang['rli_mem_auto'];
		$log_action = array(
            'header'         => '{L_ACTION_MEMBER_ADDED}',
            '{L_NAME}'       => $member['name'],
            '{L_EARNED}'     => '0',
            '{L_SPENT}'      => '0',
            '{L_ADJUSTMENT}' => ($adj_null) ? 0 : $rli->config['member_start'],
            '{L_LEVEL}'      => $member['level'],
            '{L_RACE}'       => $member['race'],
            '{L_CLASS}'      => $member['class']
        );
    	$retu[1] = $log_action;
	}
	return $retu;
}

function lang2tpl()
{
	global $tpl, $user, $rli, $core;
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
        'L_DATE'		=> $user->lang['date'],
        'L_DELETE'		=> $user->lang['delete'],
        'L_DIFFICULTY' 	=> ($core->config['default_game'] == 'WoW') ? $user->lang['difficulty'] : false,
        'L_END'         => $user->lang['rli_end'],
        'L_EVENT'       => $user->lang['event'],
        'L_GO_ON'		=> $user->lang['rli_go_on'],
        'L_HEROIC'		=> $user->lang['heroic'],
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
        'L_NONHEROIC'	=> $user->lang['non_heroic'],
        'L_NOTE'        => $user->lang['note'],
        'L_PROCESS'		=> $user->lang['rli_process'],
        'L_RAID'        => $user->lang['raid'],
        'L_RAID_ADD'    => $user->lang['rli_add_raid'],
        'L_RAIDS_ADD'	=> $user->lang['rli_add_raids'],
        'L_RAIDS'       => $user->lang['raids'],
        'L_RECALC_RAID'	=> $user->lang['rli_calc_note_value'],
		'L_START'		=> $user->lang['rli_start'],
		'L_T_DKP'		=> $user->lang['rli_t_dkp'],
		'L_TIME'		=> $user->lang['time'],
		'L_TRANSLATE_ITEMS' => ($rli->add_data['log_lang'] == $rli->config('item_save_lang')) ? $user->lang['get_itemid'] : $user->lang['translate_items'],
		'L_TRANSLATE_ITEMS_TIP' => $user->lang['translate_items_tip'],
		'L_UPD'			=> $user->lang['update'],
        'L_VALUE'       => $user->lang['value']
	);
	return $la_ar;
}


function mems2tpl($key, $member, $data)
{
    global $core, $user;
    $rali = array();
    if(is_array($member['raid_list']))
    {
      foreach($member['raid_list'] as $rakey)
      {
    	$rali[] = $data['raids'][$rakey]['note'];
      }
    }
	if(isset($member['alias']))
	{
		$member['alias'] = '('.$member['alias'].')';
	}
	return array(
       	'MITGLIED' => (($key < 9) ? '&nbsp;&nbsp;' : '').($key+1).'&nbsp;'.$member['name'],
        'ALIAS'    => $member['alias'],
        'RAID_LIST'=> implode(';&nbsp;', $rali),
        'ATT_BEGIN'=> (($member['att_dkp_begin']) ? $user->lang['yes'] : $user->lang['no']),
        'ATT_END'  => (($member['att_dkp_end']) ? $user->lang['yes'] : $user->lang['no']),
        'ZAHL'     => $core->switch_row_class(),
        'KEY'	   => $key
   	);
}

function items2tpl($item)
{
	global $core;
	return array(
		'LOOTNAME'	=> $item['name'],
		'LOOTER'	=> $item['player'],
		'RAID'		=> $item['raid'],
		'LOOTDKP'	=> $item['dkp'],
		'ITEMID'	=> $item['id'],
		'CLASS'		=> $core->switch_row_class()
	);
}


?>
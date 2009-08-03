<?php
 /*
 * Project:     EQdkp-Plus Raidlogimport
 * License:     Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
 * Link:		http://creativecommons.org/licenses/by-nc-sa/3.0/
 * -----------------------------------------------------------------------
 * Began:       2008
 * Date:        $Date$
 * -----------------------------------------------------------------------
 * @author      $Author$
 * @copyright   2008-2009 hoofy_leon
 * @link        http://eqdkp-plus.com
 * @package     raidlogimport
 * @version     $Rev$
 *
 * $Id$
 */
define('EQDKP_INC', true);
define('IN_ADMIN', true);

$eqdkp_root_path = './../../../';
include_once('./../includes/common.php');

class RLI_Settings extends EQdkp_Admin
{
	function rli_settings()
	{
		global $db, $pm, $tpl, $user, $eqdkp, $SID, $pC, $rli;
		parent::eqdkp_admin();

		$this->assoc_buttons(array(
			'form' => array(
				'name' 		=> '',
				'process'	=> 'display_form',
				'check'		=> 'a_raidlogimport_config'),
			'submit' => array(
				'name'		=> 'update',
				'process'	=> 'save_config',
				'check'		=> 'a_raidlogimport_config'),
			'man_db_up' => array(
				'name'		=> 'man_db_up',
				'process'	=> 'manual_db_update',
				'check'		=> 'a_raidlogimport_config')
			)
		);
		$this->plug_upd = new PluginUpdater('raidlogimport', 'rli_', 'raidlogimport_config', 'includes');

		//initialise upd_check
		$pluginfo = array(
			'name'		=> 'raidlogimport',
			'version'	=> $pm->get_data('raidlogimport', 'version'),
			'enabled'	=> $rli->config['rli_upd_check'],
			'vstatus'	=> $pm->plugins['raidlogimport']->vstatus,
			'build'		=> $pm->plugins['raidlogimport']->build
		);
		$cachedb = array(
			'table'			=> 'raidlogimport_config',
			'data'			=> $rli->config['rlic_data'],
			'f_data'		=> 'rlic_data',
			'lastcheck' 	=> $rli->config['rlic_lastcheck'],
			'f_lastcheck'	=> 'rlic_lastcheck'
		);
		$this->upd_check = new PluginUpdCheck($pluginfo, $cachedb);
		$this->upd_check->PerformUpdateCheck();
        $tpl->assign_vars(array(
        	'UPD_IK'	=> $this->plug_upd->OutputHTML(),
        	'UPD_CHECK'	=> $this->upd_check->OutputHTML())
        );
	}

	function save_config()
	{
		global $db, $user, $tpl, $eqdkp, $pm, $SID, $rli;

		$messages = array();
		$bytes = array('s_member_rank', 'ignore_dissed', 'use_dkp', 'event_boss');
		$floats = array('member_start', 'attendence_begin', 'attendence_end', 'am_value');
		foreach($rli->config as $old_name => $old_value)
		{
			if(in_array($old_name, $bytes))
			{
				$val = 0;
				if(is_array($_POST[$old_name]))
				{
				  foreach($_POST[$old_name] as $pos)
				  {
					$val += $pos;
				  }
				}
				$_POST[$old_name] = $val;
			}
			elseif(in_array($old_name, $floats))
			{
				$_POST[$old_name] = number_format(floatvalue($_POST[$old_name]), 2, '.', '');
			}
			if(isset($_POST[$old_name]) AND $_POST[$old_name] != $old_value)  //update
			{
				$sql = "UPDATE __raidlogimport_config
						SET config_value = '".$_POST[$old_name]."'
						WHERE config_name = '".$old_name."';";
				$result = $db->query($sql);
				if(!$result)
				{
					message_die("Error! Query: ".$sql);
				}
				else
				{
					$messages[] = $old_name;
					$log_action = array(
						'header' 		 => '{L_ACTION_RAIDLOGIMPORT_CONFIG}',
						'{L_CONFIGNAME}' => $old_name,
						'{L_CONFIG_OLD}' => $old_value,
						'{L_CONFIG_NEW}' => $_POST[$old_name]
					);
					$this->log_insert(array(
						'log_type'	 => $log_action['header'],
						'log_action' => $log_action)
					);
				}
			}
		}

		$this->display_form($messages);
	}

	function display_form($messages=array())
	{
		global $db, $user, $tpl, $eqdkp, $pm, $SID, $rli, $myHtml, $jquery;
		if($messages)
		{
			$rli->rli();
			foreach($messages as $name)
			{
				System_Message($name, $user->lang['bz_save_suc'], 'green');
			}
		}
		//select ranks
		$sql = "SELECT rank_name, rank_id FROM __member_ranks ORDER BY rank_name DESC;";
		$result = $db->query($sql);
		while ($row = $db->fetch_record($result))
		{
		  if($row['rank_id'])
		  {
			$new_member_rank[$row['rank_id']] = $row['rank_name'];
		  }
		}

		//select parsers
		$parser = array(
			'eqdkp' => $user->lang['parser_eqdkp'],
			'plus' => $user->lang['parser_plus'],
			'magicdkp' => $user->lang['parser_magicdkp']
		);

		//select raidcount
        $raidcount = array();
		for($i=0; $i<=3; $i++)
		{
			$raidcount[$i] = $user->lang['raidcount_'.$i];
		}

		//select null_sum
		$null_sum = array();
		for($i=0; $i<=2; $i++)
		{
			$null_sum[$i] = $user->lang['null_sum_'.$i];
		}

		//select item_save_lang
		$item_save_lang = array('en' => 'en', 'de' => 'de', 'fr' => 'fr', 'es' => 'es', 'ru' => 'ru');

		//select member_start_event
		$rli->get_events();
		$member_start_event = $rli->events['name'];

		//select member_display
		$member_display = array(0 => $user->lang['member_display_0'], 1 => $user->lang['member_display_1']);

		//select raid_note_time
		$raid_note_time = array(0 => $user->lang['raid_note_time_0'], 1 => $user->lang['raid_note_time_1']);

		$k = 2;
		$configs = array(
			'select' 	=> array(
				'general' 		=> array('raidcount', 'null_sum', 'raid_note_time'),
				'member'		=> array('new_member_rank', 'member_start_event', 'member_display'),
				'parse'			=> array('parser'),
				'loot'			=> array('item_save_lang')
			),
			'yes_no'	=> array(
				'general'		=> array('rli_upd_check'),
				'hnh_suffix' 	=> array('dep_match'),
				'att'		 	=> array('attendence_raid'),
				'adj'			=> array('deactivate_adj'),
				'am'			=> array('auto_minus', 'am_value_raids', 'am_allxraids')
			),
			'normal'	=> array(
				'general'		=> array('timedkp_handle'),
				'member'		=> array('member_miss_time', 'member_start'),
				'am'			=> array('am_raidnum', 'am_value'),
				'att'			=> array('attendence_begin', 'attendence_end', 'attendence_time', 'att_note_begin', 'att_note_end'),
				'loot'			=> array('loottime'),
				'adj'			=> array('adj_parse'),
				'hnh_suffix'	=> array('hero', 'non_hero'),
				'parse'			=> array('bz_parse')
			),
			'text' 		=> array(
				'general'		=> array('rli_inst_version')
			),
			'ignore'	=> array(
				'ignore'		=> array('rlic_data', 'rlic_lastcheck', 'rli_inst_build')
			),
			'special'	=> array(
				'general'		=> array('3:use_dkp'),
				'loot'			=> array('2:ignore_dissed'),
				'member'		=> array('3:s_member_rank'),
				'parse'			=> array('2:event_boss')
			)
		);

		$holder = array();
		foreach($configs as $display_type => $hold)
		{
			foreach($hold as $holde => $names)
			{
				foreach($names as $name)
				{
					switch($display_type)
					{
						case 'select':
							$holder[$holde][$k]['value'] = $myHtml->DropDown($name, $$name, $rli->config[$name]);
							$holder[$holde][$k]['name'] = $name;
							break;

						case 'yes_no':
							$a = $k;
							if($name == 'rli_upd_check')
							{
								$k = 1;
							}
							$check_1 = '';
							$check_0 = '';
							if($rli->config[$name])
							{
								$check_1 = "checked='checked'";
							}
							else
							{
								$check_0 = "checked='checked'";
							}
							$holder[$holde][$k]['value'] = "<input type='radio' name='".$name."' value='1' ".$check_1." />".$user->lang['yes']."&nbsp;&nbsp;&nbsp;";
							$holder[$holde][$k]['value'] .= "&nbsp;&nbsp;&nbsp;<input type='radio' name='".$name."' value='0' ".$check_0." />".$user->lang['no'];
							$holder[$holde][$k]['name'] = $name;
							$k = $a;
							break;

						case 'text':
							$a = $k;
							if($name == 'rli_inst_version')
							{
								$k = 0;
							}
							$holder[$holde][$k]['value'] = $rli->config[$name];
							$holder[$holde][$k]['name'] = $name;
							$k = $a;
							break;

						case 'normal':
							$holder[$holde][$k]['value'] = "<input type='text' name='".$name."' value='".$rli->config[$name]."' class='maininput' />";
							$holder[$holde][$k]['name'] = $name;
							break;

						case 'special':
						  list($num_of_opt, $name) = explode(':', $name);
						  $value = $rli->config[$name];
						  $pv = array(0,1,2,4,8,16,32);
						  for($i=1; $i<=$num_of_opt; $i++)
						  {
							  $checked = ($value & $pv[$i]) ? 'checked="checked"' : '';
                              $holder[$holde][$k]['value'] .= "<nobr><input type='checkbox' name='".$name."[]' value='".$pv[$i]."' ".$checked." />".$user->lang[$name.'_'.$pv[$i]]."</nobr>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                          }
                          $holder[$holde][$k]['name'] = $name;
						  break;

						default:
							//do nothing
							break;
					}
					$k++;
				}
			}
        }
        foreach($holder as $type => $hold)
        {
        	ksort($hold);
        	if($type == 'hnh_suffix' AND $eqdkp->config['default_game'] != 'WoW')
        	{
        		continue;
        	}
			$tpl->assign_block_vars('holder', array('TITLE'	=> $user->lang['title_'.$type]));
			foreach($hold as $nava)
			{
				$add = '';
				if($nava['name'] == 'member_display')
				{
				  	if(extension_loaded('gd'))
				  	{
						$info = gd_info();
						$add = sprintf($user->lang['member_display_add'], '<span class=\\\'positive\\\'>'.$info['GD Version'].'</span>');
				  	}
				  	else
				  	{
				 		$add = sprintf($user->lang['member_display_add'], $user->lang['no_gd_lib']);
				  	}
				}
				$tpl->assign_block_vars('holder.config', array(
					'NAME'	=> $user->lang[$nava['name']].$add,
					'VALUE' => $nava['value'],
					'CLASS'	=> $eqdkp->switch_row_class())
				);
			}
		}
		$tpl->assign_vars(array(
			'L_CONFIG' => $user->lang['raidlogimport'].' '.$user->lang['settings'],
			'L_SAVE'	 => $user->lang['bz_save'],
			'L_MANUAL'	=> $user->lang['rli_manual'],
			'S_GERMAN'	=> ($user->lang['lang'] == 'german') ? true : false)
		);

		$eqdkp->set_vars(array(
        	'page_title' 		=> sprintf($user->lang['admin_title_prefix'], $eqdkp->config['guildtag'], $eqdkp->config['dkp_name']).': '.$user->lang['configuration'],
            'template_path'     => $pm->get_data('raidlogimport', 'template_path'),
            'template_file'     => 'rli_settings.html',
            'display'           => true,
            )
        );
	}
}
$rli_settings = new RLI_Settings;
$rli_settings->process();
?>
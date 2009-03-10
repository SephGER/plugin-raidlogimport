<?php
define('EQDKP_INC', true);
define('IN_ADMIN', true);

$eqdkp_root_path = './../../../';
include_once('./../includes/common.php');

class RLI_Settings extends EQdkp_Admin
{
	function rli_settings()
	{
		global $db, $pm, $tpl, $user, $eqdkp, $SID, $pC, $rli_config;
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
		$pC->InitAdmin();
		$this->plug_upd = new PluginUpdater('raidlogimport', 'rli_', 'raidlogimport_config', 'includes');

		//initialise upd_check
		$pluginfo = array(
			'name'		=> 'raidlogimport',
			'version'	=> $pm->get_data('raidlogimport', 'version'),
			'enabled'	=> $rli_config['rli_upd_check'],
			'vstatus'	=> $pm->plugins['raidlogimport']->vstatus,
			'build'		=> $pm->plugins['raidlogimport']->build
		);
		$cachedb = array(
			'table'			=> 'raidlogimport_config',
			'data'			=> $rli_config['rlic_data'],
			'f_data'		=> 'rlic_data',
			'lastcheck' 	=> $rli_config['rlic_lastcheck'],
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
		foreach($rli->config as $old_name => $old_value)
		{
			if($old_name == 's_member_rank' or $old_name == 'ignore_dissed' or $old_name == 'use_dkp')
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
		$parser = array('ctrt' => 'CT-Raidtracker');

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

		$k = 2;
		$configs = array(
			'select' 	=> array(
				'general' 		=> array('raidcount', 'null_sum'),
				'member'		=> array('new_member_rank', 'member_start_event'),
				'parse'			=> array('parser'),
				'loot'			=> array('item_save_lang')
			),
			'yes_no'	=> array(
				'general'		=> array('rli_upd_check'),
				'hnh_suffix' 	=> array('dep_match'),
				'att'		 	=> array('attendence_raid'),
				'adj'			=> array('deactivate_adj'),
				'parse'			=> array('event_boss'),
				'am'			=> array('auto_minus', 'am_value_raids', 'am_allxraids')
			),
			'normal'	=> array(
				'member'		=> array('member_miss_time', 'member_start'),
				'am'			=> array('am_raidnum', 'am_value'),
				'att'			=> array('attendence_begin', 'attendence_end', 'attendence_time'),
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
				'general'		=> array('use_dkp'),
				'loot'			=> array('ignore_dissed'),
				'member'		=> array('s_member_rank')
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
						  if($name == 's_member_rank' or $name == 'use_dkp')
						  {
							$value = $rli->config[$name];
                            $c1 = '';
                            if($value == 1 || $value == 3 || $value == 5 || $value == 7)
                            {
                                $c1 = 'checked="checked"';
                            }
							$c2 = '';
							if($value == 2 || $value == 3 || $value == 6 || $value == 7)
							{
								$c2 = 'checked="checked"';
							}
							$c4 = '';
							if($value == 4 || $value == 5 || $value == 6 || $value == 7)
							{
								$c4 = 'checked="checked"';
							}
							$holder[$holde][$k]['value'] = "<input type='checkbox' name='".$name."[]' value='1' ".$c1." />".$user->lang[$name.'_1']."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
							$holder[$holde][$k]['value'] .= "<input type='checkbox' name='".$name."[]' value='2' ".$c2." />".$user->lang[$name.'_2']."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
							$holder[$holde][$k]['value'] .= " <nobr><input type='checkbox' name='".$name."[]' value='4' ".$c4." />".$user->lang[$name.'_4']."</nobr>";
						  }
						  if($name == 'ignore_dissed')
						  {
						  	$value = $rli->config[$name];
						  	$dissed = '';
						  	if($value == 1 || $value == 3)
						  	{
						  		$dissed = 'checked="checked"';
						  	}
						  	$bank = '';
						  	if($value == 2 || $value == 3)
						  	{
						  		$bank = 'checked="checked"';
						  	}
						  	$holder[$holde][$k]['value'] = "<input type='checkbox' name='".$name."[]' value='1' ".$dissed." />".$user->lang[$name.'_1']."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
						  	$holder[$holde][$k]['value'] .= "<input type='checkbox' name='".$name."[]' value='2' ".$bank." />".$user->lang[$name.'_2'];
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
				$tpl->assign_block_vars('holder.config', array(
					'NAME'	=> $user->lang[$nava['name']],
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
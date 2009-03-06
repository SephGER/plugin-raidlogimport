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
		$k = 2;
		$endvalues = array();
		//select ranks
		$sql = "SELECT rank_name, rank_id FROM __member_ranks ORDER BY rank_name DESC;";
		$result = $db->query($sql);
		while ($row = $db->fetch_record($result))
		{
		  if($row['rank_id'])
		  {
			$ranks[$row['rank_id']] = $row['rank_name'];
		  }
		}
		$holder = array();
		foreach($rli->config as $name => $value)
		{
			if($name == 'raidcount')
			{
                $holder['general'][$k]['value'] = "<select name='".$name."'>";
				for($i=0; $i<=3; $i++)
				{
					$select = ($i == $value) ? "selected='selected'" : "";
					$holder['general'][$k]['value'] .= "<option value='".$i."' ".$select.">".$user->lang['raidcount_'.$i]."</option>";
				}
				$holder['general'][$k]['value'] .= "</select>";
				$holder['general'][$k]['name'] = $name;
			}
			elseif($name == 'parser')
			{
				$parsers = array('ctrt' => 'CT-Raidtracker');
				$holder['parse'][$k]['value'] = $myHtml->DropDown($name, $parsers, $value);
				$holder['parse'][$k]['name'] = $name;
			}
			elseif($name == 'new_member_rank')
			{
				$holder['general'][$k]['value'] = $myHtml->DropDown($name, $ranks, $value);
				$holder['general'][$k]['name'] = $name;
			}
			elseif($name == 'event_boss' OR $name == 'attendence_raid' OR $name == 'dep_match' OR $name == 'rli_upd_check' OR $name == 'use_bossdkp' OR $name == 'use_timedkp' OR $name == 'deactivate_adj' or $name == 'auto_minus' or $name == 'ignore_dissed' OR $name == 'am_allxraids' OR $name == 'am_value_raids')
			{
				$hold = 'general';
				if($name == 'dep_match')
				{
					$hold = 'hnh_suffix';
				}
				elseif($name == 'attendence_raid')
				{
					$hold = 'att';
				}
				elseif($name == 'deactivate_adj')
				{
					$hold = 'adj';
				}
				elseif($name == 'event_boss')
				{
					$hold = 'parse';
				}
				elseif($name == 'auto_minus' or $name == 'am_allxraids' or $name == 'am_value_raids')
				{
					$hold = 'am';
				}
				elseif($name == 'ignore_dissed')
				{
					$hold = 'loot';
				}
                $a = $k;
				if($name == 'rli_upd_check')
				{
					$k = 1;
				}
				$check_1 = '';
				$check_0 = '';
				if($value)
				{
					$check_1 = "checked='checked'";
				}
				else
				{
					$check_0 = "checked='checked'";
				}
				$holder[$hold][$k]['value'] = "<input type='radio' name='".$name."' value='1' ".$check_1." />".$user->lang['yes']."&nbsp;&nbsp;&nbsp;";
				$holder[$hold][$k]['value'] .= "&nbsp;&nbsp;&nbsp;<input type='radio' name='".$name."' value='0' ".$check_0." />".$user->lang['no'];
				$holder[$hold][$k]['name'] = $name;
				$k = $a;
			}
			elseif($name == 'rli_inst_version')
			{
				$holder['general'][0]['value'] = $value;
				$holder['general'][0]['name'] = $name;
			}
			elseif($name == 'rlic_data' or $name == 'rlic_lastcheck' or $name == 'rli_inst_build')
			{
				//do nothing
			}
			elseif($name == 'null_sum')
			{
				$holder['general'][$k]['value'] = "<select name='".$name."'>";
				for($i=0; $i<=2; $i++)
				{
					$select = ($i == $value) ? "selected='selected'" : "";
					$holder['general'][$k]['value'] .= "<option value='".$i."' ".$select.">".$user->lang['null_sum_'.$i]."</option>";
				}
				$holder['general'][$k]['value'] .= "</select>";
				$holder['general'][$k]['name'] = $name;
			}
			elseif($name == 'item_save_lang')
			{
				$options = array('en' => 'en', 'de' => 'de', 'fr' => 'fr', 'es' => 'es', 'ru' => 'ru');
				$holder['loot'][$k]['value'] = '<select name="'.$name.'">';
				foreach($options as $ey => $val)
				{
					$sel = ($ey == $value) ? 'selected="selected"' : '';
					$holder['loot'][$k]['value'] .= '<option value="'.$ey.'" '.$sel.'>'.$val.'</option>';
				}
				$holder['loot'][$k]['value'] .= '</select>';
				$holder['loot'][$k]['name'] = $name;
			}
			else
			{
				$hold = 'general';
				if($name == 'am_raidnum' OR $name == 'am_value')
				{
					$hold = 'am';
				}
				elseif($name == 'attendence_begin' or $name == 'attendence_end' or $name == 'attendence_time')
				{
					$hold = 'att';
				}
				elseif($name == 'loottime')
				{
					$hold = 'loot';
				}
				elseif($name == 'adj_parse')
				{
					$hold = 'adj';
				}
				elseif($name == 'hero' or $name == 'non_hero')
				{
					$hold = 'hnh_suffix';
				}
				elseif($name == 'bz_parse')
				{
					$hold = 'parse';
				}
				$holder[$hold][$k]['value'] = "<input type='text' name='".$name."' value='".$value."' class='maininput' />";
				$holder[$hold][$k]['name'] = $name;
			}
			$k++;
        }
        $holder['general'][$k+1]['name'] = 'rli_round';
        $holder['general'][$k+1]['value'] = $user->lang['rli_round_plus'];
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
			'L_SAVE'	 => $user->lang['bz_save'])
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
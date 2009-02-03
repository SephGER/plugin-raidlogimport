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
		global $db, $user, $tpl, $eqdkp, $pm, $SID, $rli_config;

		$messages = array();
		foreach($rli_config as $old_name => $old_value)
		{
			if(isset($_POST[$old_name]) AND $_POST[$old_name] != $old_value)  //update
			{
				$sql = "UPDATE __raidlogimport_config
						SET config_value = '".$_POST[$old_name]."'
						WHERE config_name = '".$old_name."';";
				$result = $db->query($sql);
				if(!$result)
				{
					$messages[$old_name] = "Error! Query: ".$sql;
				}
				else
				{
					$messages[$old_name] = $user->lang['bz_save_suc'];
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

		foreach($messages as $name => $message)
		{
			$tpl->assign_block_vars('sucs', array(
				'PART1'	=> $name.': ',
				'PART2'	=> $message,
				'CLASS' => $eqdkp->switch_row_class())
			);
		}

		$tpl->assign_vars(array(
			'L_SUCCESS'		=> $user->lang['config_success'],
			'L_RLI_CONFIG' 	=> $user->lang['raidlogimport'].' '.$user->lang['settings'],
			'L_PLUG_UPD'	=> $user->lang['plug_upd'],
			'L_LINKS'		=> $user->lang['links'])
		);

		$eqdkp->set_vars(array(
			'page_title' 		=> sprintf($user->lang['admin_title_prefix'], $eqdkp->config['guildtag'], $eqdkp->config['dkp_name']).': '.$user->lang['configuration'],
            'template_path'     => $pm->get_data('raidlogimport', 'template_path'),
            'template_file'     => 'success.html',
            'display'           => true,
            )
        );
	}

	function manual_db_update()
	{
		global $db, $user, $tpl, $eqdkp, $pm, $SID;
		$this->plug_upd->DeleteVersionString();
		redirect('plugins/'.$pm->get_data('raidlogimport', 'path').'/admin/settings.php');
	}

	function display_form()
	{
		global $db, $user, $tpl, $eqdkp, $pm, $SID, $rli_config;
		$k = 2;
		$endvalues = array();
		foreach($rli_config as $name => $value)
		{
			if($name == 'raidcount')
			{
                $endvalues[$k]['value'] = "<select name='".$name."'>";
				for($i=0; $i<=3; $i++)
				{
					$select = ($i == $value) ? "selected='selected'" : "";
					$endvalues[$k]['value'] .= "<option value='".$i."' ".$select.">".$user->lang['raidcount_'.$i]."</option>";
				}
				$endvalues[$k]['value'] .= "</select>";
				$endvalues[$k]['name'] = $name;
			}
			elseif($name == 'parser')
			{
				$parsers = array('ctrt' => 'CT-Raidtracker');
				$endvalues[$k]['value'] = "<select name='".$name."'>";
				foreach($parsers as $parser => $display)
				{
					$select = ($parser == $value) ? "selected='selected'" : "";
					$endvalues[$k]['value'] .= "<option value='".$parser."' ".$select.">".$display."</option>";
				}
				$endvalues[$k]['value'] .= "</select>";
				$endvalues[$k]['name'] = $name;
			}
			elseif($name == 'event_boss' OR $name == 'attendence_raid' OR $name == 'dep_match' OR $name == 'rli_upd_check')
			{
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
				$endvalues[$k]['value'] = "<input type='radio' name='".$name."' value='1' ".$check_1." />".$user->lang['yes']."&nbsp;&nbsp;&nbsp;";
				$endvalues[$k]['value'] .= "&nbsp;&nbsp;&nbsp;<input type='radio' name='".$name."' value='0' ".$check_0." />".$user->lang['no'];
				$endvalues[$k]['name'] = $name;
				$k = $a;
			}
			elseif($name == 'rli_inst_version')
			{
				$endvalues[0]['value'] = $value. "&nbsp;<input type='submit' name='man_db_up' value='".$user->lang['rli_man_db_up']."' class='mainoption' />";
				$endvalues[0]['name'] = $name;
			}
			elseif($name == 'rlic_data' or $name == 'rlic_lastcheck' or $name == 'rli_inst_build')
			{
				//do nothing
			}
			else
			{
				$endvalues[$k]['value'] = "<input type='text' name='".$name."' value='".$value."' class='maininput' />";
				$endvalues[$k]['name'] = $name;
			}
			$k++;
        }
        ksort($endvalues);
        foreach($endvalues as $endvalue)
        {
			$tpl->assign_block_vars('config', array(
				'NAME'	=> $user->lang[$endvalue['name']],
				'VALUE' => $endvalue['value'],
				'CLASS' => $eqdkp->switch_row_class())
			);
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
<?php
define('EQDKP_INC', true);
define('IN_ADMIN', true);

$eqdkp_root_path = './../../../';
include_once('./../includes/common.php');

class RLI_Settings extends EQdkp_Admin
{
	function rli_settings()
	{
		global $db, $pm, $tpl, $user, $eqdkp, $SID;
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
		$tpl->assign_var('UPD_IK', $this->plug_upd->OutputHTML());
	}

	function save_config()
	{
		global $db, $user, $tpl, $eqdkp, $pm, $SID, $rli_config;

		$messages = array();
		foreach($rli_config as $old_name => $old_value)
		{
			if(isset($_POST[$old_name]) AND $_POST[$old_name] != $old_value)  //update
			{
				$sql = "UPDATE ".RLI_CONFIG_TABLE."
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
				'NAME'	=> $name,
				'MESS'	=> $message,
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
		foreach($rli_config as $name => $value)
		{
			$endvalue = '';
			if($name == 'raidcount')
			{
                $endvalue = "<select name='".$name."'>";
				for($i=0; $i<=3; $i++)
				{
					$select = ($i == $value) ? "selected='selected'" : "";
					$endvalue .= "<option value='".$i."' ".$select.">".$user->lang['raidcount_'.$i]."</option>";
				}
				$endvalue .= "</select>";
			}
			elseif($name == 'parser')
			{
				$parsers = array('ctrt' => 'CT-Raidtracker');
				$endvalue = "<select name='".$name."'>";
				foreach($parsers as $parser => $display)
				{
					$select = ($parser == $value) ? "selected='selected'" : "";
					$endvalue .= "<option value='".$parser."' ".$select.">".$display."</option>";
				}
				$endvalue .= "</select>";
			}
			elseif($name == 'event_boss' OR $name == 'attendence_raid' OR $name == 'conf_adjustment')
			{
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
				$endvalue = "<input type='radio' name='".$name."' value='1' ".$check_1." />".$user->lang['yes']."&nbsp;&nbsp;&nbsp;";
				$endvalue .= "&nbsp;&nbsp;&nbsp;<input type='radio' name='".$name."' value='0' ".$check_0." />".$user->lang['no'];
			}
			elseif($name == 'rli_inst_version')
			{
				$endvalue = $value. "&nbsp;<input type='submit' name='man_db_up' value='".$user->lang['rli_man_db_up']."' class='mainoption' />";
			}
			else
			{
				$endvalue = "<input type='text' name='".$name."' value='".$value."' class='maininput' />";
			}
			$tpl->assign_block_vars('config', array(
				'NAME'	=> $user->lang[$name],
				'VALUE' => $endvalue,
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

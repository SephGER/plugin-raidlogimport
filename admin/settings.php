<?php
/*
* Project:     EQdkp-Plus Raidlogimport
* License:     Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
* Link:		http://creativecommons.org/licenses/by-nc-sa/3.0/
* -----------------------------------------------------------------------
* Began:       2008
* Date:        $Date: 2009-05-07 17:52:03 +0200 (Do, 07 Mai 2009) $
* -----------------------------------------------------------------------
* @author      $Author: hoofy_leon $
* @copyright   2008-2009 hoofy_leon
* @link        http://eqdkp-plus.com
* @package     raidlogimport
* @version     $Rev: 4786 $
*
* $Id: settings.php 4786 2009-05-07 15:52:03Z hoofy_leon $
*/
define('EQDKP_INC', true);
define('IN_ADMIN', true);

$eqdkp_root_path = './../../../';
include_once('./../includes/common.php');

class RLI_Settings extends page_generic
{
	public function __construct() {
		global $user;
		$user->check_auth('a_raidlogimport_config');
		parent::__construct(false);
		$this->process();
	}

	public function update() {
		global $core, $rli, $in;

		$messages = array();
		$bytes = array('s_member_rank', 'ignore_dissed', 'use_dkp', 'event_boss', 'standby_dkptype');
		$floats = array('member_start', 'attendence_begin', 'attendence_end', 'am_value');
		foreach($rli->config() as $old_name => $old_value) {
			if(in_array($old_name, $bytes)) {
				$val = 0;
				if(is_array($in->getArray($old_name, 'int'))) {
					foreach($in->getArray($old_name, 'int') as $pos) {
						$val += $pos;
					}
				}
				$data[$old_name] = $val;
			} elseif(in_array($old_name, $floats)) {
				$data[$old_name] = number_format(floatvalue($in->get($old_name)), 2, '.', '');
			} else {
				$data[$old_name] = $in->get($old_name, '0');
			}
			if(isset($data[$old_name]) AND $data[$old_name] != $old_value) { //Update
				$core->config_set($old_name, $data[$old_name], 'raidlogimport');
				$messages[] = $old_name;
			}
		}
		$this->display(array(implode(', ', $messages)));
	}

	public function display($messages=array()) {
		global $user, $tpl, $core, $pm, $rli, $html, $jquery, $pdh, $eqdkp_root_path;
		if($messages) {
			$rli->__construct();
			foreach($messages as $name) {
				$core->message($name, $user->lang('bz_save_suc'), 'green');
			}
		}
		//select ranks
		$new_member_rank = $pdh->aget('rank', 'name', 0, array($pdh->get('rank', 'id_list')));

		//select parsers
		$parser = array(
			'eqdkp' => $user->lang('parser_eqdkp'),
			'plus' => $user->lang('parser_plus'),
			'magicdkp' => $user->lang('parser_magicdkp')
		);

		//select raidcount
		$raidcount = array();
		for($i=0; $i<=3; $i++) {
			$raidcount[$i] = $user->lang('raidcount_'.$i);
		}

		//select null_sum & standbyraidoptions
		$standby_raid = array();
		for($i=0; $i<=2; $i++) {
			$standby_raid[$i] = $user->lang('standby_raid_'.$i);
		}

		//select member_start_event
		$member_start_event = $pdh->aget('event', 'name', 0, array($pdh->get('event', 'id_list')));

		//select member_display
		$member_display = array(0 => $user->lang('member_display_0'), 1 => $user->lang('member_display_1'), 2 => $user->lang('member_display_2'));

		//select raid_note_time
		$raid_note_time = array(0 => $user->lang('raid_note_time_0'), 1 => $user->lang('raid_note_time_1'));

		$k = 2;
		$configs = array(
			'select' 	=> array(
				'general' 		=> array('raidcount', 'raid_note_time', 'parser'),
				'member'		=> array('new_member_rank', 'member_start_event', 'member_display'),
				'standby'		=> array('standby_raid')
			),
			'yes_no'	=> array(
				'general'		=> array('rli_upd_check', 'deactivate_adj'),
				'difficulty' 	=> array('dep_match'),
				'att'		 	=> array('attendence_raid'),
				'am'			=> array('auto_minus', 'am_value_raids', 'am_allxraids'),
				'standby'		=> array('standby_absolute', 'standby_att')
			),
			'text'		=> array(
				'general'		=> array('timedkp_handle', 'bz_parse', 'loottime'),
				'member'		=> array('member_miss_time', 'member_start', 'member_raid'),
				'am'			=> array('am_raidnum', 'am_value'),
				'att'			=> array('attendence_begin', 'attendence_end', 'attendence_time', 'att_note_begin', 'att_note_end'),
				'difficulty'	=> array('diff_1', 'diff_2', 'diff_3', 'diff_4'),
				'standby'		=> array('standby_value', 'standby_raidnote')
			),
			'normal' 	=> array(
				'general'		=> array('rli_inst_version')
			),
			'ignore'	=> array(
				'ignore'		=> array('rlic_data', 'rlic_lastcheck', 'rli_inst_build')
			),
			'special'	=> array(
				'general'		=> array('3:use_dkp', '2:ignore_dissed', '2:event_boss'),
				'member'		=> array('3:s_member_rank'),
				'standby'		=> array('3:standby_dkptype')
			)
		);

		$holder = array();
		foreach($configs as $display_type => $hold) {
			foreach($hold as $holde => $names) {
				foreach($names as $name) {
					switch($display_type) {
						case 'select':
							$holder[$holde][$k]['value'] = $html->DropDown($name, $$name, $rli->config($name));
							$holder[$holde][$k]['name'] = $name;
							break;

						case 'yes_no':
							$a = $k;
							if($name == 'rli_upd_check') {
								$k = 1;
							}
							$check_1 = '';
							$check_0 = '';
							if($rli->config($name)) {
								$check_1 = "checked='checked'";
							} else {
								$check_0 = "checked='checked'";
							}
							$holder[$holde][$k]['value'] = "<input type='radio' name='".$name."' value='1' ".$check_1." />".$user->lang('yes')."&nbsp;&nbsp;&nbsp;";
							$holder[$holde][$k]['value'] .= "&nbsp;&nbsp;&nbsp;<input type='radio' name='".$name."' value='0' ".$check_0." />".$user->lang('no');
							$holder[$holde][$k]['name'] = $name;
							$k = $a;
							break;

						case 'normal':
							$a = $k;
							if($name == 'rli_inst_version') {
								$k = 0;
								$holder[$holde][$k]['value'] = $pm->get_data('raidlogimport', 'version');
							} else {
								$holder[$holde][$k]['value'] = $rli->config($name);
							}
							$holder[$holde][$k]['name'] = $name;
							$k = $a;
							break;

						case 'text':
							$holder[$holde][$k]['value'] = "<input type='text' name='".$name."' value='".$rli->config($name)."' class='maininput' />";
							$holder[$holde][$k]['name'] = $name;
							break;

						case 'special':
							list($num_of_opt, $name) = explode(':', $name);
							$value = $rli->config($name);
							$pv = array(0,1,2,4,8,16,32);
							$holder[$holde][$k]['value'] = '';
							for($i=1; $i<=$num_of_opt; $i++) {
								$checked = ($value & $pv[$i]) ? 'checked="checked"' : '';
								$holder[$holde][$k]['value'] .= "<span class='nowrap'><input type='checkbox' name='".$name."[]' value='".$pv[$i]."' ".$checked." />".$user->lang($name.'_'.$pv[$i])."</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
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
		$num = 1;
		foreach($holder as $type => $hold) {
			ksort($hold);
			if($type == 'difficulty' AND $core->config['default_game'] != 'wow') {
				continue;
			}
			$tpl->assign_block_vars('holder', array(
				'TITLE'	=> $user->lang('title_'.$type),
				'NUM'	=> $num)
			);
			$num++;
			foreach($hold as $nava) {
				$add = ($user->lang($nava['name'].'_help')) ? $user->lang($nava['name'].'_help') : '';
				if($nava['name'] == 'member_display') {
					$info = gd_info();
					$add = sprintf($add, (extension_loaded('gd')) ? '<span class=\\\'positive\\\'>'.$info['GD Version'].'</span>' : $user->lang('no_gd_lib'));
				}
				if($add != '') {
					$add = ' <span id="h'.$nava['name'].'"><img alt="help" src="'.$eqdkp_root_path.'images/info.png"'.$jquery->tooltip('h'.$nava['name'], '', $add, false, true, false).' /></span>';
				}
				$warn = ($user->lang($nava['name'].'_warn')) ? $user->lang($nava['name'].'_warn') : '';
				if($warn != '') {
					$warn = ' <span id="w'.$nava['name'].'"><img width="16" height="16" alt="help" src="'.$eqdkp_root_path.'images/false.png"'.$jquery->tooltip('w'.$nava['name'], '', $warn, false, true, false).' /></span>';
				}
				$tpl->assign_block_vars('holder.config', array(
					'NAME'	=> $user->lang($nava['name']).' '.$add.' '.$warn,
					'VALUE' => $nava['value'],
					'CLASS'	=> $core->switch_row_class())
				);
			}
		}
		$tpl->assign_vars(array(
			'L_CONFIG' => $user->lang('raidlogimport').' '.$user->lang('settings'),
			'L_SAVE'	 => $user->lang('bz_save'),
			'L_MANUAL'	=> $user->lang('rli_manual'),
			'S_GERMAN'	=> ($user->lang('lang') == 'german') ? true : false,
			'TAB_JS'	=> $jquery->Tab_header('rli_config'))
		);

		$core->set_vars(array(
			'page_title' 		=> sprintf($user->lang('admin_title_prefix'), $core->config['guildtag'], $core->config['dkp_name']).': '.$user->lang('configuration'),
			'template_path'     => $pm->get_data('raidlogimport', 'template_path'),
			'template_file'     => 'settings.html',
			'display'           => true,
			)
		);
	}
}
$rli_settings = new RLI_Settings;
?>
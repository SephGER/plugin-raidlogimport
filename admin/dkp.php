<?php
/*
* Project:     EQdkp-Plus Raidlogimport
* License:     Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
* Link:		http://creativecommons.org/licenses/by-nc-sa/3.0/
* -----------------------------------------------------------------------
* Began:       2008
* Date:        $Date: 2009-07-04 16:06:06 +0200 (Sa, 04 Jul 2009) $
* -----------------------------------------------------------------------
* @author      $Author: hoofy_leon $
* @copyright   2008-2009 hoofy_leon
* @link        http://eqdkp-plus.com
* @package     raidlogimport
* @version     $Rev: 5166 $
*
* $Id: dkp.php 5166 2009-07-04 14:06:06Z hoofy_leon $
*/

// EQdkp required files/vars
define('EQDKP_INC', true);
define('IN_ADMIN', true);

$eqdkp_root_path = './../../../';
include_once('./../includes/common.php');

class raidlogimport extends admin_generic {
	public function raidlogimport() {
		global $user, $rli;
		$user->check_auth('a_raidlogimport_dkp');
		
		$handler = array(
			'checkraid'	=> array('process' => 'process_raids'),
			'checkmem'	=> array('process' => 'process_members'),
			'checkitem'	=> array('process' => 'process_items'),
			'checkadj'	=> array('process' => 'process_adjustments'),
			'viewall'	=> array('process' => 'process_views'),
			'insert'	=> array('process' => 'insert_log')
		);
		parent::__construct(false, $handler);
		$rli->init_import();
		$this->process();
	}

	function process_raids() {
		global $db, $core, $user, $tpl, $pm, $rli, $in;

		if($in->exists('log')) {
			$log = simplexml_load_string(utf8_encode(trim(str_replace("&", "and", html_entity_decode($_POST['log'])))));
			if ($log === false) {
				message_die($user->lang('xml_error'));
			} else {
				$rli->parser->parse_string($log);
			}
		}
		$rli->raid->add_new($in->get('raid_add', 0));
		if($in->get('checkraid') == $user->lang('rli_calc_note_value')) {
			$rli->raid->recalc();
		}

		$rli->raid->display(true);

		$tpl->assign_vars(array(
			'USE_TIMEDKP' => ($rli->config('use_dkp') & 2),
			'USE_BOSSDKP' => ($rli->config('use_dkp') & 1))
		);
		//language
		$tpl->assign_vars(lang2tpl());

		$rli->destroy();

		$core->set_vars(array(
			'page_title'        => sprintf($user->lang('admin_title_prefix'), $core->config['guildtag'], $core->config['dkp_name']).': '.$user->lang('rli_check_data'),
			'template_path'     => $pm->get_data('raidlogimport', 'template_path'),
			'template_file'     => 'rli_step2raids.html',
			'display'           => true)
		);
	}

	function process_members()
	{
		global $core, $user, $tpl, $pm, $rli, $in;

		$rli->member->add_new($in->get('members_add', 0));

		//display members
		$rli->member->display(true);

		//show raids
		$rli->raid->display();

		//language
		$tpl->assign_vars(lang2tpl());

		$tpl->assign_vars(array(
			'S_ATT_BEGIN'	 => ($rli->config('attendence_begin') > 0 AND !$rli->config('attendence_raid')) ? TRUE : FALSE,
			'S_ATT_END'		 => ($rli->config('attendence_end') > 0 AND !$rli->config('attendence_raid')) ? TRUE : FALSE,
			'MEMBER_DISPLAY' => ($rli->config('member_display') == 1) ? $rli->raid->th_raidlist : false,
			'RAIDCOUNT'		 => ($rli->config('member_display') == 1) ? $rli->raid->count() : 1,
			'RAIDCOUNT3'	 => ($rli->config('member_display') == 1) ? $rli->raid->count() +2 : 3)
		);

		$rli->destroy();

		$core->set_vars(array(
			'page_title'        => sprintf($user->lang('admin_title_prefix'), $core->config['guildtag'], $core->config['dkp_name']).': '.$user->lang('rli_check_data'),
			'template_path'     => $pm->get_data('raidlogimport', 'template_path'),
			'template_file'     => ($rli->config('member_display') == 2) ? 'rli_step2dmems.html' : 'rli_step2mems.html',
			'display'           => true)
		);
	}

	function process_items()
	{
		global $core, $user, $tpl, $pm, $rli, $in;
		
		$rli->item->add_new($in->get('items_add', 0));
		$rli->member->display();
		$rli->raid->display();
		$rli->item->display(true);
		
		$tpl->assign_vars(array(
			'S_ATT_BEGIN'	=> ($rli->config('attendence_begin') > 0 AND !$rli->config('attendence_raid')) ? true : false,
			'S_ATT_END'		=> ($rli->config('attendence_end') > 0 AND !$rli->config('attendence_raid')) ? true : false)
		);

		//language
		$tpl->assign_vars(lang2tpl());

		$rli->destroy();
		
		$core->set_vars(array(
			'page_title'        => sprintf($user->lang('admin_title_prefix'), $core->config['guildtag'], $core->config['dkp_name']).': '.$user->lang('rli_check_data'),
			'template_path'     => $pm->get_data('raidlogimport', 'template_path'),
			'template_file'     => 'rli_step2items.html',
			'display'           => true)
		);
	}

	function process_adjustments()
	{
		global $core, $tpl, $user, $pm, $rli, $in;

		$rli->adj->add_new($in->get('adjs_add', 0));
				
		$rli->member->display();
		$rli->raid->display();
		$rli->item->display();
		$rli->adj->display(true);

		$tpl->assign_vars(array(
			'S_ATT_BEGIN'	=> ($rli->config('attendence_begin') > 0 AND !$rli->config('attendence_raid')) ? true : false,
			'S_ATT_END'		=> ($rli->config('attendence_end') > 0 AND !$rli->config('attendence_raid')) ? true : false)
		);

		//language
		$tpl->assign_vars(lang2tpl());

		$rli->destroy();
		
		$core->set_vars(array(
			'page_title'        => sprintf($user->lang('admin_title_prefix'), $core->config['guildtag'], $core->config['dkp_name']).': '.$user->lang('rli_check_data'),
			'template_path'     => $pm->get_data('raidlogimport', 'template_path'),
			'template_file'     => 'rli_step2adj.html',
			'display'           => true)
		);
	}

	function insert_log()
	{
		global $db, $core, $user, $tpl, $pm, $rli, $pdh;
		
		$message = array();
		$bools = $rli->check_data();
		if(!in_array('miss', $bools) AND !in_array(false, $bools)) {
			$db->query("START TRANSACTION");
			$isok = $rli->member->insert();
			if($isok) $isok = $rli->raid->insert();
			if($isok) $isok = $rli->item->insert();
			if($isok && !$rli->config('deactivate_adj')) $isok = $rli->adj->insert();
			if($isok) {
				$db->query("COMMIT;");
				$pm->do_hooks('/plugins/raidlogimport/admin/dkp.php');
				$pdh->process_hook_queue();
				$rli->flush_cache();
				$message[] = $user->lang('bz_save_suc');
			} else {
				$db->query("ROLLBACK;");
				$rli->destroy();
				$message[] = $user->lang('rli_error');
			}
			foreach($message as $answer) {
				$tpl->assign_block_vars('sucs', array(
					'PART1'	=> $answer,
					'CLASS'	=> $core->switch_row_class())
				);
			}
			$tpl->assign_vars(array(
				'L_SUCCESS' => $user->lang('rli_success'),
				'L_LINKS'	=> $user->lang('links'))
			);
	
			$core->set_vars(array(
				'page_title'        => sprintf($user->lang('admin_title_prefix'), $core->config['guildtag'], $core->config['dkp_name']).': '.$user->lang('rli_imp_suc'),
				'template_path'     => $pm->get_data('raidlogimport', 'template_path'),
				'template_file'     => 'success.html',
				'display'           => true)
			);
		} else {
			unset($_POST);
			$check = $user->lang('rli_missing_values').'<br />';
			foreach($bools['false'] as $loc => $la) {
				if($la == 'miss') {
					$check .= $user->lang('rli_'.$loc.'_needed');
				}
				$check .= '<input type="submit" name="check'.$loc.'" value="'.$user->lang('rli_check'.$loc).'" class="mainoption" /><br />';
			}
			$tpl->assign_vars(array(
				'L_NO_IMP_SUC'	=> $user->lang('rli_imp_no_suc'),
				'CHECK'			=> $check)
			);
			$rli->destroy();
			$core->set_vars(array(
				'page_title'		=> sprintf($user->lang('admin_title_prefix'), $core->config['guildtag'], $core->config['dkp_name']).': '.$user->lang('rli_imp_no_suc'),
				'template_path'		=> $pm->get_data('raidlogimport', 'template_path'),
				'template_file'		=> 'check_input.html',
				'display'			=> true,
				)
			);
		}
	}

	function display($messages=array())
	{
		global $db, $core, $user, $tpl, $pm;
		global $SID, $myHtml, $rli;

		if($messages) {
			foreach($messages as $title => $message) {
				$type = ($title == 'rli_error' or $title == 'rli_no_mem_create') ? 'red' : 'green';
				if(is_array($message)) {
					$message = implode(',<br />', $message);
				}
				System_Message($message, $user->lang($title).':', $type);
			}
		}
		$tpl->assign_vars(array(
			'L_INSERT'		 => $user->lang('rli_dkp_insert'),
			'L_SEND'		 => $user->lang('rli_send'),
			'S_STEP1'        => true)
		);

		$core->set_vars(array(
			'page_title'        => sprintf($user->lang('admin_title_prefix'), $core->config['guildtag'], $core->config['dkp_name']).': '."DKP String",
			'template_path'     => $pm->get_data('raidlogimport', 'template_path'),
			'template_file'     => 'rli_step1.html',
			'display'           => true,
			)
		);
	}
}

$raidlogimport = new raidlogimport;
?>
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

// EQdkp required files/vars
define('EQDKP_INC', true);
define('IN_ADMIN', true);

$eqdkp_root_path = './../../../';
include_once('./../includes/common.php');

class raidlogimport extends EQdkp_Admin
{
	function raidlogimport()
    {
        global $db, $eqdkp, $user, $tpl, $pm;
        global $SID;

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
                'check'   => 'a_raidlogimport_dkp'),
            'null_sum' => array(
            	'name'	  => 'nullsum',
            	'process' => 'process_null_sum',
            	'check'	  => 'a_raidlogimport_dkp')
                )
        );
	}

	function process_raids()
	{
		global $db, $eqdkp, $user, $tpl, $pm;
		global $myHtml, $rli, $eqdkp_root_path;

		$db->query("DROP TABLE IF EXISTS item_rename;");

        if(isset($_POST['rest']))
        {
			$rli->parse_post();
        }
        if(isset($_POST['log']))
        {
          $_POST['log'] = trim(str_replace("&", "and", html_entity_decode($_POST['log'])));
          $dkpstring   = utf8_encode($_POST['log']);
          $raidxml     = simplexml_load_string($dkpstring);
		  if ($raidxml === false)
		  {
			  message_die($user->lang['xml_error']);
		  }
		  else
		  {
		      $rli->create_raids($raidxml);
		  }
		  if(isset($_POST['log_lang']))
		  {
		  	$rli->data['log_lang'] = $_POST['log_lang'];
		  }
        }//post or string

        //get events
        $rli->get_events();

		if(isset($_POST['raid_add']))
		{
			for($i=1; $i<=$_POST['raid_add']; $i++)
			{
				$rli->data['raids'][] = '';
			}
		}
		$att_raids = $rli->get_adj_raidkeys();
		foreach($rli->data['raids'] as $ky => $rai)
		{
			if(!($rli->config['attendence_raid'] AND ($ky === $att_raids['start'] OR $ky === $att_raids['end'])))
			{
			  if($_POST['checkraid'] == $user->lang['rli_calc_note_value'])
			  {
              	$rli->diff = $rai['diff'];
              	$temp = $rli->get_diff_event($rai['event']);
              	$rai['event'] = $temp['event'];
              	$rai['timebonus'] = $temp['timebonus'];
				$rai['bosskills'] = $rli->get_diff_bosskills($rai['bosskills']);
                $rai['value'] = $rli->get_raidvalue($rai['begin'], $rai['end'], $rai['bosskills'], $rai['timebonus'], $rai['event']);
				if($rai['bosskills'] AND $rli->config['raidcount'] != 2)
				{
					$rai['note'] = $rli->get_note($rai['bosskills']);
				}
			  }
			}
			if(isset($rai['bosskill_add']))
			{
				for($i=1; $i<=$rai['bosskill_add']; $i++)
				{
					$rai['bosskills'][] = '';
				}
			}
			$tpl->assign_block_vars('raids', array(
                'COUNT'     => $ky,
                'START_DATE'=> date('d.m.y', $rai['begin']),
                'START_TIME'=> date('H:i:s', $rai['begin']),
                'END_DATE'	=> date('d.m.y', $rai['end']),
                'END_TIME'	=> date('H:i:s', $rai['end']),
				'EVENT'		=> $myHtml->DropDown('raids['.$ky.'][event]', $rli->events['name'], $rai['event']),
				'TIMEBONUS'	=> $rai['timebonus'],
				'VALUE'		=> $rai['value'],
				'NOTE'		=> $rai['note'],
				'DIFF'.$rai['diff'] => TRUE
				)
			);
			if(is_array($rai['bosskills']))
			{
              foreach($rai['bosskills'] as $xy => $bk)
              {
                $tpl->assign_block_vars('raids.bosskills', array(
                    'BK_SELECT' => $rli->boss_dropdown($bk['note'], $ky, $xy),
                    'BK_TIME'   => date('H:i:s', $bk['time']),
                    'BK_DATE'   => date('d.m.y', $bk['time']),
                    'BK_VALUE'  => $bk['bonus'],
                    'BK_KEY'    => $xy)
                );
              }
            }
		}

		$tpl->assign_vars(array(
			'DATA' =>htmlspecialchars(serialize($rli->data), ENT_QUOTES),
			'USE_TIMEDKP' => ($rli->config['use_dkp'] & 2),
			'USE_BOSSDKP' => ($rli->config['use_dkp'] & 1),
			'S_NULL_SUM'  => $rli->config['null_sum'],
			'L_RV_NS'	  => $user->lang['raidval_nullsum_later'])
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
		global $myHtml, $rli, $jquery;

		$rli->parse_post();
		if(isset($_POST['members_add']))
		{
            $h = max(array_keys($rli->data['members']));
			for($i=1; $i<=$_POST['members_add']; $i++)
			{
				$h++;
				$rli->data['members'][$h]['times'] = array();
				$rli->data['members'][$h]['name'] = '';
			}
		}
		$aliases = rli_get_aliases();
		$att_raids = $rli->get_adj_raidkeys();
		foreach($rli->data['members'] as $key => $member)
		{
			if($rli->config['s_member_rank'] & 1)
			{
				$member['rank'] = $rli->rank_suffix($member['name']);
			}
			if(isset($aliases[$member['name']]))
			{
            	$rli->data['members'][$key]['alias'] = $member['name'];
                $member['alias'] = $member['name'];
                $rli->data['members'][$key]['name'] = $aliases[$member['name']];
            	$member['name'] = $aliases[$member['name']];
            }
         	//Ursprungsname hinter das Mitglied schreiben
          	$alias = "";
               if(isset($member['alias'])) {
               $alias = "(".$member['alias'].")";
               $alias .= '<input type="hidden" name="members['.$key.'][alias]" value="'.$member['alias'].'" />';
            }
            if($_POST['checkmem'] == $user->lang['rli_go_on'].' ('.$user->lang['rli_checkmem'].')')
            {
            	$member['raid_list'] = array();
	           	foreach($rli->data['raids'] as $u => $ra)
	           	{
	           		//check events
	           		if($rli->member_in_raid($key, $ra['begin'], $ra['end']))
	           		{
	           			$member['raid_list'][] = $u;
	           		}
		        }
		        $begin = $rli->data['raids'][1]['begin'];
		        $end = $rli->data['raids'][count($rli->data['raids'])]['end'];
	            $att_dkp = $rli->calculate_attendence($member['times'], $begin, $end);
	            $member['att_dkp_begin'] = $att_dkp['begin'];
	            if($att_dkp['begin'] AND !in_array($att_raids['begin'], $member['raid_list'])) {
	            	$member['raid_list'][] = $att_raids['start'];
	            }
	            $member['att_dkp_end'] = $att_dkp['end'];
	            if($att_dkp['end'] AND !in_array($att_raids['end'], $member['raid_list'])) {
	            	$member['raid_list'][] = $att_raids['end'];
	            }
	        }
	        if($rli->config['member_display'] AND extension_loaded('gd'))
	        {
	        	$raid_list = $rli->get_checkraidlist($member['raid_list'], $key);
	        }
	        else
	        {
	        	$raid_list = '<td>'.$jquery->MultiSelect('members['.$key.'][raid_list]', $rli->raidlist(), $member['raid_list'], '200', '200', false, 'members_'.$key.'_raidlist').'</td>';
	        }
           	$tpl->assign_block_vars('player', array(
               	'MITGLIED' => $member['name'],
                'ALIAS'    => $alias,
                'RAID_LIST'=> $raid_list,
                'ATT_BEGIN'=> ($member['att_dkp_begin']) ? 'checked="checked"' : '',
                'ATT_END'  => ($member['att_dkp_end']) ? 'checked="checked"' : '',
                'ZAHL'     => $eqdkp->switch_row_class(),
                'KEY'	   => $key,
                'NR'	   => $key +1,
                'RANK'	   => ($rli->config['s_member_rank'] & 1) ? $rli->rank_suffix($member['name']) : '')
           	);
        }//foreach members

		//show raids
		foreach($rli->data['raids'] as $key => $raid)
		{
			$tpl->assign_block_vars('raids', raids2tpl($key, $raid));
		}

        //language
        $tpl->assign_vars(lang2tpl());

		$tpl->assign_vars(array(
			'DATA'			 => htmlspecialchars(serialize($rli->data), ENT_QUOTES),
			'S_ATT_BEGIN'	 => ($rli->config['attendence_begin'] > 0 AND !$rli->config['attendence_raid']) ? TRUE : FALSE,
			'S_ATT_END'		 => ($rli->config['attendence_end'] > 0 AND !$rli->config['attendence_raid']) ? TRUE : FALSE,
			'MEMBER_DISPLAY' => ($rli->config['member_display']) ? $rli->th_raidlist : false,
			'RAIDCOUNT'		 => ($rli->config['member_display']) ? count($rli->data['raids']) : 1,
			'RAIDCOUNT3'	 => ($rli->config['member_display']) ? count($rli->data['raids']) +2 : 3)
		);

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
		global $myHtml, $rli, $jquery;

		$rli->parse_post();
        $p = ($rli->data['loots']) ? max(array_keys($rli->data['loots'])) : 0;
		if(isset($_POST['items_add']))
		{
			for($i=1; $i<=$_POST['items_add']; $i++)
			{
				$p++;
				$rli->data['loots'][$p] = array();
			}
		}

		//show raids&members
		foreach($rli->data['raids'] as $key => $raid)
		{
			if($rli->config['null_sum'])
			{
				$raid['value'] = $rli->get_nsr_value($key, false, true);
			}
			$tpl->assign_block_vars('raids', raids2tpl($key, $raid));
		}

		$members = array(); //for select in loots
		foreach($rli->data['members'] as $key => $member)
		{
			$tpl->assign_block_vars('player', mems2tpl($key, $member, $rli->data));
            $members['name'][$member['name']] = $member['name'];
            if(isset($member['alias']))
            {
            	$aliase[$member['alias']] = $member['name'];
            }
		}
		if($rli->config['null_sum'] AND $rli->config['auto_minus'])
		{
			$sql = "SELECT member_name FROM __members ORDER BY member_name ASC;";
			$mem_res = $db->query($sql);
			while ( $mrow = $db->fetch_record($mem_res) )
			{
				$members['name'][$mrow['member_name']] = $mrow['member_name'];
			}
		}

		//add disenchanted and bank
        $members['name']['disenchanted'] = 'disenchanted';
        $members['name']['bank'] = 'bank';
        $maxkey = 0;

        //create rename_table
		$sql = "CREATE TABLE IF NOT EXISTS item_rename (
				`id` INT NOT NULL PRIMARY KEY,
				`item_name` VARCHAR(255) NOT NULL,
				`item_id` INT NOT NULL,
				`item_name_trans` VARCHAR(255) NOT NULL);";
		$db->query($sql);

        $sql = "SELECT * FROM item_rename;";
        $result = $db->query($sql);
        while($row = $db->fetch_record($result))
        {
        	$loot_cache[$row['id']]['name'] = $row['item_name'];
        	$loot_cache[$row['id']]['trans'] = $row['item_name_trans'];
        	$loot_cache[$row['id']]['itemid'] = $row['item_id'];
        }

		if(is_array($rli->data['loots']))
		{
          $start = 0;
          $end = $p+1;
		  if($vars = ini_get('suhosin.post.max_vars'))
		  {
			$vars = $vars - 5;
			$dic = $vars/6;
			settype($dic, 'int');
			$page = 1;

			if(!(strpos($_POST['checkitem'], $user->lang['rli_itempage']) === FALSE))
			{
				$page = str_replace($user->lang['rli_itempage'], '', $_POST['checkitem']);
			}
			if($page >= 1)
			{
				$start = ($page-1)*$dic;
				$page++;
			}
			$end = $start+$dic;
		  }
		  $rli->iteminput2tpl($loot_cache, $start, $end, $members, $aliase);
		}

		if($rli->config['null_sum'])
		{
			$next_button = '<input type="submit" name="nullsum" value="'.$user->lang['rli_go_on'].' ('.$user->lang['check_raidval'].')" class="mainoption" />';
		}
		else
		{
			if($rli->config['deactivate_adj'])
			{
				$next_button = '<input type="submit" name="insert" value="'.$user->lang['rli_go_on'].' ('.$user->lang['rli_insert'].')" class="mainoption" />';
			}
			else
			{
				$next_button = '<input type="submit" name="checkadj" value="'.$user->lang['rli_go_on'].' ('.$user->lang['rli_checkadj'].')" class="mainoption" />';
			}
		}

		if($end <= $p AND $end)
		{
			$next_button = '<input type="submit" name="checkitem" value="'.$user->lang['rli_itempage'].(($page) ? $page : 2).'" class="mainoption" />';
		}
        elseif($end+$dic >= $p AND $dic)
        {
            $next_button .= ' <input type="submit" name="checkitem" value="'.$user->lang['rli_itempage'].(($page) ? $page : 2).'" class="mainoption" />';
        }
		$tpl->assign_vars(array(
			'DATA'			=> htmlspecialchars(serialize($rli->data), ENT_QUOTES),
			'S_ATT_BEGIN'	=> ($rli->config['attendence_begin'] > 0 AND !$rli->config['attendence_raid']) ? TRUE : FALSE,
			'S_ATT_END'		=> ($rli->config['attendence_end'] > 0 AND !$rli->config['attendence_raid']) ? TRUE : FALSE,
			'MAXCOUNT'		=> ($end < p) ? $end : $p,
			'MINCOUNT'		=> $start,
			'LANGFROM'		=> $rli->data['log_lang'],
			'LANGTO'		=> $rli->config['item_save_lang'],
			'NEXT_BUTTON'	=> $next_button,
			'JBOX_RENAME_WINDOW' => $jquery->Dialog_URL('RenameItemsWindow', 'Rename Items', "renameitems.php?actual=0&langto='+langto+'&langfrom='+langfrom+'&count='+count+'", '440', '330'))
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
		global $myHtml, $rli;

		$db->query("DROP TABLE IF EXISTS item_rename;");

		$rli->parse_post();

		if(isset($_POST['adjs_add']))
		{
			for($i=1; $i<=$_POST['adjs_add']; $i++)
			{
				$rli->data['adjs'][] = '';
			}
		}

		//show raids, members & items
		foreach($rli->data['raids'] as $key => $raid)
		{
			$tpl->assign_block_vars('raids', raids2tpl($key, $raid));
		}
		foreach($rli->data['members'] as $key => $member)
		{
			$tpl->assign_block_vars('player', mems2tpl($key, $member, $rli->data));
		}
		if(is_array($rli->data['loots']))
		{
		  foreach($rli->data['loots'] as $loot)
		  {
			$tpl->assign_block_vars('loots', items2tpl($loot));
		  }
		}

        //get events
        $rli->get_events();

		if(isset($rli->data['adjs']))
		{
			$sql = "SELECT member_name FROM __members ORDER BY member_name ASC;";
			$res = $db->query($sql);
			$members = array();
			while ($row = $db->fetch_record($res))
			{
				$members[$row['member_name']] = $row['member_name'].(($rli->config['s_member_rank'] & 4) ? $rli->rank_suffix($row['member_name']) : '');
			}
			foreach($rli->data['members'] as $member)
			{
				$members_r[$member['name']] = $member['name'].(($rli->config['s_member_rank'] & 4) ? $rli->rank_suffix($member['name']) : '');
				if(isset($member['alias']))
				{
					$adj_alias[$member['alias']] = $member['name'];
				}
			}
			$members = array_merge($members_r, $members);
			foreach($rli->data['adjs'] as $a => $adj)
			{
				if(isset($adj_alias[$adj['member']]))
				{
					$adj['member'] = $adj_alias[$adj['member']];
				}
				$ev_sel = (isset($adj['event'])) ? $adj['event'] : '';
				if(runden($adj['value']) === 0)
				{
					unset($data['adjs'][$a]);
				}
				else
				{
				  $tpl->assign_block_vars('adjs', array(
					'MEMBER'	=> $myHtml->DropDown('adjs['.$a.'][member]', $members, $adj['member'], '', '', true),
					'EVENT'		=> $myHtml->DropDown('adjs['.$a.'][event]', $rli->events['name'], $ev_sel, '', '', true),
					'NOTE'		=> $adj['reason'],
					'VALUE'		=> round($adj['value'], 2),
					'CLASS'		=> $eqdkp->switch_row_class(),
					'KEY'		=> $a,
					'READONLY'	=> ($adj['reason'] == $user->lang['am_name']) ? "readonly='readonly'" : "")
				  );
				}
			}
		}

		$tpl->assign_vars(array(
			'DATA'			=> htmlspecialchars(serialize($rli->data), ENT_QUOTES),
			'S_ATT_BEGIN'	=> ($rli->config['attendence_begin'] > 0 AND !$rli->config['attendence_raid']) ? TRUE : FALSE,
			'S_ATT_END'		=> ($rli->config['attendence_end'] > 0 AND !$rli->config['attendence_raid']) ? TRUE : FALSE)
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

	function process_null_sum()
	{
		global $db, $eqdkp, $user, $tpl, $pm, $SID, $rli, $myHtml;

		$db->query("DROP TABLE IF EXISTS item_rename");

		$rli->parse_post();

		//show members & items
		foreach($rli->data['members'] as $key => $member)
		{
			$tpl->assign_block_vars('player', mems2tpl($key, $member, $rli->data));
		}
		foreach($rli->data['loots'] as $loot)
		{
			$tpl->assign_block_vars('loots', items2tpl($loot));
		}

       	if($rli->config['null_sum'] == 1)
        {
			foreach($rli->data['raids'] as $raid_key => $raid)
			{
				$temp = $rli->get_nsr_value($raid_key, true);
				$raid['value'] = $temp['v'];
                $formul[$raid_key] = $temp['p'].'/'.$temp['c'];
            	$tpl->assign_block_vars('raids', raids2tpl($raid_key, $raid, $formul));
			}
		}

		if($rli->config['null_sum'] == 2)
		{
			$rli->data['adjs'] = array();
			$maxkey = 0;
			foreach($rli->data['members'] as $key => $member)
			{
				$members[$member['name']] = $member['name'].(($rli->config['s_member_rank'] & 4) ? $rli->rank_suffix($member['name']) : '');
				$maxkey = ($maxkey < $key) ? $key : $maxkey;
			}
			$sql = "SELECT member_name FROM __members";
			$res = $db->query($sql);
			while ($row = $db->fetch_record($res))
			{
				if(!in_array($row['member_name'], $members))
				{
					$maxkey++;
					$members[$row['member_name']] = $row['member_name'].(($rli->config['s_member_rank'] & 4) ? $rli->rank_suffix($row['member_name']) : '');
					$rli->data['members'][$maxkey]['name'] = $row['member_name'];
					$rli->data['members'][$maxkey]['raid_list'] = array();
				}
			}
        	//get events
        	$rli->get_events();

			foreach($rli->data['raids'] as $raid_key => $raid)
			{
				$temp = $rli->get_nsr_value($raid_key, TRUE);
				$raid['value'] = $temp['v'];
                $formul[$raid_key] = $temp['p'].'/'.$temp['c'];
                $u = 0;
				foreach($rli->data['members'] as $key => $member)
				{
					if(!in_array($raid_key, $member['raid_list']))
					{
						$rli->data['adjs'][$u]['member'] = $member['name'];
						$rli->data['adjs'][$u]['value'] = $raid['value'];
						$rli->data['adjs'][$u]['event'] = $raid['event'];
						$rli->data['adjs'][$u]['reason'] = 'Raid '.date('d.m.y', $raid['begin']);
						$u++;
					}
				}
            	$tpl->assign_block_vars('raids', raids2tpl($raid_key, $raid, $formul));
			}
			foreach($rli->data['adjs'] as $a => $adj)
			{
				if(isset($adj_alias[$adj['member']]))
				{
					$adj['member'] = $adj_alias[$adj['member']];
				}
				$ev_sel = (isset($adj['event'])) ? $adj['event'] : '';
				if(runden($adj['value']) === 0)
				{
					unset($data['adjs'][$a]);
				}
				else
				{
				  $tpl->assign_block_vars('adjs', array(
					'MEMBER'	=> $myHtml->DropDown('adjs['.$a.'][member]', $members, $adj['member'], '', '', true),
					'EVENT'		=> $myHtml->DropDown('adjs['.$a.'][event]', $rli->events['name'], $ev_sel, '', '', true),
					'NOTE'		=> $adj['reason'],
					'VALUE'		=> $adj['value'],
					'CLASS'		=> $eqdkp->switch_row_class(),
					'KEY'		=> $a)
				  );
				}
			}
		}

		$tpl->assign_vars(array(
			'DATA'			=> htmlspecialchars(serialize($rli->data), ENT_QUOTES),
			'S_ATT_BEGIN'	=> ($rli->config['attendence_begin'] > 0 AND !$rli->config['attendence_raid']) ? TRUE : FALSE,
			'S_ATT_END'		=> ($rli->config['attendence_end'] > 0 AND !$rli->config['attendence_raid']) ? TRUE : FALSE,
			'S_NULL_SUM_2'	=> ($rli->config['null_sum'] == 2) ? TRUE : FALSE,
			'FORMEL'		=> $user->lang['form_null_sum_'.$rli->config['null_sum']])
		);

		//language
		$tpl->assign_vars(lang2tpl());
		$eqdkp->set_vars(array(
        	'page_title'        => sprintf($user->lang['admin_title_prefix'], $eqdkp->config['guildtag'], $eqdkp->config['dkp_name']).': Daten prüfen',
            'template_path'     => $pm->get_data('raidlogimport', 'template_path'),
            'template_file'     => 'rli_step2ns.html',
            'display'           => true)
        );
	}

	function insert_log()
	{
		global $db, $eqdkp, $user, $tpl, $pm;
		global $SID, $rli, $conf_plus, $eqdkp_root_path, $pdc;

		$rli->parse_post();
		$isok = true;

		$bools = $rli->check_data();
		if(!isset($bools['false']))
		{
		  $sql = "SELECT member_id, member_name, member_firstraid, member_status, member_lastraid FROM __members";
		  $result = $db->query($sql);
		  $members = array(0 => NULL);
		  while ( $row = $db->fetch_record($result) )
		  {
		  	$members[$row['member_id']]['name'] = $row['member_name'];
		  	$members[$row['member_id']]['firstraid'] = $row['member_firstraid'];
			$members[$row['member_id']]['status'] = $row['member_status'];
			$members[$row['member_id']]['lastraid'] = $row['member_lastraid'];
		  }
		  $db->query("START TRANSACTION");
		  $newraidid = 0;
		  $newraidid = $db->query_first("SELECT MAX(`raid_id`) FROM __raids;");

		  foreach($rli->data['raids'] as $raid_key => $raid)
		  {
			$newraidid++;
			$rli->data['raids'][$raid_key]['id'] = $newraidid;
			$sql = "INSERT INTO __raids
        		      (`raid_id`, `raid_name`, `raid_date`, `raid_note`, `raid_value`, `raid_added_by`)
        		    VALUES
        		      ('".$newraidid."', '".mysql_real_escape_string($raid['event'])."', '".$raid['begin']."', '".mysql_real_escape_string($raid['note'])."', '".number_format($raid['value'], 2, '.','')."', 'Raid-Log-Import (by ".$user->data['username'].")');";
        	if(!$db->query($sql))
        	{
        		echo "raids_table: <br />".$sql."<br />";
        		$isok = false;
        		break;
        	}
		  }

		  if ($isok)
		  {
	        $sql = "SHOW COLUMNS
	                        FROM __items
	                        LIKE 'game_itemid';";
	        $result = $db->query($sql);
	        if ($db->num_rows($result) > 0) {
	            $item_gameidExists = TRUE;
	        }
       		$lootdkp = array();
       		if(is_array($rli->data['loots']))
       		{
	          foreach($rli->data['loots'] as $key => $loot)
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
	                $sql .= ",`game_itemid`";
	            }
	            $sql .= ") VALUES
	                     ('".mysql_real_escape_string(mysql_real_escape_string($loot['name']))."',
	                      '".mysql_real_escape_string($loot['player'])."',
	                      '".mysql_real_escape_string($rli->data['raids'][$loot['raid']]['id'])."',
	                      '".mysql_real_escape_string(number_format($loot['dkp'], 2, '.', ''))."',
	                      '".mysql_real_escape_string($loot['time'])."',
	                      'DKP Import(by ".$user->data['username'].")',
	                      '".mysql_real_escape_string($this->gen_group_key($loot['name'], $loot['time'], $rli->data['raids'][$loot['raid']]['id']))."'";
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
		   if(!($rli->config['null_sum'] == 1 OR $rli->config['deactivate_adj']))
		   {
		  	if(is_array($rli->data['adjs']))
		  	{
			  	foreach($rli->data['adjs'] as $adj)
			  	{
			  	  if($adj['value'])
			  	  {
					$group_key = $this->gen_group_key($this->time, stripslashes($adj['reason']), $adj['value'], mysql_real_escape_string($adj['event']));
					$sql = "INSERT INTO __adjustments
								(`adjustment_value`, `adjustment_date`, `member_name`, `adjustment_reason`, `adjustment_added_by`, `adjustment_group_key`, `raid_name`)
						    VALUES
						    	('".number_format($adj['value'], 2, '.', '')."', '".$rli->data['raids'][1]['begin']."', '".$adj['member']."', '".mysql_real_escape_string($adj['reason'])."', 'Raid-Log-Import (by ".$user->data['username'].")', '".$group_key."', '".mysql_real_escape_string($adj['event'])."');";
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
		   }
		  }

		  if($isok)
		  {
          	foreach($rli->data['members'] as $dmem)
            {
		  		foreach($members as $id => $mem)
		  		{
		  			if($mem['name'] == $dmem['name'])
		  			{
		  				$members[$id] = array();
		  				$members[$id] = $dmem;
		  				$members[$id]['status'] = $mem['status'];
		  				$members[$id]['firstraid'] = $mem['firstraid'];
		  				$members[$id]['lastraid'] = $mem['lastraid'];
		  			}
		  		}
		  		if(!deep_in_array($dmem['name'], $members))
		  		{
		  			$members[] = $dmem;
		  		}
		  	}
			foreach ($members as $key => $member)
			{
			  if($member['name'] != '')
			  {
                if($isok)
                {
                	$dkp = 0;
                	$sql = array();
            		if(!isset($member['status']) AND !isset($member['alias']))
            		{
            			$answer = create_member($member, $rli->config['new_member_rank']);
            			if($answer[1])
            			{
			            	$this->log_insert(array(
			            		'log_type'	 => $answer[1]['header'],
			            		'log_action' => $answer[1])
			            	);
			            }
		            	$message[] = $answer[2];
					}
					//raid_attendence
					if(isset($member['raid_list']))
					{
					  if(!$member['status'])
					  { //active
					  	$sql[] = "member_status = '1'";
					  }
                      $keys = array_keys($member['raid_list']);
                      if(!$member['firstraid'])
                      {
                      	$sql[] = "member_firstraid = '".$rli->data['raids'][$member['raid_list'][$keys[0]]]['begin']."'";
                      }
                      krsort($keys);
                      $sql[] = "member_lastraid = '".$rli->data['raids'][$member['raid_list'][$keys[0]]]['end']."'";
                      $member_raid_count = 0;
					  foreach($rli->data['raids'] as $raid_key => $raid)
					  {
						if(in_array($raid_key, $member['raid_list']) AND $isok)
						{
							$rsql = "INSERT INTO __raid_attendees
										(`raid_id`, `member_name`)
								    VALUES
								    	('".$raid['id']."', '".$member['name']."');";
							if(!$db->query($rsql))
							{
								echo "raid_attendees_table: <br />".$rsql."<br />";
								$isok = false;
								break;
							}
                        	$dkp = $dkp + $raid['value'];
                        	$member_raid_count++;
						}
					  }
					  //update raidcount
					  if($member_raid_count)
					  {
					  	$sql[] = "member_raidcount = member_raidcount + '".$member_raid_count."'";
					  }
					}
                    //inactive
                    if($member['status'] AND !isset($member['raid_list']))
                    {
                        $now = time();
                        if(($now - $eqdkp->config['inactive_period']*24*3600) > $member['lastraid'] AND $member['lastraid'])
                        { //move member to inactive
                        	$sql[] = "member_status = '0'";
                        }
                    }

                    //dkp
					if(!$conf_plus['pk_multidkp'])
					{
						if($dkp)
						{
							$sql[] = "member_earned = member_earned + '".number_format($dkp, 2, '.', '')."'";
						}
						if($lootdkp[$member['name']])
						{
							$sql[] = "member_spent = member_spent + '".number_format($lootdkp[$member['name']], 2, '.', '')."'";
						}
						if($adj_dkp[$member['name']])
						{
							$sql[] = "member_adjustment = member_adjustment + '".number_format($adj_dkp[$member['name']], 2, '.', '')."'";
						}
					}

					if($sql[1])
					{
						$esql = "UPDATE __members SET ";
						$esql .= implode(', ', $sql);
						$esql .= " WHERE
						   			member_name = '".$member['name']."' LIMIT 1;";
						if(!$db->query($esql))
						{
							echo "members_table: <br />".$esql."<br />";
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

			//logging
			//raids
            $num = count($rli->data['members'])-1;
            $member_str = $rli->data['members'][0]['name'];
            for ( $i=1; $i<=$num; $i++ )
            {
                $member_str .= ", ".$rli->data['members'][$i]['name'];
            }
			foreach($rli->data['raids'] as $key => $raid)
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
			}

            //items
            if(is_array($rli->data['loots']))
            {
              	foreach ($rli->data['loots'] as $loot)
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
            if(!$rli->config['deactivate_adj'])
            {
              if(is_array($rli->data['adjs']))
              {
              	foreach($rli->data['adjs'] as $adj)
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
            }
            foreach($log_actions as $log_action)
            {
            	$this->log_insert(array(
            		'log_type'	 => $log_action['header'],
            		'log_action' => $log_action)
            	);
            }
			$db->query("COMMIT;");
			$pm->do_hooks('/plugins/raidlogimport/admin/dkp.php');
			$pdc->del_suffix('dkp');
			$message[] = $user->lang['bz_save_suc'];
		  }
		  else
		  {
			$db->query("ROLLBACK;");
			$message[] = $user->lang['rli_error'];
		  }

		  #$success['rli_insert'] = $message;
		  #$this->display_form($success);
		  foreach($message as $answer)
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
		  	'DATA'			=> htmlspecialchars(serialize($rli->data), ENT_QUOTES))
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

	function display_form($messages=array())
    {
        global $db, $eqdkp, $user, $tpl, $pm;
        global $SID, $myHtml, $rli;

		if($messages)
		{
			foreach($messages as $title => $message)
			{
            	$type = 'green';
				if($title == 'rli_error' or $title == 'rli_no_mem_create')
				{
					$type = 'red';
				}
				if(is_array($message))
				{
					$message = implode(',<br />', $message);
				}
				System_Message($message, $user->lang[$title].':', $type);
			}
		}
		$lang_array = array('de' => 'de', 'en' => 'en', 'fr' => 'fr', 'ru' => 'ru', 'es' => 'es');
		switch($user->lang['lang'])
		{
			case "german": $sel_lang = "de"; break;
			case "english": $sel_lang = "en"; break;
			case "russian": $sel_lang = "ru"; break;
			case "french": $sel_lang = "fr"; break;
			case "spanish": $sel_lang = "es"; break;
		}
        $tpl->assign_vars(array(
            'F_PARSE_LOG'    => 'dkp.php' . $SID,
            'L_INSERT'		 => $user->lang['rli_dkp_insert'],
            'L_SEND'		 => $user->lang['rli_send'],
            'S_STEP1'        => true,
            'WHICH_LANG'	 => ($rli->config['parser'] == 'plus') ? '' : $user->lang['rli_log_lang'],
            'LANG_SELECT'	 => ($rli->config['parser'] == 'plus') ? '' : $myHtml->DropDown('log_lang', $lang_array, $sel_lang))
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
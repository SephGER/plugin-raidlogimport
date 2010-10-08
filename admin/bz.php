<?php
 /*
 * Project:     EQdkp-Plus Raidlogimport
 * License:     Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
 * Link:		http://creativecommons.org/licenses/by-nc-sa/3.0/
 * -----------------------------------------------------------------------
 * Began:       2008
 * Date:        $Date: 2009-05-15 18:51:52 +0200 (Fr, 15 Mai 2009) $
 * -----------------------------------------------------------------------
 * @author      $Author: hoofy_leon $
 * @copyright   2008-2009 hoofy_leon
 * @link        http://eqdkp-plus.com
 * @package     raidlogimport
 * @version     $Rev: 4868 $
 *
 * $Id: bz.php 4868 2009-05-15 16:51:52Z hoofy_leon $
 */

// EQdkp required files/vars
define('EQDKP_INC', true);
define('IN_ADMIN', true);

$eqdkp_root_path = './../../../';

include_once('./../includes/common.php');

class Bz extends EQdkp_Admin
{

	function bz()
    {
        global $db, $core, $user, $tpl, $pm;
        global $SID;

        parent::eqdkp_admin();

        $this->assoc_buttons(array(
            'form' => array(
                'name'    => '',
                'process' => 'display_form',
                'check'   => 'a_raidlogimport_bz'),
            'save' => array(
                'name'    => 'save',
                'process' => 'bz_save',
                'check'   => 'a_raidlogimport_bz'),
            'delete' => array(
            	'name'	  => 'delete',
            	'process' => 'bz_del',
            	'check'	  => 'a_raidlogimport_bz'),
            'update' => array(
            	'name' 	  => 'update',
            	'process' => 'bz_upd',
            	'check'	  => 'a_raidlogimport_bz')
                )
        );
	}

	function bz_save()
	{
		global $db, $core, $user, $tpl, $SID, $pm;

		$message = "";
		if($_POST['save'] == $user->lang['bz_save'])
		{
        	if(isset($_POST['bz_string']) AND isset($_POST['bz_note']) AND isset($_POST['bz_bonus']) AND isset($_POST['bz_sort']))
            {
            	$data = array();
            	$ids = array();
				foreach($_POST['bz_type'] as $id => $type)
				{
                    if($id != 'neu')
                    {
                    	$ids[] = $id;
                    }
                    $data[$id]['type'] = $type;
                    $data[$id]['string'] = $_POST['bz_string'][$id];
                    $data[$id]['note'] = $_POST['bz_note'][$id];
                    $data[$id]['bonus'] = number_format(floatvalue($_POST['bz_bonus'][$id]), 2, '.', '');
                    $data[$id]['sort'] = $_POST['bz_sort'][$id];
                    if($type == 'boss')
                    {
                        $data[$id]['tozone'] = $_POST['bz_tozone'][$id];
                    }
				}
				//get old data
				$old_data = array();
				if(($selected = count($ids)-1)>=0)
				{
					$sql = "SELECT bz_id, bz_string, bz_note, bz_bonus, bz_type, bz_tozone, bz_sort FROM __raidlogimport_bz WHERE ";
					for($i=0; $i<$selected; $i++)
					{
						$sql .= "bz_id = '".$ids[$i]."' OR ";
					}
					$sql .= "bz_id = '".$ids[$selected]."';";
					$result = $db->query($sql);
					if($result)
					{
						while( $row = $db->fetch_record($result) )
						{
							$old_data[$row['bz_id']]['type'] = $row['bz_type'];
							$old_data[$row['bz_id']]['string'] = $row['bz_string'];
							$old_data[$row['bz_id']]['note'] = $row['bz_note'];
							$old_data[$row['bz_id']]['bonus'] = $row['bz_bonus'];
							$old_data[$row['bz_id']]['tozone'] = $row['bz_tozone'];
							$old_data[$row['bz_id']]['sort'] = $row['bz_sort'];
						}
					}
					else
					{
						message_die('SQL-Error! Query: '.$sql);
					}
				}

				foreach($data as $id => $vs)
				{
                    if($id == "neu")
                    {
                        $sql = "INSERT INTO __raidlogimport_bz
                                    (bz_string, bz_note, bz_bonus, bz_type, bz_sort, bz_tozone)
                                VALUES
                                    ('".mysql_real_escape_string($vs['string'])."', '".mysql_real_escape_string($vs['note'])."', '".$vs['bonus']."', '".$vs['type']."', '".$vs['sort']."'";
                        if($vs['type'] == "boss")
                        {
                            $sql .= ", '".$vs['tozone']."');";
                        }
                        else
                        {
                            $sql .= ", '');";
                        }
						$log_action = array(
							'header'		=> '{L_ACTION_RAIDLOGIMPORT_BZ_ADD}',
	                        '{L_BZ_TYPE}'   => $vs['type'],
							'{L_BZ_STRING}'	=> $vs['string'],
							'{L_BZ_BNOTE}'	=> $vs['note'],
							'{L_BZ_BONUS}'	=> $vs['bonus'],
							'{L_BZ_TOZONE}' => $vs['tozone'],
							'{L_BZ_SORT}'	=> $vs['sort']
						);
                    }
					else
					{
						$sql = "UPDATE __raidlogimport_bz SET
								bz_string = '".mysql_real_escape_string($vs['string'])."',
								bz_note = '".mysql_real_escape_string($vs['note'])."',
								bz_bonus = '".$vs['bonus']."',
								bz_type = '".$vs['type']."',
								bz_sort = '".$vs['sort']."'";
						if($vs['type'] == 'boss')
						{
							$sql .= ", bz_tozone = '".$vs['tozone']."'";
						}
						$sql .= " WHERE bz_id = '".$id."';";
						$log_action = array(
							'header'		=> '{L_ACTION_RAIDLOGIMPORT_BZ_UPD}',
	                        '{L_BZ_TYPE}'   => $old_data[$id]['type']." => ".$vs['type'],
							'{L_BZ_STRING}'	=> $old_data[$id]['string']." => ".$vs['string'],
							'{L_BZ_BNOTE}'	=> $old_data[$id]['note']." => ".$vs['note'],
							'{L_BZ_BONUS}'	=> $old_data[$id]['bonus']." => ".$vs['bonus'],
							'{L_BZ_BONUS}'	=> $old_data[$id]['sort']." => ".$vs['sort'],
							'{L_BZ_TOZONE}' => $old_data[$id]['tozone']." => ".$vs['tozone']
						);
					}
					$send = $db->query($sql);
					if($send)
					{
						$message['bz_save_suc'][] = $vs['note'];
						$this->log_insert(array(
							'log_type'	 => $log_action['header'],
							'log_action' => $log_action)
						);
					}
					else
					{
						$message['bz_no_save'][] = $vs['note'].": SQL-Error! Query: <br />".$sql;
					}
				}
			}
			else
			{
				message_die($user->lang['bz_missing_values']);
			}
		}
		elseif($_POST['save'] == $user->lang['bz_yes'] AND isset($_POST['del']))
		{
			$sel = "SELECT bz_id, bz_string, bz_note, bz_bonus, bz_type, bz_tozone FROM __raidlogimport_bz WHERE ";
			$sql = "DELETE FROM __raidlogimport_bz WHERE ";
            $selected = count($_POST['del'])-1;
			for($i=0; $i<$selected; $i++)
			{
				$sql .= "bz_id = '".$_POST['del'][$i]."' OR ";
				$sel .= "bz_id = '".$_POST['del'][$i]."' OR ";
			}
			$sql .= "bz_id = '".$_POST['del'][$selected]."';";
			$sel .= "bz_id = '".$_POST['del'][$selected]."';";

			$selres = $db->query($sel);
			if($selres)
			{
				$data = array();
				while ( $row = $db->fetch_record($selres) )
				{
					$data[$row['bz_id']]['string'] = $row['bz_string'];
					$data[$row['bz_id']]['note'] = $row['bz_note'];
					$data[$row['bz_id']]['bonus'] = $row['bz_bonus'];
					$data[$row['bz_id']]['type'] = $row['bz_type'];
					$data[$row['bz_id']]['tozone'] = $row['bz_tozone'];
					$data[$row['bz_id']]['sort'] = $row['bz_sort'];
				}
				$result = $db->query($sql);
                if($result)
	            {
                    foreach($_POST['del'] as $id)
                    {
                    	$message['bz_del_suc'][] = $data[$id]['note'];
						//logging
						$log_action = array(
							'header'		=> '{L_ACTION_RAIDLOGIMPORT_BZ_DEL}',
                            '{L_BZ_TYPE}'   => $data[$id]['type'],
							'{L_BZ_STRING}'	=> $data[$id]['string'],
							'{L_BZ_NOTE}'	=> $data[$id]['note'],
							'{L_BZ_BONUS}'	=> $data[$id]['bonus'],
							'{L_BZ_TOZONE}' => $data[$id]['tozone'],
							'{L_BZ_SORT}'	=> $data[$id]['sort']
						);
						$this->log_insert(array(
							'log_type'	 => $log_action['header'],
							'log_action' => $log_action)
						);
					}
				}
                else
            	{
                    $message['bz_no_del'][] = "SQL-Error! Query: <br />".$sql;
                }
			}
			else
			{
				$message['bz_no_del'][] = "SQL-Error! Query: <br />".$sql;
			}
		}
		else
		{
			redirect('plugins/raidlogimport/admin/bz.php');
		}
		$this->display_form($message);
	}

	function bz_del() {
		global $core, $user, $tpl, $pm, $in, $pdh;

		if(isset($_POST['zone_id'])) {
			$zids = $in->getArray('zone_id', 'int');
			foreach($zids as $id) {
				$tpl->assign_block_vars('zdel_list', array(
					'STRING'	=> $pdh->geth('rli_zone', 'string', array($id)),
					'ID'		=> $id)
				);
			}
		}
		if(isset($_POST['boss_id'])) {
			$bids = $in->getArray('boss_id', 'int');
			foreach($bids as $id) {
				$tpl->assign_block_vars('bdel_list', array(
					'STRING'	=> $pdh->geth('rli_boss', 'string', array($id)),
					'ID'		=> $id)
				);
			}
		}
		if(!($zids OR $bids)) {
			message_die($user->lang['bz_no_id']);
		}

		$tpl->assign_vars(array(
			'L_BZ_DEL' 	 	=> $user->lang['bz_del'],
			'L_CONFIRM_DEL' => $user->lang['bz_confirm_del'],
			'L_YES'	 		=> $user->lang['bz_yes'],
			'L_NO'			=> $user->lang['bz_no'])
		);

		$core->set_vars(array(
            'page_title'        => sprintf($user->lang['admin_title_prefix'], $core->config['guildtag'], $core->config['dkp_name']).': '.$user->lang['rli_bz_bz'],
            'template_path'     => $pm->get_data('raidlogimport', 'template_path'),
            'template_file'     => 'bz_del.html',
            'display'           => true,
           	)
       	);
	}
	
	private function get_upd_data($type, $id) {
		global $html, $pdh, $core;
		return array(
				'ID'			=> $id,
				'STRING'		=> implode($core->config['raidlogimport']['bz_parse'], $pdh->get('rli_'.$type, 'string', array($id))),
				'NOTE'			=> $pdh->get('rli_'.$type, 'note', array($id)),
				'BONUS'			=> ($type == 'boss') ? $pdh->get('rli_boss', 'bonus', array($id)) : '',
				'TIMEBONUS'		=> $pdh->get('rli_'.$type, 'timebonus', array($id)),
				'DIFF'			=> $pdh->get('rli_'.$type, 'diff', array($id)),
				'SORT'			=> $pdh->get('rli_'.$type, 'sort', array($id)),
				'BSELECTED'		=> ($type == 'boss') ? 'selected="selected"' : '',
				'ZSELECTED'		=> ($type == 'zone') ? 'selected="selected"' : '',
				'DIFF_ARRAY'	=> $html->DropDown("diff[".$id."]", $this->diff_drop, $pdh->get('rli_'.$type, 'diff', array($id)), '', '', true),
				'ZONE_ARRAY'	=> $html->DropDown("tozone[".$id."]", $this->zone_drop, (($type == 'boss') ? $pdh->get('rli_boss', 'tozone', array($id)) : $id), '', '', true),
				'CLASS'			=> $core->switch_row_class());
	}

	public function bz_upd() {
		global $db, $core, $user, $tpl, $pm, $html, $in, $pdh, $game;
		if(!$this->zone_drop) $this->zone_drop = $pdh->aget('rli_zone', 'html_string', 0, array($pdh->get('rli_zone', 'id_list')));
		if(!$this->diff_drop) $this->diff_drop = array($user->lang['diff_0'], $user->lang['diff_1'], $user->lang['diff_2'], $user->lang['diff_3'], $user->lang['diff_4']);
		if(isset($_POST['boss_id'])) {
			$bids = $in->getArray('boss_id', 'int');
			foreach($bids as $id) {
				$tpl->assign_block_vars('upd_list', $this->get_upd_data('boss', $id));
			}
		}
		if(isset($_POST['zone_id'])) {
			$zids = $in->getArray('zone_id', 'int');
			foreach($zids as $id) {
				$tpl->assign_block_vars('upd_list', $this->get_upd_data('zone', $id));
			}
		}
		if(!($bids OR $zids)) {
			$tpl->assign_block_vars('upd_list', array(
				'ID'		=> 'neu',
				'STRING'	=> '',
				'NOTE'		=> '',
				'BONUS'		=> '',
				'TIMEBONUS'	=> '',
				'SORT'		=> '',
				'BSELECTED'	=> '',
				'ZSELECTED'	=> '',
				'DIFF_ARRAY' => $html->DropDown("diff[neu]", $this->diff_drop, '', '', '', true),
				'ZONE_ARRAY' => $html->DropDown("tozone[neu]", $this->zone_drop, '', '', '', true),
				'CLASS'		=> $core->switch_row_class())
			);
		}

		$tpl->assign_vars(array(
			'L_BZ_UPD'		=> $user->lang['bz_upd'],
			'L_TYPE'		=> $user->lang['bz_type'],
			'L_STRING'		=> $user->lang['bz_string'],
			'L_NOTE_EVENT'	=> $user->lang['bz_note_event'],
			'L_BONUS'		=> $user->lang['bz_bonus'],
			'L_TIMEBONUS'	=> $user->lang['bz_timebonus'],
			'S_DIFF'		=> ($game->get_game() == 'wow') ? true : false,
			'L_DIFF'		=> $user->lang['difficulty'],
			'L_SAVE'		=> $user->lang['bz_save'],
			'L_ZONE'		=> $user->lang['bz_zone_s'],
			'L_BOSS'		=> $user->lang['bz_boss_s'],
			'L_TOZONE'		=> $user->lang['bz_tozone'],
			'L_SORT'		=> $user->lang['bz_sort'])
		);
		$core->set_vars(array(
            'page_title'        => sprintf($user->lang['admin_title_prefix'], $core->config['guildtag'], $core->config['dkp_name']).': '.$user->lang['rli_bz_bz'],
            'template_path'     => $pm->get_data('raidlogimport', 'template_path'),
            'template_file'     => 'bz_upd.html',
            'display'           => true,
           	)
       	);
	}

	public function display_form($messages=array()) {
		global $tpl, $core, $pm, $user, $pdh;

		if($messages) {
			$type = 'green';
			foreach($messages as $title => $mess) {
				if(preg_match('#_no_#', $title)) {
					$type = 'red';
				}
				foreach($mess as $message) {
					System_Message($message, $user->lang[$title], $type);
				}
			}
		}
		$bosses = $pdh->get('rli_boss', 'id_list');
		$tozone = array();
		foreach($bosses as $boss_id) {
			$tozone[$pdh->get('rli_boss', 'tozone', array($boss_id))][] = $boss_id;
		}
		$data = array();
		foreach($tozone as $zone_id => $boss_ids) {
			$data[$pdh->get('rli_zone', 'sort', array($zone_id))] = array(
            	'ZID'		=> $zone_id,
	            'ZSTRING'	=> ($zone_id) ? $pdh->geth('rli_zone', 'string', array($zone_id)) : $user->lang['bz_boss_oz'],
                'ZTIMEBONUS'=> ($zone_id) ? $pdh->get('rli_zone', 'timebonus', array($zone_id)) : '',
                'ZNOTE'		=> ($zone_id) ? $pdh->get('rli_zone', 'note', array($zone_id)) : '',
				'bosses'	=> array()
            );
			foreach($boss_ids as $boss_id) {
				$data[$pdh->get('rli_zone', 'sort', array($zone_id))]['bosses'][$pdh->get('rli_boss', 'sort', array($boss_id))] = array(
					'BID'		=> $boss_id,
					'BSTRING'	=> $pdh->geth('rli_boss', 'string', array($boss_id)),
					'BNOTE'		=> $pdh->get('rli_boss', 'note', array($boss_id)),
					'BBONUS'	=> $pdh->get('rli_boss', 'bonus', array($boss_id)),
					'BTIMEBONUS'=> $pdh->get('rli_boss', 'timebonus', array($boss_id))
				);
			}
        }
		ksort($data);
		foreach($data as $zone) {
			$bosses = $zone['bosses'];
			ksort($bosses);
			unset($zone['bosses']);
			$tpl->assign_block_vars('zone_list', $zone);
			foreach($bosses as $boss) {
				$tpl->assign_block_vars('zone_list.boss_list', array_merge($boss, array('CLASS' => $core->switch_row_class())));
			}
		}
		$tpl->assign_vars(array(
			'L_BZ'			=> $user->lang['rli_bz_bz'],
			'L_STRING'		=> $user->lang['bz_string'],
			'L_NOTE'		=> $user->lang['bz_note_event'],
			'L_BONUS'		=> $user->lang['bz_bonus'],
			'L_TIMEBONUS'	=> $user->lang['bz_timebonus'],
			'L_UPDATE'		=> $user->lang['bz_update'],
			'L_DELETE'		=> $user->lang['bz_delete'])
		);

		$core->set_vars(array(
            'page_title'        => sprintf($user->lang['admin_title_prefix'], $core->config['guildtag'], $core->config['dkp_name']).': '.$user->lang['rli_bz_bz'],
            'template_path'     => $pm->get_data('raidlogimport', 'template_path'),
            'template_file'     => 'bz.html',
            'display'           => true,
           	)
       	);
    }
}
$bosszone = new Bz;
$bosszone->process();
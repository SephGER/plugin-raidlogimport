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
 * @copyright   2008-2010 hoofy_leon
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

class Bz extends EQdkp_Admin
{

	function bz()
    {
        global $db, $eqdkp, $user, $tpl, $pm;
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
		global $db, $eqdkp, $user, $tpl, $SID, $pm;

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
                    $data[$id]['bonusph'] = number_format(floatvalue($_POST['bz_bonusph'][$id]), 2, '.', '');
                    $data[$id]['sort'] = ($_POST['bz_tozone'][$id]) ? $_POST['bz_tozone'][$id].".".$_POST['bz_sort'][$id] : $_POST['bz_sort'][$id];
                    $data[$id]['diff'] = $_POST['bz_diff'][$id];
				}
				//get old data
				$old_data = array();
				if(($selected = count($ids)-1)>=0)
				{
					$sql = "SELECT bz_id, bz_string, bz_note, bz_bonus, bz_type, bz_bonusph, bz_diff, bz_sort FROM __raidlogimport_bz WHERE ";
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
							$old_data[$row['bz_id']]['bonusph'] = $row['bz_bonusph'];
							$old_data[$row['bz_id']]['diff'] = $row['bz_diff'];
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
                                    (bz_string, bz_note, bz_bonus, bz_type, bz_sort, bz_bonusph, bz_diff)
                                VALUES
                                    ('".$db->escape($vs['string'])."', '".$db->escape($vs['note'])."', '".$vs['bonus']."', '".$vs['type']."', '".$vs['sort']."', '".$vs['bonusph']."', '".$vs['diff']."');";
						$log_action = array(
							'header'		=> '{L_ACTION_RAIDLOGIMPORT_BZ_ADD}',
	                        '{L_BZ_TYPE}'   => $vs['type'],
							'{L_BZ_STRING}'	=> $vs['string'],
							'{L_BZ_BNOTE}'	=> $vs['note'],
							'{L_BZ_BONUS}'	=> $vs['bonus'],
							'{L_BZ_BONUSPH}' => $vs['bonusph'],
							'{L_BZ_DIFF}' 	=> $vs['diff'],
							'{L_BZ_SORT}'	=> $vs['sort']
						);
                    }
					else
					{
						$sql = "UPDATE __raidlogimport_bz SET
								bz_string = '".$db->escape($vs['string'])."',
								bz_note = '".$db->escape($vs['note'])."',
								bz_bonus = '".$vs['bonus']."',
								bz_type = '".$vs['type']."',
								bz_sort = '".$vs['sort']."',
								bz_bonusph = '".$vs['bonusph']."',
								bz_diff = '".$vs['diff']."'
								WHERE bz_id = '".$id."';";
						$log_action = array(
							'header'		=> '{L_ACTION_RAIDLOGIMPORT_BZ_UPD}',
	                        '{L_BZ_TYPE}'   => $old_data[$id]['type']." => ".$vs['type'],
							'{L_BZ_STRING}'	=> $old_data[$id]['string']." => ".$vs['string'],
							'{L_BZ_BNOTE}'	=> $old_data[$id]['note']." => ".$vs['note'],
							'{L_BZ_BONUS}'	=> $old_data[$id]['bonus']." => ".$vs['bonus'],
							'{L_BZ_SORT}'	=> $old_data[$id]['sort']." => ".$vs['sort'],
							'{L_BZ_BONUSPH}' => $old_data[$id]['bonusph']." => ".$vs['bonusph'],
							'{L_BZ_DIFF}' => $old_data[$id]['diff']." => ".$vs['diff'],
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
			$sel = "SELECT bz_id, bz_string, bz_note, bz_bonus, bz_type, bz_bonusph, bz_diff FROM __raidlogimport_bz WHERE ";
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
					$data[$row['bz_id']]['bonusph'] = $row['bz_bonusph'];
					$data[$row['bz_id']]['sort'] = $row['bz_sort'];
					$data[$row['bz_id']]['diff'] = $row['bz_diff'];
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
							'{L_BZ_BONUSPH}' => $data[$id]['bonusph'],
							'{L_BZ_SORT}'	=> $data[$id]['sort'],
							'{L_BZ_DIFF}'	=> $data[$id]['diff']
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

	function bz_del()
	{
		global $db, $eqdkp, $user, $tpl, $SID, $pm;

		if(isset($_POST['bz_id']))
		{
			$sql = "SELECT bz_id, bz_string FROM __raidlogimport_bz WHERE ";
			$selected = count($_POST['bz_id'])-1;
			for($i=0; $i<$selected; $i++)
			{
				$sql .= "bz_id = '".$_POST['bz_id'][$i]."' OR ";
			}
			$sql .= "bz_id = '".$_POST['bz_id'][$selected]."';";
			$result = $db->query($sql);
			while ( $row = $db->fetch_record($result))
			{
				$tpl->assign_block_vars('del_list', array(
					'STRING' => $row['bz_string'],
					'BZ_ID'  => $row['bz_id'])
				);
			}
		}
		else
		{
			message_die($user->lang['bz_no_id']);
		}

		$tpl->assign_vars(array(
			'L_BZ_DEL' 	 	=> $user->lang['bz_del'],
			'L_CONFIRM_DEL' => $user->lang['bz_confirm_del'],
			'L_YES'	 		=> $user->lang['bz_yes'],
			'L_NO'			=> $user->lang['bz_no'])
		);

		$eqdkp->set_vars(array(
            'page_title'        => sprintf($user->lang['admin_title_prefix'], $eqdkp->config['guildtag'], $eqdkp->config['dkp_name']).': '.$user->lang['rli_bz_bz'],
            'template_path'     => $pm->get_data('raidlogimport', 'template_path'),
            'template_file'     => 'bz_del.html',
            'display'           => true,
           	)
       	);
	}

	function bz_upd()
	{
		global $db, $eqdkp, $user, $tpl, $SID, $pm, $myHtml;

		$sql1 = "SELECT bz_id, bz_note, bz_diff FROM __raidlogimport_bz WHERE bz_type = 'zone';";
		$result1 = $db->query($sql1);
		$zones = array();
        $zones[NULL] = $user->lang['bz_no_zone'];
		while ( $row1 = $db->fetch_record($result1) )
		{
			$zones[$row1['bz_id']] = $row1['bz_note'].(($eqdkp->config['default_game'] == 'WoW' AND $row1['bz_diff']) ? ' &nbsp;&nbsp;&nbsp; ('.$user->lang['diff_'.$row1['bz_diff']].')' : '');
		}
		$diffs = array(0 => $user->lang['bz_no_diff']);
		if($eqdkp->config['default_game'] == 'WoW') {
			for($i=1; $i<=4; $i++) {
				$diffs[$i] = $user->lang['diff_'.$i];
			}
		}
		if(isset($_POST['bz_id']))
		{
			$sql = "SELECT bz_id, bz_string, bz_bonus, bz_note, bz_type, bz_bonusph, bz_sort, bz_diff FROM __raidlogimport_bz WHERE ";
			$selected = count($_POST['bz_id'])-1;
			for($i=0; $i<$selected; $i++)
			{
				$sql .= "bz_id = '".$_POST['bz_id'][$i]."' OR ";
			}
			$sql .= "bz_id = '".$_POST['bz_id'][$selected]."';";
			$result = $db->query($sql);
			while ( $row = $db->fetch_record($result) ) {
				if(strpos($row['bz_sort'], '.')) {
					list($tozone, $sort) = explode('.', $row['bz_sort']);
				} else {
					$sort = $row['bz_sort'];
				}
				$tpl->assign_block_vars('upd_list', array(
					'BZ_ID' 	 => $row['bz_id'],
					'BZ_STRING'  => $row['bz_string'],
					'BZ_BONUS'	 => $row['bz_bonus'],
					'BZ_BONUSPH' => $row['bz_bonusph'],
					'BZ_NOTE'	 => $row['bz_note'],
					'BZ_SORT'	 => $sort,
					'B_SELECTED' => ($row['bz_type'] == 'boss') ? 'selected0"selected"' : '',
					'Z_SELECTED' => ($row['bz_type'] == 'zone') ? 'selected="selected"' : '',
					'ZONE_ARRAY' => $myHtml->DropDown("bz_tozone[".$row['bz_id']."]", $zones, $tozone,'','',true),
					'DIFF_ARRAY' => $myHtml->DropDown("bz_diff[".$row['bz_id']."]", $diffs, $row['bz_diff'],'','',true),
					'CLASS'		 => $eqdkp->switch_row_class())
				);
			}
		}
		else
		{
			$tpl->assign_block_vars('upd_list', array(
				'BZ_ID' 	 => 'neu',
				'BZ_STRING'  => '',
				'BZ_BONUS'	 => '',
				'BZ_BONUSPH' => '',
				'BZ_NOTE'	 => '',
				'BZ_SORT'	 => '',
				'Z_SELECTED' => false,
				'ZONE_ARRAY' => $myHtml->DropDown("bz_tozone[neu]", $zones, 0,'','',true),
				'DIFF_ARRAY' => $myHtml->DropDown("bz_diff[neu]", $diffs, 0,'','',true),
				'CLASS'		 => $eqdkp->switch_row_class())
			);
		}

		$tpl->assign_vars(array(
			'L_BZ_UPD'     => $user->lang['bz_upd'],
			'L_TYPE'	   => $user->lang['bz_type'],
			'L_STRING'	   => $user->lang['bz_string'],
			'L_NOTE_EVENT' => $user->lang['bz_note_event'],
			'L_BONUS'	   => $user->lang['bz_bonus'],
			'L_SAVE'	   => $user->lang['bz_save'],
			'L_ZONE'	   => $user->lang['bz_zone_s'],
			'L_BOSS'	   => $user->lang['bz_boss_s'],
			'L_TOZONE'	   => $user->lang['bz_tozone'],
			'L_DIFF'	   => $user->lang['difficulty'],
			'L_SORT'	   => $user->lang['bz_sort'],
			'L_BONUSPH'	   => $user->lang['bz_bonusph'])
		);
		$eqdkp->set_vars(array(
            'page_title'        => sprintf($user->lang['admin_title_prefix'], $eqdkp->config['guildtag'], $eqdkp->config['dkp_name']).': '.$user->lang['rli_bz_bz'],
            'template_path'     => $pm->get_data('raidlogimport', 'template_path'),
            'template_file'     => 'bz_upd.html',
            'display'           => true,
           	)
       	);
	}

	function display_form($messages=array())
	{
		global $tpl, $eqdkp, $pm, $db, $user, $SID;

		if($messages)
		{
			$type = 'green';
			foreach($messages as $title => $mess)
			{
				if(preg_match('#_no_#', $title))
				{
					$type = 'red';
				}
				foreach($mess as $message)
				{
					System_Message($message, $user->lang[$title], $type);
				}
			}
		}

		$sql = "SELECT bz_id, bz_string, bz_bonus, bz_note, bz_type, bz_bonusph, bz_diff, bz_sort FROM __raidlogimport_bz;";
		$result = $db->query($sql);

		$data = array();
		while ( $row = $db->fetch_record($result) ) {
			if(strpos($row['bz_sort'], '.') !== false) {
				list($tozone, $sort) = explode('.', $row['bz_sort']);
			} else {
				$sort = $row['bz_sort'];
				$tozone = false;
			}
			if($row['bz_type'] == 'zone') {
				$data['zone'][$row['bz_id']]['string'] = $row['bz_string'];
				$data['zone'][$row['bz_id']]['bonus'] = $row['bz_bonus'];
				$data['zone'][$row['bz_id']]['bonusph'] = $row['bz_bonusph'];
				$data['zone'][$row['bz_id']]['note'] = $row['bz_note'];
				$data['zone'][$row['bz_id']]['diff'] = $row['bz_diff'];
				$data['zone'][$row['bz_id']]['sort'] = $sort;
			} else {
				if($tozone !== false) {
					$data['tozone'][$tozone][] = array('id' => $row['bz_id'], 'sort' => $sort);
				}
				$data['boss'][$row['bz_id']]['string'] = $row['bz_string'];
				$data['boss'][$row['bz_id']]['bonus'] = $row['bz_bonus'];
				$data['boss'][$row['bz_id']]['bonusph'] = $row['bz_bonusph'];
				$data['boss'][$row['bz_id']]['note'] = $row['bz_note'];
				$data['boss'][$row['bz_id']]['diff'] = $row['bz_diff'];
				$data['boss'][$row['bz_id']]['sort'] = $sort;
			}
		}
        $bidsiz = array();
        function sort_my_bzs($a, $b) {
        	if($a['sort'] == $b['sort']) { return 0; }
        	return ($a['sort'] < $b['sort']) ? -1 : 1;
        }
        function sort_my_bzsa($a, $b) {
            if($a['sor'] == $b) { return 0; }
        	return ($a < $b) ? -1 : 1;
        }
        uasort($data['zone'], 'sort_my_bzs');
        uasort($data['boss'], 'sort_my_bzs');
		foreach($data['zone'] as $id => $values)
		{
			$bosses = '';
        	usort($data['tozone'][$id], 'sort_my_bzs');
            foreach($data['tozone'][$id] as $b_id)
            {
            	$b_id = $b_id['id'];
            	$bidsiz[] = $b_id;
				$bosses .= '<tr class="'.$eqdkp->switch_row_class().'">
					<td><input type="checkbox" name="bz_id[]" value="'.$b_id.'" /></td>
					<td>'.$data['boss'][$b_id]['string'].(($eqdkp->config['default_game'] == 'WoW' AND $data['boss'][$b_id]['diff']) ? ' &nbsp;&nbsp;&nbsp; ('.$user->lang['diff_'.$data['boss'][$b_id]['diff']].')' : '').'</td>
					<td>'.$data['boss'][$b_id]['note'].'</td>
					<td>'.$data['boss'][$b_id]['bonus'].'</td>
					<td>'.$data['boss'][$b_id]['bonusph'].'</td>
				</tr>';
            }
			$tpl->assign_block_vars('zone_list', array(
            	'Z_ID'      => $id,
	            'Z_STRING'  => $values['string'].(($eqdkp->config['default_game'] == 'WoW' AND $values['diff']) ? ' &nbsp;&nbsp;&nbsp; ('.$user->lang['diff_'.$values['diff']].')' : ''),
                'Z_BONUS'   => $values['bonus'],
                'Z_BONUSPH' => $values['bonusph'],
                'Z_NOTE'    => $values['note'],
                'BOSSES'	=> $bosses)
            );
        }
        $boss = false;
        foreach($data['boss'] as $id => $values)
        {
        	if(!in_array($id, $bidsiz))
        	{
            	$boss = true;
	        	$tpl->assign_block_vars('boss_list', array(
	            	'B_ID'      => $id,
		            'B_STRING'  => $values['string'].(($eqdkp->config['default_game'] == 'WoW' AND $values['diff']) ? ' &nbsp;&nbsp;&nbsp; ('.$user->lang['diff_'.$values['diff']].')' : ''),
	                'B_BONUS'   => $values['bonus'],
	                'B_BONUSPH'	=> $values['bonusph'],
	                'B_NOTE'    => $values['note'],
	                'CLASS'     => $eqdkp->switch_row_class())
	            );
	        }
	    }

		$db->free_result($result);

		$tpl->assign_vars(array(
			'L_BZ'			=> $user->lang['rli_bz_bz'],
			'L_BOSS'		=> $user->lang['bz_boss_oz'],
			'L_STRING'		=> $user->lang['bz_string'].(($eqdkp->config['default_game'] == 'WoW') ? ' &nbsp;&nbsp;&nbsp; ('.$user->lang['difficulty'].')' : ''),
			'L_BNOTE'		=> $user->lang['bz_note_event'],
			'L_BONUS'		=> $user->lang['bz_bonus'],
			'L_BONUSPH'		=> $user->lang['bz_bonusph'],
			'L_UPDATE'		=> $user->lang['bz_update'],
			'L_DELETE'		=> $user->lang['bz_delete'],
			'BOSS'			=> $boss)
		);

		$eqdkp->set_vars(array(
            'page_title'        => sprintf($user->lang['admin_title_prefix'], $eqdkp->config['guildtag'], $eqdkp->config['dkp_name']).': '.$user->lang['rli_bz_bz'],
            'template_path'     => $pm->get_data('raidlogimport', 'template_path'),
            'template_file'     => 'bz.html',
            'display'           => true,
           	)
       	);
    }
}
$bosszone = new Bz;
$bosszone->process();
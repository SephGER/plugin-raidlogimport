<?php
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
                    $data[$id]['bonus'] = $_POST['bz_bonus'][$id];
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
		global $db, $eqdkp, $user, $tpl, $SID, $pm, $html;

		$sql1 = "SELECT bz_id, bz_note FROM __raidlogimport_bz WHERE bz_type = 'zone';";
		$result1 = $db->query($sql1);
		$zones = array();
        $zones[NULL] = 'Keine Zone';
		while ( $row1 = $db->fetch_record($result1) )
		{
			$zones[$row1['bz_id']] = $row1['bz_note'];
		}
		if(isset($_POST['bz_id']))
		{
			$sql = "SELECT bz_id, bz_string, bz_bonus, bz_note, bz_type, bz_tozone, bz_sort FROM __raidlogimport_bz WHERE ";
			$selected = count($_POST['bz_id'])-1;
			for($i=0; $i<$selected; $i++)
			{
				$sql .= "bz_id = '".$_POST['bz_id'][$i]."' OR ";
			}
			$sql .= "bz_id = '".$_POST['bz_id'][$selected]."';";
			$result = $db->query($sql);
			while ( $row = $db->fetch_record($result) )
			{
				if($row['bz_type'] == 'boss')
				{
					$b_selected = 'selected="selected"';
					$z_selected = '';
				}
				else
				{
					$b_selected = '';
					$z_selected = 'selected="selected"';
				}
				$tpl->assign_block_vars('upd_list', array(
					'BZ_ID' 	 => $row['bz_id'],
					'BZ_STRING'  => $row['bz_string'],
					'BZ_BONUS'	 => $row['bz_bonus'],
					'BZ_NOTE'	 => $row['bz_note'],
					'BZ_SORT'	 => $row['bz_sort'],
					'B_SELECTED' => $b_selected,
					'Z_SELECTED' => $z_selected,
					'ZONE_ARRAY' => $html->DropDown("bz_tozone[".$row['bz_id']."]", $zones, $row['bz_tozone'],'','',true),
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
				'BZ_NOTE'	 => '',
				'BZ_SORT'	 => '',
				'B_SELECTED' => '',
				'Z_SELECTED' => '',
				'ZONE_ARRAY' => $html->DropDown("bz_tozone[neu]", $zones, $row['bz_tozone'],'','',true),
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
			'L_SORT'	   => $user->lang['bz_sort'])
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

		$sql = "SELECT bz_id, bz_string, bz_bonus, bz_note, bz_type, bz_tozone FROM __raidlogimport_bz ORDER BY bz_sort ASC;";
		$result = $db->query($sql);

		$data = array();
		while ( $row = $db->fetch_record($result) )
		{
			if($row['bz_type'] == 'zone')
			{
				$data['zone'][$row['bz_id']]['string'] = $row['bz_string'];
				$data['zone'][$row['bz_id']]['bonus'] = $row['bz_bonus'];
				$data['zone'][$row['bz_id']]['note'] = $row['bz_note'];
			}
			else
			{
				$data['boss'][$row['bz_id']]['string'] = $row['bz_string'];
				$data['boss'][$row['bz_id']]['bonus'] = $row['bz_bonus'];
				$data['boss'][$row['bz_id']]['note'] = $row['bz_note'];
				$data['tozone'][$row['bz_tozone']][] = $row['bz_id'];
			}
		}
        $bidsiz = array();
		foreach($data['zone'] as $id => $values)
		{
			$bosses = '';
            foreach($data['tozone'][$id] as $b_id)
            {
            	$bidsiz[] = $b_id;
				$bosses .= '<tr class="'.$eqdkp->switch_row_class().'">
					<td><input type="checkbox" name="bz_id[]" value="'.$b_id.'" /></td>
					<td>'.$data['boss'][$b_id]['string'].'</td>
					<td>'.$data['boss'][$b_id]['note'].'</td>
					<td>'.$data['boss'][$b_id]['bonus'].'</td>
				</tr>';
            }
			$tpl->assign_block_vars('zone_list', array(
            	'Z_ID'      => $id,
	            'Z_STRING'  => $values['string'],
                'Z_BONUS'   => $values['bonus'],
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
		            'B_STRING'  => $values['string'],
	                'B_BONUS'   => $values['bonus'],
	                'B_NOTE'    => $values['note'],
	                'CLASS'     => $eqdkp->switch_row_class())
	            );
	        }
	    }

		$db->free_result($result);

		$tpl->assign_vars(array(
			'L_BZ'			=> $user->lang['rli_bz_bz'],
			'L_BOSS'		=> $user->lang['bz_boss_oz'],
			'L_STRING'		=> $user->lang['bz_string'],
			'L_BNOTE'		=> $user->lang['bz_note_event'],
			'L_BONUS'		=> $user->lang['bz_bonus'],
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
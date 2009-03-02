<?php
// EQdkp required files/vars
define('EQDKP_INC', true);
define('IN_ADMIN', true);

include_once('common.php');


class Show_Alias extends EQdkp_Admin
{
	function Show_Alias()
	{
        global $db, $eqdkp, $user, $tpl, $pm;
        global $SID;

        parent::eqdkp_admin();

        $this->assoc_buttons(array(
        	'form' => array(
        		'name' 	   => '',
        		'process'  => 'display_form',
        		'check'    => 'a_raid_add')
        	)
        );
	}
	function display_form()
	{
        global $db, $eqdkp, $user, $tpl, $pm;
        global $SID, $table_prefix;

        if(isset($_POST['submit']) AND $_POST['submit'] == $user->lang['rli_edit']) {
            $sql = "SELECT `alias_name`, `alias_member_id`
                    FROM __raidlogimport_aliases
                    WHERE alias_id = '".$_POST['alias_id']."';";
            $aliasqry = $db->query($sql);
            $alias_member_id = 1;
            $alias = "";
            while($row = $db->fetch_record($aliasqry)) {
            	$alias_member_id = $row['alias_member_id'];
            	$alias = $row['alias_name'];
            }
    		// Member-Liste
	        $sql1 = "SELECT member_id, member_name
	        		FROM __members
	        		ORDER BY member_name;";
	        $result = $db->query($sql1);
	        while ( $row1 = $db->fetch_record($result)) {
	        	$member_list .= "<option value=\"".$row1['member_id']."\"";
	        	if($alias_member_id == $row1['member_id']) {
	        		$member_list .= "selected=\"selected\"";
	        	}
	        	$member_list .= ">".$row1['member_name']."</option>\n";
	        }
	        $db->free_result($result);

	        $tpl->assign_vars(array(
	        	'ALIAS'	      => $alias,
	        	'ALIAS_ID'    => $_POST['alias_id'],
	        	'MEMBER_LIST' => $member_list,
	        	// language
	        	'RLI_OF'		  => $user->lang['rli_of'],
	        	'RLI_GET'	  => $user->lang['rli_get'],
	        	'RLI_UPD'	  => $user->lang['rli_upd'],
	        	'RLI_DEL'	  => $user->lang['rli_del'],
	        	'RLI_EDIT'	  => $user->lang['rli_edit'])
	        );

			$eqdkp->set_vars(array(
	            'page_title'        => sprintf($user->lang['admin_title_prefix'], $eqdkp->config['guildtag'], $eqdkp->config['dkp_name']).': '.$user->lang['rli_edit'],
	            'template_path'     => $pm->get_data('raidlogimport', 'template_path'),
	            'template_file'     => 'editalias.html',
	            'display'           => true,
            	)
        	);
        } elseif(isset($_POST['submit']) AND $_POST['submit'] == $user->lang['rli_del']) {
        	$success = "";
        	$sql = "DELETE FROM __raidlogimport_aliases
        			WHERE alias_id = '".$_POST['alias_id']."';";
        	$delete = $db->query($sql);
        	if(!$delete) {
        		$success = $user->lang['rli_alias_no_delete'];
        	} else {
        		$success = $user->lang['rli_alias_delete'];
        	}
        	$tpl->assign_block_vars('sucs', array(
	        	'PART1'	=> $success,
	        	'CLASS'	=> $eqdkp->switch_row_class())
	        );
	        $links = array(
	        	'alias.php' . $SID . '&amp;mode=addalias' => $user->lang['rli_addalias'],
	        	'alias.php' . $SID . '&amp;mode=showalias' => $user->lang['rli_showalias']
	        );
	        foreach($links as $link => $page)
	        {
	        	$tpl->assign_block_vars('links', array(
	        		'SUC_LINK'	=> $link,
	        		'SUC_PAGE'	=> $page,
	        		'CLASS'		=> $eqdkp->switch_row_class())
	        	);
	        }
	        $tpl->assign_vars(array(
	        	'L_SUCCESS'	=> $user->lang['rli_suc'],
	        	'L_LINKS'	=> $user->lang['links'])
	        );
        	$eqdkp->set_vars(array(
        		'page_title'        => sprintf($user->lang['admin_title_prefix'], $eqdkp->config['guildtag'], $eqdkp->config['dkp_name']).': '."Alias Erfolg",
	            'template_path'     => $pm->get_data('raidlogimport', 'template_path'),
	            'template_file'     => 'success.html',
	            'display'           => true,
            	)
        	);
        } elseif(isset($_POST['submit']) AND $_POST['submit'] == $user->lang['rli_upd']) {
        	$success = "";
        	$sql = "UPDATE __raidlogimport_aliases
        			SET alias_name 		= '".$_POST['alias_name']."',
        				alias_member_id = '".$_POST['member_id']."'
        			WHERE alias_id = '".$_POST['alias_id']."';";
        	$update = $db->query($sql);
        	if(!$update) {
        		$success = $user->lang['rli_alias_no_update'];
        	} else {
        		$success = $user->lang['rli_alias_update'];
        	}
	        $tpl->assign_block_vars('sucs', array(
	        	'PART1'	=> $success,
	        	'CLASS'	=> $eqdkp->switch_row_class())
	        );
	        $links = array(
	        	'alias.php' . $SID . '&amp;mode=addalias' => $user->lang['rli_addalias'],
	        	'alias.php' . $SID . '&amp;mode=showalias' => $user->lang['rli_showalias']
	        );
	        foreach($links as $link => $page)
	        {
	        	$tpl->assign_block_vars('links', array(
	        		'SUC_LINK'	=> $link,
	        		'SUC_PAGE'	=> $page,
	        		'CLASS'		=> $eqdkp->switch_row_class())
	        	);
	        }
	        $tpl->assign_vars(array(
	        	'L_SUCCESS'	=> $user->lang['alias_suc'],
	        	'L_LINKS'	=> $user->lang['links'])
	        );
        	$eqdkp->set_vars(array(
        		'page_title'		=> sprintf($user->lang['admin_title_prefix'], $eqdkp->config['guildtag'], $eqdkp->config['dkp_name']).': '."Alias Erfolg",
        		'template_path'     => $pm->get_data('raidlogimport', 'template_path'),
	            'template_file'     => 'success.html',
	            'display'           => true,
            	)
        	);
        } else {
        	$sql = "SELECT `alias_id`, `alias_name`, `alias_member_id`
	        		FROM __raidlogimport_aliases
	        		ORDER BY alias_name;";
	        $aliase = $db->query($sql);
	        $number = 1;
	        while($row = $db->fetch_record($aliase)) {
	        	if($number != 2) {
	        		$number = 1;
	        	} else {
	        		$number = 2;
	        	}
	        	$alias_name = $row['alias_name'];
	        	$member_id = $row['alias_member_id'];
	        	$alias_id = $row['alias_id'];
	        	$memberqry = "SELECT `member_name`
	        			      FROM __members
	        			      WHERE member_id = '".$member_id."';";
	        	$member = $db->query($memberqry);
	        	$member_name = "";
	        	while($row1 = $db->fetch_record($member)) {
	        		$member_name = $row1['member_name'];
	        	}
	        	$tpl->assign_block_vars('alias_list', array(
	        		'ALIAS' 	=> $alias_name,
	        		'ALIAS_ID'  => $alias_id,
	        		'MEMBER'	=> $member_name,
	        		'NUMBER'	=> $number)
	        	);
	        	$number++;
	        }
	        $tpl->assign_vars(array(
	        	'RLI_OF'	  => $user->lang['rli_of'],
	        	'RLI_GET'  => $user->lang['rli_get'],
	        	'RLI_EDIT' => $user->lang['rli_edit'],
	        	'RLI_SHOWALIAS' => $user->lang['rli_showalias'])
	        );

			$eqdkp->set_vars(array(
	            'page_title'        => sprintf($user->lang['admin_title_prefix'], $eqdkp->config['guildtag'], $eqdkp->config['dkp_name']).': '.$user->lang['rli_showalias'],
	            'template_path'     => $pm->get_data('raidlogimport', 'template_path'),
	            'template_file'     => 'showalias.html',
	            'display'           => true,
            	)
        	);
		}
	}
}
?>
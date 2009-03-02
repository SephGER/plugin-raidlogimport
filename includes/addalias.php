<?php
// EQdkp required files/vars
define('EQDKP_INC', true);
define('IN_ADMIN', true);

include_once('common.php');


class Add_Alias extends EQdkp_Admin
{
	function add_alias()
	{
        global $db, $eqdkp, $user, $tpl, $pm;
        global $SID;

        parent::eqdkp_admin();

        $this->assoc_buttons(array(
        	'form' => array(
        		'name' 		=> '',
        		'process' 	=> 'display_form',
        		'check'		=> 'a_raidlogimport_alias'),
        	'submit' => array(
        		'name'		=> 'submit',
        		'process'   => 'process_add',
        		'check'     => 'a_raidlogimport_alias')
        	)
        );
    }
    function process_add()
    {
        global $db, $eqdkp, $user, $tpl, $pm;
        global $SID, $table_prefix;

        if(isset($_POST['submit']) AND $_POST['submit'] == $user->lang['rli_addalias']) {
        	if(isset($_POST['alias_name'])) {
        		$sql1 = "SELECT `alias_member_id`, `alias_name`
        				 FROM __raidlogimport_aliases
        				 WHERE alias_member_id = '".$_POST['member_id']."';";
        		$result = $db->query($sql1);
        		$names = array();
        		while ( $row = $db->fetch_record($result)) {
        			$names[] = $row['alias_name'];
        		}
        		if(!in_array($_POST['alias_name'], $names)) {
        			$sql = "INSERT INTO __raidlogimport_aliases (`alias_member_id`, `alias_name`)
        					VALUES ('".$_POST['member_id']."', '".$_POST['alias_name']."');";
        			$addalias = $db->query($sql);
        			$sql2 = "SELECT `member_name`
        					 FROM __members
        					 WHERE member_id = '".$_POST['member_id']."';";
        			$result = $db->query($sql2);
        			$membername = "string";
        			while ( $row = $db->fetch_record($result)) {
        				$membername = $row['member_name'];
        			}
        			$success = $user->lang['rli_of']." ".$_POST['alias_name']." ".$user->lang['rli_get']." ".$membername.".";
        		} else {
        			$success = $user->lang['rli_alias_exists'];
        		}
        	}
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
        	'page_title' => sprintf($user->lang['admin_title_prefix'], $eqdkp->config['guildtag'], $eqdkp->config['dkp_name']).': '.$user->lang['rli_addalias'],
            'template_path'     => $pm->get_data('raidlogimport', 'template_path'),
            'template_file'     => 'success.html',
            'display'           => true,
            )
        );
    }
    function display_form()
    {
        global $db, $eqdkp, $user, $tpl, $pm;
        global $SID;

        $sql = "SELECT member_id, member_name
        		FROM __members
        		ORDER BY member_name;";
        $result = $db->query($sql);

        while ( $row = $db->fetch_record($result)) {
        	 $tpl->assign_block_vars('members_row', array(
        	 	'VALUE'  => $row['member_id'],
        	 	'OPTION' => $row['member_name'])
        	 );
        }
        $tpl->assign_vars(array(
        	'RLI_ADDALIAS'	=> $user->lang['rli_addalias'],
        	'RLI_REPLACE'	=> $user->lang['rli_replace'],
        	'RLI_EARNER'		=> $user->lang['rli_earner'])
        );

        $db->free_result($result);

        $eqdkp->set_vars(array(
        	'page_title' => sprintf($user->lang['admin_title_prefix'], $eqdkp->config['guildtag'], $eqdkp->config['dkp_name']).': '.$user->lang['rli_menu_alias'],
            'template_path'     => $pm->get_data('raidlogimport', 'template_path'),
            'template_file'     => 'addalias.html',
            'display'           => true,
            )
        );
    }
}
?>
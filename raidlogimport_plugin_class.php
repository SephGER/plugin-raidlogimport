<?php
	/*
	 * @original version by kokson
	 * @copyright 2007 - 2009
	 * @further development by hoofy (since version 0.2)
	 *
	 */
if ( !defined('EQDKP_INC') )
{
    die('You cannot access this file directly.');
}

/**
 * EqDkp Plugin definition class.
 *
 */

class raidlogimport_Plugin_Class extends EQdkp_Plugin
{
	var $vstatus = 'Stable';
	var $version = '0.5.2.2';
	var $fwversion = '1.0.3';
	var $jqversion = '2.0.1';
	var $build = 4296;

    function raidlogimport_plugin_class($pm)
    {
        global $eqdkp_root_path, $user, $SID, $conf_plus, $eqdkp;

        $this->eqdkp_plugin($pm);
        $this->pm->get_language_pack('raidlogimport');
        //Load Game-Specific Language
        $lang_file = $eqdkp_root_path.'plugins/raidlogimport/games/'.$eqdkp->config['default_game'].'/language/'.$user->lang_name.'/lang_game.php';
        if(file_exists($lang_file))
        {
        	include($lang_file);
        	$user->lang = (@is_array($lang)) ? array_merge($user->lang, $lang) : $user->lang;
        }

        $this->add_data(array(
            'name'          => 'Raid-Log-Import',
            'code'          => 'raidlogimport',
            'path'          => 'raidlogimport',
            'contact'       => 'bloodyhoof@gmx.net',
            'template_path' => 'plugins/raidlogimport/templates/',
            'version'       => $this->version)
        );

    	$this->additional_data = array(
        	'author'            => 'Hoofy',
	        'description'       => $user->lang['raidlogimport_short_desc'],
	        'long_description'  => $user->lang['raidlogimport_long_desc'],
	        'homepage'          => 'http://www.eqdkp-plus.com',
	        'manuallink'        => ($user->lang_name != 'german') ? false : $eqdkp_root_path . 'plugins/raidlogimport/language/'.$user->lang_name.'/Manual.pdf',
	    );

		//permissions
		$this->add_permission('730', 'a_raidlogimport_config', 'N', $user->lang['configuration']);
		$this->add_permission('731', 'a_raidlogimport_dkp', 'N', $user->lang['raidlogimport_dkp']);
		$this->add_permission('732', 'a_raidlogimport_alias', 'N', $user->lang['raidlogimport_alias']);
		$this->add_permission('733', 'a_raidlogimport_bz', 'N', $user->lang['raidlogimport_bz']);

        //installation
        $steps = 3;
		for($i=1; $i<=$steps; $i++)
		{
        	$this->add_sql(SQL_INSTALL, $this->create_raidlogimport_tables($i."a"));
        	$this->add_sql(SQL_INSTALL, $this->create_raidlogimport_tables($i."b"));
        	//uninstallation
        	$this->add_sql(SQL_UNINSTALL, $this->create_raidlogimport_tables($i."a"));
        }

		//further installation
    	if (!($this->pm->check(PLUGIN_INSTALLED, 'raidlogimport')))
    	{
			//set permissions for installing user
			$perms = array('730', '731', '732', '733');
			$this->set_perms($perms);

			//insert config-data
			$config_data = array(
				'rli_inst_version'	=> $this->get_data('version'),
				'new_member_rank' 	=> '1',
				'raidcount'			=> '0', //0 = one raid, 1 = raid per hour, 2 = raid per boss, 3 = raid per hour and per boss
				'loottime'			=> '600', //time after bosskill to assign loot to boss (in seconds)
				'attendence_begin' 	=> '0',
				'attendence_end'	=> '0',
				'attendence_raid'	=> $conf_plus['pk_multidkp'], //create extra raid for attendence?
				'attendence_time'	=> '900', //time of inv (in seconds)
				'event_boss'		=> '0',  //exists an event per boss?
				'adj_parse'			=> ': ', //string, which separates the reason and the value for a adjustment in the note of a member
				'bz_parse'			=> ',',  //separator, which is used for separating the different strings of a boss or zone
				'parser'			=> 'ctrt',  //which format has the xml-string?
				'rli_upd_check'		=> '1',		//enable update check?
				'use_dkp'			=> '1',		//1: bossdkp, 2:zeitdkp, 4: event-dkp
				'null_sum'			=> '0', 	//use null-sum-system?
				'item_save_lang'	=> 'de',
				'deactivate_adj'	=> '0',
				'auto_minus'		=> '0',		//automatic minus
				'am_raidnum'		=> '3',		//if not joined last 3 raids
				'am_value'			=> '10',	//member looses 10dkp
				'am_value_raids'	=> '0',		//dkp-value depends on value of last 3 (or set number) of raids (option above becomes useless)
				'am_allxraids'		=> '0',		//reset raidcounter if member gains minus? (default off)
				'ignore_dissed'		=> '0',		//ignore disenchanted and bank loot?
				'member_miss_time' 	=> '300',	//time in secs member can miss without it being tracked
				's_member_rank'		=> '0',		//show member_rank? (0: no, 1: memberpage, 2: lootpage, 4: adjustmentpage, 3:member+lootpage, 5:adjustments+memberpage, 6: loot+adjustmentpage, 7: overall)
				'member_start'		=> '0',		//amount of DKP a member gains as an individual adjustment, when he is auto-created
				'member_start_event' => '0'		//event for Start-DKP
			);
			$this->insert_data($config_data);
			//add default bz_data
			switch($eqdkp->config['default_game'])
			{
				case "WoW":
					include_once($eqdkp_root_path.'plugins/raidlogimport/games/WoW/bz_sql.php');
					if(is_array($bz_data))
					{
						foreach($bz_data as $bz)
						{
							$sql = "INSERT INTO __raidlogimport_bz
									(bz_type, bz_string, bz_note, bz_bonus, bz_tozone, bz_sort)
									VALUES
									('".$bz[0]."', '".mysql_escape_string($bz[1])."', '".mysql_escape_string($bz[2])."', '".$bz[3]."', '".$bz[4]."', '".$bz[5]."');";
							$this->add_sql(SQL_INSTALL, $sql);
						}
					}
					$config_data = array(
						'hero'				=> '_25',	//suffix for hero
						'non_hero'			=> '_10',	//suffix for non-hero
						'dep_match'			=> '0'		//also append suffix to boss-note?
					);
					$this->insert_data($config_data);
					break;

			}
		}

        //log actions
        $this->add_log_action('{L_ACTION_RAIDLOGIMPORT_DKP}', $user->lang['action_raidlogimport_dkp']);
        $this->add_log_action('{L_ACTION_RAIDLOGIMPORT_BZ_UPD}', $user->lang['action_raidlogimport_bz_upd']);
        $this->add_log_action('{L_ACTION_RAIDLOGIMPORT_BZ_ADD}', $user->lang['action_raidlogimport_bz_add']);
        $this->add_log_action('{L_ACTION_RAIDLOGIMPORT_BZ_DEL}', $user->lang['action_raidlogimport_bz_del']);
        $this->add_log_action('{L_ACTION_RAIDLOGIMPORT_ALIAS_UPD}', $user->lang['action_raidlogimport_alias_upd']);
        $this->add_log_action('{L_ACTION_RAIDLOGIMPORT_ALIAS_DEL}', $user->lang['action_raidlogimport_alias_del']);
        $this->add_log_action('{L_ACTION_RAIDLOGIMPORT_ALIAS_ADD}', $user->lang['action_raidlogimport_alias_add']);
        $this->add_log_action('{L_ACTION_RAIDLOGIMPORT_CONFIG}', $user->lang['action_raidlogimport_config']);

		//menu
        $this->add_menu('admin_menu', $this->gen_admin_menu());
    }

    function create_raidlogimport_tables($step)
    {
    	global $table_prefix;

		switch($step)
		{
			case "1a":
                $sql = "DROP TABLE IF EXISTS __raidlogimport_aliases;";
                break;

			case "1b":
	    		$sql = "CREATE TABLE IF NOT EXISTS __raidlogimport_aliases (
					   	`alias_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					   	`alias_member_id` INT NOT NULL ,
					   	`alias_name` VARCHAR(50) NOT NULL
					   );";
				break;

			case "2a":
				$sql = "DROP TABLE IF EXISTS __raidlogimport_bz;";
				break;

			case "2b":
				$sql = "CREATE TABLE IF NOT EXISTS __raidlogimport_bz (
						`bz_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
						`bz_string` VARCHAR(255) NOT NULL,
						`bz_bonus` INT NOT NULL,
						`bz_note` VARCHAR(255) NOT NULL,
						`bz_type` ENUM ('boss', 'zone') NOT NULL,
						`bz_tozone` INT NULL,
						`bz_sort` INT NOT NULL
						);";
				break;

			case "3a":
				$sql = "DROP TABLE IF EXISTS __raidlogimport_config;";
				break;

			case "3b":
				$sql = "CREATE TABLE IF NOT EXISTS __raidlogimport_config (
						`config_name` VARCHAR(255) NOT NULL PRIMARY KEY,
						`config_value` VARCHAR(255) NOT NULL
						);";
				break;

		}
		return $sql;
	}

    function gen_admin_menu()
    {
        if ( $this->pm->check(PLUGIN_INSTALLED, 'raidlogimport') )
        {
            global $db, $user, $SID;

             $url_prefix = ( EQDKP_VERSION < '1.3.2' ) ? $eqdkp_root_path : '';
		     $admin_menu = array(
		    		'raidlogimport' => array(
		    		99 => './../../plugins/raidlogimport/images/rli_logo.png',
		            0 => $user->lang['raidlogimport'],
		            1 => array(
		            	'link' => $url_prefix . 'plugins/' . $this->get_data('path') . '/admin/settings.php',
		            	'text' => $user->lang['settings'],
		            	'check' => 'a_raidlogimport_config'),
		            2 => array(
		            	'link' => $url_prefix . 'plugins/' . $this->get_data('path') . '/admin/bz.php',
		            	'text' => $user->lang['raidlogimport_bz'],
		            	'check' => 'a_raidlogimport_bz'),
		            3 => array(
		            	'link' => $url_prefix . 'plugins/' . $this->get_data('path') . '/admin/dkp.php',
		            	'text' => $user->lang['raidlogimport_dkp'],
		            	'check' => 'a_raidlogimport_dkp'),
		            4 => array(
		            	'link' => $url_prefix . 'plugins/' . $this->get_data('path') . '/admin/alias.php',
		            	'text' => $user->lang['raidlogimport_alias'],
		            	'check' => 'a_raidlogimport_alias')
		        ),
		           'raids' => array(
		           					99 => 'calendar.png',
                                    0 => $user->lang['raids'],
                                    1 => array('link' => 'admin/addraid.php' . $SID,
                                               'text' => $user->lang['add'],
                                               'check' => 'a_raid_add'),
                                    2 =>  array(
								            	'link' => $url_prefix . 'plugins/' . $this->get_data('path') . '/admin/dkp.php',
								            	'text' => $user->lang['raidlogimport_dkp'],
								            	'check' => 'a_raidlogimport_dkp'),
                                    3 => array('link' => 'admin/listraids.php' . $SID,
                                               'text' => $user->lang['list'],
                                               'check' => 'a_raid_')
                )
		     );

            return $admin_menu;
        }
        return;
    }

    function set_perms($perms, $perm_value='Y')
    {
    	global $table_prefix, $db, $user;
		if($user->data['user_id'] != ANONYMOUS)
		{
			foreach ($perms as $perm)
			{
		    	$sql = "INSERT INTO `__auth_users` VALUES('".$user->data['user_id']."', '".$perm."', '".$perm_value."');";
    			$this->add_sql(SQL_INSTALL, $sql);
    			$sql = "UPDATE `".$table_prefix."auth_users`
    					SET auth_setting='".$perm_value."'
    					WHERE user_id='".$user->data['user_id']."' AND auth_id='".$perm."';";
    			$this->add_sql(SQL_INSTALL, $sql);
  			}
		}
	}

	function insert_data($data)
	{
		global $db;
		foreach($data as $config_name => $config_value)
		{
			$sql = "INSERT INTO __raidlogimport_config
					 (config_name, config_value)
				    VALUES
				     ('".$config_name."', '".$config_value."');";
			$this->add_sql(SQL_INSTALL, $sql);
		}
	}
}
?>
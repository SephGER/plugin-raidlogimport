<?php
/*
* Project:     EQdkp-Plus Raidlogimport
* License:     Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
* Link:		http://creativecommons.org/licenses/by-nc-sa/3.0/
* -----------------------------------------------------------------------
* Began:       2008
* Date:        $Date: 2009-07-05 18:49:25 +0200 (So, 05 Jul 2009) $
* -----------------------------------------------------------------------
* @author      $Author: hoofy_leon $
* @copyright   2008-2009 hoofy_leon
* @link        http://eqdkp-plus.com
* @package     raidlogimport
* @version     $Rev: 5173 $
*
* $Id: raidlogimport_plugin_class.php 5173 2009-07-05 16:49:25Z hoofy_leon $
*/

if ( !defined('EQDKP_INC') ) {
	die('You cannot access this file directly.');
}

class raidlogimport_Plugin_Class extends EQdkp_Plugin {
	public $vstatus = 'Stable';
	public $version = '0.6.0.0';
	public $build = 0;
	
	public function pre_install() {
		global $core;
		//initialize config
		$core->config_set($this->create_default_configs(), '', $this->get_data('code'));
		$sqls = $this->create_install_sqls();
		foreach($sqls as $sql) {
			$this->add_sql(SQL_INSTALL, $sql);
		}
	}
	
	public function pre_uninstall() {
		global $core;
		$core->config_del(array_keys($this->create_default_configs()), $this->get_data('code'));
		$sqls = $this->create_uninstall_sqls();
		foreach($sqls as $sql) {
			$this->add_sql(SQL_UNINSTALL, $sql);
		}
	}

	public function raidlogimport_plugin_class($pm) {
		global $eqdkp_root_path, $user, $SID, $core;

		$this->build = (int) substr('$Rev: 5173 $', 6, 4);

		$this->eqdkp_plugin($pm);
		$this->pm->get_language_pack('raidlogimport');
		//Load Game-Specific Language
		$lang_file = $eqdkp_root_path.'plugins/raidlogimport/language/'.$user->lang_name.'/'.$core->config['default_game'].'_lang.php';
		if(file_exists($lang_file)) {
			include($lang_file);
			$user->lang = (@is_array($lang)) ? array_merge($user->lang, $lang) : $user->lang;
		}

		$this->add_dependency(array(
			'plus_version' => '0.7',
			'lib_version' => '2.0',
			'games'	=> array('wow', 'eq', 'rom'))
		);

		$this->add_data(array(
			'name'				=> 'Raid-Log-Import',
			'code'				=> 'raidlogimport',
			'path'				=> 'raidlogimport',
			'contact'			=> 'bloodyhoof@gmx.net',
			'template_path' 	=> 'plugins/raidlogimport/templates/',
			'version'			=> $this->version,
			'author'			=> 'Hoofy',
			'description'		=> $user->lang['raidlogimport_short_desc'],
			'long_description'	=> $user->lang['raidlogimport_long_desc'],
			'homepage'			=> 'http://www.eqdkp-plus.com',
			'manuallink'		=> ($user->lang_name != 'german') ? false : $eqdkp_root_path . 'plugins/raidlogimport/language/'.$user->lang_name.'/Manual.pdf',
			'build'				=> $this->build,
			)
		);

		//permissions
		$this->add_permission('a', 'config', 'N', $user->lang['configuration'], array(2,3));
		$this->add_permission('a', 'dkp', 'N', $user->lang['raidlogimport_dkp'], array(2,3));
		$this->add_permission('a', 'bz', 'N', $user->lang['raidlogimport_bz'], array(2,3));
		
		//pdh-modules
		$this->add_pdh_read_module('rli_zone');
		$this->add_pdh_read_module('rli_boss');
		$this->add_pdh_write_module('rli_zone');
		$this->add_pdh_write_module('rli_boss');

		//menu
		$this->add_menu('admin_menu', $this->gen_admin_menu());
	}
	
	private function create_default_configs() {
		global $user, $core;
		//create config-data
		$config_data = array(
			'new_member_rank' 	=> '1',
			'raidcount'			=> '0', //0 = one raid, 1 = raid per hour, 2 = raid per boss, 3 = raid per hour and per boss
			'loottime'			=> '600', //time after bosskill to assign loot to boss (in seconds)
			'attendence_begin' 	=> '0',
			'attendence_end'	=> '0',
			'attendence_raid'	=> '0', //create extra raid for attendence?
			'attendence_time'	=> '900', //time of inv (in seconds)
			'event_boss'		=> '0',  //exists an event per boss?
			'adj_parse'			=> ': ', //string, which separates the reason and the value for a adjustment in the note of a member
			'bz_parse'			=> ',',  //separator, which is used for separating the different strings of a boss or zone
			'parser'			=> 'plus',  //which format has the xml-string?
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
			'member_start_event' => '0',	//event for Start-DKP
			'att_note_begin'	=> $user->lang['rli_att'].' '.$user->lang['rli_start'],	//note for attendence_start-raid
			'att_note_end'		=> $user->lang['rli_att'].' '.$user->lang['rli_end'],	//  "	"		"	 _end-raid
			'raid_note_time'	=> '0', 	//0: exact time (20:03:43-21:03:43); 1: hour (1. hour, 2. hour)
			'timedkp_handle'	=> '0',		//should timedkp be given exactly(0) or fully after x minutes
			'member_display'	=> '2',		//0: multi-dropdown; 1: checkboxes; 2: detailed join/leave
			'standby_raid'		=> '0',		//0: no extra-raid for standby, 1: extra-raid, 2: attendance on normal raid
			'standby_absolute'	=> '0',		//0: relative dkp, 1: absolute dkp
			'standby_value'		=> '0',		//value in percent or absolute
			'standby_att'		=> '0', 	//shall standbys get att start/end?
			'standby_dkptype'	=> '0',		//which dkp shall standbys get? (1 boss, 2 time, 4 event)
			'standby_raidnote'	=> $user->lang['standby_raid_note'],		//note for standby-raid
			'member_raid'		=> '50',	//percent which member has to be in raid, to gain assignment to raid
		);
		if(strtolower($core->config['default_game']) == 'wow') {
			$config_data = array_merge($config_data, array(
				'diff_1'	=> ' (10)',		//suffix for 10-player normal
				'diff_2'	=> ' (25)', 	//suffix for 25-player normal
				'diff_3'	=> ' HM (10)',	//suffix for 10-player heroic
				'diff_4'	=> ' HM (25)',	//suffix for 25-player heroic
				'dep_match'	=> '1'			//also append suffix to boss-note?
			));
		}
		return $config_data;
	}

	private function create_install_sqls() {
		global $core, $db, $eqdkp_root_path, $pdh;
		$install_sqls = array(
			"CREATE TABLE IF NOT EXISTS __raidlogimport_boss (
				`boss_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`boss_string` VARCHAR(255) NOT NULL,
				`boss_note` VARCHAR(255) NOT NULL,
				`boss_bonus` FLOAT(5,2) NOT NULL DEFAULT 0,
				`boss_timebonus` FLOAT(5,2) NOT NULL DEFAULT 0,
				`boss_diff` INT NOT NULL DEFAULT 0,
				`boss_tozone` INT NOT NULL DEFAULT 0,
				`boss_sort` INT NOT NULL DEFAULT 0
			);",
			"CREATE TABLE IF NOT EXISTS __raidlogimport_zone (
				`zone_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`zone_string` VARCHAR(255) NOT NULL,
				`zone_event` INT NOT NULL,
				`zone_timebonus` FLOAT(5,2) NOT NULL DEFAULT 0,
				`zone_diff` INT NOT NULL DEFAULT 0,
				`zone_sort` INT NOT NULL DEFAULT 0
			);",
			"CREATE TABLE IF NOT EXISTS __raidlogimport_cache (
				`cache_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`cache_class` VARCHAR(255) NOT NULL,
				`cache_data` BLOB DEFAULT NULL
			);");
		
		//add default bz_data
		$file = $eqdkp_root_path.'plugins/raidlogimport/games/'.$core->config['default_game'].'/bz_sql.php';
		if(is_file($file)) {
			include_once($file);
			$data = (is_array(${$user->lang_name})) ? ${$user->lang_name} : $english;
			if (is_array($data)) {
				$zones = $pdh->aget('event', 'name', 0, array($pdh->get('event', 'id_list')));
				foreach($data as $bz) {
					if($bz[0] == 'zone') {
						$id = 1;
						foreach($zones as $zid => $zone) {
							if(strpos($zone, $bz[2]) !== false) {
								$id = $zid;
								break;
							}
						}							
						$install_sqls[] = 	"INSERT INTO __raidlogimport_zone
												(zone_string, zone_event, zone_timebonus, zone_diff, zone_sort)
											VALUES
												('".$db->escape($bz[1])."', '".$id."', '".$bz[4]."', '".$bz[5]."', '".$bz[7]."');";
					} else {
						$install_sqls[] = 	"INSERT INTO __raidlogimport_boss
												(boss_string, boss_note, boss_bonus, boss_timebonus, boss_diff, boss_tozone, boss_sort)
											VALUES
												('".$db->escape($bz[1])."', '".$db->escape($bz[2])."', '".$bz[3]."', '".$bz[4]."', '".$bz[5]."', '".$bz[6]."', '".$bz[7]."');";
					}
				}
			}
		}
		return $install_sqls;
	}
	
	private function create_uninstall_sqls() {
		$uninstall_sqls = array(
			"DROP TABLE IF EXISTS __raidlogimport_boss;",
			"DROP TABLE IF EXISTS __raidlogimport_zone;",
			"DROP TABLE IF EXISTS __raidlogimport_cache;");
		return $uninstall_sqls;
	}
	
	public function gen_admin_menu() {
		if ( $this->pm->check(PLUGIN_INSTALLED, 'raidlogimport') ) {
			global $db, $user, $SID, $eqdkp_root_path;

			$admin_menu = array(
				'raidlogimport' => array(
					'icon' => './../../plugins/raidlogimport/images/report.png',
					'name' => $user->lang['raidlogimport'],
					1 => array(
						'link' => 'plugins/' . $this->get_data('path') . '/admin/settings.php'.$SID,
						'text' => $user->lang['settings'],
						'check' => 'a_raidlogimport_config',
						'icon' => 'settings.png'),
					2 => array(
						'link' => 'plugins/' . $this->get_data('path') . '/admin/bz.php'.$SID,
						'text' => $user->lang['raidlogimport_bz'],
						'check' => 'a_raidlogimport_bz',
						'icon' => './../../plugins/raidlogimport/images/report_edit.png'),
					3 => array(
						'link' => 'plugins/' . $this->get_data('path') . '/admin/dkp.php'.$SID,
						'text' => $user->lang['raidlogimport_dkp'],
						'check' => 'a_raidlogimport_dkp',
						'icon' => './../../plugins/raidlogimport/images/report_add.png')
				)
			);
			return $admin_menu;
		}
		return;
	}

	public function get_info($varname) {
		return $this->$varname;
	}
}
?>
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
* $Id: rli.class.php 5173 2009-07-05 16:49:25Z hoofy_leon $
*/

if(!defined('EQDKP_INC'))
{
	header('HTTP/1.0 Not Found');
	exit;
}
if(!class_exists('rli'))
{
class rli
{
	private $bonus = array();
	private $config = array();
	private $bk_list = array();
	private $events = array();
	private $member_ranks = array();
	private $data = array();
	public $parser = false;
	public $member = false;
	public $raid = false;
	public $item = false;
	public $adj = false;
	public $add_data = false;

	public function __construct() {
		global $core, $settings;
		$this->config = $settings->get_config('raidlogimport');
		if($this->config['bz_parse'] == '' or !$this->config['bz_parse'])
		{
			$this->config['bz_parse'] = ',';
			$core->config_set('bz_parse', ',', 'raidlogimport');
		}
	}

	public function init_import() {
		$this->raid = new rli_raid;
		$this->member = new rli_member;
		$this->item = new rli_item;
		$this->adj = new rli_adjustment;
		$this->parser = new rli_parse;
	}

	public function get_bonus() {
		$this->load_bonus();
		return $this->bonus;
	}

	public function config($name='') {
		return ($name == '') ? $this->config : $this->config[$name];
	}

	private function load_bonus() {
		global $db, $pdl, $dbname, $table_prefix;

		if(!$this->bonus) {
			$sql = "SELECT bz_id, bz_string, bz_note, bz_bonus, bz_type, bz_tozone FROM __raidlogimport_bz;";
		if($result = $db->query($sql)) {
			while($row = $db->fetch_record($result)) {
				if($row['bz_type'] == 'boss') {
					$this->bonus['boss'][$row['bz_id']]['string'] = explode($this->config('bz_parse'), $row['bz_string']);
					$this->bonus['boss'][$row['bz_id']]['note'] = $row['bz_note'];
					$this->bonus['boss'][$row['bz_id']]['bonus'] = $row['bz_bonus'];
					$this->bonus['boss'][$row['bz_id']]['tozone'] = $row['bz_tozone'];
				} else {
					$this->bonus['zone'][$row['bz_id']]['string'] = explode($this->config('bz_parse'), $row['bz_string']);
					$this->bonus['zone'][$row['bz_id']]['note'] = $row['bz_note'];
					$this->bonus['zone'][$row['bz_id']]['bonus'] = $row['bz_bonus'];
				}
			}
		} else {
			$sql_error = $db->_sql_error();
			$pdl->log('sql_error', $sql, $sql_error['message'], $sql_error['code'], $dbname, $table_prefix);
			message_die("SQL-Error! <br /> Query:".$sql);
		}
		}
	}

	private function load_events() {
		global $pdh;
		if(!$this->events) {
			$events = $pdh->get('event', 'id_list');
			foreach($events as $event_id) {
				$this->events['name'][$event_id] = $pdh->get('event', 'name', array($event_id));
				$this->events['value'][$event_id] = $pdh->get('event', 'value', array($event_id));
			}
		}
	}

	public function get_events($key=false, $sec_key=false) {
		$this->load_events();
		return ($key) ? (($sec_key) ? $this->events[$key][$sec_key] : $this->events[$key]) : $this->events;
	}

	public function get_cache_data($type) {
		global $db, $core;

		if(!$this->data[$type]) {
			$sql = "SELECT cache_class, cache_data FROM __raidlogimport_cache;";
			$result = $db->query($sql);
			while ( $row = $db->fetch_record($result) ) {
				$this->data[$row['cache_class']] = ($core->config['enable_gzip']) ? unserialize(gzuncompress($row['cache_data'])) : unserialize($row['cache_data']);
			}
			$db->query("TRUNCATE __raidlogimport_cache;");
		}
		return $this->data[$type];
	}

	public function boss_dropdown($bossname, $raid_key, $key) {
		global $html;
		$this->get_bonus();
		if(!$this->bk_list) {
			foreach($this->bonus['boss'] as $boss) {
				$this->bk_list[htmlspecialchars($boss['string'][0], ENT_QUOTES)] = htmlentities($boss['note'], ENT_QUOTES);
				if($this->config['use_dkp'] & 1) {
					$this->bk_list[htmlspecialchars($boss['string'][0], ENT_QUOTES)] .= ' ('.$boss['bonus'].')';
				}
			}
		}
		foreach($this->bonus['boss'] as $boss)
		{
			if(in_array($bossname, $boss['string']))
			{
				$sel = htmlspecialchars($boss['string'][0], ENT_QUOTES);
			}
		}
		return $html->DropDown('raids['.$raid_key.'][bosskills]['.$key.'][name]', $this->bk_list, $sel);
	}

	public function check_data() {
		global $rli;
		$bools = array();
		$rli->raid->check(&$bools);
		$rli->member->check(&$bools);
		$rli->item->check(&$bools);
		$rli->adj->check(&$bools);
		return $bools;
	}
	
	public function add_cache_data($type, $data) {
		$this->data[$type] = $data;
	}
	
	public function flush_cache() {
		global $db;
		return $db->query("TRUNCATE __raidlogimport_cache;");
	}

	public function destroy() {
		global $db, $core;

		unset($this->raid);
		unset($this->member);
		unset($this->item);
		unset($this->adj);
		unset($this->parse);

		$sql = "INSERT INTO __raidlogimport_cache
				(cache_class, cache_data)
				VALUES ";
		if($this->data) {
			foreach($this->data as $type => $data) {
				$data = ($core->config['enable_gzip']) ? gz_compress(serialize($data)) : serialize($data);
				$sqls[] = "('".$type."', '".$db->escape($data)."')";
			}
			$sql .= implode(", ", $sqls).";";
			$db->query($sql);
		}
	}
}//class
}//class exist
?>
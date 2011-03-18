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

	public function __construct() {
		global $core;
		$this->config = $core->config['raidlogimport'];
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

	public function config($name='') {
		return ($name == '') ? $this->config : ((isset($this->config[$name])) ? $this->config[$name] : null);
	}

	public function suffix($string, $append, $diff) {
		global $game;
		if($game->get_game() == 'wow' AND $append) {
			return $string.$this->config('diff_'.$diff);
		}
		return $string;
	}

	public function get_cache_data($type) {
		global $db, $core;
		if(!$this->data) {
			$sql = "SELECT cache_class, cache_data FROM __raidlogimport_cache;";
			$result = $db->query($sql);
			while ( $row = $db->fetch_record($result) ) {
				$this->data[$row['cache_class']] = ($core->config['enable_gzip']) ? unserialize(gzuncompress($row['cache_data'])) : unserialize($row['cache_data']);
			}
			$this->data['fetched'] = true;
		}
		return (isset($this->data[$type])) ? $this->data[$type] : null;
	}

	public function check_data() {
		$bools = array();
		$this->raid->check(&$bools);
		$this->member->check(&$bools);
		$this->item->check(&$bools);
		$this->adj->check(&$bools);
		return $bools;
	}
	
	public function add_cache_data($type, $data) {
		$this->data[$type] = $data;
	}
	
	public function flush_cache() {
		global $db;
		$this->raid->reset();
		$this->member->reset();
		$this->item->reset();
		$this->adj->reset();
		return $db->query("TRUNCATE __raidlogimport_cache;");
	}

	public function destroy() {
		global $db, $core;

		unset($this->raid);
		unset($this->member);
		unset($this->item);
		unset($this->adj);
		unset($this->parse);
		
		$db->query("TRUNCATE __raidlogimport_cache;");
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
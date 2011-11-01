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

class rli extends gen_class {
	public static $shortcuts = array('cconfig' => 'config', 'game', 'db',
		'adj'		=> 'rli_adjustment',
		'item'		=> 'rli_item',
		'member'	=> 'rli_member',
		'raid'		=> 'rli_raid',
	);
	public static $dependencies = array('db', 'cconfig' => 'config');

	private $bonus = array();
	private $config = array();
	private $bk_list = array();
	private $events = array();
	private $member_ranks = array();
	private $data = array();
	
	private $destruct_called = false;

	public function __construct() {
		$this->config = $this->cconfig->get('raidlogimport');
		if(empty($this->config['bz_parse'])) {
			$this->config['bz_parse'] = ',';
			$this->cconfig->set('bz_parse', ',', 'raidlogimport');
		}
	}
	
	public function reload_config() {
		$this->config = $this->cconfig->get('raidlogimport');
	}

	public function config($name='') {
		return ($name == '') ? $this->config : ((isset($this->config[$name])) ? $this->config[$name] : null);
	}

	public function suffix($string, $append, $diff) {
		if($this->game->get_game() == 'wow' AND $append) {
			return $string.$this->config('diff_'.$diff);
		}
		return $string;
	}

	public function get_cache_data($type) {
		if(!$this->data) {
			$sql = "SELECT cache_class, cache_data FROM __raidlogimport_cache;";
			$result = $this->db->query($sql);
			while ( $row = $this->db->fetch_record($result) ) {
				$this->data[$row['cache_class']] = ($this->cconfig->get('enable_gzip')) ? unserialize(gzuncompress($row['cache_data'])) : unserialize($row['cache_data']);
			}
			$this->data['fetched'] = true;
		}
		return (isset($this->data[$type])) ? $this->data[$type] : null;
	}

	public function check_data() {
		$bools = array();
		$bools = $this->raid->check($bools);
		$bools = $this->member->check($bools);
		$bools = $this->item->check($bools);
		$bools = $this->adj->check($bools);
		return $bools;
	}
	
	public function add_cache_data($type, $data) {
		$this->data[$type] = $data;
	}
	
	public function flush_cache() {
		$this->raid->reset();
		$this->member->reset();
		$this->item->reset();
		$this->adj->reset();
		return $this->db->query("TRUNCATE __raidlogimport_cache;");
	}

	public function __destruct() {
		$this->db->query("TRUNCATE __raidlogimport_cache;");
		$sql = "INSERT INTO __raidlogimport_cache
				(cache_class, cache_data)
				VALUES ";
		if($this->data) {
			foreach($this->data as $type => $data) {
				$data = ($this->cconfig->get('enable_gzip')) ? gz_compress(serialize($data)) : serialize($data);
				$sqls[] = "('".$type."', '".$this->db->escape($data)."')";
			}
			$sql .= implode(", ", $sqls).";";
			$this->db->query($sql);
		}
		parent::__destruct();
	}
}//class

if(version_compare(PHP_VERSION, '5.3.0', '<')) {
	registry::add_const('short_rli', rli::$shortcuts);
	registry::add_const('dep_rli', rli::$dependencies);
}
?>
<?php
/*
* Project:     EQdkp-Plus Raidlogimport
* License:     Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
* Link:		http://creativecommons.org/licenses/by-nc-sa/3.0/
* -----------------------------------------------------------------------
* Began:       2009
* Date:        $Date: 2013-09-30 01:28:14 +0200 (Mo, 30 Sep 2013) $
* -----------------------------------------------------------------------
* @author      $Author: hoofy_leon $
* @copyright   2008-2009 hoofy_leon
* @link        http://eqdkp-plus.com
* @package     raidlogimport
* @version     $Rev: 13608 $
*
* $Id: rli_parse.class.php 13608 2013-09-29 23:28:14Z hoofy_leon $
*/

if(!defined('EQDKP_INC'))
{
	header('HTTP/1.0 Not Found');
	exit;
}

if(!class_exists('rli_parse')) {
class rli_parse extends gen_class {
	public static $shortcuts = array('rli', 'in', 'config', 'user',
		'adj'		=> 'rli_adjustment',
		'item'		=> 'rli_item',
		'member'	=> 'rli_member',
		'raid'		=> 'rli_raid',
	);

	private $toload = array();

	public function parse_string($log) {
		$parser = $this->rli->config('parser');
		$path = $this->root_path.'plugins/raidlogimport/includes/parser/';
		if(is_file($path.$parser.'.parser.class.php')) {
			include_once($path.'parser.aclass.php');
			include_once($path.$parser.'.parser.class.php');
			if($parser::$xml) {
				$log = @simplexml_load_string($log);
				if ($log === false) {
					message_die($this->user->lang('xml_error'));
				}
			}
			$back = $parser::check($log);
			if($back[1]) {
				$this->raid->flush_data();
				$data = $parser::parse($log);
				foreach($data as $type => $ddata) {
					switch($type) {
						case 'zones':
							foreach($ddata as $args) {call_user_func_array(array($this->raid, 'add_zone'), $args);}
							break;
						case 'bosses':
							foreach($ddata as $args) {call_user_func_array(array($this->raid, 'add_bosskill'), $args);}
							break;
						case 'members':
							foreach($ddata as $args) {call_user_func_array(array($this->member, 'add'), $args);}
							break;
						case 'times':
							foreach($ddata as $args) {call_user_func_array(array($this->member, 'add_time'), $args);}
							break;
						case 'items':
							foreach($ddata as $args) {call_user_func_array(array($this->item, 'add'), $args);}
							break;
					}
				}
				$this->raid->create();
				$this->raid->recalc(true);
				$this->member->finish();
			} else {
				message_die(sprintf($this->user->lang('rli_error_wrong_format'), $parser::$name).'<br />'.$this->user->lang('rli_miss').implode(', ', $back[2]));
			}
		} else {
			message_die($this->user->lang('rli_error_no_parser'));
		}
	}
}
}
if(version_compare(PHP_VERSION, '5.3.0', '<')) registry::add_const('short_rli_parse', rli_parse::$shortcuts);
?>
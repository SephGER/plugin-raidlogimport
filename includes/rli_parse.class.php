<?php
/*
* Project:     EQdkp-Plus Raidlogimport
* License:     Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
* Link:		http://creativecommons.org/licenses/by-nc-sa/3.0/
* -----------------------------------------------------------------------
* Began:       2009
* Date:        $Date$
* -----------------------------------------------------------------------
* @author      $Author$
* @copyright   2008-2009 hoofy_leon
* @link        http://eqdkp-plus.com
* @package     raidlogimport
* @version     $Rev$
*
* $Id$
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

	public function parse_string($xml) {
		$parser = $this->rli->config('parser');
		$path = $this->root_path.'plugins/raidlogimport/includes/parser/';
		if(is_file($path.$parser.'.parser.class.php')) {
			include_once($path.'parser.aclass.php');
			include_once($path.$parser.'.parser.class.php');
			$back = $parser::check($xml);
			if($back[1]) {
				$this->raid->flush_data();
				$data = $parser::parse($xml);
				foreach($data as $type => $ddata) {
					switch($type) {
						case 'zones':
							foreach($ddata as $args) {call_user_func(array($this->raid, 'add_zone'), $args);}
							break;
						case 'bosses':
							foreach($ddata as $args) {call_user_func(array($this->raid, 'add_bosskill'), $args);}
							break;
						case 'members':
							foreach($ddata as $args) {call_user_func(array($this->member, 'add_member'), $args);}
							break;
						case 'members':
							foreach($ddata as $args) {call_user_func(array($this->member, 'add_time'), $args);}
							break;
						case 'members':
							foreach($ddata as $args) {call_user_func(array($this->item, 'add_item'), $args);}
							break;
					}
				}
				$this->raid->create();
				$this->raid->recalc(true);
				$this->member->finish();
			} else {
				message_die($this->user->lang('wrong_format').' '.$paser::$name.'<br />'.$this->user->lang('rli_miss').implode(', ', $back[2]));
			}
		} else {
			message_die($this->user->lang('no_parser'));
		}
	}
}
}
?>
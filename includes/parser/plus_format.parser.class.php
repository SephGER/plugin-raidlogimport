<?php
/*
* Project:     EQdkp-Plus Raidlogimport
* License:     Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
* Link:		http://creativecommons.org/licenses/by-nc-sa/3.0/
* -----------------------------------------------------------------------
* Began:       2009
* Date:        $Date: 2013-03-24 19:46:58 +0100 (So, 24 Mrz 2013) $
* -----------------------------------------------------------------------
* @author      $Author: hoofy_leon $
* @copyright   2008-2009 hoofy_leon
* @link        http://eqdkp-plus.com
* @package     raidlogimport
* @version     $Rev: 13243 $
*
* $Id: rli_parse.class.php 13243 2013-03-24 18:46:58Z hoofy_leon $
*/

if(!defined('EQDKP_INC'))
{
	header('HTTP/1.0 Not Found');
	exit;
}

if(!class_exists('plus_format')) {
class plus_format extends rli_parser {

	public static $name = 'EQdkpPlus XML Format';
	
	public static function parse($input) {
		if(	(trim($input->head->gameinfo->game) == 'Runes of Magic' AND register('config')->get('default_game') != 'rom') OR
			(trim($input->head->gameinfo->game) == 'World of Warcraft' AND register('config')->get('default_game') != 'wow')) {
				message_die(register('user')->lang('wrong_game'));
		}
		$lang = trim($input->head->gameinfo->language);
		#$this->rli->add_data['log_lang'] = substr($lang, 0, 2);
		$xml = $input->raiddata;
		$data = array();
		foreach($xml->zones->children() as $zone) {
			$data['zones'][] = array(trim($zone->name), (int) trim($zone->enter), (int) trim($zone->leave), (int) trim($zone->difficulty));
		}
		foreach($xml->bosskills->children() as $bosskill) {
			$data['bosses'][] = array(trim($bosskill->name), (int) trim($bosskill->time), (int) trim($bosskill->difficulty));
		}
		foreach($xml->members->children() as $xmember) {
			$name = trim($xmember->name);
			$note = (isset($xmember->note)) ? trim($xmember->note) : '';
			$data['members'][] = array($name, trim($xmember->class), trim($xmember->race), trim($xmember->level), $note);
			foreach($xmember->times->children() as $time) {
				$attrs = $time->attributes();
				$type = (string) $attrs['type'];
				$extra = isset($attrs['extra']) ? (string) $attrs['extra'] : '';
				$data['times'][] = array($name, (int) $time, $type, $extra);
			}
		}
		foreach($xml->items->children() as $xitem) {
			$cost = (isset($xitem->cost)) ? trim($xitem->cost) : '';
			$id = (isset($xitem->itemid)) ? trim($xitem->itemid) : '';
			$data['items'][] = array(trim($xitem->name), trim($xitem->member), $cost, (int) $id, (int) trim($xitem->time));
		}
		return $data;
	}
	
	public static function check($input) {
		$check_array = array(
			'multiple:zones' => array(
				'zone' => array(
					'enter'	=> '',
					'leave' => '',
					'name'	=> ''
				)
			),
			'multiple:bosskills' => array(
				'optional:bosskill' => array(
					'name'	=> '',
					'time'	=> ''
				)
			),
			'multiple:members' => array(
				'member' => array(
					'name'	=> '',
					'multiple:times' => array('time' => '')
				)
			),
			'multiple:items' => array(
				'optional:item'	=> array(
					'name'		=> '',
					'time'		=> '',
					'member'	=> ''
				)
			)
		);
		return self::check_xml_format($input->raiddata, $check_array);
	}
}
}
?>
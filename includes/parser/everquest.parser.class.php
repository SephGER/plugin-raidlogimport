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

if(!class_exists('everquest')) {
class everquest extends rli_parser {

	public static $name = 'Everquest';
	public static $xml = false;

	public static function check($text) {
		$back[1] = true;
		// plain text format - nothing to check
		return $back;
	}
	/*1 Arionex 50 Cleric Group Leader
1 Nazgoolz 50 Necromancer Raid Leader
1 Tantrum 50 Enchanter
1 Lezo 50 Wizard
1 Apoctic 50 Monk
1 Defibrillator 50 Shadow Knight
2 Tuwoq 85 Warrior Group Leader
2 Woob 50 Ranger
2 Mortesis 78 Necromancer
2 Warrins 50 Magician
2 Iinis 81 Necromancer
2 Zoundz 50 Enchanter
3 Jotan 50 Shaman Group Leader
3 Mertozze 50 Rogue
3 Xiriana 85 Necromancer
3 Xenozx 50 Wizard
3 Melwhen 50 Cleric
3 Sakawsi 85 Shaman
4 Retron 50 Shaman Group Leader
4 Wander 75 Ranger
4 Thorgador 50 Cleric
4 Taikuri 50 Necromancer
4 Trellos 83 Druid
5 Ganeana 50 Rogue Group Leader
5 Sendiile 85 Enchanter
5 Phloyd 77 Bard
5 Sandmannx 50 Monk
5 Leandred 50 Paladin
6 Felrom 50 Warrior Group Leader
6 Gims 85 Rogue
6 Kyuubi 50 Warrior
6 Keslar 50 Paladin
6 Callean 84 Magician
6 Vaman 80 Magician
7 Selb 82 Shadow Knight Group Leader
7 Magicaljack 50 Bard
7 Dragoni 50 Ranger
7 Bexie 50 Shadow Knight 
*/
	
	public static function parse($text) {
		$regex = '~[0-9]\h(?<name>\w*)\h(?<lvl>[0-9]{1,2})\h(?<class>\w*(?(?!\hGroup|\hRaid)\h\w*){0,1})~';
		preg_match_all($regex, $text, $matches, PREG_SET_ORDER);
		foreach($matches as $match) {
			$lvl = (isset($match['lvl'])) ? trim($match['lvl']) : 0;
			$class = (isset($match['class'])) ? trim($match['class']) : '';
			$data['members'][] = array(trim($match['name']), $class, '', $lvl);
		}
		return $data;
	}
}
}
?>
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

if(!class_exists('vanguard_soh')) {
class vanguard_soh extends rli_parser {

	public static $name = 'Vanguard - Saga of Heroes';
	public static $xml = false;

	public static function check($text) {
		$back[1] = true;
		// plain text format - nothing to check
		return $back;
	}
	
	public static function parse($text) {
		$timestamp_regex = '[0-9]{2}:[0-9]{2}:[0-9]{2}';
		$lvl_class_regex = ': Level (?<lvl>[0-9]{1,2}) (?<class>.*),';
		$regex = '~\[(?<time>'.$timestamp_regex.')\]\h(?:'.$timestamp_regex.'\h){0,1}(?<name>\w*)(?:'.$lvl_class_regex.'){0,1}~';
		preg_match_all($regex, $text, $matches, PREG_SET_ORDER);
		if(empty($matches[0]['name'])) {
			//delete all even entries of matches
			$max = max(array_keys($matches));
			for($i=0;$i<$max;$i+=2) {
				unset($matches[$i]);
			}
			$matches = array_values($matches);
		}
		$data['zones'][] = array('zone-name', strtotime($matches[0]['time']), strtotime($matches[0]['time'])+10);
		foreach($matches as $match) {
			$lvl = (isset($match['lvl'])) ? trim($match['lvl']) : 0;
			$class = (isset($match['class'])) ? trim($match['class']) : '';
			$data['members'][] = array(trim($match['name']), $class, '', $lvl);
			$data['times'][] = array(trim($match['name']), strtotime($match['time']), 'join');
			$data['times'][] = array(trim($match['name']), strtotime($match['time'])+86400, 'leave'); //leave one day later (no one raids longer than a day!)
		}
		return $data;
	}
}
}
?>
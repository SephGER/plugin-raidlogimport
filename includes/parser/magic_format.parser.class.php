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

if(!class_exists('magic_format')) {
class magic_format extends rli_parser {

	public static $name = 'MagicDKP';

	public static function check($xml) {
		$back[1] = true;
		if(!isset($xml->start)) {
			$back[1] = false;
			$back[2][] = 'start';
		} else {
			if(!(stristr($xml->start, ':'))) {
				$back[1] = false;
				$back[2][] = 'start in format: MM/DD/YY HH:MM:SS';
			}
		}
		if(!isset($xml->end)) {
			$back[1] = false;
			$back[2][] = 'end';
		} else {
			if(!(stristr($xml->start, ':'))) {
				$back[1] = false;
				$back[2][] = 'end in format: MM/DD/YY HH:MM:SS';
			}
		}
		if(!isset($xml->BossKills)) {
			$back[1] = false;
			$back[2][] = 'BossKills';
		} else {
			foreach($xml->BossKills->children() as $bosskill) {
				if($bosskill) {
					if(!isset($bosskill->name)) {
						$back[1] = false;
						$back[2][] = 'BossKills->name';
					}
					if(!isset($bosskill->time)) {
						$back[1] = false;
						$back[2][] = 'BossKills->time';
					}
				}
			}
		}
		if(!isset($xml->Loot)) {
			$back[1] = false;
			$back[2][] = 'Loot';
		} else {
			foreach($xml->Loot->children() as $loot) {
				if($loot) {
					if(!isset($loot->ItemName)) {
						$back[1] = false;
						$back[2][] = 'Loot->ItemName';
					}
					if(!isset($loot->Player)) {
						$back[1] = false;
						$back[2][] = 'Loot->Player';
					}
					if(!isset($loot->Time)) {
						$back[1] = false;
						$back[2] = 'Loot->Time';
					}
				}
			}
		}
		if(!isset($xml->Join)) {
			$back[1] = false;
			$back[2][] = 'Join';
		} else {
			foreach($xml->Join->children() as $join) {
				if(!isset($join->player)) {
					$back[1] = false;
					$back[2][] = 'Join->player';
				}
				if(!isset($join->time)) {
					$back[1] = false;
					$back[2][] = 'Join->time';
				}
			}
		}
		if(!isset($xml->Leave)) {
			$back[1] = false;
			$back[2][] = 'Leave';
		} else {
			foreach($xml->Leave->children() as $leave) {
				if(!isset($leave->player)) {
					$back[1] = false;
					$back[2][] = 'Leave->player';
				}
				if(!isset($leave->time)) {
					$back[1] = false;
					$back[2][] = 'Leave->time';
				}
			}
		}
		return $back;
	}

	public static function parse($xml) {
		$data['zones'][] = array(trim($xml->zone), strtotime($xml->start), strtotime($xml->end), trim($xml->difficulty));
		foreach ($xml->BossKills->children() as $bosskill) {
			$data['bosses'][] = array(trim($bosskill->name), strtotime($bosskill->time));
		}
		foreach($xml->Loot->children() as $loot) {
			$player = (trim($loot->Player));
			$cost = (array_key_exists('Costs', $loot)) ? (int) $loot->Costs : (int) $loot->Note;
			$data['items'][] = array(trim($loot->ItemName), $player, $cost, substr(trim($loot->ItemID), 0, 5), strtotime($loot->Time));
		}
		foreach ($xml->Join->children() as $joiner) {
			$data['times'][] = array(trim($joiner->player), strtotime($joiner->time), 'join');
		}
		foreach ($xml->Leave->children() as $leaver) {
			$data['times'][] = array(trim($leaver->player), strtotime($leaver->time), 'leave');
		}
		return $data;
	}
}
}
?>
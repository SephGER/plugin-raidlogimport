<?php
/*	Project:	EQdkp-Plus
 *	Package:	RaidLogImport Plugin
 *	Link:		http://eqdkp-plus.eu
 *
 *	Copyright (C) 2006-2016 EQdkp-Plus Developer Team
 *
 *	This program is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU Affero General Public License as published
 *	by the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU Affero General Public License for more details.
 *
 *	You should have received a copy of the GNU Affero General Public License
 *	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */


if(!defined('EQDKP_INC'))
{
	header('HTTP/1.0 Not Found');
	exit;
}

if(!class_exists('generic_loot')) {
class generic_loot extends rli_parser {

	public static $name = 'Generic Loot';
	public static $xml = false;

	public static function check($text) {
		$back[1] = true;
		// plain text format - nothing to check
		return $back;
	}
	
	public static function parse($text) {
		// Item-Membername@Itemcost
		// Gleaming Mesh Girdle-Josida@37
		
		$regex = '/(.*)-(.*)@(.*)/';
		$matches = array();
		
		preg_match_all($regex, $text, $matches, PREG_SET_ORDER);
		
		$arrMembersDone = $data = array();
		
		foreach($matches as $match) {
			$lvl = 0;
			$class = '';
			
			$strMembername = trim($match[2]);

			if(!in_array($strMembername, $arrMembersDone)){
				$data['members'][] = array(trim($strMembername), $class, '', $lvl);
				$data['times'][] = array(trim($strMembername), time() - (2*3600), 'join');
				$data['times'][] = array(trim($strMembername), time(), 'leave');
				
				$arrMembersDone[] = $strMembername;
			}
			
			$strItemname = trim($match[1]);
			$fltItemcost = self::floatvalue($match[3]);
			
			$data['items'][] = array($strItemname, $strMembername, $fltItemcost, '', time());
		}
		
		$data['zones'][] = array('unknown zone',  time() - (2*4000), time());
		$data['bosses'][] = array('unknown boss', time() - (1*3600), 0);
				
		return $data;
	}
	
	private static function floatvalue($val){
		$val = str_replace(",",".",$val);
		$val = preg_replace('/\.(?=.*\.)/', '', $val);
		return floatval($val);
	}
}
}
?>
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

if(!class_exists('everquest2_act')) {
class everquest2_act extends rli_parser {

	public static $name = 'Everquest2 ACT';
	public static $xml = false;

	public static function check($text) {
		$back[1] = true;
		// plain text format - nothing to check
		return $back;
	}
	
	public static function parse($text) {
		$Data = str_getcsv($text, "\n"); //parse the rows
		$arrFirstUser = false;
		
		$blnUseHttp = false;
		$intVersatz = 0;
		$arrLeaveTimes = $arrJoinTimes = array();
		
		foreach ($Data as $row){
			$arrRow = str_getcsv($row);
			
			//New Format
			//Player,DKP Holder,DKP,Bid/Roll,Raid Duration (d.HH:MM),Join Time,Join Zone,Leave Time,Leave Zone,Gap Time,Looted (ID),Sit,Comment,
			if($arrRow[5] == 'Join Time') $blnUseHttp = true;
			if($blnUseHttp) $intVersatz = 1;
			
			//Is Headline
			if($arrRow[0] === 'Player') continue;
			
			if(!$arrFirstUser && $arrRow[5+$intVersatz] != "Unknown") $arrFirstUser = $arrRow;
			
			//Format
			//Player,DKP Holder,Bid/Roll,Raid Duration (d.HH:MM),Join Time,Join Zone,Leave Time,Leave Zone,Gap Time,Looted (ID),comment
			
			$data['members'][] = array(trim($arrRow[0]), '', '', 0);
			$data['times'][] = array(trim($arrRow[0]), strtotime($arrRow[4+$intVersatz]), 'join');
			$data['times'][] = array(trim($arrRow[0]), strtotime($arrRow[6+$intVersatz]), 'leave');
			$arrLeaveTimes[] = strtotime($arrRow[6+$intVersatz]);
			$arrJoinTimes[] = strtotime($arrRow[4+$intVersatz]);
			
			//Loot, 9
			$strLootLine = $arrRow[9+$intVersatz];

			if($strLootLine != ""){
				$arrLootArray = array();
				$intMatches = preg_match_all("/(.*)\((http:\/\/u\.eq2wire\.com\/item\/index\/[0-9]*|[0-9]*)\)/U", $strLootLine, $arrLootArray);
				
				if($intMatches > 0){
					foreach($arrLootArray[0] as $key => $val){
						$strItem = $arrLootArray[2][$key];
						//New Format containts link to eq2wire
						if(strpos($strItem, 'http') !== false) $strItem = str_replace('http://u.eq2wire.com/item/index/', '', $strItem);						
						$data['items'][] = array(trim($arrLootArray[1][$key]), trim($arrRow[0]), 0, trim($strItem), strtotime($arrRow[4])+100);
					}
				}
			}
		}
		
		if($arrFirstUser){
			$data['zones'][] = array(
					$arrFirstUser[5+$intVersatz], min($arrJoinTimes), max($arrLeaveTimes)
			);
			
			
			$array_sum = array_sum($arrJoinTimes);
			$abs_join = ($array_sum / (count($arrJoinTimes)));
			
			
			$array_sum = array_sum($arrLeaveTimes);
			$abs_leave = ($array_sum / (count($arrLeaveTimes)));
			
			$intBossTime = ($abs_join + $abs_leave) / 2;
			
			$data['bosses'][] = array($arrFirstUser[5+$intVersatz], round($intBossTime, 0));
		}
		
		return $data;
	}
}
}
?>
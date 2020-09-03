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

if(!class_exists('everquest_loot')) {
class everquest_loot extends rli_parser {

	public static $name = 'Everquest Loot';
	public static $xml = false;

	public static function check($text) {
		$back[1] = true;
		// plain text format - nothing to check
		return $back;
	}
	
	public static function parse($text) {
		//[Thu Feb 18 19:12:49 2016] --Raneen has looted a Whitened Treant Fists.--
		$regex = '/\[(.*)\] --(.*) has looted a (.*).--/';
		$matches = array();
		
		preg_match_all($regex, $text, $matches, PREG_SET_ORDER);
		$timestart = $timeend = false;
		$arrMembersDone = $data = array();
		
		foreach($matches as $match) {
			if(!$timestart) $timestart = strtotime($match[1]);
			$timeend = strtotime($match[1]);
		}
		
		foreach($matches as $match) {
			$lvl = 0;
			$class = '';

			if(!in_array(trim($match[2]), $arrMembersDone)){
				$data['members'][] = array(trim($match[2]), $class, '', $lvl);
				$data['times'][] = array(trim($match[2]), $timestart - (1*3600), 'join');
				$data['times'][] = array(trim($match[2]), $timeend+(500), 'leave');
				
				$arrMembersDone[] = trim($match[2]);
			}
			
			$data['items'][] = array(trim($match[3]), $match[2], 0, '', strtotime($match[1]));
		}
		
		
		
		//[Thu Apr 09 08:00:25 2020] You tell btdkp:1, 'Halo of the Enlightened-Duskin@100'
		$regex = '/\[(.*)\].*\'(.*)-(.*)@(.*)\'/';
		$matches = array();
		
		preg_match_all($regex, $text, $matches, PREG_SET_ORDER);
		
		foreach($matches as $match) {
			if(!$timestart) $timestart = strtotime($match[1]);
			if(!$timeend) $timeend = strtotime($match[1]);
		}
		
		foreach($matches as $match) {
			$lvl = 0;
			$class = '';
			
			$strMembername = trim($match[3]);
			$floatItemvalue = (float)trim($match[4]);
			$strItemname = trim($match[2]);
			
			if(!in_array(trim($match[2]), $arrMembersDone)){
				$data['members'][] = array($strMembername, $class, '', $lvl);
				$data['times'][] = array($strMembername, $timestart - (1*3600), 'join');
				$data['times'][] = array($strMembername, $timeend+(500), 'leave');
				
				$arrMembersDone[] = trim($match[2]);
			}
			
			$data['items'][] = array($strItemname, $strMembername, $floatItemvalue, '', strtotime($match[1]));
		}
	
				
		//Try to find the members
		//[Mon Nov 11 19:27:29 2019] Channel Cranberry(47) members:
		//[Mon Nov 11 19:27:29 2019] Izzn, Efton, Adruger, Wanluan, Eenelil, Arlyana, Valadan, Toastie, Rowansbane, *Xslia
		$arrLines = explode("\r\n", $text);
		$blnStartMembers = false;
		foreach($arrLines as $strLine){
			if(strpos($strLine, 'members:') !== false){
				$blnStartMembers = true;
				
				if(!$timestart){
					$regex = '/\[(.*)\](.*)/';
					$matches = array();
					preg_match($regex, $strLine, $matches);
					if(isset($matches[1])){
						$timestart = $timeend = strtotime($matches[1]);
					}
				}
				continue;
			}
			
			if($blnStartMembers && strpos($strLine, '--') !== false){
				$blnStartMembers = false;
				continue;
			}
			
			if($blnStartMembers){

				
				$strMembers = preg_replace('/\[(.*)\] /', '', $strLine);
				if($strMembers && strlen($strMembers)){
					$arrMembers = explode(', ', $strMembers);
					
					foreach($arrMembers as $strMember){
						if(!in_array($strMember, $arrMembersDone)){
							$lvl = 0;
							$class = '';
							
							if(!in_array(trim($strMember), $arrMembersDone)){
								$data['members'][] = array(trim($strMember), $class, '', $lvl);
								$data['times'][] = array(trim($strMember), $timestart - (1*3600), 'join');
								$data['times'][] = array(trim($strMember), $timeend+(500), 'leave');
								
								$arrMembersDone[] = trim($strMember);
							}
						}
					}

				}
			}
		}
		
		$data['zones'][] = array('unknown zone',  $timestart - (1*3600), $timeend+(500));
	
		return $data;
	}
}
}
?>
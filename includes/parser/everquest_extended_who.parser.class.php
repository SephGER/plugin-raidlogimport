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

if(!class_exists('everquest_extended_who')) {
	class everquest_extended_who extends rli_parser {

		public static $name = 'Everquest \who (extended)';
		public static $xml = false;

		public static function check($text) {
			$back[1] = true;
			// plain text format - nothing to check
			return $back;
		}

		public static function parse($text) {

			$localtime = localtime();
			$dst = $localtime[8];
			//$dst = $this->time->date("I");

			// Determine the event and raid times
			$matches = array();
			$regex = '~\[(?<time>.*)\].*\((?<event>.*)\).*~';
			preg_match_all($regex, $text, $matches, PREG_SET_ORDER);
			foreach($matches as $match) {
				$eventLine = $match[0];
				$regex = '~((\[ANONYMOUS\])|((\[(?<lvl>[0-9]{1,3})\s(?<class>\w*\s?\w*)(\s\((?<title>.*)\))?\])))\s(?<name>\w*)((\s\((?<race>.*)\))*(\s<(?<guild>.*)>)*)*~';
				$a = preg_match_all($regex, $eventLine, $b, PREG_SET_ORDER);
				
				$event = trim($match['event']);
				$start = $end = trim($match['time']);
				
				//Continue if the matched line is a character line
				if($a > 0) continue;
					
				if (!is_numeric($event)) $data['zones'][] = array($event, $dst ? strtotime('+1 hours', strtotime($start)) : strtotime($start), $dst ? strtotime('+1 hours', strtotime($end)) : strtotime($end), 0, $event);
				//if (!is_numeric($event)) $data['zones'][] = array('', strtotime($start), strtotime($end), 0, $event);
			}

			// Determine the members attending the raid
			$matches = array();
			$regex = '~((\[ANONYMOUS\])|((\[(?<lvl>[0-9]{1,3})\s(?<class>\w*\s?\w*)(\s\((?<title>.*)\))?\])))\s(?<name>\w*)((\s\((?<race>.*)\))*(\s<(?<guild>.*)>)*)*~';
			preg_match_all($regex, $text, $matches, PREG_SET_ORDER);
			foreach($matches as $match) {
				$name = trim($match['name']);
				$lvl = trim($match['lvl']);
				$title = (isset($match['title'])) ? trim($match['title']) : '';
				$class = (isset($match['class'])) ? trim($match['class']) : '';
				$race = (isset($match['race'])) ? trim($match['race']) : '';
					
				$data['members'][] = array($name, $class, $race, $lvl);
				$data['times'][] = array($name, time() - (2*3600), 'join');
				$data['times'][] = array($name, time(), 'leave');
			}

			// Add the bench to the raid
			$matches = array();
			$regex = '~\(\w*\).*:\R(\[.*\]\h(?<mbrlist>.*))*~';
			preg_match_all($regex, $text, $matches, PREG_SET_ORDER);
			foreach($matches as $match) {
				$mbrlist = trim($match['mbrlist']);
					
				$regex = '~(?<name>\w+)(,\h)?~';
				preg_match_all($regex, $mbrlist, $mbrmatches, PREG_SET_ORDER);
				foreach($mbrmatches as $mbrmatch) {
					$name = trim($mbrmatch['name']);

					$data['members'][] = array($name);
					$data['times'][] = array($name, time() - (2*3600), 'join');
					$data['times'][] = array($name, time(), 'leave');
				}
			}

			// Determine the loot dropped and purchased
			$regex = '~\[.*\].*\'LOOT:\h(?<item>.*)\h(?<name>\w*)\h(?<cost>[0-9]+)\'~';
			preg_match_all($regex, $text, $matches, PREG_SET_ORDER);
			foreach($matches as $match) {
				$item = trim($match['item']);
				$name = trim($match['name']);
				$cost = trim($match['cost']);
					
				if ($name == 'ROT') $name = '';
					
				$data['items'][] = array($item, $name, $cost, '', '');
			}
			
			//Create one zone and one boss if there are none
			if(count($data['zones']) === 0) $data['zones'][] = array('unknown zone',  time() - (2*4000), time()+(500));

			return $data;
		}
	}
}
?>
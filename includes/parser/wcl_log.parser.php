
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
    class wcl_log extends rli_parser {

        public static $name = 'Generic Loot';
        public static $xml = false;
    
        public static function check($text) {
            json_decode($text);
            $back[1] = json_last_error() === JSON_ERROR_NONE;
            return $back;
        }
        
        public static function parse($text) {
            
            $data = json_decode($text);
    
    
            $start = strtotime($data->start);
            $end = strtotime($data->end);
            $zone = $data->zone;
            $members = $data->members;
    
            $log = [
                'members' => [],
                'times' => [],
                'items' => [],
                'zones' => [
                    [$zone,$start,$end]
                ]
            ];
    
            foreach($members as $member){
                $log['members'][] = [
                    $member,
                    "",
                    "",
                    0
                ];
                $log['times'][] = [
                    $member,
                    $start,
                    "join"
                ];
                $log['times'][] = [
                    $member,
                    $end,
                    "leave"
                ];
            }
            return $log;
        }
        
        private static function floatvalue($val){
            $val = str_replace(",",".",$val);
            $val = preg_replace('/\.(?=.*\.)/', '', $val);
            return floatval($val);
        }
    }
}
?>

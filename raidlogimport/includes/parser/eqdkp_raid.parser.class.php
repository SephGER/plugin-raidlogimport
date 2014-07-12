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

if(!class_exists('eqdkp_raid')) {
class eqdkp_raid extends rli_parser {

	public static $name = 'EQdkpPlus Raid Transformer';
	public static $xml = false;

	public static function check($text) {
		$back[1] = true;
		// plain text format - nothing to check
		return $back;
	}
	
	public static function parse($text) {
		$raidID = register('in')->get('log', 0);
		$arrRaidData = register('pdh')->get('calendar_events', 'export_data', array($raidID));
		
		$data['zones'][] = array(register('pdh')->get('event', 'name', array($arrRaidData['raid_eventid'])), $arrRaidData['timestamp_start'], $arrRaidData['timestamp_end']);

		foreach($arrRaidData['attendees'] as $status => $arrMembers){
			if($status != "confirmed" && $status != "backup") continue;
			
			$standby = ($status == "backup") ? 'standby' : '';
			
			foreach($arrMembers as $member_id){
				$membername = register('pdh')->get('member', 'name', array($member_id));
				$data['members'][] = array($membername);
				
				$data['times'][] = array($membername, $arrRaidData['timestamp_start'], 'join', $standby);
				$data['times'][] = array($membername, $arrRaidData['timestamp_end'], 'leave', $standby);
			}
		}
		return $data;
	}
}
}
?>
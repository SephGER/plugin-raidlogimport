<?php
 /*
 * Project:     EQdkp-Plus Raidlogimport
 * License:     Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
 * Link:		http://creativecommons.org/licenses/by-nc-sa/3.0/
 * -----------------------------------------------------------------------
 * Began:       2009
 * Date:        $Date: 2009-06-09 17:20:27 +0200 (Di, 09 Jun 2009) $
 * -----------------------------------------------------------------------
 * @author      $Author: hoofy_leon $
 * @copyright   2008-2009 hoofy_leon
 * @link        http://eqdkp-plus.com
 * @package     raidlogimport
 * @version     $Rev: 5040 $
 *
 * $Id: rli.class.php 5040 2009-06-09 15:20:27Z hoofy_leon $
 */

if(!defined('EQDKP_INC'))
{
	header('HTTP/1.0 Not Found');
	exit;
}

if(!class_exists('rli_parse'))
{
  class rli_parse
  {
  	private $toload = array();

	public function __construct()
	{
		global $db;
		if(isset($_POST['adjs'])) {
			$this->parse_adjs();
		}
		if(isset($_POST['loots'])) {
			$this->parse_items();
		}
		if(isset($_POST['members'])) {
			$this->parse_members();
		}
		if(isset($_POST['raids'])) {
			if(!isset($_POST['ns'])) {
				$this->parse_raids();
			} else {
				foreach($this->data['raids'] as $key => $raid) {
					foreach($_POST['raids'] as $k => $r) {
						if($k == $key) {
							$this->data['raids'][$k]['value'] = floatvalue($r['value']);
						}
					}
				}
			}
		}
	}

	private function parse_raids()
	{
		global $in, $rli;

	 	foreach($_POST['raids'] as $key => $raid) {
	  		if(!isset($raid['delete'])) {
      			list($day, $month, $year) = explode('.', $in->get('raids:'.$key.':start_date','1.1.1970'), 3);
      			list($hour, $min, $sec) = explode(':', $in->get('raids:'.$key.':start_time','00:00:00'), 3);
      			$raids[$key]['begin'] = mktime($hour, $min, $sec, $month, $day, $year);
      			list($day, $month, $year) = explode('.', $in->get('raids:'.$key.':end_date','1.1.1970'), 3);
      			list($hour, $min, $sec) = explode(':', $in->get('raids:'.$key.':end_time','00:00:00'), 3);
      			$raids[$key]['end'] = mktime($hour, $min, $sec, $month, $day, $year);
      			$raids[$key]['note'] = $in->get('raids:'.$key.':note');
      			$raids[$key]['value'] = $in->get('raids:'.$key.':value', 0.0);
      			$raids[$key]['event'] = $in->get('raids:'.$key.':event');
      			$raids[$key]['bosskill_add'] = $in->get('raids:'.$key.':bosskill_add',0);
      			$raids[$key]['diff'] = $in->get('raids:'.$key.':diff',0);
      			$bosskills = array();
      			if(is_array($raid['bosskills'])) {
      	  			foreach($raid['bosskills'] as $u => $bk) {
      					if(!isset($bk['delete'])) {
    	  					list($hour, $min, $sec) = explode(':', $in->get('raids:'.$key.':bosskills:'.$u.':time', '00:00:00'), 3);
	      					list($day, $month, $year) = explode('.', $in->get('raids:'.$key.':bosskills:'.$u.':date', '1.1.1970'), 3);
      						$bosskills[$u]['time'] = mktime($hour, $min, $sec, $month, $day, $year);
      						$bosskills[$u]['bonus'] = $in->get('raids:'.$key.':bosskills:'.$u.':bonus', 0.0);
      						$bosskills[$u]['name'] = $in->get('raids:'.$key.':bosskills:'.$u.':name');
						}
					}
				}
      			$raids[$key]['bosskills'] = $bosskills;
      			$raids[$key]['timebonus'] = $in->get('raids:'.$key.':timebonus', 0.0);
			}
		}
		$rli->raid->give($raids);
	}

	private function load_members()
	{
	  global $db, $rli, $user, $eqdkp;
      $members = array();
      $sql = "SELECT cache_data FROM __raidlogimport_cache WHERE cache_class = 'member';";
      $member_data = ($eqdkp->config['enable_gzip']) ? unserialize(gzuncompress($db->query_first($sql))) : unserialize($db->query_first($sql));

	  foreach($_POST['members'] as $k => $mem)
	  {
		foreach($member_data as $key => $member)
		{
			if($k == $key)
			{
			  if(!$mem['delete'])
			  {
			  	$members[$key] = $member;
			  	if($members[$key]['name'] != $mem['name'] AND !$mem['alias'])
			  	{
			  		$mem['alias'] = $members[$key]['name'];
			  		$force_alias = true;
			  	}
			  	$members[$key]['name'] = $mem['name'];
				$members[$key]['raid_list'] = $mem['raid_list'];
				$members[$key]['att_dkp_begin'] = (isset($mem['att_begin'])) ? true : false;
				$members[$key]['att_dkp_end'] = (isset($mem['att_end'])) ? true : false;
				if(isset($mem['alias']) or $force_alias)
				{
	                $members[$key]['alias'] = $mem['alias'];
	            }
				if($mem['raid_list'])
				{
					$raids = $mem['raid_list'];
	           		$raid_attendees[$mem['name']] = true;

					foreach($raids as $raid_id)
					{
					  if(($raid_id != $adj_ra['start'] AND $raid_id != $adj_ra['end']) OR !$this->config['attendence_raid'])
					  {
                        $dkp = 0;
						$raid = $this->data['raids'][$raid_id];
						if($this->config['use_dkp'] & 2)
						{
							$dkp = $dkp + $this->calc_timedkp($raid['begin'], $raid['end'], $member, $raid['timebonus']);
						}
						if($this->config['use_dkp'] & 1)
						{
							$dkp = $dkp + $this->calc_bossdkp($raid['bosskills'], $member);
						}
						if($this->config['use_dkp'] & 4)
						{
							$dkp = $dkp + $this->calc_eventdkp($raid['event']);
						}
						if($this->config['attendence_begin'] AND $raid_id == $adj_ra['start'])
						{
							$dkp = $dkp + (($mem['att_begin']) ? $this->config['attendence_begin'] : 0);
						}
						if($this->config['attendence_end'] AND $raid_id == $adj_ra['end'])
						{
							$dkp = $dkp + (($mem['att_end']) ? $this->config['attendence_end'] : 0);
						}
						$dkp = round($dkp, 2);
						if($dkp <  $raid['value'])
						{	//add an adjustment
                          $dkp -= $raid['value'];
						  if($tempkey = $this->check_adj_exists($mem['name'], $user->lang['rli_partial_raid']." ".date('d.m.y H:i:s', $raid['begin'])))
						  {
						  	$this->data['adjs'][$tempkey]['value'] = $dkp;
						  }
						  else
						  {
							$this->data['adjs'][$i]['member'] = $mem['name'];
							$this->data['adjs'][$i]['reason'] = $user->lang['rli_partial_raid']." ".date('d.m.y H:i:s', $raid['begin']);
							$this->data['adjs'][$i]['value'] = $dkp;
							$this->data['adjs'][$i]['event'] = $raid['event'];
							$i++;
						  }
						}
					  }
					}
				}
			  } //delete
			}
		}
	  }

	  $this->auto_minus($raid_attendees);
	  $this->data['members'] = $members;
	}

	function parse_items()
	{
	  $loot_sum = 0;
	  foreach($_POST['loots'] as $k => $loot)
	  {
		if(is_array($this->data['loots']))
		{
    	  foreach($this->data['loots'] as $key => $item)
    	  {
			if($k == $key)
			{
			  if(!$loot['delete'])
			  {
				$this->data['loots'][$key] = $loot;
				$this->data['loots'][$key]['dkp'] = floatvalue($loot['dkp']);
				$this->data['loots'][$key]['time'] = $item['time'];
			  }
			  else
			  {
			  	unset($this->data['loots'][$key]);
			  }
			}
		  }
		}
	  }
	}

	function parse_adjs()
	{
	  $adjs = array();
	  foreach($_POST['adjs'] as $f => $adj)
	  {
		if(!$adj['delete'])
		{
			$adjs[$f] = $adj;
			$adjs[$f]['value'] = floatvalue($adj['value']);
		}
	  }
	  $this->data['adjs'] = $adjs;
	}

	/**
	 *	checks wether all nodes are available (if not optional) and complete.
	 *	returns an array(1 => bool, 2 => array( contains strings of missing/wrong nodes ))
	 *	params: xml => xml to check
	 *			xml_form => array, which describes the xml: array(node => array(node => ''));
	 *					if prefix is "optional:", the node is only checked for completion
	 *					if prefix is "multiple:", all occuring nodes are checked
	 */
	public function check_xml_format($xml, $xml_form, $back=array(1 => true), $pre='')
	{
		foreach($xml_form as $name => $val)
		{
			$optional = false;
			if(strpos($name, 'optional:') !== false)
			{
				$name = str_replace('optional:', '', $name);
				$optional = true;
			}
			$multiple = false;
			if(strpos($name, 'multiple:') !== false)
			{
				$name = str_replace('multiple:', '', $name);
				$multiple = true;
			}
			if($multiple)
			{
				$pre .= $name.'->';
				foreach($val as $nname => $vval)
				{
                	$optional = false;
                    if(strpos($nname, 'optional:') !== false)
                    {
                        $nname = str_replace('optional:', '', $nname);
                        $optional = true;
                    }
                    if((!isset($xml->$name->$nname)) AND !$optional)
                    {
                    	$back[1] = false;
                    	$back[2][] = $pre.$nname;
                    }
                    else
                    {
                      if(isset($xml->$name))
                      {
					    if(is_array($vval))
					    {
						  foreach($xml->$name->children() as $child)
						  {
							$back = $this->check_xml_format($child, $vval, $back, $pre);
						  }
                    	  $pre = substr($pre, 0, -(strlen($nname)+2));
					    }
					    else
					    {
                    	  foreach($xml->$name->children() as $child)
                    	  {
                        	if((!isset($child) OR trim($child) == '') AND !$optional)
                        	{
                            	$back[1] = false;
                            	$back[2][] = $pre.$name;
                        	}
                    	  }
                        }
                      }
                      else
                      {
                          $back[1] = false;
                          $back[2][] = $name;
                      }
					}
					$pre = '';
				}
			}
			else
			{
				if((!isset($xml->$name) OR (trim($xml->$name) == '') AND !is_array($val)) AND !$optional)
				{
					$back[1] = false;
					$back[2][] = $pre.$name;
				}
				else
				{
					if(is_array($val))
					{
						$pre .= $name.'->';
						$back = $this->check_xml_format($xml->$name, $val, $back, $pre);
						$pre = '';
					}
				}
			}
			if(strpos($val, 'function:') !== false)
			{
				$func = str_replace('function:', '', $val);
				$back = call_user_func($func, $xml->name, $back);
			}
		}
		return $back;
	}

	private function check_plus_format($xml)
	{
		$xml = $xml->raiddata;
		$xml_form = array(
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
		return $this->check_xml_format($xml, $xml_form);
	}

	private function parse_plus_string($xml)
	{
		global $eqdkp, $user, $rli;

		if((trim($xml->head->gameinfo->game) == 'Runes of Magic' AND strtolower($eqdkp->config['default_game']) != 'runesofmagic') OR
		   (trim($xml->head->gameinfo->game) == 'World of Warcraft' AND strtolower($eqdkp->config['default_game']) != 'wow'))
		{
			message_die($user->lang['wrong_game']);
		}
		$lang = trim($xml->head->gameinfo->language);
		$rli->add_data['log_lang'] = substr($lang, 0, 2);
		$xml = $xml->raiddata;
		foreach($xml->zones->children() as $zone)
		{
			$rli->raid->add_zone(trim(utf8_decode($zone->name)), (int) trim($zone->enter), (int) trim($zone->leave), (int) trim($zone->difficulty));
		}
		foreach($xml->bosskills->children() as $bosskill)
		{
			$rli->raid->add_bosskill(trim(utf8_decode($bosskill->name)), (int) trim($bosskill->time));
		}
		foreach($xml->members->children() as $xmember)
		{
			$name = trim(utf8_decode($xmember->name));
            $note = (isset($xmember->note)) ? trim(utf8_decode($xmember->note)) : '';
			$rli->member->add($name, trim(utf8_decode($xmember->race)), trim(utf8_decode($xmember->class)), trim($xmember->level), $note);
			foreach($xmember->times->children() as $time)
			{
				$attrs = $time->attributes();
				$type = $attrs['type'];
                $extra = $attrs['extra'];
				$rli->member->add_time($name, $time, $type, $extra);
			}
		}
		foreach($xml->items->children() as $xitem)
		{
			$cost = (isset($xitem->cost)) ? trim($xitem->cost) : '';
			$id = (isset($xitem->itemid)) ? trim($xitem->itemid) : '';
			$rli->item->add(trim(utf8_decode($xitem->name)), trim(utf8_decode($xitem->member)), $cost, $id, (int) trim($xitem->time));
		}
	}

	private function check_eqdkp_format($xml, $magic=false)
	{
		$back[1] = true;
		if(!isset($xml->start))
		{
			$back[1] = false;
			$back[2][] = 'start';
		}
		else
		{
			if(!(stristr($xml->start, ':')))
			{
				$back[1] = false;
				$back[2][] = 'start in format: MM/DD/YY HH:MM:SS';
			}
		}
		if(!isset($xml->end))
		{
		 	$back[1] = false;
		 	$back[2][] = 'end';
		}
		else
		{
			if(!(stristr($xml->start, ':')))
			{
				$back[1] = false;
				$back[2][] = 'end in format: MM/DD/YY HH:MM:SS';
			}
		}
		if(!isset($xml->BossKills))
		{
		  	$back[1] = false;
		  	$back[2][] = 'BossKills';
		}
		else
		{
			foreach($xml->BossKills->children() as $bosskill)
			{
			  if($bosskill)
			  {
				if(!isset($bosskill->name))
				{
					$back[1] = false;
					$back[2][] = 'BossKills->name';
				}
				if(!isset($bosskill->time))
				{
					$back[1] = false;
					$back[2][] = 'BossKills->time';
				}
			  }
			}
		}
		if(!isset($xml->Loot))
		{
		   	$back[1] = false;
		   	$back[2][] = 'Loot';
		}
		else
		{
			foreach($xml->Loot->children() as $loot)
			{
			  if($loot)
			  {
				if(!isset($loot->ItemName))
				{
					$back[1] = false;
					$back[2][] = 'Loot->ItemName';
				}
				if(!isset($loot->Player))
				{
					$back[1] = false;
					$back[2][] = 'Loot->Player';
				}
				if(!isset($loot->Time))
				{
					$back[1] = false;
					$back[2] = 'Loot->Time';
				}
			  }
			}
		}
		if(!$magic)
		{
		  if(!isset($xml->PlayerInfos))
		  {
			$back[1] = false;
			$back[2][] = 'PlayerInfos';
		  }
		  else
		  {
			foreach($xml->PlayerInfos->children() as $mem)
			{
				if(!isset($mem->name))
				{
					$back[1] = false;
					$back[2][] = 'PlayerInfos->name';
				}
			}
		  }
		}
		if(!isset($xml->Join))
		{
			$back[1] = false;
			$back[2][] = 'Join';
		}
		else
		{
			foreach($xml->Join->children() as $join)
			{
				if(!isset($join->player))
				{
					$back[1] = false;
					$back[2][] = 'Join->player';
				}
				if(!isset($join->time))
				{
					$back[1] = false;
					$back[2][] = 'Join->time';
				}
			}
		}
		if(!isset($xml->Leave))
		{
			$back[1] = false;
			$back[2][] = 'Leave';
		}
		else
		{
			foreach($xml->Leave->children() as $leave)
			{
				if(!isset($leave->player))
				{
					$back[1] = false;
					$back[2][] = 'Leave->player';
				}
				if(!isset($leave->time))
				{
					$back[1] = false;
					$back[2][] = 'Leave->time';
				}
			}
		}
		return $back;
	}

	private function parse_eqdkp_string($xml, $magic=false)
	{
		global $user, $rli;

		$rli->raid->add_zone(trim($xml->zone), strtotime($xml->start), strtotime($xml->end), trim($xml->difficulty));
		foreach ($xml->BossKills->children() as $bosskill)
		{
			$rli->raid->add_bosskill(trim($bosskill->name), strtotime($bosskill->time));
		}
		foreach($xml->Loot->children() as $loot)
		{
		  $player = uft8_decode(trim($loot->Player));
		  if(!(($rli->config['ignore_dissed'] & 1 AND $player == 'disenchanted') OR ($rli->config['ignore_dissed'] & 2 AND $player == 'bank')))
		  {
            $cost = (array_key_exists('Costs', $loot)) ? (int) $loot->Costs : (int) $loot->Note;
			$rli->item->add(utf8_decode(trim($loot->ItemName)), $player, $cost, substr(trim($loot->ItemID), 0, 5), strtotime($loot->Time));
		  }
		}
		if(!$magic)
		{
		  foreach($xml->PlayerInfos->children() as $xmember)
		  {
			$rli->member->add(trim(utf8_decode($xmember->name)), trim(utf8_decode($xmember->class)), trim(utf8_decode($xmember->race)), trim($xmember->level), trim(utf8_decode($xmember->note)));
		  }
		}
		foreach ($xml->Join->children() as $joiner)
		{
			$rli->member->add_time(utf8_decode(trim($joiner->player)), strtotime($joiner->time), 'join');
		}
		foreach ($xml->Leave->children() as $leaver)
		{
			$rli->member->add_time(uft8_decode(trim($leaver->player)), strtotime($leaver->time), 'leave');
		}
	}

	private function parse_magicdkp_string($xml)
	{
		return parse_eqdkp_string($xml, true);
	}

	private function check_magicdkp_format($xml)
	{
		return parse_eqdkp_format($xml, true);
	}

	public function parse_string($xml)
	{
		global $user, $eqdkp_root_path, $rli;

		if(method_exists($this, 'parse_'.$rli->config('parser').'_string'))
		{
			$back = call_user_func(array($this, 'check_'.$rli->config('parser').'_format'), $xml);
			if($back[1])
			{
				call_user_func(array($this, 'parse_'.$rli->config('parser').'_string'), $xml);
				$rli->raid->create();
				$rli->raid->recalc(true);
				$rli->member->finish();
			}
			else
			{
			  message_die($user->lang['wrong_format'].' '.$user->lang[$rli->config('parser').'_format'].'<br />'.$user->lang['rli_miss'].implode(', ', $back[2]));
			}
		}
		else
		{
			message_die($user->lang['no_parser']);
		}
	}

	public function __destruct()
	{
	}
  }
}
?>
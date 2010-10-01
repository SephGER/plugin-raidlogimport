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

if(!class_exists('rli_item')) {
class rli_item {
	private $items = array();

	public function __construct() {
		global $rli;
		$this->items = $rli->get_cache_data('item');
	}

	private function config($name) {
		global $rli;
		return $rli->config($name);
	}
	
	public function add($name, $member, $value, $id=0, $time=0, $raid=0) {
		$this->items[] = array('name' => $name, 'member' => $member, 'value' => $value, 'game_id' => $id, 'time' => $time, 'raid' => $raid);
	}
	
	public function load_items() {
		$this->items = array();
		foreach($_POST['loots'] as $k => $loot) {
			if(is_array($this->data['loots'])) {
				foreach($this->data['loots'] as $key => $item) {
					if($k == $key) {
						if(!$loot['delete']) {
							$this->data['loots'][$key] = $loot;
							$this->data['loots'][$key]['dkp'] = floatvalue($loot['dkp']);
							$this->data['loots'][$key]['time'] = $item['time'];
						} else {
							unset($this->data['loots'][$key]);
						}
					}
				}
			}
		}
	}
	
	public function display($with_form=false) {$members = array(); //for select in loots
		foreach($rli->data['members'] as $key => $member)
		{
			$tpl->assign_block_vars('player', mems2tpl($key, $member, $rli->data));
			$members['name'][$member['name']] = $member['name'];
			if(isset($member['alias']))
			{
				$aliase[$member['alias']] = $member['name'];
			}
		}
		if($rli->config['null_sum'] AND $rli->config['auto_minus'])
		{
			$sql = "SELECT member_name FROM __members ORDER BY member_name ASC;";
			$mem_res = $db->query($sql);
			while ( $mrow = $db->fetch_record($mem_res) )
			{
				$members['name'][$mrow['member_name']] = $mrow['member_name'];
			}
		}

		//add disenchanted and bank
		$members['name']['disenchanted'] = 'disenchanted';
		$members['name']['bank'] = 'bank';
		$maxkey = 0;

		//create rename_table
		$sql = "CREATE TABLE IF NOT EXISTS item_rename (
				`id` INT NOT NULL PRIMARY KEY,
				`item_name` VARCHAR(255) NOT NULL,
				`item_id` INT NOT NULL,
				`item_name_trans` VARCHAR(255) NOT NULL);";
		$db->query($sql);

		$sql = "SELECT * FROM item_rename;";
		$result = $db->query($sql);
		while($row = $db->fetch_record($result))
		{
			$loot_cache[$row['id']]['name'] = $row['item_name'];
			$loot_cache[$row['id']]['trans'] = $row['item_name_trans'];
			$loot_cache[$row['id']]['itemid'] = $row['item_id'];
		}

		if(is_array($rli->data['loots']))
		{
		$start = 0;
		$end = $p+1;
		if($vars = ini_get('suhosin.post.max_vars'))
		{
			$vars = $vars - 5;
			$dic = $vars/6;
			settype($dic, 'int');
			$page = 1;

			if(!(strpos($_POST['checkitem'], $user->lang['rli_itempage']) === FALSE))
			{
				$page = str_replace($user->lang['rli_itempage'], '', $_POST['checkitem']);
			}
			if($page >= 1)
			{
				$start = ($page-1)*$dic;
				$page++;
			}
			$end = $start+$dic;
		}
		$rli->iteminput2tpl($loot_cache, $start, $end, $members, $aliase);
		}

		if($rli->config['null_sum'])
		{
			$next_button = '<input type="submit" name="nullsum" value="'.$user->lang['rli_go_on'].' ('.$user->lang['check_raidval'].')" class="mainoption" />';
		}
		else
		{
			if($rli->config['deactivate_adj'])
			{
				$next_button = '<input type="submit" name="insert" value="'.$user->lang['rli_go_on'].' ('.$user->lang['rli_insert'].')" class="mainoption" />';
			}
			else
			{
				$next_button = '<input type="submit" name="checkadj" value="'.$user->lang['rli_go_on'].' ('.$user->lang['rli_checkadj'].')" class="mainoption" />';
			}
		}

		if($end <= $p AND $end)
		{
			$next_button = '<input type="submit" name="checkitem" value="'.$user->lang['rli_itempage'].(($page) ? $page : 2).'" class="mainoption" />';
		}
		elseif($end+$dic >= $p AND $dic)
		{
			$next_button .= ' <input type="submit" name="checkitem" value="'.$user->lang['rli_itempage'].(($page) ? $page : 2).'" class="mainoption" />';
		}
	}
	
	public function __destruct() {
		global $rli;
		$rli->add_cache_data('item', $this->items);
	}
}
}
?>
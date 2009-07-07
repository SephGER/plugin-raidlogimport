<?php
 /*
 * Project:     EQdkp-Plus Raidlogimport
 * License:     Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
 * Link:		http://creativecommons.org/licenses/by-nc-sa/3.0/
 * -----------------------------------------------------------------------
 * Began:       2008
 * Date:        $Date: 2009-05-07 17:52:03 +0200 (Do, 07 Mai 2009) $
 * -----------------------------------------------------------------------
 * @author      $Author: hoofy_leon $
 * @copyright   2008-2009 hoofy_leon
 * @link        http://eqdkp-plus.com
 * @package     raidlogimport
 * @version     $Rev: 4786 $
 *
 * $Id: renameitems.php 4786 2009-05-07 15:52:03Z hoofy_leon $
 */

// EQdkp required files/vars
define('EQDKP_INC', true);
define('IN_ADMIN', true);
$eqdkp_root_path = './../../../';
include_once($eqdkp_root_path . 'common.php');
include_once($eqdkp_root_path . 'itemstats/includes/urlreader.php');
$user->check_auth('a_raidlogimport_dkp');
My_ob_start();

if(strtolower($eqdkp->config['default_game']) == 'wow')
{
	function get_itemID($itemname, $lang)
	{
		$url_lang = ($lang == 'en') ? 'www' : $lang;
		$encoded_name = urlencode(utf8_encode($itemname));
		$encoded_name = str_replace('+' , '%20' , $encoded_name);
    	$encoded_name = str_replace(' ' , '%20' , $encoded_name);
		$item_xml = simplexml_load_string(itemstats_read_url('http://'.$url_lang.'.wowhead.com/?item='.$encoded_name.'&xml', $lang));
		return trim($item_xml->item['id']);
	}

	function get_itemname($itemID, $lang)
	{
		$url_lang = ($lang == 'en') ? 'www' : $lang;
		$item_xml = simplexml_load_string(itemstats_read_url('http://'.$url_lang.'.wowhead.com/?item='.$itemID.'&xml', $lang));
		return utf8_decode(trim($item_xml->item->name));
	}
}
elseif(strtolower($eqdkp->config['default_game']) == 'runesofmagic')
{
	include_once($eqdkp_root_path . 'itemstats/includes/rom_blasc.php');
	$parseblasc = new ParseBlasc;
	function get_itemID($itemname, $lang)
	{
		global $parseblasc;
		return $parseblasc->searchitemid($itemname);
	}

	function get_itemname($itemID, $lang, $itemname='')
	{
		global $conf_plus, $parseblasc;
		$lang = ($lang == 'de') ? 'deDE' : 'enUS';
		if($conf_plus['pk_is_useitemlist'])
		{
			$parseblasc->getItemlist();
			foreach($parseblasc->itemlist as $iteml)
			{
				if($iteml['id'] == $itemID)
				{
					return $iteml[$lang];
				}
			}
		}
        settype($itemID, 'int');
		if(!$itemID) return null;
		$url = ($lang == 'deDE') ? 'buffed.de' : 'getbuffed.com';
		$itemxml = itemstats_read_url('http://romdata.'.$url.'/tooltiprom/items/xml/'.$itemID.'.xml', $lang);
		$xmltoarray = new XmlToArray;
		$itemdata = $xmltoarray->parse($itemxml);
		$itemdata = $itemdata[0]['child'];
		$item_name = (isUTF8($itemdata[2]['data'])) ? utf8_decode($itemdata[2]['data']) : $itemdata[2]['data'];
		unset($itemdata);
		$parseblasc->close();
		return $item_name;
	}
}

$sql = "SELECT id, item_name, item_id FROM item_rename;";
$result = $db->query($sql);
$ii = 0;
while ($row = $db->fetch_record($result))
{
	$items[$ii]['id'] = $row['id'];
	$items[$ii]['name'] = $row['item_name'];
	$items[$ii]['itemID'] = $row['item_id'];
	$ii++;
}

if($_GET['actual'] <= $_GET['count'])
{
	$nextactual = $_GET['actual']+1;

	$output = 'actual item: '.$items[$_GET['actual']]['name'].'<br />';
	if(!$items[$_GET['actual']]['itemID'])
	{
		$items[$_GET['actual']]['itemID'] = get_itemID($items[$_GET['actual']]['name'], $_GET['langfrom']);
        $output .= 'get itemid (search lang: '.$_GET['langfrom'].'): '.$items[$_GET['actual']]['itemID'].'<br />';
	}
	$output .= 'rename item to: '.$_GET['langto'].'<br />';
	if($_GET['langfrom'] != $_GET['langto'])
	{
		$renamed = get_itemname($items[$_GET['actual']]['itemID'], $_GET['langto'], $items[$_GET['actual']]['name']);
	}
	else
	{
		$renamed = $items[$_GET['actual']]['name'];
	}
	$output .= 'new itemname: '.$renamed;
	$sql = "UPDATE item_rename SET item_id = '".$items[$_GET['actual']]['itemID']."', item_name_trans = '".mysql_real_escape_string($renamed)."' WHERE id = '".$items[$_GET['actual']]['id']."';";
	$db->query($sql);
	echo $output;

	header('Refresh: 0.1; url=renameitems.php?count='.$_GET['count'].'&actual='.$nextactual.'&langto='.$_GET['langto'].'&langfrom='.$_GET['langfrom']);
	ob_get_contents();
}
if($_GET['actual'] > $_GET['count'])
{
	echo "finished";
}



?>

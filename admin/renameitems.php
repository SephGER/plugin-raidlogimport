<?php

// EQdkp required files/vars
define('EQDKP_INC', true);
define('IN_ADMIN', true);
$eqdkp_root_path = './../../../';
include_once($eqdkp_root_path . 'common.php');
include_once($eqdkp_root_path . 'itemstats/includes/urlreader.php');
$user->check_auth('a_raidlogimport_dkp');
My_ob_start();

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
		$renamed = get_itemname($items[$_GET['actual']]['itemID'], $_GET['langto']);
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
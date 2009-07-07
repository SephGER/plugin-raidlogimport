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
 * $Id: 0519-0522.php 4786 2009-05-07 15:52:03Z hoofy_leon $
 */

if(!defined('EQDKP_INC'))
{
	header('HTTP/1.0 404 Not Found');
	exit;
}

$new_version    = '0.5.2.2';
$updateFunction = 'addUlduar';

$updateDESC = array(
	'',
	'Added Ulduar Triggers',
);
$reloadSETT = false;

$updateSQL = array(
	"INSERT INTO __raidlogimport_bz (bz_type, bz_string, bz_note, bz_bonus, bz_tozone, bz_sort) VALUES ('zone', 'Ulduar', 'Ulduar', '5', '0', '4');",
);

function addUlduar()
{
	global $db;
	$id = $db->query_first("SELECT bz_id FROM __raidlogimport_bz WHERE bz_string = 'Ulduar';");
	$bz_data = array(
		26 => array('boss', 'Flame Leviathan', 'Leviathan', '3', $id, '0'),
		27 => array('boss', 'Ignis the Furnace Master', 'Ignis', '3', $id, '1'),
	    28 => array('boss', 'Razorscale', 'Razorscale', '3', $id, '2'),
	    29 => array('boss', 'XT-002 Deconstructor', 'XT-002', '3', $id, '3'),
	    30 => array('boss', 'The Iron Council', 'Iron Council', '3', $id, '4'),
	    31 => array('boss', 'Kologarn', 'Kologarn', '3', $id, '5'),
	    32 => array('boss', 'Auriaya', 'Auriaya', '3', $id, '6'),
	    33 => array('boss', 'Hodir', 'Hodir', '3', $id, '7'),
	    34 => array('boss', 'Thorim', 'Thorim', '3', $id, '8'),
	    35 => array('boss', 'Freya', 'Freya', '3', $id, '9'),
	    36 => array('boss', 'Mimiron', 'Mimiron', '3', $id, '10'),
	    37 => array('boss', 'General-Vezax', 'Vezax', '3', $id, '11'),
	    38 => array('boss', 'Yogg-Saron', 'Yoggy', '4', $id, '12'),
	    39 => array('boss', 'Algalon the Observer', 'Algalon', '4', $id, '13')
	);
	foreach($bz_data as $bz)
	{
		$sql = "INSERT INTO __raidlogimport_bz
				(bz_type, bz_string, bz_note, bz_bonus, bz_tozone, bz_sort)
				VALUES
				('".$bz[0]."', '".mysql_escape_string($bz[1])."', '".mysql_escape_string($bz[2])."', '".$bz[3]."', '".$bz[4]."', '".$bz[5]."');";
		$db->query($sql);
	}
}
?>

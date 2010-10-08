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
 * $Id: bz_sql.php 4786 2009-05-07 15:52:03Z hoofy_leon $
 */
$english  = array(
#			    type  |  string   | note  |dkp|bonusph|diff|tozone|order
	 1 => array('zone', 'Naxxramas', 'Naxx', '0', '5', '0', '0', '0'),
     2 => array('zone', 'The Obsidian Sanctum', 'Sanctum', '0', '5', '0', '0', '1'),
	 3 => array('zone', 'The Eye of Eternity', 'Malygos', '0', '5', '0', '0', '2'),
     4 => array('zone', 'Vault of Archavon', 'Archavon', '0', '5', '0', '0', '3'),
     5 => array('zone', 'Ulduar', 'Ulduar', '0', '5', '0', '0', '4'),
     6 => array('zone', 'Trial of the Crusader', 'Coliseum', '0', '5', '2', '0', '5'),
     7 => array('zone', 'Trial of the Crusader', 'Coliseum', '0', '5', '4', '0', '6'),
     8 => array('zone', 'Onyxia\'s Lair', 'Onyxia', '5', '0', '0', '0', '7'),
     9 => array('zone', 'Icecrown Citadel', 'Icecrown', '0', '5', '2', '0', '8'),
    10 => array('zone', 'Icecrown Citadel', 'Icecrown', '0', '10', '4', '0', '9'),
	11 => array('zone', 'Ruby Sanctum', 'Ruby Sanctum', '0', '5', '2', '0', '10'),
	12 => array('zone', 'Ruby Sanctum', 'Ruby Sanctum', '0', '5', '4', '0', '11'),
	13 => array('boss', 'Loatheb', 'Loatheb', '2', '0', '0', '1', '5'),
	14 => array('boss', 'Instructor Razuvious', 'Razuvious', '2', '0', '0', '1', '6'),
	15 => array('boss', 'Gothik the Harvester', 'Gothik', '2', '0', '0', '1', '7'),
	16 => array('boss', 'Four Horsemen', 'Reiter', '2', '0', '0', '1', '8'),
	17 => array('boss', "Anub'Rekhan", "Anub'rekhan", '2', '0', '0', '1', '0'),
	18 => array('boss', 'Grand Widow Faerlina', 'Faerlina', '2', '0', '0', '1', '1'),
	19 => array('boss', 'Maexxna', 'Maexxna', '2', '0', '0', '1', '2'),
	20 => array('boss', 'Noth the Plaguebringer', 'Noth', '2', '0', '0', '1', '3'),
	21 => array('boss', 'Heigan the Unclean', 'Heigan', '2', '0', '0', '1', '4'),
	22 => array('boss', 'Patchwerk', 'Patchwerk', '2', '0', '0', '1', '9'),
	23 => array('boss', 'Grobbulus', 'Grobbulus', '2', '0', '0', '1', '10'),
	24 => array('boss', 'Gluth', 'Gluth', '2', '0', '0', '1', '11'),
	25 => array('boss', 'Thaddius', 'Thaddius', '2', '0', '0', '1', '12'),
    26 => array('boss', 'Sapphiron', 'Sapphiron', '2', '0', '0', '1', '13'),
	27 => array('boss', "Kel'Thuzad", "Kel'Thuzad", '4', '0', '0', '1', '14'),
    28 => array('boss', 'Flame Leviathan', 'Leviathan', '3', '0', '0', '5', '0'),
    29 => array('boss', 'Ignis the Furnace Master', 'Ignis', '3', '0', '0', '5', '1'),
    30 => array('boss', 'Razorscale', 'Razorscale', '3', '0', '0', '5', '2'),
    31 => array('boss', 'XT-002 Deconstructor', 'XT-002', '3', '0', '0', '5', '3'),
    32 => array('boss', 'The Iron Council', 'Iron Council', '3', '0', '0', '5', '4'),
    33 => array('boss', 'Kologarn', 'Kologarn', '3', '0', '0', '5', '5'),
    34 => array('boss', 'Auriaya', 'Auriaya', '3', '0', '0', '5', '6'),
    35 => array('boss', 'Hodir', 'Hodir', '3', '0', '0', '5', '7'),
    36 => array('boss', 'Thorim', 'Thorim', '3', '0', '0', '5', '8'),
    37 => array('boss', 'Freya', 'Freya', '3', '0', '0', '5', '9'),
    38 => array('boss', 'Mimiron', 'Mimiron', '3', '0', '0', '5', '10'),
    39 => array('boss', 'General-Vezax', 'Vezax', '3', '0', '0', '5', '11'),
    40 => array('boss', 'Yogg-Saron', 'Yoggy', '4', '0', '0', '5', '12'),
    41 => array('boss', 'Algalon the Observer', 'Algalon', '4', '0', '0', '5', '13'),
    42 => array('boss', 'Emalon the Storm Watcher', 'Emalon', '2', '0', '0', '4', '1'),
    43 => array('boss', 'Northrend Beasts', 'Beasts', '2', '0', '2', '6', '0'),
    44 => array('boss', 'Lord Jaraxxus', 'Jaraxxus', '2', '0', '2', '6', '1'),
    45 => array('boss', 'Faction Champions', 'Champions', '3', '0', '2', '6', '2'),
    46 => array('boss', 'Twin Val\'kyr', 'Twin Val\'kyr', '3', '0', '2', '6', '3'),
    47 => array('boss', 'Anub\'arak', 'Anub\'arak', '4', '0', '2', '6', '4'),
    48 => array('boss', 'Northrend Beasts', 'Beasts', '2', '0', '4', '7', '0'),
    49 => array('boss', 'Lord Jaraxxus', 'Jaraxxus', '2', '0', '4', '7', '1'),
    50 => array('boss', 'Faction Champions', 'Champions', '3', '0', '4', '7', '2'),
    51 => array('boss', 'Twin Val\'kyr', 'Twin Val\'kyr', '3', '0', '4', '7', '3'),
    52 => array('boss', 'Anub\'arak', 'Anub\'arak', '4', '0', '4', '7', '4'),
    53 => array('boss', 'Koralon the Flame Watcher', 'Koralon', '2', '0', '0', '4', '2'),
    55 => array('boss', 'Onyxia', 'Onyxia', '2', '0', '0', '8', '0'),
    57 => array('boss', 'Lord Marrowgar', 'Marrowgar', '2', '0', '2', '9', '0'),
    58 => array('boss', 'Lady Deathwhisper', 'Deathwhisper', '2', '0', '2', '9', '1'),
    59 => array('boss', 'Gunship Battle', 'Gunship', '2', '0', '2', '9', '2'),
    60 => array('boss', 'Deathbringer Saurfang', 'Saurfang', '2', '0', '2', '9', '3'),
    61 => array('boss', 'Festergut', 'Festergut', '2', '0', '2', '9', '4'),
    62 => array('boss', 'Rotface', 'Rotface', '2', '0', '2', '9', '5'),
    63 => array('boss', 'Professor Putricide', 'Putricide', '2', '0', '2', '9', '6'),
    64 => array('boss', 'Blood Prince Council', 'Blood Council', '2', '0', '2', '9', '7'),
    65 => array('boss', 'Blood-Queen Lana\'thel', 'Lana\'thel', '2', '0', '2', '9', '8'),
    66 => array('boss', 'Valithiria Dreamwalker', 'Dreamwalker', '2', '0', '2', '9', '9'),
    67 => array('boss', 'Sindragosa', 'Sindragosa', '2', '0', '2', '9', '10'),
    68 => array('boss', 'The Lich King', 'Arthas', '2', '0', '2', '9', '11'),
    69 => array('boss', 'Toravon the Ice Watcher', 'Toralon', '2', '0', '0', '4', '3'),
    71 => array('boss', 'Lord Marrowgar', 'Marrowgar', '2', '0', '4', '10', '0'),
    72 => array('boss', 'Lady Deathwhisper', 'Deathwhisper', '2', '0', '4', '10', '1'),
    73 => array('boss', 'Gunship Battle', 'Gunship', '2', '0', '4', '10', '2'),
    74 => array('boss', 'Deathbringer Saurfang', 'Saurfang', '2', '0', '4', '10', '3'),
    75 => array('boss', 'Festergut', 'Festergut', '2', '0', '4', '10', '4'),
    76 => array('boss', 'Rotface', 'Rotface', '2', '0', '4', '10', '5'),
    77 => array('boss', 'Professor Putricide', 'Putricide', '2', '0', '4', '10', '6'),
    78 => array('boss', 'Blood Prince Council', 'Blood Council', '2', '0', '4', '10', '7'),
    79 => array('boss', 'Blood-Queen Lana\'thel', 'Lana\'thel', '2', '0', '4', '10', '8'),
    80 => array('boss', 'Valithiria Dreamwalker', 'Dreamwalker', '2', '0', '4', '10', '9'),
    81 => array('boss', 'Sindragosa', 'Sindragosa', '2', '0', '4', '10', '10'),
    82 => array('boss', 'The Lich King', 'Arthas', '2', '0', '4', '10', '11'),
	83 => array('boss', 'Halion the Twilight Destroyer', 'Halion', '4', '0', '2', '11', '0'),
	84 => array('boss', 'Halion the Twilight Destroyer', 'Halion', '5', '0', '4', '12', '0'),
	85 => array('boss', 'Malygos', 'Malygos', '4', '0', '0', '3', '0'),
	86 => array('boss', 'Sartharion', 'Sartharion', '2', '0', '0', '2', '0'),
	87 => array('boss', 'Sartharion 1D', 'Sartharion 1D', '4', '0', '0', '2', '1'),
	88 => array('boss', 'Sartharion 2D', 'Sartharion 2D', '6', '0', '0', '2', '2'),
	89 => array('boss', 'Sartharion 3D', 'Sartharion 3D', '8', '0', '0', '2', '3'),
    90 => array('boss', 'Archavon the Stone Watcher', 'Archavon', '2', '0', '0', '4', '0'),
);
//$german = array();
//$spanish = array();
?>

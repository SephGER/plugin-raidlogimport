<?php
 /*
 * Project:     EQdkp-Plus Raidlogimport
 * License:     Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
 * Link:		http://creativecommons.org/licenses/by-nc-sa/3.0/
 * -----------------------------------------------------------------------
 * Began:       2008
 * Date:        $Date: 2009-08-07 15:48:04 +0200 (Fr, 07 Aug 2009) $
 * -----------------------------------------------------------------------
 * @author      $Author: hoofy_leon $
 * @copyright   2008-2009 hoofy_leon
 * @link        http://eqdkp-plus.com
 * @package     raidlogimport
 * @version     $Rev: 5563 $
 *
 * $Id: bz_sql.php 5563 2009-08-07 13:48:04Z hoofy_leon $
 */
$bz_data  = array(
#			    type  |  string   | note  |dkp|bonusph|diff|tozone.order
	0 => array('zone', 'Naxxramas', 'Naxx', '0', '5', '', '0'),
	1 => array('boss', 'Loatheb', 'Loatheb', '2', '0', '', '1.5'),
	2 => array('boss', 'Instructor Razuvious', 'Razuvious', '2', '0', '', '1.6'),
	3 => array('boss', 'Gothik the Harvester', 'Gothik', '2', '0', '', '1.7'),
	4 => array('boss', 'Four Horsemen', 'Reiter', '2', '0', '', '1.8'),
	5 => array('boss', "Anub'Rekhan", "Anub'rekhan", '2', '0', '', '1.0'),
	6 => array('boss', 'Grand Widow Faerlina', 'Faerlina', '2', '0', '', '1.1'),
	7 => array('boss', 'Maexxna', 'Maexxna', '2', '0', '', '1.2'),
	8 => array('boss', 'Noth the Plaguebringer', 'Noth', '2', '0', '', '1.3'),
	9 => array('boss', 'Heigan the Unclean', 'Heigan', '2', '0', '', '1.4'),
	10 => array('boss', 'Patchwerk', 'Patchwerk', '2', '0', '', '1.9'),
	11 => array('boss', 'Grobbulus', 'Grobbulus', '2', '0', '', '1.10'),
	12 => array('boss', 'Gluth', 'Gluth', '2', '0', '', '1.11'),
	13 => array('boss', 'Thaddius', 'Thaddius', '2', '0', '', '1.12'),
    14 => array('boss', 'Sapphiron', 'Sapphiron', '2', '0', '', '1.13'),
	15 => array('boss', "Kel'Thuzad", "Kel'Thuzad", '4', '0', '', '1.14'),
	16 => array('zone', 'The Eye of Eternity', 'Malygos', '0', '5', '', '2'),
	17 => array('boss', 'Malygos', 'Malygos', '4', '0', '', '17.0'),
    18 => array('zone', 'The Obsidian Sanctum', 'Sanctum', '0', '5', '', '1'),
	19 => array('boss', 'Sartharion', 'Sartharion', '2', '0', '', '19.0'),
	20 => array('boss', 'Sartharion 1D', 'Sartharion 1D', '4', '0', '', '19.1'),
	21 => array('boss', 'Sartharion 2D', 'Sartharion 2D', '6', '0', '', '19.2'),
	22 => array('boss', 'Sartharion 3D', 'Sartharion 3D', '8', '0', '', '19.3'),
    23 => array('zone', 'Vault of Archavon', 'Archavon', '0', '5', '', '3'),
    24 => array('boss', 'Archavon the Stone Watcher', 'Archavon', '2', '0', '', '24.0'),
    25 => array('zone', 'Ulduar', 'Ulduar', '0', '5', '', '4'),
    26 => array('boss', 'Flame Leviathan', 'Leviathan', '3', '0', '', '26.0'),
    27 => array('boss', 'Ignis the Furnace Master', 'Ignis', '3', '0', '', '26.1'),
    28 => array('boss', 'Razorscale', 'Klingenschuppe', '3', '0', '', '26.2'),
    29 => array('boss', 'XT-002 Deconstructor', 'XT-002', '3', '0', '', '26.3'),
    30 => array('boss', 'The Iron Council', 'Eiserne Rat', '3', '0', '', '26.4'),
    31 => array('boss', 'Kologarn', 'Kologarn', '3', '0', '', '26.5'),
    32 => array('boss', 'Auriaya', 'Auriaya', '3', '0', '', '26.6'),
    33 => array('boss', 'Hodir', 'Hodir', '3', '0', '', '26.7'),
    34 => array('boss', 'Thorim', 'Thorim', '3', '0', '', '26.8'),
    35 => array('boss', 'Freya', 'Freya', '3', '0', '', '26.9'),
    36 => array('boss', 'Mimiron', 'Mimiron', '3', '0', '', '26.10'),
    37 => array('boss', 'General-Vezax', 'Vezax', '3', '0', '', '26.11'),
    38 => array('boss', 'Yogg-Saron', 'Yogg-Saron', '4', '0', '', '26.12'),
    39 => array('boss', 'Algalon the Observer', 'Algalon', '4', '0', '', '26.13'),
    40 => array('boss', 'Emalon the Storm Watcher', 'Emalon', '2', '0', '', '24.1'),
    41 => array('zone', 'Trial of the Crusader', 'Kolosseum', '0', '5', '2', '5'),
    42 => array('boss', 'Northrend Beasts', 'Bestien', '2', '0', '2', '42.0'),
    43 => array('boss', 'Lord Jaraxxus', 'Jaraxxus', '2', '0', '2', '42.1'),
    44 => array('boss', 'Faction Champions', 'Champions', '3', '0', '2', '42.2'),
    45 => array('boss', 'Twin Val\'kyr', 'Zwillingsval\'kyr', '3', '0', '2', '42.3'),
    46 => array('boss', 'Anub\'arak', 'Anub\'arak', '4', '0', '2', '42.4'),
    47 => array('zone', 'Trial of the Crusader', 'Kolosseum', '0', '5', '4', '6'),
    48 => array('boss', 'Northrend Beasts', 'Bestien', '2', '0', '4', '48.0'),
    49 => array('boss', 'Lord Jaraxxus', 'Jaraxxus', '2', '0', '4', '48.1'),
    50 => array('boss', 'Faction Champions', 'Champions', '3', '0', '4', '48.2'),
    51 => array('boss', 'Twin Val\'kyr', 'Zwillingsval\'kyr', '3', '0', '4', '48.3'),
    52 => array('boss', 'Anub\'arak', 'Anub\'arak', '4', '0', '4', '48.4'),
    53 => array('boss', 'Koralon the Flame Watcher', 'Koralon', '2', '0', '', '24.2'),
    54 => array('zone', 'Onyxia\'s Lair', 'Onyxia', '0', '5', '', '7'),
    55 => array('boss', 'Onyxia', 'Onyxia', '2', '0', '', '55.0'),
    56 => array('zone', 'Icecrown Citadel', 'Eiskrone', '0', '5', '2', '8'),
    57 => array('boss', 'Lord Marrowgar', 'Mark\'gar', '2', '0', '2', '57.0'),
    58 => array('boss', 'Lady Deathwhisper', 'Todeswisper', '2', '0', '2', '57.1'),
    59 => array('boss', 'Gunship Battle', 'Kanonenschiff', '2', '0', '2', '57.2'),
    60 => array('boss', 'Deathbringer Saurfang', 'Saurfang', '2', '0', '2', '57.3'),
    61 => array('boss', 'Festergut', 'Fauldarm', '2', '0', '2', '57.4'),
    62 => array('boss', 'Rotface', 'Modermiene', '2', '0', '2', '57.5'),
    63 => array('boss', 'Professor Putricide', 'Seuchenmord', '2', '0', '2', '57.6'),
    64 => array('boss', 'Blood Prince Council', 'Rat des Blutes', '2', '0', '2', '57.7'),
    65 => array('boss', 'Blood-Queen Lana\'thel', 'Lana\'thel', '2', '0', '2', '57.8'),
    66 => array('boss', 'Valithiria Dreamwalker', 'Traumwandler', '2', '0', '2', '57.9'),
    67 => array('boss', 'Sindragosa', 'Sindragosa', '2', '0', '2', '57.10'),
    68 => array('boss', 'The Lich King', 'Arthas', '2', '0', '2', '57.11'),
    69 => array('boss', 'Toravon the Ice Watcher', 'Toravon', '2', '0', '', '24.3'),
    70 => array('zone', 'Icecrown Citadel', 'Eiskrone', '0', '5', '4', '8'),
    71 => array('boss', 'Lord Marrowgar', 'Mark\'gar', '2', '0', '4', '71.0'),
    72 => array('boss', 'Lady Deathwhisper', 'Todeswisper', '2', '0', '4', '71.1'),
    73 => array('boss', 'Gunship Battle', 'Kanonenschiff', '2', '0', '4', '71.2'),
    74 => array('boss', 'Deathbringer Saurfang', 'Saurfang', '2', '0', '4', '71.3'),
    75 => array('boss', 'Festergut', 'Fauldarm', '2', '0', '4', '71.4'),
    76 => array('boss', 'Rotface', 'Modermiene', '2', '0', '4', '71.5'),
    77 => array('boss', 'Professor Putricide', 'Seuchenmord', '2', '0', '4', '71.6'),
    78 => array('boss', 'Blood Prince Council', 'Rat des Blutes', '2', '0', '4', '71.7'),
    79 => array('boss', 'Blood-Queen Lana\'thel', 'Lana\'thel', '2', '0', '4', '71.8'),
    80 => array('boss', 'Valithiria Dreamwalker', 'Traumwandler', '2', '0', '4', '71.9'),
    81 => array('boss', 'Sindragosa', 'Sindragosa', '2', '0', '4', '71.10'),
    82 => array('boss', 'The Lich King', 'Arthas', '2', '0', '4', '71.11'),
	83 => array('zone', 'Ruby Sanctum', 'Rubin Sanktum', '0', '5', '2', '10'),
	84 => array('boss', 'Halion the Twilight Destroyer', 'Halion', '4', '0', '2', '84.0'),
	85 => array('zone', 'Ruby Sanctum', 'Rubin Sanktum', '0', '5', '4', '11'),
	86 => array('boss', 'Halion the Twilight Destroyer', 'Halion', '5', '0', '4', '86.0'),
);
?>
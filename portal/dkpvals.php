<?php
 /*
 * Project:     EQdkp-Plus Raidlogimport
 * License:     Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
 * Link:		http://creativecommons.org/licenses/by-nc-sa/3.0/
 * -----------------------------------------------------------------------
 * Began:       2008
 * Date:        $Date$
 * -----------------------------------------------------------------------
 * @author      $Author$
 * @copyright   2008-2010 hoofy_leon
 * @link        http://eqdkp-plus.com
 * @package     raidlogimport
 * @version     $Rev$
 *
 * $Id$
 */

if ( !defined('EQDKP_INC') ) {
	header('HTTP/1.0 404 Not Found');exit;
}

$portal_module['dkpvals'] = array(
            'name'              => 'DKP-Value Module',
            'path'              => 'dkpvals',
            'version'           => '1.1.0',
            'author'            => 'Hoofy',
            'contact'           => 'http://www.eqdkp-plus.com',
            'positions'     => array('left1', 'left2', 'right'),
      'signedin'      => '1',
      'install'       => array(
                            'autoenable'        => '1',
                            'defaultposition'   => 'right',
                            'defaultnumber'     => '3',
                          ),
);

if(!class_exists('rli_portal')) {
	class rli_portal {
	
	public $bonus = array();
	
	function __construct() {
		global $db;
		if(!$this->bonus) {
  		  $sql = "SELECT bz_id, bz_note, bz_bonus, bz_type, bz_bonusph, bz_diff, bz_sort FROM __raidlogimport_bz;";
		  if($result = $db->query($sql))
		  {
			while($row = $db->fetch_record($result))
			{
				if($row['bz_type'] == 'boss')
				{
					$this->bonus['boss'][$row['bz_id']]['note'] = $row['bz_note'];
					$this->bonus['boss'][$row['bz_id']]['bonus'] = $row['bz_bonus'];
					$this->bonus['boss'][$row['bz_id']]['bonusph'] = $row['bz_bonusph'];
					$this->bonus['boss'][$row['bz_id']]['diff'] = $row['bz_diff'];
					if(strpos($row['bz_sort'], '.') !== false) {
						list($tozone, $sort) = explode('.', $row['bz_sort']);
					} else {
						$tozone = false;
						$sort = $row['bz_sort'];
					}
					$this->bonus['boss'][$row['bz_id']]['tozone'] = $tozone;
				}
				else
				{
					$this->bonus['zone'][$row['bz_id']]['note'] = $row['bz_note'];
					$this->bonus['zone'][$row['bz_id']]['bonus'] = $row['bz_bonus'];
					$this->bonus['zone'][$row['bz_id']]['bonusph'] = $row['bz_bonusph'];
					$this->bonus['zone'][$row['bz_id']]['diff'] = $row['bz_diff'];
				}
			}
		  } else {
		  	$this->bonus = false;
		  }
		}
	}
	
	function create_zone_array()
	{
		$arr = array();
		foreach($this->bonus['zone'] as $zone_id => $zone)
		{
			$arr[$zone_id] = $zone['note'];
		}
		return $arr;
	}

	function get_zone($zone_id)
	{
        $zone = $this->bonus['zone'][$zone_id];
		$output = "<table width='100%' cellpadding='1' cellspacing='1' class='forumline'>
					<tr><th width='66%'>".$zone['note']."</th><th width='34%'>".$zone['bonusph']."/h</th></tr>";
		foreach($this->bonus['boss'] as $boss_id => $boss)
		{
			if($i != 1) { $i = 2; }
			if($boss['tozone'] == $zone_id)
			{
				$output .= "<tr class='row".$i."'><td>".$boss['note']."</td><td>".$boss['bonus']." (".$boss['bonusph']."/h)</td></tr>";
			}
			$i--;
		}
		return $output."</table>";
	}
  }
}
if(!function_exists('create_dkpval_settings')) {
  function create_dkpval_settings() {
	$rli_portal = new rli_portal;
	return array(
		'pk_rli_zone_0' => array(
		'name'		=> 'rli_zone_display',
		'language'	=> 'p_rli_zone_display',
		'property'	=> 'multiselect',
		'options' 	=> $rli_portal->create_zone_array(),
		)
	);
  }
}

$portal_settings['dkpvals'] = create_dkpval_settings();

if(!function_exists('dkpvals_module')) {
  function dkpvals_module()
  {
  	global $user, $conf_plus, $eqdkp;
  	
  	$rli_portal = new rli_portal;

  	$out = "<table width='100%'border='0' cellspacing='1' cellpadding='2'>
  				<tr><th width='66%'>".$user->lang['bz_zone_s']."</th><th width='34%'>".$eqdkp->config['dkp_name']."</th></tr>";
  	foreach($rli_portal->bonus['zone'] as $zone_id => $zone)
  	{
  		$zones2display = explode('|', $conf_plus['rli_zone_display']);
  		if(in_array($zone_id, $zones2display))
  		{
  			$out .= "<tr><td colspan='2'>".$rli_portal->get_zone($zone_id)."</td></tr>";
  		}
  	}
  	return $out."</table>";
  }
}



?>
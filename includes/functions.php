<?php
 /*
 * Project:     EQdkp-Plus Raidlogimport
 * License:     Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
 * Link:		http://creativecommons.org/licenses/by-nc-sa/3.0/
 * -----------------------------------------------------------------------
 * Began:       2008
 * Date:        $Date: 2009-06-09 17:20:27 +0200 (Di, 09 Jun 2009) $
 * -----------------------------------------------------------------------
 * @author      $Author: hoofy_leon $
 * @copyright   2008-2009 hoofy_leon
 * @link        http://eqdkp-plus.com
 * @package     raidlogimport
 * @version     $Rev: 5040 $
 *
 * $Id: functions.php 5040 2009-06-09 15:20:27Z hoofy_leon $
 */

if(!defined('EQDKP_INC')) {
	header('HTTP/1.0 Not Found');
	exit;
}

if(!function_exists('floatvalue')) {
    function floatvalue($value) {
        return floatval(preg_replace('#^([-]*[0-9\.,]+?)((\.|,)([0-9-]+))*$#e', "str_replace(array('.', ','), '', '\\1') . '.\\4'", $value));
    }
}

function __rli_autoload($name) {
	global $eqdkp_root_path;
	if(file_exists($eqdkp_root_path.'plugins/raidlogimport/includes/'.$name.'.class.php')) {
		require_once($eqdkp_root_path.'plugins/raidlogimport/includes/'.$name.'.class.php');
	}
}
spl_autoload_register('__rli_autoload');

if(!function_exists('stripslashes_array')) {
	function stripslashes_array($array) {
		return is_array($array) ? array_map('stripslashes_array', $array) : stripslashes($array);
	}
}

function format_duration($seconds) {
    $periods = array(
        'hours' => 3600,
        'minutes' => 60,
        'seconds' => 1
    );

    $durations = array();
    $durations['hours'] = 0;
    $durations['minutes'] = 0;

    foreach ($periods as $period => $seconds_in_period) {
        if ($seconds >= $seconds_in_period) {
            $durations[$period] = floor($seconds / $seconds_in_period);
            $seconds -= $durations[$period] * $seconds_in_period;
        }
    }
    return $durations;

}

function fktMultiArraySearch($arrInArray,$varSearchValue) {
    foreach ($arrInArray as $key => $row){
        $ergebnis = array_search($varSearchValue, $row);
        if ($ergebnis) {
            $arrReturnValue[0] = $key;
            $arrReturnValue[1] = $ergebnis;
            return $arrReturnValue;
        }
    }
}

function deep_in_array($search, $array) {
	foreach($array as $value) {
		if(!is_array($value)) {
			if($search === $value) return true;
		} else {
			if(deep_in_array($search, $value)) return true;
		}
	}
	return false;
}

function lang2tpl()
{
	global $tpl, $user, $rli, $core;
	$la_ar = array(
		'L_ADJ_ADD'		=> $user->lang['rli_add_adj'],
		'L_ADJS_ADD'	=> $user->lang['rli_add_adjs'],
		'L_ADJS'		=> $user->lang['rli_adjs'],
        'L_ATT'         => $user->lang['rli_att'],
        'L_B_DKP'       => $user->lang['rli_b_dkp'],
        'L_BACK2ITEM'	=> $user->lang['rli_back2item'],
        'L_BACK2MEM'    => $user->lang['rli_back2mem'],
        'L_BACK2RAID'   => $user->lang['rli_back2raid'],
        'L_BK_ADD'		=> $user->lang['rli_add_bk'],
        'L_BKS_ADD'		=> $user->lang['rli_add_bks'],
        'L_BOSSKILLS'   => $user->lang['rli_bosskills'],
        'L_CHECKADJS'	=> $user->lang['rli_checkadj'],
        'L_CHECKITEMS'  => $user->lang['rli_checkitem'],
        'L_CHECKMEM'    => $user->lang['rli_checkmem'],
        'L_CHECK_RAIDVAL' => $user->lang['check_raidval'],
        'L_COST'		=> $user->lang['rli_cost'],
        'L_DATE'		=> $user->lang['date'],
        'L_DELETE'		=> $user->lang['delete'],
        'L_DIFFICULTY' 	=> ($core->config['default_game'] == 'wow') ? $user->lang['difficulty'] : false,
		'L_DIFF_1'		=> $user->lang['diff_1'],
		'L_DIFF_2'		=> $user->lang['diff_2'],
		'L_DIFF_3'		=> $user->lang['diff_3'],
		'L_DIFF_4'		=> $user->lang['diff_4'],
        'L_END'         => $user->lang['rli_end'],
        'L_EVENT'       => $user->lang['event'],
        'L_GO_ON'		=> $user->lang['rli_go_on'],
        'L_INSERT'		=> $user->lang['rli_insert'],
        'L_ITEM'		=> $user->lang['item'],
        'L_ITEM_ADD'	=> $user->lang['rli_add_item'],
        'L_ITEM_ID'		=> $user->lang['rli_item_id'],
		'L_ITEMPOOL'	=> $user->lang['itempool'],
        'L_ITEMS_ADD'	=> $user->lang['rli_add_items'],
        'L_LOOTER'		=> $user->lang['rli_looter'],
        'L_MEM_ADD'     => $user->lang['rli_add_mem'],
        'L_MEMS_ADD'	=> $user->lang['rli_add_mems'],
        'L_MEMBER'      => $user->lang['member'],
        'L_MEMBERS'     => $user->lang['members'],
        'L_NAME'		=> $user->lang['name'],
        'L_NOTE'        => $user->lang['note'],
        'L_PROCESS'		=> $user->lang['rli_process'],
        'L_RAID'        => $user->lang['raid'],
        'L_RAID_ADD'    => $user->lang['rli_add_raid'],
        'L_RAIDS_ADD'	=> $user->lang['rli_add_raids'],
        'L_RAIDS'       => $user->lang['raids'],
        'L_RECALC_RAID'	=> $user->lang['rli_calc_note_value'],
		'L_START'		=> $user->lang['rli_start'],
		'L_T_DKP'		=> $user->lang['rli_t_points'],
		'L_TIME'		=> $user->lang['time'],
		'L_TRANSLATE_ITEMS' => ($rli->add_data['log_lang'] == $rli->config('item_save_lang')) ? $user->lang['get_itemid'] : $user->lang['translate_items'],
		'L_TRANSLATE_ITEMS_TIP' => $user->lang['translate_items_tip'],
		'L_UPD'			=> $user->lang['update'],
        'L_VALUE'       => $user->lang['value']
	);
	return $la_ar;
}
?>
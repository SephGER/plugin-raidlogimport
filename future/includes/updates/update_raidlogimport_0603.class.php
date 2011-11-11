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

if(!defined('EQDKP_INC')) {
	header('HTTP/1.0 Not Found');
	exit;
}

include_once(registry::get_const('root_path').'maintenance/includes/sql_update_task.class.php');
if (!class_exists('update_raidlogimport_0603')) {
class update_raidlogimport_0603 extends sql_update_task {
	public $author      = 'Hoofy';
	public $version     = '0.6.0.3';
	public $name        = 'Raidlogimport 0.6.0.3 Update';
	public $type        = 'plugin_update';
	public $plugin_path = 'raidlogimport';
	
	private $data		= array();

	public function __construct() {
		parent::__construct();

		// init language
		$this->langs = array(
			'english' => array(
				'update_raidlogimport_0603' => 'Raidlogimport 0.6.0.3 Update Package',
				'update_function' => 'New config: No delete warning',
			),
			'german' => array(
				'update_raidlogimport_0603' => 'Raidlogimport 0.6.0.3 Update Package',
				'update_function' => 'Neue Einstellung: keine Löschen-Warnung',
			),
		);
	}

	public function update_function() {
		$this->config->set('no_del_warn', 0, 'raidlogimport');
		return true;
	}
}
}
if(version_compare(PHP_VERSION, '5.3.0', '<')) registry::add_const('short_update_raidlogimport_0603', update_raidlogimport_0603::__shortcuts());
?>
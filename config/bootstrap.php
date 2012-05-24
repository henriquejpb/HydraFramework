<?php
require_once 'defines.php';
require_once 'autoload.php';
require_once 'error_handling.php';

Core::getInstance(Core::getInstance(
		array(
			Core::APP_ROOT => dirname(dirname(__FILE__)))
		))
	->setLocalization(new Localization('America/Campo_Grande', array('pt_BR', 'pt_BR.iso-8859-1', 'pt_BR.utf-8', 'portuguese')));

/* O arquivo db_config.php deve ser um array da forma:
 * 
 * return array (
 *	'mysqli' => array(
 *		'host' => 'HOST',
 *		'username' => 'USER',
 *		'password' => 'SENHA',
 *		'dbname' => 'DB'
 *	)
 *); 
 */
$dbConf = require_once INIT_DIR . 'db_config.php';
Db_Table::setDefaultAdapter(
	Db::factory(
		'Mysqli', 
		$dbConf['mysqli']
	)
);

$defConf = require_once INIT_DIR . 'table_definition.php';
$definition = new Db_Table_Definition(
	$defConf
);

Db_Table::setDefaultDefinition($definition);
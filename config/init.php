<?php
require_once 'config/defines.php';
require_once 'config/autoload.php';
require_once 'config/error_handling.php';

Core::getInstance(dirname(dirname(__FILE__)))
	->setLocalization(new Localization('America/Campo_Grande', array('pt_BR', 'pt_BR.iso-8859-1', 'pt_BR.utf-8', 'portuguese')));

Db_Table::setDefaultAdapter(
	Db::factory(
		'Mysqli', 
		array (
			'host' => 'localhost',
			'username' => 'root',
			'password' => '1234',
			'dbname' => 'test'
		)
	)
);

$definition = new Db_Table_Definition(
	array(
		'comments' => array(
			'referenceMap' => array(
				'ownerUser' => array(
					Db_Table::COLUMNS => 'user_id',
					Db_Table::REF_COLUMNS => 'id',
					Db_Table::REF_TABLE => 'users'
				)
			)
		)
	)
);

Db_Table::setDefaultDefinition($definition);
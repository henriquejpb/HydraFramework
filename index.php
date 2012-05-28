<?php
require_once 'config/bootstrap.php';

// $select = new Db_Select($db);
// $select->from('comments AS c');

// echo $select->assemble().PHP_EOL;
// $data = $db->fetchAll($select);
// print_r($data);
		
// Cache::set('teste', 'bla', 'woooow! Ventiladores...', '+ 10 SECONDS');


// var_dump(Cache::get('teste', 'bla'));
//Cache::remove('teste', 'bla');

$dbConf = require INIT_DIR . 'db_config.php';
$mysql = Db::factory(
		'Mysqli',
		$dbConf['mysqli']
);

$pgsql = Db::factory('Pgsql', $dbConf['pgsql']);
$table = new Db_Table('mesa');
var_dump($table->setAdapter($pgsql)->fetchAll(null, 'numero')->toArray());

//$table = new Db_Table('users');
//print_r($table->getById(1)->findDependentRowset('comments')->toArray());

// $res = new Controller_Response();

// $res->appendBody('foo');
// $res->insert('bla', 'baz', 'default', true);

// echo $res;
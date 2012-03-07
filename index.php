<?php
require_once 'config/init.php';

// $db = Db::factory('Mysqli', array('host' => 'localhost',
// 									'username' => 'root',
// 									'password' => '1234',
// 									'dbname' => 'test'));

// $select = new Db_Select($db);
// $select->from('comments AS c');

// echo $select->assemble().PHP_EOL;
// $data = $db->fetchAll($select);
// print_r($data);
		
// Cache::set('teste', 'bla', 'woooow! Ventiladores...', '+ 10 SECONDS');


// var_dump(Cache::get('teste', 'bla'));
//Cache::remove('teste', 'bla');



// $table = new Db_Table('users');
// print_r($table->getById(1)->findDependentRowset('comments')->toArray());

$res = new Controller_Response();

$res->appendBody('foo');
$res->insert('bla', 'baz', 'default', true);

echo $res;
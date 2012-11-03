<?php
require_once 'library/Hydra/Core.php';

$core = Hydra_Core::getInstance(array(Hydra_Core::ROOT => dirname(__FILE__)))
	->setLocalization(
		new Hydra_Localization(
			'America/Campo_Grande',
			array('pt_BR', 'pt_BR.iso-8859-1', 'pt_BR.utf-8', 'portuguese')
	)
);

// $core->setEnvironment(Hydra_Core::PRODUCTION);
require $core->getConfigFile('error_handling');

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
$adapter = new Hydra_Util_DottalNotation_Adapter();
$array = [
	'a' => [
		'b' => [
			'c' => 1,
			'd' => 2
		]
	]
];
$adapter->set($array, 'a.b.c.d.f', 1);
$adapter->set($array, 'a.b.c.d.e', 1);
var_dump($array);
// $dbConf = $core->getIni('db_config');
// Hydra_Db_Table::setDefaultAdapter(
// 	Hydra_Db::factory(
// 		'Mysqli',
// 		$dbConf['Mysqli']
// 	)
// );

// Hydra_Db_Table::setDefaultCacheHandler(new Hydra_Cache_Facade());

// Hydra_Db_Table::setDefaultDefinition(
// 	new Hydra_Db_Table_Definition(
// 		$core->getIni('table_def')
// 	)
// );

// $table = new Hydra_Db_Table('mesa');
// $table = new Hydra_Db_Table('comments');

// print_r($table->fetchAll()->toArray());
// $row = $table->fetchOne($select);
// var_dump($row->isReadOnly());
// $row['status'] = 0;
// $row->save();
// print_r($table->getById(4)->toArray());


// Hydra_View::setDefaultPath(Hydra_Core::getInstance()->getAppRoot() . 'view/');

// $homeView = new Hydra_View('home');
// $headView = new Hydra_View('head');
// $contentView = new Hydra_View('content');

// $homeView->head = $headView;
// $homeView->content = $contentView;

// $headView->title = 'Teste CompositeView';
// $headView->charset = 'iso-8859-1';

// $contentView->header = '<header><h1>Isto é um HEADER!</h1></header>';
// $contentView->aside = '<aside>Isto é um conteúdo lateral!</aside>';
// $contentView->main = '<section id="main">Este é o conteúdo principal</section>';
// $contentView->footer = '<footer>' . strftime ("%A, %d de %B de %Y") . '</footer>';

// echo $homeView;

// print_r($norm);
// var_dump($norm->offsetExists('a'));

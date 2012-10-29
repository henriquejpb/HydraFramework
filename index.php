<?php
require_once 'library/Hydra/Core.php';

$core = Core::getInstance(array(Core::ROOT => dirname(__FILE__)))
	->setLocalization(
		new Localization(
			'America/Campo_Grande',
			array('pt_BR', 'pt_BR.iso-8859-1', 'pt_BR.utf-8', 'portuguese')
	)
);

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
$dbConf = $core->getIni('db_config');
Db_Table::setDefaultAdapter(
	Db::factory(
		'Pgsql',
		$dbConf['pgsql']
	)
);

Db_Table::setDefaultCacheHandler(new Cache_Facade());

// Db_Table::setDefaultDefinition(
// 	new Db_Table_Definition(
// 		$core->getIni('table_def')
// 	)
// );

$table = new Db_Table('mesa');
// $table = new Db_Table('comments');

print_r($table->fetchAll()->toArray());
// $row = $table->fetchOne($select);
// var_dump($row->isReadOnly());
// $row['status'] = 0;
// $row->save();
// print_r($table->getById(4)->toArray());


// View::setDefaultPath(Core::getInstance()->getAppRoot() . 'view/');

// $homeView = new View('home');
// $headView = new View('head');
// $contentView = new View('content');

// $homeView->head = $headView;
// $homeView->content = $contentView;

// $headView->title = 'Teste CompositeView';
// $headView->charset = 'iso-8859-1';

// $contentView->header = '<header><h1>Isto � um HEADER!</h1></header>';
// $contentView->aside = '<aside>Isto � um conte�do lateral!</aside>';
// $contentView->main = '<section id="main">Este � o conte�do principal</section>';
// $contentView->footer = '<footer>' . strftime ("%A, %d de %B de %Y") . '</footer>';

// echo $homeView;

// print_r($norm);
// var_dump($norm->offsetExists('a'));

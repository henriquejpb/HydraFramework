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

$db = 'Mysqli';
$dbConf = $core->getIni('db_config');
Hydra_Db_Table::setDefaultAdapter(
	Hydra_Db::factory(
		$db,
		$dbConf[$db]
	)
);

Hydra_Db_Table::setDefaultCacheHandler(new Hydra_Cache_Facade());

Hydra_Db_Table::setDefaultDefinition(
	new Hydra_Db_Table_Definition(
		$core->getIni('table_def')
	)
);

$table = new Hydra_Db_Table('usuario');
$select = $table->select(array('nome_completo'));
$result = $table->fetchAll($select)->toArray();
var_dump($result);
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
// $contentView->main = $table->fetchAll($select)->toArray();
// $contentView->footer = '<footer>' . strftime ("%A, %d de %B de %Y") . '</footer>';

// $response = new Hydra_Controller_Response();
// $response->setHeader('content-type', 'text/html;charset=utf-8');
// $response->appendBody($homeView->__toString());
// $response->setAutoSend(true);


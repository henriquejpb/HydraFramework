<?php
require_once 'config/bootstrap.php';

View::setDefaultPath(Core::getInstance()->getAppRoot() . 'view/');

$homeView = new View('home');
$headView = new View('head');
$contentView = new View('content');

$homeView->head = $headView;
$homeView->content = $contentView;

$headView->title = 'Teste CompositeView';
$headView->charset = 'iso-8859-1';

$contentView->header = '<header><h1>Isto � um HEADER!</h1></header>';
$contentView->aside = '<aside>Isto � um conte�do lateral!</aside>';
$contentView->main = '<section id="main">Este � o conte�do principal</section>';
$contentView->footer = '<footer>Este � o rodap�</footer>';

echo $homeView;
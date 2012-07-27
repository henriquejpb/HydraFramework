<?php
require_once 'config/bootstrap.php';

// $homeView = new View('home');
// $headView = new View('head');
// $contentView = new View('content');

// $homeView->head = $headView;
// $homeView->content = $contentView;

// $headView->title = 'Teste CompositeView';
// $headView->charset = 'iso-8859-1';

// $contentView->header = '<header><h1>Isto é um HEADER!</h1></header>';
// $contentView->aside = '<aside>Isto é um conteúdo lateral!</aside>';
// $contentView->main = '<section id="main">Este é o conteúdo principal</section>';
// $contentView->footer = '<footer>' . strftime ("%A, %d de %B de %Y") . '</footer>';

// echo $homeView;

$foo = array('a.d' => 'foo', 'a.c' => 'bar');
$norm = Util_Array::fromArray($foo);
// $newIt = new ArrayIterator($norm->getData());
// $norm->setIterator($newIt);
// $it = $norm->getIterator();
// $it->setMaxDepth(2);
var_dump($norm['a.d']);

// print_r($norm);
// var_dump($norm->offsetExists('a'));
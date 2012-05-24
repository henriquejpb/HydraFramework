<?php
require_once 'Loader.php';

$loader = new Loader(dirname(__FILE__));
spl_autoload_register(array($loader, 'autoload'));
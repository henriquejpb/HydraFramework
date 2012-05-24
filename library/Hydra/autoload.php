<?php
require_once 'Loader.php';

spl_autoload_register(array(new Loader(dirname(__FILE__)), 'autoload'));
<?php
define('PS', PATH_SEPARATOR);
define('DS', DIRECTORY_SEPARATOR);

define('ROOT', dirname(dirname(__FILE__)) . DS);

define('SYSTEM_DIR', ROOT . 'system' . DS);
// Deve possuir permissão 0777
define('CACHE_DIR', SYSTEM_DIR . 'cache' . DS);
// Deve possuir permissão 0777
define('LOG_DIR', SYSTEM_DIR . 'log' . DS);

define('CONFIG_DIR', ROOT . 'config' . DS);
define('INIT_DIR', CONFIG_DIR . 'init' . DS);

define('ENVOIREMENT', 'developing');
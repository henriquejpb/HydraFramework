<?php
define('PS', PATH_SEPARATOR);
define('DS', DIRECTORY_SEPARATOR);

define('ROOT', dirname(dirname(__FILE__)) . DS);

define('SYSTEM_DIR', ROOT . 'system' . DS);
define('CACHE_DIR', SYSTEM_DIR . 'cache' . DS);
define('LOG_DIR', SYSTEM_DIR . 'log' . DS);

define('ENVOIREMENT', 'developing');
<?php
set_include_path(get_include_path() . PATH_SEPARATOR . ROOT);
spl_autoload_extensions('.php,.inc,.log,.cache');

include_once('library/Hydra/autoload.php');
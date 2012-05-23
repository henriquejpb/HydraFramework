<?php
require_once 'library/Hydra/Core.php';
require_once 'library/Hydra/Loader.php';

Loader::setCoreInstance(Core::getInstance(
							array(
								Core::APP_ROOT => dirname(dirname(__FILE__)))
							)
						);

function __autoload($className) {
	Loader::autoload($className);
}
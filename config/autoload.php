<?php
require_once 'library/Core/Loader.php';

function __autoload($className) {
	Loader::autoload($className);
}
<?php
set_error_handler('errorHandler');

function errorHandler($errCode, $errDesc, $errFile = null, $errLine = null, $errContext = null) {
	throw new ErrorException($errDesc, $errCode, null, $errFile, $errLine);
}
<?php
/**
 * Representação do ambiente do servidor.
 * @author <a href="mailto:rick.hjpbacelos@gmail.com">Henrique Barcelos</a>
 */
abstract class Environment {
	/**
	 * Retorna uma variável do ambiente (em $_SERVER ou $_ENV).
	 * 
	 * @param string $key
	 * @return string|null
	 */
	public static function getVar($key) {
		$key = strtoupper($key);
		
		if($key == 'HTTPS') {
			if(isset($_SERVER['HTTPS'])) {
				return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off');
			}
			return (strpos(self::getVar('SCRIPT_URI'), 'https://') === 0);
		}
		
		if($key == 'SCRIPT_NAME') {
			if(self::getVar('CGI_MODE') && isset($_ENV['SCRIPT_URL'])) {
				$key = 'SCRIPT_URL';
			}
		}
		
		$val = null;
		
		if(isset($_SERVER[$key])) {
			$val = $_SERVER[$key];
		} else if(isset($_ENV[$key])) {
			$val = $_ENV[$key];
		} else if(getenv($key) !== false) {
			$val = getenv($key);
		}
		
		if($key == 'REMOTE_ADDR' && $val === self::getVar('SERVER_ADDR')) {
			$addr = self::getVar('HTTP_PC_REMOTE_ADDR');
			if($addr !== null) {
				$val = $addr;
			}
		}
		
		if($val !== null) {
			return $val;
		} else {
			switch($key) {
				case 'SCRIPT_FILENAME':
					if(defined('SERVER_IIS') && SERVER_IIS === true) {
						return str_replace('\\\\', '\\', self::getVar('PATH_TRANSLATED'));
					}
					break;
				case 'DOCUMENT_ROOT':
					$name = self::getVar('SCRIPT_NAME');
					$filename = self::getVar('SCRIPT_FILENAME');
					$offset = strpos($name, '.php') ? 0 : 4;
					return substr($filename, 0,  strlen($filename) - (strlen($name) + $offset));
					break;
				case 'PHP_SELF':
					return str_replace(self::getVar('DOCUMENT_ROOT'), '', self::getVar('SCRIPT_FILENAME'));
					break;
				case 'CGI_MODE':
					return (PHP_SAPI == 'cgi');
					break;
			}
		}
		
		return null;
	}
}
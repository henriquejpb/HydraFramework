<?php
require_once 'Core.php';
require_once 'Loader/Exception.php';

abstract class Loader {
	const DS = DIRECTORY_SEPARATOR;
	
	/**
	 * Armazena os paths de bibliotecas para autoloading
	 * @var array
	 */
	private static $_libraryPaths = array();
	
	/**
	 * Responsável pelo autoloading.
	 * @param string $className
	 */
	public static function autoload($className) {
		$file = str_replace('_', self::DS, $className);
		
		if(!in_array(Core::getInstance()->getCoreLib(), self::$_libraryPaths)) {
			self::$_libraryPaths = array(Core::getInstance()->getCoreLib());
		}
		foreach(self::$_libraryPaths as $lib) {
			$path = Core::getInstance()->getLibraryDir() . $lib . self::DS . $file . '.php';
			if(is_file($path)) {
				require_once $path;
				return;
			}
		}

		throw new Loader_Exception(sprintf('Classe %s não encontrada!', $className));
	}
	
	/**
	 * Seta os caminhos das bibilotecas para autoloading
	 * @param array $paths
	 */
	public static function setLibraryPaths(array $paths) {
		foreach($paths as $dir) {
			self::addLibraryPath($dir);			
		}
	}
	
	/**
	 * Adiciona um caminho 
	 * @param unknown_type $path
	 * @throws Loader_Exception
	 */
	public static function addLibraryPath($path) {
		if(!is_dir($path)) {
			throw new Loader_Exception(sprintf('O caminho %s não é um diretório válido!', $path));
		}
		
		if(!in_array($path, self::$_libraryPaths)) {
			self::$_libraryPaths[] = $path;
		}
	}
}
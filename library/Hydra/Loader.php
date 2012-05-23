<?php
require_once 'Core.php';
require_once 'Loader/Exception.php';

/**
 * Carregador de classes.
 * 
 * @author <a href="mailto:rick.hjpbacelos@gmail.com">Henrique Barcelos</a>
 * @version 0.1.1
 * 
 * <p>
 * 	Data de modificação: 06/03/2011
 * </p>
 * <h2>Lista de Modificações</h2>
 * <ul>
 * 	<li>Utilização de injeção de dependências estática, afim de melhorar a
 * 	testabilidade</li>
 * </ul>
 */
abstract class Loader {
	const DS = DIRECTORY_SEPARATOR;
	
	/**
	 * Armazena os paths de bibliotecas para autoloading
	 * @var array
	 */
	private static $_libraryPaths = array();
	
	/**
	 * Armazena a instância do Core do framework
	 * @var Core
	 */
	private static $_coreInstance;
	
	/**
	 * Responsável pelo autoloading.
	 * @param string $className
	 * @throw Loader_Exception : caso uma instância de Core não seja configurada no bootstrap
	 */
	public static function autoload($className) {
		if(! self::$_coreInstance instanceof Core) {
			throw new Loader_Exception('Core não especificado em Loader!');
		}
		
		$file = str_replace('_', self::DS, $className);
		
		if(!in_array(self::$_coreInstance->getCoreLib(), self::$_libraryPaths)) {
			self::$_libraryPaths = array(Core::getInstance()->getCoreLib());
		}
		foreach(self::$_libraryPaths as $lib) {
			$path = self::$_coreInstance->getLibraryDir() . $lib . self::DS . $file . '.php';
			if(is_file($path)) {
				require $path;
				return;
			}
		}

		throw new Loader_Exception(sprintf('Classe %s não encontrada!', $className));
	}
	
	/**
	 * Seta a instância do Core do framework.
	 * @param Core $core
	 * @return void
	 */
	public static function setCoreInstance(Core $core) {
		self::$_coreInstance = $core;
	}
	
	/**
	 * Seta os caminhos das bibilotecas para autoloading
	 * @param array $paths
	 * @return void
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
	 * @return void
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
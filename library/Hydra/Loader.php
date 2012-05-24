<?php
require_once 'Core.php';
require_once 'Loader/Exception.php';

/**
 * Carregador de classes.
 * 
 * @author <a href="mailto:rick.hjpbacelos@gmail.com">Henrique Barcelos</a>
 * @version 0.5
 * 
 * <p>
 * 	Data de modificação: 23/05/2011
 * </p>
 * <h2>Lista de Modificações</h2>
 * <ul>
 * 	<li>
 *		A classe não é mais abstrata
 * 	</li>
 * 	<li>
 *		A partir de agora, é para ser usada com o autoloader da SPL
 * 	</li>
 * </ul>
 */
class Loader {
	const DS = DIRECTORY_SEPARATOR;
	
	/**
	 * Armazena o caminho para o diretório do framework Hydra
	 * @var array
	 */
	private $_hydraPath;
	
	/**
	 * 
	 * @param string $path
	 */
	public function __construct($path) {
		if(!is_dir($path)) {
			throw new Exception('Diretório inválido para autoload!');
		}
		$this->_hydraPath = $path;
	}
	
	/**
	 * Responsável pelo autoloading.
	 * @param string $className
	 * @throw Loader_Exception : caso uma instância de Core não seja configurada no bootstrap
	 */
	public function autoload($className) {
		$file = str_replace('_', self::DS, $className);
		
		$path = realpath($this->_hydraPath ) . self::DS . $file . '.php';
		if(is_file($path)) {
			require $path;
			return;
		}

		throw new Loader_Exception(sprintf('Classe %s não encontrada!', $className));
	}
}
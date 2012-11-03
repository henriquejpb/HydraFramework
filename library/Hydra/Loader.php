<?php
require_once 'Loader/Exception.php';

/**
 * Carregador de classes.
 *
 * @author <a href="mailto:rick.hjpbacelos@gmail.com">Henrique Barcelos</a>
 * @version 0.8
 *
 * <p>
 * 	Data de modificação: 11/07/2011
 * </p>
 * <h2>Lista de Modificações</h2>
 * <ul>
 * 	<li>
 *		Hydra_Loader agora é genérico, pode ser utilizado tanto para o Hydra,
 *		quanto para módulos de terceiros, que sigam a estrutura Zend.
 * 	</li>
 * </ul>
 */
class Hydra_Loader {
	const DS = DIRECTORY_SEPARATOR;

	/**
	 * Armazena o caminho para o diretório base para autoload
	 * @var array
	 */
	private $_path;

	/**
	 *
	 * @param string $path
	 */
	public function __construct($path) {
		if(!is_dir($path)) {
			throw new Exception('Diretório inválido para autoload!');
		}
		$this->_path = $path;
		spl_autoload_register(array($this, 'autoload'));
	}

	/**
	 * Responsável pelo autoloading.
	 * @param string $className
	 */
	public function autoload($className) {
		$file = str_replace('_', self::DS, $className);

		$path = realpath($this->_path ) . self::DS . $file . '.php';
		if(is_file($path)) {
			require $path;
			return;
		}
	}
}
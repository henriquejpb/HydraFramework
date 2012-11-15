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
	private $_path = array();

	/**
	 * @param string|array $path : caminho base para o autoloading ou um array de paths
	 */
	public function __construct($path) {
		$this->addPath($path);
	}
	
	/**
	 * Adiciona um caminho ao objeto Loader.
	 * 
	 * @param string|array $path : string com um caminho válido ou ou um array de caminhos
	 * @return Loader : fluent interface
	 * @throws InvalidArgumentException
	 */
	public function addPath($path) {
		if(is_array($path)) {
			foreach($path as $p) {
				$this->addPath($p);
			}
		} else if(is_string($path) && !in_array($path, $this->_path)) {
			$realPath = realpath($path);
			if(!is_dir($realPath)) {
				throw new Exception('Diretório ' . $path . ' inválido para autoload!');
			}
			$this->_path[] = $path;
		} else {
			throw new InvalidArgumentException('Paths devem ser strings ou arrays de strings');	
		}
		return $this;
	}
	
	/**
	 * Remove um caminho ou um array de caminhos do objeto Loader.
	 * 
	 * @param string|array $path
	 * @return Hydra_Loader : fluent interface
	 */
	public function removePath($path) {
		$this->_path = array_diff($this->_path, (array) $path);
		return $this;
	}
	
	/**
	 * Registra o objeto Loader na pilha SPLAutoload.
	 * @returns Hydra_Loader : fluent interface
	 * @throws Exception caso o registro falhe
	 */
	public function register() {
		spl_autoload_register(array($this, 'autoload'), true);
		return $this;
	}

	/**
	 * Responsável pelo autoloading.
	 * @param string $className
	 * @return void
	 */
	public function autoload($className) {
		var_dump($className);
		$file = str_replace('_', self::DS, $className);
		
		foreach($this->_path as $path) { 
			$filePath = realpath($path) . self::DS . $file . '.php';
			if(is_file($filePath)) {
				require $filePath;
				return;
			}
		}
	}
}
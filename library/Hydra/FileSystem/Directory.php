<?php
/**
 * Representa um diretório no sistema de arquivos
 * @author <a href="mailto:rick.hjpbacelos@gmail.com">Henrique Barcelos</a>
 */
class FileSystem_Directory extends FileSystem {
	/**
	 * Faz o escaneamento do diretório procurando apenas por diretórios
	 * @var integer
	 */
	const ONLY_DIR 	= GLOB_ONLYDIR;

	/**
	 * Não ordena o resultado do escaneamento
	 * @var integer
	 */
	const NO_SORT 	= GLOB_NOSORT;

	/**
	 * Array que armazena o conteúdo do diretório
	 * @var array
	 */
	private $_contents = array();

	/**
	 * Construtor para um diretório. Se o caminho especificado não for um diretório,
	 * haverá uma tentativa de criá-lo.
	 *
	 * @param string $path
	 * @param integer $permissionMode
	 * @throws FileSystem_Directory_Exception
	 */
	public function __construct($path, $permissionMode = 0777){
		if(!is_dir($path)){
			$created = mkdir($path, $permissionMode, true);
			if(!$created){
				throw new FileSystem_Directory_Exception(sprintf('Impossível criar o diretório "%s"', $path));
			}
			
			parent::__construct($path);
			$this->setMode($permissionMode);
		} else {
			parent::__construct($path);
		}
		
		$this->_path .= self::SEPARATOR;
	}

	/**
	 * Retorna arquivo um dentro do diretório pelo seu nome, se existir.
	 *
	 * @param string $fileName
	 * @return FileSystem_File | null
	 */
	public function getFile($fileName) {
		return $this->_getChild($fileName, 'FileSystem_File');
	}

	/**
	 * Retorna o subdiretório do diretório atual, se existir.
	 *
	 * @param string $dirName : o nome do subdiretório
	 * @return FileSystem_Directory | null
	 */
	public function getSubdir($dirName) {
		return $this->_getChild($dirName, 'FileSystem_Directory');
	}

	/**
	 * Faz a busca por um nó (arquivo ou pasta) filho do diretório atual.
	 *
	 * @param string $name : o nome do nó
	 * @param string $type : a classe que indica o tipo de nó (arquivo ou pasta)
	 * 								[FileSystem_Directory | FileSystem_File]
	 */
	private function _getChild($name, $type) {
		$this->getContents();
		$child = isset($this->_contents[$name]) ? $this->_contents[$name] : null;

		if(!$child instanceof $type) {
			$child = null;
		}

		return $child;
	}

	/**
	 * Retorna o conteúdo do diretório.
	 *
	 * @param string $pattern [OPCIONAL] : padrão glob
	 * @param integer $flags [OPCIONAL] : flags glob
	 * @return array
	 */
	public function getContents($pattern = '*', $flags = 0) {
		$this->_contents = $this->_doScan($pattern, $flags, true);
		return $this->_contents;
	}

	/**
	 * Retorna apenas os subdiretórios do diretório atual.
	 *
	 * @param bool $recursive [OPCIONAL] : indica se a busca deve ser recursiva ou não
	 * @return array
	 */
	public function getSubDirectories($recursive = false) {
		$this->_contents = $this->_doScan('*', self::ONLY_DIR, $recursive);
		return $this->_contents;
	}

	/**
	 * Escaneia o diretório atual
	 *
	 * @param string $pattern : padrão glob
	 * @param integer $flags [OPCIONAL] : flags glob
	 * @param bool $recursive [OPCIONAL] : indica se o escaneamento deve ser recursivo ou não
	 * @return array
	 */
	public function scan($pattern, $flags = 0, $recursive = false) {
		$this->_contents = $this->_doScan($pattern, $flags, $recursive);
		return $this->_contents;
	}

	/**
	 * Faz o escaneamento do diretório
	 *
	 * @param string $pattern : padrão glob
	 * @param integer $flags : flags glob
	 * @param unknown_type $recursive
	 * @param bool $recursive : indica se o escaneamento deve ser recursivo ou não
	 * @return array
	 */
	private function _doScan($pattern, $flags, $recursive){
		if(!$this->_valid) {
			throw new FileSystem_Directory_Exception(sprintf('O diretório "%s" é inválido!', $this->_path));
		}

		$scan = array();
		$result = glob($this->_path . $pattern, $flags);
		foreach($result as $key => $each) {
			$baseName = basename($each);
			if(!self::isDir($each)) {
				$scan[$baseName] = new FileSystem_File($each);
			}
		}
		
		if($recursive) {
			$subFolders = glob($this->_path.'*', self::ONLY_DIR);
			foreach($subFolders as $each) {
				$subDir = new FileSystem_Directory($each);
				$recScan = $subDir->_doScan($pattern, $flags, $recursive);
				$baseName = basename($each);
				$scan[$baseName] = $recScan;
			}
		}
		return $scan;
	}

	/**
	 * Remove o diretório atual e todo o seu conteúdo.
	 *
	 * @see FileSystem::delete()
	 */
	public function delete() {
		$this->getContents();
		foreach($this->_contents as $key => $each) {
			$each->delete();
		}
		rmdir($this->_path);
		$this->_valid = false;
		return true;
	}

	/**
	 * Verifica se o caminho é um diretório.
	 *
	 * @param string $path : o caminho para verificação
	 */
	public static function isDir($path) {
		$realpath = realpath($path);
		return is_dir($realpath);
	}

	/**
	 * Transforma o diretório em uma representação array-árvore.
	 *
	 * @return array
	 */
	public function toArray() {
		$this->getContents();

		$array = array();
		foreach($this->_contents as $key => $each) {
			if($each instanceof FileSystem_Directory) {
				$array[$key] = $each->toArray();
			} else {
				$array[$key] = $each->__toString();
			}
		}
		return $array;
	}
}
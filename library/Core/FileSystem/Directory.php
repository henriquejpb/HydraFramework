<?php
/**
 * Representa um diret�rio no sistema de arquivos
 * @author <a href="mailto:rick.hjpbacelos@gmail.com">Henrique Barcelos</a>
 */
class FileSystem_Directory extends FileSystem {
	/**
	 * Faz o escaneamento do diret�rio procurando apenas por diret�rios
	 * @var integer
	 */
	const ONLY_DIR 	= GLOB_ONLYDIR;

	/**
	 * N�o ordena o resultado do escaneamento
	 * @var integer
	 */
	const NO_SORT 	= GLOB_NOSORT;

	/**
	 * Array que armazena o conte�do do diret�rio
	 * @var array
	 */
	private $_contents = array();

	/**
	 * Construtor para um diret�rio. Se o caminho especificado n�o for um diret�rio,
	 * haver� uma tentativa de cri�-lo.
	 *
	 * @param string $path
	 * @param integer $permissionMode
	 * @throws FileSystem_Directory_Exception
	 */
	public function __construct($path, $permissionMode = 0777){
		if(!is_dir($path)){
			$created = mkdir($path, $permissionMode, true);
			if(!$created){
				throw new FileSystem_Directory_Exception(sprintf('Imposs�vel criar o diret�rio "%s"', $path));
			}
			
			parent::__construct($path);
			$this->setMode($permissionMode);
		} else {
			parent::__construct($path);
		}
		
		$this->_path .= self::SEPARATOR;
	}

	/**
	 * Retorna arquivo um dentro do diret�rio pelo seu nome, se existir.
	 *
	 * @param string $fileName
	 * @return FileSystem_File | null
	 */
	public function getFile($fileName) {
		return $this->_getChild($fileName, 'FileSystem_File');
	}

	/**
	 * Retorna o subdiret�rio do diret�rio atual, se existir.
	 *
	 * @param string $dirName : o nome do subdiret�rio
	 * @return FileSystem_Directory | null
	 */
	public function getSubdir($dirName) {
		return $this->_getChild($dirName, 'FileSystem_Directory');
	}

	/**
	 * Faz a busca por um n� (arquivo ou pasta) filho do diret�rio atual.
	 *
	 * @param string $name : o nome do n�
	 * @param string $type : a classe que indica o tipo de n� (arquivo ou pasta)
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
	 * Retorna o conte�do do diret�rio.
	 *
	 * @param string $pattern [OPCIONAL] : padr�o glob
	 * @param integer $flags [OPCIONAL] : flags glob
	 * @return array
	 */
	public function getContents($pattern = '*', $flags = 0) {
		$this->_contents = $this->_doScan($pattern, $flags, true);
		return $this->_contents;
	}

	/**
	 * Retorna apenas os subdiret�rios do diret�rio atual.
	 *
	 * @param bool $recursive [OPCIONAL] : indica se a busca deve ser recursiva ou n�o
	 * @return array
	 */
	public function getSubDirectories($recursive = false) {
		$this->_contents = $this->_doScan('*', self::ONLY_DIR, $recursive);
		return $this->_contents;
	}

	/**
	 * Escaneia o diret�rio atual
	 *
	 * @param string $pattern : padr�o glob
	 * @param integer $flags [OPCIONAL] : flags glob
	 * @param bool $recursive [OPCIONAL] : indica se o escaneamento deve ser recursivo ou n�o
	 * @return array
	 */
	public function scan($pattern, $flags = 0, $recursive = false) {
		$this->_contents = $this->_doScan($pattern, $flags, $recursive);
		return $this->_contents;
	}

	/**
	 * Faz o escaneamento do diret�rio
	 *
	 * @param string $pattern : padr�o glob
	 * @param integer $flags : flags glob
	 * @param unknown_type $recursive
	 * @param bool $recursive : indica se o escaneamento deve ser recursivo ou n�o
	 * @return array
	 */
	private function _doScan($pattern, $flags, $recursive){
		if(!$this->_valid) {
			throw new FileSystem_Directory_Exception(sprintf('O diret�rio "%s" � inv�lido!', $this->_path));
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
	 * Remove o diret�rio atual e todo o seu conte�do.
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
	 * Verifica se o caminho � um diret�rio.
	 *
	 * @param string $path : o caminho para verifica��o
	 */
	public static function isDir($path) {
		$realpath = realpath($path);
		return is_dir($realpath);
	}

	/**
	 * Transforma o diret�rio em uma representa��o array-�rvore.
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
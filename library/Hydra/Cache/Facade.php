<?php
/**
 * Controlador simples de cache
 * @author <a href="mailto:rick.hjpbacelos@gmail.com">Henrique Barcelos</a>
 */
class Cache_Facade extends Cache_Facade_Abstract {
	/**
	 * @var int
	 */
	const FILE_APPEND = FILE_APPEND;

	/**
	 * @var NULL
	 */
	const FILE_OVERWRITE = null;

	/**
	 * @var string
	 */
	const CACHE_EXTENSION = 'cache';

	/**
	 * Armazena o diretório de cache.
	 * @var string
	 */
	private $_cacheDir;

	/**
	 * Construtor.
	 *
	 * @param string $cacheDir [OPTIONAL] : o diretório-base para os arquivos de cache
	 */
	public function __construct($cacheDir = null) {
		if($cacheDir !== null) {
			$this->setCacheDir($cacheDir);
		}
	}

	/**
	 * @see Cache_Facade_Abstract::set()
	 */
	public function set($directory, $fileName, $contents, $expires = null, $flag = null) {
		if(!self::isCacheEnabled()) {
			throw new Cache_DisabledException('O cache foi desabilitado nesta aplicação!');
		}

		try {
			$dir = new FileSystem_Directory($this->getCacheDir().$directory);
		} catch (FileSystem_Directory_Exception $e) {
			throw new Cache_WriteException(sprintf('Problemas ao criar do diterório de cache "%s"', $directory));
		}

		$path = $dir->getPath().$fileName. '.' . self::CACHE_EXTENSION;

		if(Cache_File::isFile($path) && !Cache_File::isWritable($path)) {
			throw new Cache_WriteException('Permissão negada ao tentar escrever no arquivo de cache ' . $path);
		}

		if($contents instanceof Serializable) {
			$contents = $contents->serialize();
		} else {
			$contents = serialize($contents);
		}

		try {
			$mode = $flag == self::FILE_OVERWRITE ? Cache_File::LOCK_EX : Cache_File::LOCK_EX | Cache_File::APPEND;
			$cacheFile = new Cache_File($path);
			$cacheFile->setExpiration($expires);
			return $cacheFile->write($contents, $mode);
		} catch (FileSystem_File_Exception $e) {
			throw new Cache_WriteException('Impossí­vel escrever no arquivo de cache em ' . $path);
		}
	}

	/**
	 * @see Cache_Facade_Abstract::remove()
	 */
	public function remove($directory, $fileName) {
		if(!self::exists($directory, $fileName)) {
			return true;
		}

		$file = $this->_getFile($directory, $fileName);
		return $file->delete();
	}

	/**
	 * @see Cache_Facade_Abstract::exists()
	 */
	public function exists($directory, $fileName) {
		if(!FileSystem_Directory::isDir(self::getCacheDir().$directory)) {
			return false;
		}

		$dir = new FileSystem_Directory(self::getCacheDir().$directory);
		$path = $dir->getPath().$fileName. '.' . self::CACHE_EXTENSION;
		return Cache_File::isFile($path) && Cache_File::isReadable($path);
	}

	/**
	 * @see Cache_Facade_Abstract::get()
	 */
	public function get($directory, $fileName) {
		if(!self::isCacheEnabled()) {
			throw new Cache_DisabledException('O cache foi desabilitado nesta aplicação!');
		}

		if(!$this->exists($directory, $fileName)) {
			return null;
		}

		$file = $this->_getFile($directory, $fileName);
		if($file->isExpired()) {
			return null;
		}

		$contents = $file->read();
		try {
			$ret = unserialize($contents);
			return $ret === false ? null : $ret;
		} catch (ErrorException $e) {
			return $contents;
		}
	}

	/**
	 * Seta o diretório para os arquivos de cache.
	 *
	 * @param string $cacheDir : caminho absoluto para o diretório
	 * 		ou relatiovo ao diretório padrão de cache da aplicação
	 * @return Cache_Facade : fluent interface
	 */
	public function setCacheDir($cacheDir) {
		$cacheDir = (string) $cacheDir;
		// Caso seja um caminho realativo, deve ser relativo ao diretório padrão
		if(strpos($cacheDir, '/') !== 0) {
			$cacheDir = Core::getInstance()->getCacheDir() . $cacheDir;
		}
		$this->_cacheDir = $cacheDir;
		return $this;
	}

	/**
	 * Retorna o diretório de cache.
	 *
	 * @return string
	 */
	public function getCacheDir() {
		// Se não há um diretório de cache setado, usamos o padrão
		if($this->_cacheDir === null) {
			$this->_cacheDir = Core::getInstance()->getCacheDir();
		}
		return $this->_cacheDir;
	}

	/**
	 * Retorna uma instância de Cache_File com base no diretório e nome informados.
	 *
	 * @param string $directory : o subdiretório do arquivo
	 * @param string $fileName : o nome do arquivo
	 * @return Cache_File
	 */
	private function _getFile($directory, $fileName) {
		$dir = new FileSystem_Directory($this->getCacheDir().$directory);
		$path = $dir->getPath().$fileName. '.' . self::CACHE_EXTENSION;
		return new Cache_File($path);
	}
}
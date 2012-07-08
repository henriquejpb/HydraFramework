<?php
/**
 * Controlador simples de cache
 * @author <a href="mailto:rick.hjpbacelos@gmail.com">Henrique Barcelos</a>
 */
abstract class Cache {
	const FILE_APPEND = FILE_APPEND;
	const FILE_OVERWRITE = null;
	
	const CACHE_EXTENSION = 'cache';
	
	/**
	 * Armazena o diret�rio de cache.
	 * @var string
	 */
	private static $_cacheDir;
	
	/**
	 * Seta um arquivo de cache
	 * @param string $directory : o no me do subdiret�rio no qual o arquivo ser� salvo
	 * @param string $fileName : o nome do arquivo a ser salvo (sem extens�o)
	 * @param mixed $contents : o conte�do a ser inclu�do no arquivo de cache
	 * @param mixed $expires : um timestamp ou tempo relativo para expira��o do arquivo de cache
	 * @param int $flag : adicionar o conte�do ao arquivo ou sobrescrevê-lo 
	 * 					Cache::FILE_APPEND ou Cache::FILE_OVERWRITE
	 * @return int : o n�mero de bytes escritos
	 * @throws Cache_Exception : caso a cria��o do arquivo de cache falhe
	 * @throws Exception : caso o cache esteja desabilitado na aplica��o
	 */
	public static function set($directory, $fileName, $contents, $expires, $flag = null) {
		if(!self::isCacheEnabled()) {
			throw new Exception('O cache foi desabilitado nesta aplica��o!');
		}
		
		try {
			$dir = new FileSystem_Directory(self::getCacheDir().$directory);
		} catch (FileSystem_Directory_Exception $e) {
			throw new Cache_Exception(sprintf('Problemas ao criar do diter�rio de cache "%s"', $directory));
		}
		
		$path = $dir->getPath().$fileName. '.' . self::CACHE_EXTENSION;
		
		if(Cache_File::isFile($path) && !Cache_File::isWritable($path)) {
			throw new Cache_Exception('Permiss�o negada ao tentar escrever no arquivo de cache ' . $path);
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
			throw new Cache_Exception('Imposs�vel escrever no arquivo de cache em ' . $path);
		}
	}
	
	/**
	 * Remove o arquivo do cache
	 * 
	 * @param string $directory : o subdiret�rio do arquivo
	 * @param string $fileName : o nome do arquivo
	 * @return boolean
	 */
	public static function remove($directory, $fileName) {
		if(!self::exists($directory, $fileName)) {
			return true;
		}
		
		$file = self::_getFile($directory, $fileName);
		return $file->delete();
	}
	
	/**
	 * Verifica se o arquivo de cache existe.
	 * 
	 * @param string $directory : o subdiret�rio do arquivo
	 * @param string $fileName : o nome do arquivo
	 * @return boolean
	 */
	public static function exists($directory, $fileName) {
		if(!FileSystem_Directory::isDir(self::getCacheDir().$directory)) {
			return false;
		}
		
		$dir = new FileSystem_Directory(self::getCacheDir().$directory);
		$path = $dir->getPath().$fileName. '.' . self::CACHE_EXTENSION;
		return Cache_File::isFile($path) && Cache_File::isReadable($path);
	}
	
	/**
	 * Retorna o conte�do de um arquivo do cache.
	 * @param string $directory : o subdiret�rio do arquivo
	 * @param string $fileName : o nome do arquivo
	 * @return mixed : null ou o conte�do do arquivo
	 * @throws Cache_Exception : caso a cria��o do arquivo de cache falhe
	 * @throws Exception : caso o cache esteja desabilitado na aplica��o
	 */
	public static function get($directory, $fileName) {
		if(!self::isCacheEnabled()) {
			throw new Exception('O cache foi desabilitado nesta aplica��o!');
		}
		
		if(!self::exists($directory, $fileName)) {
			return null;
		}
		
		$file = self::_getFile($directory, $fileName);
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
	 * Retorna o diret�rio de cache.
	 * 
	 * @return string
	 */
	public static function getCacheDir() {
		if(self::$_cacheDir === null) {
			self::$_cacheDir = Core::getInstance()->getCacheDir();
		}
		return self::$_cacheDir;
	}
	
	/**
	 * Retorna se o caching est� habilitado.
	 * 
	 * @return boolean
	 */
	public static function isCacheEnabled() {
		return Core::getInstance()->isCaching();
	}
	
	/**
	 * Retorna uma inst�ncia de Cache_File com base no diret�rio e nome informados.
	 * 
	 * @param string $directory : o subdiret�rio do arquivo
	 * @param string $fileName : o nome do arquivo
	 * @return Cache_File
	 */
	private static function _getFile($directory, $fileName) {
		$dir = new FileSystem_Directory(self::getCacheDir().$directory);
		$path = $dir->getPath().$fileName. '.' . self::CACHE_EXTENSION;
		return new Cache_File($path);
	}
}
<?php
abstract class Cache_Facade_Abstract {
	/**
	 * Retorna se o caching est� habilitado.
	 *
	 * @return boolean
	 */
	public static function isCacheEnabled() {
		return Core::getInstance()->isCaching();
	}

	/**
	 * Seta um arquivo de cache
	 * @param string $directory : o no me do subdiret�rio no qual o arquivo ser� salvo
	 * @param string $fileName : o nome do arquivo a ser salvo (sem extens�o)
	 * @param mixed $contents : o conte�do a ser inclu�do no arquivo de cache
	 * @param mixed $expires [OPTIONAL] : um timestamp ou tempo relativo para expira��o	do arquivo de cache.
	 * 		Se NULL, ser� utilizado o tempo padr�o definido por Cache_File::DEFAULT_EXPIRATION
	 * @param int $flag [OPTIONAL] : adicionar o conte�do ao arquivo ou sobrescrev�-lo
	 * 					Cache::FILE_APPEND ou Cache::FILE_OVERWRITE
	 * @return int : o n�mero de bytes escritos
	 * @throws Cache_WriteException caso a cria��o do arquivo de cache falhe
	 * @throws Exception caso o cache esteja desabilitado na aplica��o,
	 */
	abstract public function set($directory, $fileName, $contents, $expires = null, $flag = null);

	/**
	 * Remove o arquivo do cache
	 *
	 * @param string $directory : o subdiret�rio do arquivo
	 * @param string $fileName : o nome do arquivo
	 * @return boolean : se a opera��o ocorreu com sucesso ou n�o
	 */
	abstract public function remove($directory, $fileName);

	/**
	 * Verifica se o arquivo de cache existe.
	 *
	 * @param string $directory : o subdiret�rio do arquivo
	 * @param string $fileName : o nome do arquivo
	 * @return boolean : se o arquivo de cache existe ou n�o
	 */
	abstract public function exists($directory, $fileName);

	/**
	 * Retorna o conte�do de um arquivo do cache.
	 * @param string $directory : o subdiret�rio do arquivo
	 * @param string $fileName : o nome do arquivo
	 * @return mixed : null ou o conte�do do arquivo
	 * @throws Cache_WriteException : caso a cria��o do arquivo de cache falhe
	 * @throws Exception : caso o cache esteja desabilitado na aplica��o
	 */
	abstract public function get($directory, $fileName);
}
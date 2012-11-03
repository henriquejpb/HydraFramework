<?php
abstract class Hydra_Cache_Facade_Abstract {
	/**
	 * Retorna se o caching está habilitado.
	 *
	 * @return boolean
	 */
	public static function isCacheEnabled() {
		return Hydra_Core::getInstance()->isCaching();
	}

	/**
	 * Seta um arquivo de cache
	 * @param string $directory : o no me do subdiretório no qual o arquivo será salvo
	 * @param string $fileName : o nome do arquivo a ser salvo (sem extensão)
	 * @param mixed $contents : o conteúdo a ser incluí­do no arquivo de cache
	 * @param mixed $expires [OPTIONAL] : um timestamp ou tempo relativo para expiração	do arquivo de cache.
	 * 		Se NULL, será utilizado o tempo padrão definido por Hydra_Cache_File::DEFAULT_EXPIRATION
	 * @param int $flag [OPTIONAL] : adicionar o conteúdo ao arquivo ou sobrescrevê-lo
	 * 					Hydra_Cache::FILE_APPEND ou Hydra_Cache::FILE_OVERWRITE
	 * @return int : o número de bytes escritos
	 * @throws Hydra_Cache_WriteException caso a criação do arquivo de cache falhe
	 * @throws Exception caso o cache esteja desabilitado na aplicação,
	 */
	abstract public function set($directory, $fileName, $contents, $expires = null, $flag = null);

	/**
	 * Remove o arquivo do cache
	 *
	 * @param string $directory : o subdiretório do arquivo
	 * @param string $fileName : o nome do arquivo
	 * @return boolean : se a operação ocorreu com sucesso ou não
	 */
	abstract public function remove($directory, $fileName);

	/**
	 * Verifica se o arquivo de cache existe.
	 *
	 * @param string $directory : o subdiretório do arquivo
	 * @param string $fileName : o nome do arquivo
	 * @return boolean : se o arquivo de cache existe ou não
	 */
	abstract public function exists($directory, $fileName);

	/**
	 * Retorna o conteúdo de um arquivo do cache.
	 * @param string $directory : o subdiretório do arquivo
	 * @param string $fileName : o nome do arquivo
	 * @return mixed : null ou o conteúdo do arquivo
	 * @throws Hydra_Cache_WriteException : caso a criação do arquivo de cache falhe
	 * @throws Exception : caso o cache esteja desabilitado na aplicação
	 */
	abstract public function get($directory, $fileName);
}
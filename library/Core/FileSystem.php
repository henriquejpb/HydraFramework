<?php
/**
 * Representa��o abstrata de uma entidade (arquivo ou diret�rio) no Sistema de Arquivos
 * @author <a href="mailto:rick.hjpbacelos@gmail.com">Henrique Barcelos</a>
 */
abstract class FileSystem {
	/**
	 * O caractere separador de diret�rios.
	 * No Windows, ele � '\', enquanto que no Mac e no Linux temos o '/'
	 * @var string
	 */
	const SEPARATOR = DIRECTORY_SEPARATOR;

	/**
	 * O caminho para esta entidade.
	 *
	 * @var string
	 */
	protected $_path;

	/**
	 * Indica se a entidade � v�lida.
	 *
	 * Ela pode ser tornar inv�lida quando removida.
	 * @var bool
	 */
	protected $_valid = true;

	/**
	 * Construtor
	 *
	 * @param string $path : o caminho do diret�rio ou arquivo (ser� criado se n�o existir)
	 * @param integer $permissionMode : o modo de permiss�o para cria��o do diret�rio
	 * @throws FileSystem_Directory_Exception
	 */
	public function __construct($path, $permissionMode = 0777){
		$this->_path = realpath($path);
	}

	/**
	 * Retorna o caminho para a entidade.
	 *
	 * @return string;
	 */
	public function getPath() {
		return $this->_path;
	}

	/**
	 * Verifica se a entidade � v�lida.
	 *
	 * @return bool
	 */
	public function isValid() {
		return $this->_valid;
	}

	/**
	 * Seta o modo de permiss�es da entidade.
	 *
	 * @param integer $mode : o modo de permiss�es no formato OCTAL. Ex: 0775
	 * @throws FileSystem_Exception
	 * @return bool
	 */
	public function setMode($mode){
		$vMode = (int) '0'.base_convert($mode, 10, 8);
		if(!preg_match('#0[0-7]+#', $vMode)) {
			throw new FileSystem_Exception(sprintf('Modo de permiss�o "%s" inv�lido!', $mode));
		}
		return chmod($this->_path, $mode);
	}

	/**
	 * Remove a entidade.
	 *
	 * @return bool
	 */
	abstract function delete();

	/**
	 * Verifica se h� permiss�o de escrita para o caminho $path.
	 *
	 * @param string $path : o caminho para o diret�rio ou arquivo
	 */
	public static function isWritable($path) {
		return is_writable($path);
	}

	/**
	 * Verifica se h� permiss�o de leitura para o caminho $path.
	 *
	 * @param string $path
	 */
	public static function isReadable($path) {
		return is_readable($path);
	}

	/**
	 * Ao tentar converter um objeto FileSystem em string,
	 * retornamos o caminho para o mesmo.
	 * @return string
	 */
	public function __toString() {
		return $this->_path;
	}
}
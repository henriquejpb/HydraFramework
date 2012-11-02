<?php
/**
 * Representação abstrata de uma entidade (arquivo ou diretório) no Sistema de Arquivos
 * @author <a href="mailto:rick.hjpbacelos@gmail.com">Henrique Barcelos</a>
 */
abstract class FileSystem {
	/**
	 * O caractere separador de diretórios.
	 * No Windows, ele é '\', enquanto que no Mac e no Linux temos o '/'
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
	 * Indica se a entidade é válida.
	 *
	 * Ela pode ser tornar inválida quando removida.
	 * @var bool
	 */
	protected $_valid = true;

	/**
	 * Construtor
	 *
	 * @param string $path : o caminho do diretório ou arquivo (será criado se não existir)
	 * @param integer $permissionMode : o modo de permissão para criação do diretório
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
	 * Verifica se a entidade é válida.
	 *
	 * @return bool
	 */
	public function isValid() {
		return $this->_valid;
	}

	/**
	 * Seta o modo de permissões da entidade.
	 *
	 * @param integer $mode : o modo de permissões no formato OCTAL. Ex: 0775
	 * @throws FileSystem_Exception
	 * @return bool
	 */
	public function setMode($mode){
		$vMode = (int) '0'.base_convert($mode, 10, 8);
		if(!preg_match('#0[0-7]+#', $vMode)) {
			throw new FileSystem_Exception(sprintf('Modo de permissão "%s" inválido!', $mode));
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
	 * Verifica se há permissão de escrita para o caminho $path.
	 *
	 * @param string $path : o caminho para o diretório ou arquivo
	 */
	public static function isWritable($path) {
		return is_writable($path);
	}

	/**
	 * Verifica se há permissão de leitura para o caminho $path.
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
<?php
/**
 * Representa um arquivo no sistema de arquivos
 * @author <a href="mailto:rick.hjpbacelos@gmail.com">Henrique Barcelos</a>
 */
class FileSystem_File extends FileSystem {
	/**
	 * Indica se o conteúdo de escrita deve ser adicionado ao conteúdo atual.
	 * @var integer
	 */
	const APPEND  = FILE_APPEND;

	/**
	 * Define que o arquivo deve ter escrita exclusiva por uma única thread.
	 * @var integer
	 */
	const LOCK_EX = LOCK_EX;
	
	/**
	 * Define o tamanho máximo do buffer de leitura.
	 * É necessário caso o tamanho máximo da leitura 
	 * não seja informado. Valor: 40 MB
	 * @var integer
	 */
	const BUFFER_MAX_SIZE = 41943040;

	/**
	 * Armazena o nome do arquivo
	 * @var string
	 */
	private $_name;

	/**
	 * Armazena o tipo do arquivo
	 * @var string
	 */
	private $_type;

	/**
	 * Armazena o tamanho em bytes do arquivo
	 * @var integer
	 */
	private $_size;
	
	/**
	 * Enter description here ...
	 * @see FileSystem::__construct($path, $permissionMode = 0777)
	 */
	public function __construct($path, $permissionMode = 0777) {
		if(!self::isFile($path)) {
			$dirName = dirname($path);
			if(!FileSystem_Directory::isDir($dirName)) {
				new FileSystem_Directory($dirName);
			}
			$this->_create($path);
			parent::__construct($path);
			$this->setMode($permissionMode);
		} else {
			parent::__construct($path);
		}
		
		$this->_info();
	}
	
	/**
	 * Cria um arquivo.
	 * 
	 * @param string $path : o caminho para o arquivo a ser cirado
	 * @return void
	 * @throws FileSystem_File_Exception caso o arquivo não possa ser criado
	 */
	private function _create($path) {
		$created = fopen($path, 'w+');
		if(!$created) {
			throw new FileSystem_File_Exception(sprintf('Não foi possível criar o arquivo "%s"', $path));
		}
		fclose($created);
	}
	
	/**
	 * Busca as informações sobre o arquivo.
	 * 
	 * @return void
	 */
	private function _info() {
		$this->_name = basename($this->_path);
		$this->_size = filesize($this->_path);

		/* Before PHP 5.3.0, the magic_open library is needed to build this extension.
		 * Somente a classe finfo esteja definida vamos recuperar o tipo do arquivo
		 */
		if(class_exists('finfo')) {
			$fInfo = new finfo(FILEINFO_MIME_TYPE);
			$this->_type = $fInfo->file($this->_path);
		}
	}
	
	/**
	 * Retorna o nome do arquivo.
	 * 
	 * @return string
	 */
	public function getName() {
		return $this->_name;
	}
	
	/**
	 * Retorna o tipo (mime-type) do arquivo.
	 * 
	 * @return string
	 */
	public function getType() {
		return $this->_type;
	}
	
	/**
	 * Retorna o tamanho (em bytes) do arquivo.
	 * 
	 * @return integer
	 */
	public function getSize() {
		return $this->_size;
	}
	
	/**
	 * Retorna o conteúdo do arquivo.
	 * 
	 * @return string
	 * @throws FileSystem_File_Exception
	 */
	public function read($offset = null, $maxlen = null) {
		if(!$this->isValid()) {
			throw new FileSystem_File_Exception(sprintf('Impossível recuperar os dados em "%s": o arquivo é inválido', $this->_path));
		}
		
		if((int) $maxlen === 0) {
			$maxlen = self::BUFFER_MAX_SIZE;
		}
		
		$ret = file_get_contents($this->_path, null, null, $offset, $maxlen);
		return $ret === false ? null : $ret;
	}
	
	/**
	 * Escreve no arquivo.
	 * 
	 * @param string $data : os dados para escrever no arquivo
	 * @param integer $flags : as flags para a operação de escrita 
	 * 							[FileSystem_File::LOCK_EX | FileSystem_File::APPEND]
	 * @return int | bool : o número de bytes escritos com sucesso no arquivo ou false em caso de falha  
	 * @throws FileSystem_File_Exception
	 */
	public function write($data, $flags = self::LOCK_EX) {
		if(!$this->isValid()) {
			throw new FileSystem_File_Exception(sprintf('Impossível salvar dados em "%s": o arquivo é inválido', $this->_path));
		}
		return file_put_contents($this->_path, $data, $flags);
	}
	
	/**
	 * Remove o arquivo.
	 * 
	 * @see FileSystem::delete()
	 */
	public function delete() {
		$ret = unlink($this->_path);
		if(!$ret) {
			throw new Exception(sprintf('Impossível remover o arquivo "%s"', $this->_path));
		}
		$this->_valid = false;
		return $ret;
	}
	
	/**
	 * Verifica se o caminho $path é um arquivo válido.
	 * 
	 * @param string $path
	 * @return bool
	 */
	public static function isFile($path) {
		return is_file($path);
	}
}
<?php
class Cache_File extends FileSystem_File {
	/**
	 * Define um prazo de expiração padrão
	 * @var string
	 */
	const DEFAULT_EXPIRATION = '+ 1 WEEK';
	
	/**
	 * Os primeiros 'n' bytes do arquivo de cache são informações sobre
	 * o próprio arquivo (data de expiração). 
	 * Por enquanto, definimos 'n' como 16: 
	 * 		15 bytes para o timestamp + 1 para uma quebra de linha.
	 * 
	 * @var integer
	 */
	const METADATA_SIZE = 16;
	
	/**
	 * Armazena uma unix-timestamp com a data de expiração informada
	 * @var integer
	 */
	private $_expires;
	
	/**
	 * Informa se a data de expiração do arquivo foi modificada
	 * @var boolean
	 */
	private $_expModified = false;
	
	/**
	 * Construtor.
	 * Cria um arquivo de cache.
	 * 
	 * @see FileSystem_File::__construct
	 */
	public function __construct($path, $permissionMode = 0777){
		parent::__construct($path, $permissionMode);
	}
	
	/**
	 * Destrutor: se a expiração foi alterada e nenhuma operação sobre
	 * o arquivo foi executada, ao destruir a variável, precisamos fazer
	 * o update da data de expiração
	 */
	public function __destruct() {
		if($this->_expModified === true && $this->isValid()) {
			$buffer = $this->read();
			$this->write($buffer);
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see FileSystem_File::read()
	 */
	public function read($offset = null, $maxlen = null) {
		try {
			return parent::read(self::METADATA_SIZE + (int) $offset, $maxlen);
		} catch (FileSystem_File_Exception $e) {
			return null;
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see FileSystem_File::write()
	 */
	public function write($data, $flags = FileSystem_File::LOCK_EX) {
		if(!$this->_expires) {
			$this->setExpiration(self::DEFAULT_EXPIRATION);
		}
		$buffer = str_pad($this->getExpiration(), self::METADATA_SIZE-1, '0', STR_PAD_LEFT)."\n";
		
		//As flags podem ser concatenadas por meio do operador de bit |
		//Para testar se a flag self::APPEND = 8 está setada, precisamos de uma máscara.
		$mask = self::APPEND;
		
		//Aplicamos essa máscara à flag utilizando o operador de bit & 
		//e fazemos um shift 3 bits para a direita, pois 8 em binário é 1000... 
		$isAppend = ($flags & $mask) >> 3;
		
		//... e verificamos se seu valor é 1. Se for, temos uma concatenação
		if($isAppend === 1) {
			$buffer = $this->read();
		} 
		
		$buffer .= $data;
		
		$this->_expModified = false;
		return parent::write($buffer, FileSystem_File::LOCK_EX);
	}
	
	/**
	 * Seta a data de expiração do arquivo de cache
	 * 
	 * @param string|integer $expires :
	 * 		Integer: uma unix-timestamp
	 * 		String: data no formato USA ou ISO, ou strings relativas como '+ 3 DAY'
	 * @return Cache_File : Fluent Interface
	 */
	public function setExpiration($expires) {
		//Se um valor inteiro é informado, então supõe-se que é uma unix-timestamp
		if(is_int($expires)) {
			$this->_expires = $expires;
		//Se não, esperamos uma string que possa ser convertida em timestamp
		} else {
			$this->_expires = strtotime($expires);
		}
		
		$this->_expModified = true;
		return $this;
	}
	
	/**
	 * Retorna a data de expiração do arquivo de cache
	 * 
	 * @return integer
	 */
	public function getExpiration() {
		if($this->_expires === null) {
			try {
				$exp = parent::read(0, self::METADATA_SIZE);
				$this->setExpiration((int) $exp);
			} catch (FileSystem_File_Exception $e) {
				$this->setExpiration(self::DEFAULT_EXPIRATION);
			}
		}
		return $this->_expires;
	}
	
	/**
	 * Verifica se o arquivo de cache não está expirado
	 * 
	 * @return boolean
	 */
	public function isExpired(){
		return $this->getExpiration() <= time();
	}
}
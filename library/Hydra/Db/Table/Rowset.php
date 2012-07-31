<?php
class Db_Table_Rowset implements SeekableIterator, Countable, ArrayAccess {
	/**
	 * Os dados originais para cada linha.
	 * @var array
	 */
	protected $_data = array();

	/**
	 * Instância de Db_Table pai deste objeto
	 * @var Db_Table
	 */
	protected $_table;
	
	/**
	 * O nome da tabela pai deste objeto.
	 * @var string
	 */
	protected $_tableName;
	
	/**
	 * É TRUE se temos uma referência a um objeto Db_Table ativo.
	 * @var boolean
	 */
	protected $_connected = true;
	
	/**
	 * Nome de classe que representa uma linha da tabela.
	 * @var string
	 */
	protected $_rowClass = 'Db_Table_Row';
	
	/**
	 * Ponteiro para o iterador.
	 * @var int
	 */
	protected $_pointer = 0;
	
	/**
	 * Quantas linhas existem neste objeto.
	 * @var int
	 */
	protected $_count = 0;
	
	/**
	 * Coleção de instâncias de Db_Table_Row ou classes derivadas.
	 * @var array
	 */
	protected $_rows = array();
	
	/**
	 * Se os dados neste objeto já estão armazenados no banco de dados.
	 * @var boolean
	 */
	protected $_stored = false;
	
	/**
	 * Se os dados neste objeto são somente-leitura
	 * @var boolean
	 */
	protected $_readOnly = false;
	
	/**
	 * Construtor.
	 * 
	 * Parâmetros de configuração possíveis:
	 * - table 		: [Db_Table] a tabela ao qual este objeto está vinculado
	 * - rowClass	: [string] a classe para um objeto Db_Table_Row  
	 * - data		: [array] os dados para armazenamento neste objeto
	 * - readOnly	: [boolean] se os dados são somente-leitura
	 * - stored		: [boolean] se os dados são provindos do banco de dados ou não
	 * 
	 * @param array $config : os parâmetros de configuração
	 */
	public function __construct(array $config) {
		if(isset($config['table']) && $config['table'] instanceof Db_Table) {
			$this->_table = $config['table'];
			$this->_tableName = $this->_table->getName();
		}
		
		
		if(isset($config['rowClass'])) {
			$this->_rowClass = (string) $config['rowClass'];
		}
		
		if(isset($config['data'])) {
			$this->_data = $config['data']; 
		}
		
		if(isset($config['readOnly'])) {
			$this->_readOnly = $config['readOnly'];
		}
		
		if(isset($config['stored'])) {
			$this->_stored = $config['stored'];
		}
		
		$this->_count = count($this->_data);
		
		$this->init();
	}
	
	/**
	 * Dados armazenados em um objeto serializado.
	 * 
	 * @return array
	 */
	public function __sleep() {
		return array('_data', '_tableName', '_rowClass', '_pointer', '_count', '_rows', '_stored', '_readOnly');
	}
	
	/**
	 * Não é assumido que um objeto desserializado possua uma conexão 
	 * com um banco de dados, logo, _connected é setado par FALSE.
	 * 
	 * @return void
	 */
	public function __wakeup() {
		$this->_connected = false;
	}
	
	/**
	 * Inicializa o objeto.
	 * 
	 * Chamado ao final do construtor.
	 * 
	 * @return void
	 */
	public function init() {
		
	}
	
	/**
	 * Retorna o estado de conexão do objeto.
	 * 
	 * @return boolean
	 */
	public function isConnected() {
		return $this->_connected;
	}
	
	/**
	 * Retorna a objeto-tabela ou null se o rowset estiver desconectado.
	 * 
	 * @return Db_Table|null
	 */
	public function getTable() {
		return $this->_table;
	}
	
	/**
	 * Seta o objeto-tabela para restabelecer a conexão com o
	 * banco de dados para o rowset que tenha sido desserializado.
	 * 
	 * @param Db_Table $table
	 */
	public function setTable(Db_Table $table) {
		if($table->getName() !== $this->_tableName) {
			throw new Db_Table_Rowset_Exception(sprintf(
														'O objeto rowset pertence à tabela "%s", 
														houve uma tentativa de associá-la a tabela "%s"', 
														$this->_tableName,
														$table->getName()
													));
			
		}
		
		$this->_table = $table;
		$this->_connected = false;
		
		$misses = 0;
		foreach($this as $row) {
			$connected = $row->setTable($table);
			if(!$connected) {
				$misses++;
			}	
		}
		
		$this->_connected = ($misses == 0);
		return $this->_connected;
	}
	
	/**
	 * Retorna o nome da tabela ao qual o rowset está associado.
	 * 
	 * @return string
	 */
	public function getTableName() {
		return $this->_tableName;
	}
	
	/**
	 * 'Rebobina' o iterador até o primeiro elemento.
	 * Similar à função reset() para arrays em PHP.
	 * Requerido pela interface Iterator.
	 * 
	 * @return Iterator
	 * @see SeekableIterator::rewind()
	 */
	public function rewind() {
		$this->_pointer = 0;
		return $this;
	}
	
	/**
	 * Retorna o elemento atual.
	 * Similar à função current() para arrays em PHP.
	 * Requerido pela interface Iterator.
	 * 
	 * @return Db_Table_Row
	 * @see SeekableIterator::current()
	 */
	public function current() {
		if($this->valid() === false) {
			return null;
		}
		
		return $this->_loadAndReturnRow($this->_pointer);
	}
	
	/**
	 * Retorna a chave do elemento atual.
	 * Similar à função key() para arrays em PHP.
	 * Requerido pela interface Iterator.
	 * 
	 * @return int
	 * @see SeekableIterator::key()
	 */
	public function key() {
		return $this->_pointer;	
	}
	
	/**
	 * Move o ponteiro para o próximo elemento.
	 * Similar à função next() para arrays em PHP.
	 * Requerido pela interface Iterator.
	 * 
	 * @return void
	 * @see SeekableIterator::next()
	 */
	public function next() {
		++$this->_pointer;
	}
	
	/**
	 * Checa se há um elemento válido após chamar prev() ou next().
	 * Requerido pela interface Iterator.
	 * 
	 * @return boolean
	 * @see SeekableIterator::valid()
	 */
	public function valid() {
		return $this->_pointer >= 0 && $this->_pointer < $this->_count;
	}
	
	/**
	 * Retorna o número de elementos na colenção.
	 * 
	 * @return int
	 * @see Countable::count()
	 */
	public function count() {
		return $this->_count;
	}
	
	/**
	 * Leva o iterador à posição $position.
	 * Requerido pela interface SeekableIterator.
	 * 
	 * @param int $position : a posição de destino
	 * @return Db_Table_Rowset : fluent interface
	 * @throws OutOfBoundsException
	 * @see SeekableIterator::seek()
	 */
	public function seek($position) {
		$position = (int) $position;
		
		// Lança uma exceção se a posição não for válida ou não existir
		$this->_requireOffset($position);
		
		$this->_pointer = $position;
		return $this;
	}
	
	/**
	 * Checa se um índice existe.
	 * Requerido pela interface ArrayAccess.
	 * 
	 * @return boolean
	 * @see ArrayAccess::offsetExists()
	 */
	public function offsetExists($offset) {
		$offset = (int) $offset;
		return ($offset >= 0 && $offset < $this->_count) && isset($this->_data[$offset]);
	}
	
	/**
	 * Retorna a linha em um dado índice.
	 * Requerido pela interface ArrayAccess.
	 * 
	 * @param string $offset
	 * @return Db_Table_Row
	 * @throws OutOfBoundsException
	 * @see ArrayAccess::offsetGet()
	 */
	public function offsetGet($offset) {
		$offset = (int) $offset;
		
		// Lança uma exceção se a posição não for válida ou não existir
		$this->_requireOffset($offset);
		
		$this->_pointer = $offset;
		return $this->current();
	}
	
	/**
	 * Seta uma linha na posição $offset
	 * 
	 * @param string $offset : esperado int
	 * @param mixed $value : esperado Db_Table_Row
	 * @return void
	 * @throws OutOfBoundsException
	 * @see ArrayAccess::offsetSet()
	 */
	public function offsetSet($offset, $value) {
		if(!$value instanceof Db_Table_Row) {
			$type = gettype($value);
			if($type == 'object') {
				$type = get_class($value);
			}
			throw new Db_Table_Rowset_Exception('Objetos Db_Table_Rowset devem conter apenas 
												objetos do tipo Db_Table_Row. Tipo informado: ' . $type);
		}
		
		// Lança uma exceção se a posição não for válida ou não existir
		$this->_requireOffset($offset);
		
		$this[(int) $offset] = $value;
	}
	
	/**
	 * Remove uma linha na posição $offset
	 * 
	 * @param string $offset : esperado int
	 * @throws OutOfBoundsException
	 * @see ArrayAccess::offsetUnset()
	 */
	public function offsetUnset($offset) {
		$this->_requireOffset($offset);
		
		unset($this->_data[(int) $offset]);
		
		// Reseta os índices do array
		$this->_data = array_values($this->_data);
		$this->_count = count($this->_data);
		
		// Se removermos o último elemento, estaremos apontando para uma posição inválida.
		if($this->_pointer >= $this->_count) {
			//Assim, fazemos com que o ponteiro fique sempre na última posição.
			$this->_pointer = $this->_count - 1;
		}		
	}
	
	/**
	 * Lança uma exceção se a posição não for válida ou não existir
	 * @param string $offset
	 * @throws OutOfBoundsException
	 */
	protected function _requireOffset($offset) {
		if(!$this->offsetExists($offset)) {
			throw new OutOfBoundsException(sprintf('O índice %d é ilegal.', $offset));
		}
	}
	
	/**
	 * Retorna uma linha na posição $position
	 * @param int $position
	 * @param boolean $seek
	 * @throws OutOfBoundsException
	 */
	public function getRow($position, $seek = false) {
		// Lança uma exceção caso a posição $position não seja válida
		$row = $this->_loadAndReturnRow($position);
		
		if($seek === true) {
			$this->seek($position);
		}
		
		return $row;
	}
	
	/**
	 * Retorna todos os dados como um array.
	 * 
	 * @return array
	 */
	public function toArray() {
		foreach($this->_rows as $i => $row) {
			$this->_data[$i] = $row->toArray();
		}	
		return $this->_data;
	}
	
	
	protected function _loadAndReturnRow($position) {
		// Lança uma exceção caso $position não seja uma posição válida
		$this->_requireOffset($position);
		
		// Se não há um objeto-linha nesta posição...
		if(empty($this->_rows[$position])) {
			// ... precisamos criá-lo
			$this->_rows[$position] = new $this->_rowClass(
				array(
					'table'		=>	$this->_table,
					'data'		=>	$this->_data[$position],
					'stored'	=>	$this->_stored,
					'readOnly'	=>	$this->_table	
				)
			);
		}
		
		return $this->_rows[$position];
	}
}
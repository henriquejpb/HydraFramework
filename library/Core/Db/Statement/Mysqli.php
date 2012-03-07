<?php
class Db_Statement_Mysqli extends Db_Statement_Abstract {
	
	/**
	 * Armazena os nomes das colunas
	 * @var array
	 */
	private $_keys;
	
	/**
	 * Armazena os valores das colunas
	 * @var array
	 */
	private $_values;
	
	/**
	 * Dados sobre o próprio statement
	 * @var array
	 */
	private $_metaData = null;
	
	/**
	 * (non-PHPdoc)
	 * @see Db_Statement_Abstract::_prepare()
	 */
	public function _prepare($sql) {
		$conn = $this->_adapter->getConnection();
		$this->_stmt = $conn->prepare($sql);
		if($this->_stmt === false || $conn->errno) {
			throw new Db_Statement_Mysqli_Exception('Erro MySQLi:' . $conn->error, $conn->errno);
		}
	}
	
	/**
	 * Fecha o cursor e o statement
	 * @return Db_Statement_Mysqli : Fluent Interface
	 */
	public function close() {
		if($this->_stmt) {
			$this->_stmt->close();
			$this->_stmt = null;
		}
		return $this;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Db_Statement_Interface::closeCursor()
	 */
	public function closeCursor() {
		if($this->_stmt) {
			$conn = $this->_adapter->getConnection();
			while($conn->more_results()) {
				$conn->next_result();
			}
			$this->_stmt->free_result();
			$this->_stmt->reset();
		}
		return $this;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Db_Statement_Interface::columnCount()
	 */
	public function columnCount() {
		if($this->_metaData !== null) {
			return $this->_metaData->field_count;
		}
		return 0;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Db_Statement_Interface::errorCode()
	 */
	public function errorCode() {
		if(!$this->_stmt) {
			return null;
		}
		return substr($this->_stmt->sqlstate, 0, 5);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Db_Statement_Interface::errorInfo()
	 */
	public function errorInfo() {
		if(!$this->_stmt) {
			return null;
		}
		
		return array(
						$this->errorCode(),
						$this->_stmt->errno,
						$this->_stmt->error
					);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Db_Statement_Interface::execute()
	 */
	public function execute(array $params = array()) {
		if(!$this->_stmt) {
			return null;
		}
		
		if(empty($params)) {
			$params = $this->_boundParams;
		}

		$stmtParams = array();
		if(!empty($params)) {
			/* O primeiro parâmetro passado para o método bind_param deve conter uma string 
			 * contendo os formatos dos parâmetros, tal como a função sprintf, mas sem o '%'
			 * Assumimos aqui que todos os parâmetros são strings, por isso o 's'
			 */
			array_unshift($params, str_repeat('s', count($params)));
			
			//bind_param precisa de referências como parâmetros
            foreach ($params as $k => &$value) {
                $stmtParams[$k] = &$value;
            }
		}
	
		if(!empty($stmtParams)) {
			call_user_func_array(
				array($this->_stmt, 'bind_param'), 
				$stmtParams
			);
		}
		
		$ret = $this->_stmt->execute();
		if($ret === false) {
			throw new Db_Statement_Mysqli_Exception('Erro de execução MySQLi Statement: ' . $this->_stmt->error, $this->_stmt->errno);
		}
		
		$this->_metaData = $this->_stmt->result_metadata();
		if($this->_stmt->errno) {
			throw new Db_Statement_Mysqli_Exception('Erro na obtenção dos metadados MySQLi: ' . $this->_stmt->error, $this->_stmt->errno);
		}
		
		//Vai retornar false se executarmos operações que não sejam SELECT
		if($this->_metaData !== false) {
			$this->_keys = array();
			foreach($this->_metaData->fetch_fields() as $col) {
				$this->_keys[] = $col->name;
			}
			
            $aux = array_fill(0, count($this->_keys), null);

            $refs = array();
            foreach ($aux as $i => &$f) {
                $refs[$i] = &$f;
            }

            $this->_stmt->store_result();
            call_user_func_array(
                array($this->_stmt, 'bind_result'),
                $refs
            );
            
            $this->_values = $aux;
		}
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Db_Statement_Abstract::_doFetch()
	 */
	public function _doFetch($mode = null) {
		if(!$this->_stmt) {
			return null;
		}
		
		$ret = $this->_stmt->fetch();
		//null -> fim dos dados, false -> erro
		if(!$ret) {
			$this->_stmt->reset();
			return null;
		}
		
		if($mode === null) {
			$mode = $this->_fetchMode;
		}
		
		$values = array();
		foreach($this->_values as $val) {
			$values[] = $val;
		}
		
		$row = null;
		switch($mode) {
			case Db::FETCH_NUM:
				$row = $values;
				break;
			case Db::FETCH_ASSOC:
				$row = array_combine($this->_keys, $values);
				break;
			case Db::FETCH_ARRAY:
				$assoc = array_combine($this->_keys, $value);
				$row = array_merge($assoc, $values);
				break;
			case Db::FETCH_OBJ:
				$row = (object) array_combine($this->_keys, $values);
				break;
			default:
				throw new Db_Statement_Mysqli_Exception('Modo de fetch inválido!');
		}
		return $row;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Db_Statement_Interface::nextRowset()
	 */
	public function nextRowset() {
		throw new Db_Statement_Mysqli_Exception('MySQLi não suporta esta operação: ' . __FUNCTION__ . '()');	
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Db_Statement_Interface::rowCount()
	 */
	public function rowCount() {
		if(!$this->_adapter) {
			return null;
		}
		
		return $this->_adapter->getConnection()->affected_rows;
	}
}
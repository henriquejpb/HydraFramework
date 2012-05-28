<?php
class Db_Statement_Pgsql extends Db_Statement_Abstract {
	
	/**
	 * No PostgreSQL, cada prepared statement precisa ter um nome.
	 * Para executá-lo, informamos apenas o nome do mesmo.
	 * @var string
	 */
	private $_name;
	
	
	/**
	 * Armazena o último resultado de uma query.
	 * @var resource
	 */
	private $_lastResult = null;
	
	public function __construct(Db_Adapter_Abstract $adapter, $sql) {
		// Dando um nome único para cada statement
		$this->_name = uniqid('stmt');
		parent::__construct($adapter, $sql);
	}
	
	protected function _prepare($sql) {
		$conn = $this->_adapter->getConnection();
		// No PostgreSQL, os placeholders para parâmetros são da forma '$n', não '?'
		preg_replace_callback('/\?/', 
			create_function(
				'$matches', 
				'static $count = 0;
				 return "$" . ++$count;'
			), $sql, -1, $count);
		
		/* Infelizmente não há outro jeito de suprimir os warnings gerados pela 
		 * função pg_prepare, se não o uso do '@'.
		 */
		$this->_stmt = pg_prepare($conn, $this->_name, $sql);
		$error = pg_last_error($conn);
		if($this->_stmt === false || $error) {
			throw new Db_Statement_Pgsql_Exception('Erro PostgreSQL:' . $error);
		}
	}
	
	public function closeCursor() {
		if($this->_lastResult !== null) {
			pg_result_seek($this->_lastResult, 0);
		}
		return $this;
	}
	
	public function columnCount() {
		// TODO: verificar como retornar o número de colunas retornadas de uma query
		return null; 
	}
	
	public function execute(array $params = array()) {
		if($this->_stmt === null) {
			return null;
		}
		
		$conn = $this->_adapter->getConnection();
		
		/* Infelizmente não há outro jeito de suprimir os warnings gerados pela
		 * função pg_prepare, se não o uso do '@'.
		 */
		$this->_lastResult = @pg_execute($conn, $this->_name, $params);
		if($this->_lastResult === false) {
			throw new Db_Statement_Pgsql_Exception('Erro PgSQL: ' . pg_last_error($conn));
		}
	}
	
	/**
	 * @see Db_Statement_Abstract::_doFetch()
	 */
	protected function _doFetch($mode = null) {
		if($this->_stmt === null) {
			return null;
		}
		
		if($mode === null) {
			$mode = $this->_fetchMode;
		}
		
		switch($mode) {
			case Db::FETCH_NUM:
				return pg_fetch_row($this->_lastResult);
			case Db::FETCH_ASSOC:
				return pg_fetch_assoc($this->_lastResult);
			case Db::FETCH_ARRAY:
				return pg_fetch_array($this->_lastResult);
			case Db::FETCH_OBJ:
				return pg_fetch_object($this->_lastResult);
			default:
				throw new Db_Statement_Pgsql_Exception('Modo de fetch inválido!');
		}
	}
	
	/**
	 * @see Db_Statement_Interface::nextRowset()
	 */
	public function nextRowset() {
		throw new Db_Statement_Pgsql_Exception('PostgreSQL não suporta esta operação: ' . __FUNCTION__ . '()');
	}
	
	/**
	 * @see Db_Statement_Interface::rowCount()
	 */
	public function rowCount() {
		if($this->_adapter === null || $this->_lastResult === null) {
			return null;
		}
	
		return pg_affected_rows($this->_lastResult);
	}
	
	public function errorCode() {
		$info = $this->errorInfo();
		if($info == null) {
			return null;
		}
		
		if(preg_match('/^ERROR:\s+(\d+):/', $info, $matches)) {
			return (int) $matches[1];
		}
	}
	
	public function errorInfo() {
		if($this->_lastResult) {
			$error = pg_result_error($this->_lastResult);
			if($error === false) {
				return pg_last_error($this->_adapter->getConnection());
			}
		}
		return null;
	}
}
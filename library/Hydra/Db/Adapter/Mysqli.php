<?php
final class Db_Adapter_Mysqli extends Db_Adapter_Abstract {
	/**
	 * Tipos numéricos do MySQL
	 * @var array
	 */
	protected $_numericDataTypes = array(
			Db::INT_TYPE    => Db::INT_TYPE,
			Db::BIGINT_TYPE => Db::BIGINT_TYPE,
			Db::FLOAT_TYPE  => Db::FLOAT_TYPE,
	        'INT'                => Db::INT_TYPE,
	        'INTEGER'            => Db::INT_TYPE,
	        'MEDIUMINT'          => Db::INT_TYPE,
	        'SMALLINT'           => Db::INT_TYPE,
	        'TINYINT'            => Db::INT_TYPE,
	        'BIGINT'             => Db::BIGINT_TYPE,
	        'SERIAL'             => Db::BIGINT_TYPE,
	        'DEC'                => Db::FLOAT_TYPE,
	        'DECIMAL'            => Db::FLOAT_TYPE,
	        'DOUBLE'             => Db::FLOAT_TYPE,
	        'DOUBLE PRECISION'   => Db::FLOAT_TYPE,
	        'FIXED'              => Db::FLOAT_TYPE,
	        'FLOAT'              => Db::FLOAT_TYPE
	);

	/**
	 * @var Db_Statement_Mysqli
	 */
	private $_statement = null;

	/**
	 * @var className
	 */
	protected $_statementClass = 'Db_Statement_Mysqli';
	
	/**
	 * @see Db_Adapter_Abstract::_connect()
	 */
	protected function _connect(){
		if($this->isConnected()){
			return;
		}
		
		if(!extension_loaded('mysqli')){
			throw new Db_Adapter_Mysqli_Exception('A extensão mysqli não foi carregada!');
		}
		
		if(isset($this->_config['port'])){
			$this->_config['port'] = (int) $this->_config['port'];
		} else {
			$this->_config['port'] = 3306;
		}
		
		try {
			$this->_connection = new mysqli(
											$this->_config['host'],
											$this->_config['username'],
											$this->_config['password'],
											$this->_config['dbname'],
											$this->_config['port']
										);
		} catch (Exception $e) {
			$this->disconnect();
			throw new Db_Adapter_Mysqli_Exception($e->getMessage());
		}
		
		if(isset($this->_config['charset'])){
			$this->_connection->set_charset($this->_config['charset']);
		}
	}
	
	/**
	 * @see Db_Adapter_Abstract::isConnected()
	 */
	public function isConnected() {
		return (bool) ($this->_connection instanceof mysqli);
	}
	
	/**
	 * @see Db_Adapter_Abstract::disconnect()
	 */
	public function disconnect() {
		if($this->isConnected()) {
			$this->_connection->close();
		}
		$this->_connection = null;
	}
	
	/**
	 * @see Db_Adapter_Abstract::prepare()
	 */
	public function prepare($sql) {
		$this->_connect();
		if($this->_statement) {
			$this->_statement->close();
		}
		
		$stmtClass = $this->_statementClass;
		
		$newStmt = new $stmtClass($this, $sql);
		$newStmt->setFetchMode($this->_fetchMode);
		
		$this->_statement = $newStmt;
		return $this->_statement;
	}
	
	/**
	 * @see Db_Adapter_Abstract::lastInsertId()
	 */
	public function lastInsertId($table = null, $pk = null) {
		return $this->getConnection()->insert_id;
	}
	
	/**
	 * @see Db_Adapter_Abstract::beginTransaction()
	 */
	public function beginTransaction() {
		$this->getConnection()->autocommit(false);
		$this->query('BEGIN');
	}
	
	/**
	 * @see Db_Adapter_Abstract::commitTransaction()
	 */
	public function commitTransaction() {
		$connection = $this->getConnection();
		$connection->commit();
		$connection->autocommit(true);
	}
	
	/**
	 * @see Db_Adapter_Abstract::rollBackTransaction()
	 */
	public function rollBackTransaction() {
		$connection = $this->getConnection();
		$connection->rollback();
		$connection->autocommit(true);
	}
	
	/**
	 * @see Db_Adapter_Abstract::limit()
	 */
	public function limit($sql, $count, $offset = 0) {
		$count = (int) $count;
		if($count <= 0) {
			throw new Db_Adapter_Mysqli_Exception(sprintf('O argumento $count=%s para a cláusula LIMIT não é valido', $count));
		}
		
		$offset = (int) $offset;
		if($offset < 0) {
			throw new Db_Adapter_Mysqli_Exception(sprintf('O argumento $offset=%s para a cláusula LIMIT não é valido', $count));
		}
		
		$sql .= "\nLIMIT " . $count;
		if($offset > 0) {
			$sql .= ' OFFSET ' . $offset;
		}
		
		return $sql;
	}
	
	/**
	 * @see Db_Adapter_Abstract::supportsParameters()
	 */
	public function supportsParameters($type) {
		return $type === Db_Adapter_Abstract::POSITIONAL_PARAMETERS;
	}

	/**
	 * @see Db_Adapter_Abstract::listTables()
	 */
	public function listTables() {
		$results = array();
		$sql = 'SHOW TABLES';
		if($query = $this->getConnection()->query($sql)) {
			while($row = $query->fetch_row()) {
				$results[] = $row[0];
			}
			$query->close();
		} else {
			throw new Db_Adapter_Mysqli_Exception($this->getConnection()->error);
		}

		return $results;
	}

	/**
	 * @see Db_Adapter_Abstract::describeTable()
	 */
	public function describeTable($tableName, $schemaName = null) {
		$sql = 	'DESCRIBE '. ($schemaName === null
				? $this->quoteIdentifier($tableName)
				: $this->quoteIdentifier($schemaName . '.' . $tableName));

		$results = $this->fetchAll($sql);

		$desc = array();

		$defaults = array(
			'Lenght' 			=> null,
			'Scale' 			=> null,
			'Precision' 		=> null,
			'Unsigned' 			=> null,
			'Primary' 			=> false,
			'PrimaryPosition'	=> null,
			'Identity'			=> false
		);

		$i = 1;
		$p = 1;
		foreach($results as $key => $row) {
			$row = array_merge($defaults, $row);
			if (preg_match('/unsigned/', $row['Type'])) {
				$row['Unsigned'] = true;
			}
			if (preg_match('/^((?:var)?char)\((\d+)\)/', $row['Type'], $matches)) {
				$row['Type'] = $matches[1];
				$row['Length'] = $matches[2];
			} else if (preg_match('/^decimal\((\d+),(\d+)\)/', $row['Type'], $matches)) {
				$row['Type'] = 'decimal';
				$row['Precision'] = $matches[1];
				$row['Scale'] = $matches[2];
			} else if (preg_match('/^float\((\d+),(\d+)\)/', $row['Type'], $matches)) {
				$row['Type'] = 'float';
				$row['Precision'] = $matches[1];
				$row['Scale'] = $matches[2];
			} else if (preg_match('/^((?:big|medium|small|tiny)?int)\((\d+)\)/', $row['Type'], $matches)) {
				$row['Type'] = $matches[1];
			}
				
			if (strtoupper($row['Key']) == 'PRI') {
				$row['Primary'] = true;
				$row['PrimaryPosition'] = $p;
				if ($row['Extra'] == 'auto_increment') {
					$row['Identity'] = true;
				} else {
					$row['Identity'] = false;
				}
				++$p;
			}
			
			$desc[$row['Field']] = array(
			                self::SCHEMA_NAME		=> $schemaName, 
			                self::TABLE_NAME		=> $tableName,
			                self::COLUMN_NAME		=> $row['Field'],
			                self::COLUMN_POSITION	=> $i,
			                self::DATA_TYPE			=> strtoupper($row['Type']),
			                self::DEFAULT_VALUE		=> $row['Default'],
			                self::NULLABLE			=> (bool) ($row['Null'] == 'YES'),
			                self::LENGHT			=> $row['Lenght'],
			                self::SCALE				=> $row['Scale'],
			                self::PRECISION			=> $row['Precision'],
			                self::UNSIGNED			=> $row['Unsigned'],
			                self::PRIMARY			=> $row['Primary'],
			                self::PRIMARY_POSITION	=> $row['PrimaryPosition'],
			                self::IDENTITY			=> $row['Identity']
			);
			++$i;
		}
		return $desc;
	}

	/**
	 * Ajusta o quoting para MySQL, utilizando o método real_escape_string
	 * @see Db_Adapter_Abstract::_doQuote()
	 */
	protected function _doQuote($value) {
		if(is_int($value) || is_float($value)) {
			return $value;
		}

		$this->_connect();
		return "'" . $this->_connection->real_escape_string($value) . "'";
	}

	/**
	 * @see Db_Adapter_Abstract::getQuoteIdentifierSymbol()
	 */
	public function getQuoteIdentifierSymbol() {
		return '`';
	}
}
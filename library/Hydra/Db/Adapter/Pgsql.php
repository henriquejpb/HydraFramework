<?php
class Db_Adapter_Pgsql extends Db_Adapter_Abstract {
	/**
	 * Tipos numéricos do PostgreSQL
	 * @var array
	 */
	protected $_numericDataTypes = array(
			Db::INT_TYPE    => Db::INT_TYPE,
			Db::BIGINT_TYPE => Db::BIGINT_TYPE,
			Db::FLOAT_TYPE  => Db::FLOAT_TYPE,
			'INTEGER'            => Db::INT_TYPE,
			'SERIAL'             => Db::INT_TYPE,
			'SMALLINT'           => Db::INT_TYPE,
			'BIGINT'             => Db::BIGINT_TYPE,
			'BIGSERIAL'          => Db::BIGINT_TYPE,
			'DECIMAL'            => Db::FLOAT_TYPE,
			'DOUBLE PRECISION'   => Db::FLOAT_TYPE,
			'NUMERIC'            => Db::FLOAT_TYPE,
			'REAL'               => Db::FLOAT_TYPE
	);
	
	/**
	 * @var className
	 */
	protected $_statementClass = 'Db_Statement_Pgsql';
	
	/**
	 * @var Db_Statement_Pgsql
	 */
	private $_statement = null;
	
	/**
	 * @see Db_Adapter_Abstract::_connect()
	 */
	protected function _connect() {
		if($this->isConnected()) {
			return;
		}
		
		if(!extension_loaded('pgsql')) {
			throw new Db_Adapter_Pgsql_Exception('A extensão pgsql não foi carregada!');
		}
		
		if(isset($this->_config['port'])){
			$this->_config['port'] = (int) $this->_config['port'];
		} else {
			$this->_config['port'] = 5432;
		}
		
		$this->_connection = pg_connect(
			'host=' . $this->_config['host'] . ' '.
			'port=' . $this->_config['port'] . ' ' . 
			'dbname=' . $this->_config['dbname'] . ' ' .
			'user=' . $this->_config['username'] . ' ' .
			'password=' . $this->_config['password']
		);
		
		if($this->_connection === false) {
			$this->_connection = null;
			throw new Db_Adapter_Pgsql_Exception(sprintf(
				'Impossível conectar ao banco de dados %s no servidor %s através do usuário %s', 
				$this->_config['dbname'], $this->_config['host'], $this->_config['user']
			));
		}
		
		if(isset($this->_config['charset'])){
			pg_set_client_encoding($this->_connection, $this->_config['charset']);
		}
	}
	
	/**
	 * @see Db_Adapter_Abstract::isConnected()
	 */
	public function isConnected() {
		return $this->_connection !== null;
	}
	
	/**
	 * @see Db_Adapter_Abstract::disconnect()
	 */
	public function disconnect() {
		if($this->isConnected()) {
			pg_close($this->_connection);
		}
		$this->_connection = null;
	}
	
	/**
	 * @see Db_Adapter_Abstract::prepare()
	 */
	public function prepare($sql) {
		$stmtClass = $this->_statementClass;
		
		$newStmt = new $stmtClass($this, $sql);
		$newStmt->setFetchMode($this->_fetchMode);
		
		$this->_statement = $newStmt;
		return $this->_statement;
	}
	
	/**
	 * @see Db_Adapter_Abstract::lastInsertId()
	 */
	public function lastInsertId($tableName = null, $pk = null) {
		if($tableName !== null) {
			$seqName = $tableName;
			if($pk !== null) {
				$seqName .= '_' . $pk;
			} else {
				$desc = $this->describeTable($tableName);
				foreach($desc as $key => $val) {
					if($val[self::IDENTITY] === true) {
						$seqName .= '_' . $key;
						break;
					}
				}
			}
			$seqName .= '_seq';
			$this->getConnection();
			return $this->lastSequenceId($seqName);
		} else {
			throw new Db_Adapter_Pgsql_Exception('É necessário informar ao menos o nome da tabela para
					obter o último id inserido!');
		}
	}
	
	/**
	 * Retorna o último ID da sequência $sequenceName.
	 * 
	 * @param string $sequenceName
	 * @return int
	 */
	public function lastSequenceId($sequenceName) {
		return $this->fetchColumn('SELECT currval(\'' . $seqName . '\')', 'currval');
	}

	/**
	 * @see Db_Adapter_Abstract::nextSequenceId()
	 */
	public function nextSequenceId($sequenceName) {
		$this->getConnection();
		return $this->fetchColumn('SELECT nextval(\'' . $seqName . '\')', 'currval');
	}
	
	/**
	 * @see Db_Adapter_Abstract::beginTransaction()
	 */
	public function beginTransaction() {
		$this->getConnection();
		$this->query('BEGIN;');
	}

	/**
	 * @see Db_Adapter_Abstract::commitTransaction()
	 */
	public function commitTransaction() {
		$this->getConnection();
		$this->query('COMMIT;');
	}
	
	/**
	 * @see Db_Adapter_Abstract::rollBackTransaction()
	 */	
	public function rollBackTransaction() {
		$this->getConnection();
		$this->query('ROLLBACK;');
	}
	
	/**
	 * @see Db_Adapter_Abstract::limit()
	 */
	public function limit($sql, $count, $offset = 0) {
		$count = (int) $count;
		if($count <= 0) {
			$count = 'ALL';
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
	 * TODO: verificar se há suporte a named parameters
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
		$sql = "SELECT c.relname FROM pg_catalog.pg_class c
				LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
				WHERE c.relkind IN ('r','') AND n.nspname NOT IN ('pg_catalog', 'pg_toast')
				AND pg_catalog.pg_table_is_visible(c.oid)";
		
		$this->getConnection();
		$results = $this->fetchAllColumns($sql, 0);
	
		return $results;
	}
	
	/**
	 * @see Db_Adapter_Abstract::describeTable()
	 */
	public function describeTable($tableName, $schemaName = null) {
		$results = array();
		
		// Workaround, pois o PgSQL não possui o comando DESCRIBE
		$sql = "SELECT
                a.attnum AS colpos,
                n.nspname AS schema_name,
                c.relname AS table_name,
                a.attname AS colname,
                t.typname AS type,
                a.atttypmod,
                FORMAT_TYPE(a.atttypid, a.atttypmod) AS complete_type,
                d.adsrc AS default_value,
                a.attnotnull AS notnull,
                a.attlen AS length,
                co.contype,
                ARRAY_TO_STRING(co.conkey, ',') AS conkey
            FROM pg_attribute AS a
                JOIN pg_class AS c ON a.attrelid = c.oid
                JOIN pg_namespace AS n ON c.relnamespace = n.oid
                JOIN pg_type AS t ON a.atttypid = t.oid
                LEFT OUTER JOIN pg_constraint AS co ON (co.conrelid = c.oid
                    AND a.attnum = ANY(co.conkey) AND co.contype = 'p')
                LEFT OUTER JOIN pg_attrdef AS d ON d.adrelid = c.oid AND d.adnum = a.attnum
            WHERE a.attnum > 0 AND c.relname = " . $this->quote($tableName);
		
		if($schemaName !== null) {
			$sql .= ' AND n.nspname = ' . $this->quote($schemaName);
		}
		$sql .= ' ORDER BY a.attnum';
		
		$results = $this->fetchAll($sql, array(), Db::FETCH_ASSOC);
		
		$desc = array();
		
		foreach($results as $key => $row) {
			$defaultValue = $row['default_value'];
			$precision = null;
			$scale = null;
			$type = strtoupper($row['complete_type']);
			
			if($row['type'] == 'varchar' || $row['type'] == 'bpchar') {
				if(preg_match('/character(?: varying)?(?:\((\d+)\))?/', $row['complete_type'], $matches)) {
					$row['length'] = isset($matches[1]) ? $matches[1] : null;
				}
			} else if($row['type'] == 'numeric') {
				if(preg_match('/numeric\((\d+)(?:, ?(\d+))?\)/', $row['complete_type'], $matches)) {
					$precision = isset($matches[1]) ? $matches[1] : null;
					$scale = isset($matches[2]) ? $matches[2] : null;
					$type = strtoupper($row['type']);
				}	
			}
			
			if(preg_match("/^'(.*?)'::.*$/", $row['default_value'], $matches)) {
				$defaultValue = $matches[1];
			}
			
			$keys = explode(',', $row['conkey']);
			$pos = array_search($row['colpos'], $keys);
			
			list($isPk, $pkPos, $isId) = array(false, null, false);
			if($pos !== false && $row['contype'] == 'p') {
				$isPk = true;
				$pkPos = $pos + 1; // 1-based
				$isId = (bool) preg_match('/^nextval/', $row['default_value']); // se é uma sequência
			}
			
			$desc[$row['colname']] = array(
				self::SCHEMA_NAME		=> $schemaName ? $schemaName : $row['schema_name'],
				self::TABLE_NAME		=> $tableName,
				self::COLUMN_NAME		=> $row['colname'],
				self::COLUMN_POSITION	=> (int) $row['colpos'],
				self::DATA_TYPE			=> $type,
				self::DEFAULT_VALUE		=> $defaultValue,
				self::NULLABLE			=> (bool) ($row['notnull'] != 't'),
				self::LENGHT			=> $row['length'],
				self::SCALE				=> $scale !== null ? (int) $scale : null,
				self::PRECISION			=> $precision !== null ? (int) $precision : $precision,
				self::UNSIGNED			=> null, // TODO
				self::PRIMARY			=> $isPk,
				self::PRIMARY_POSITION	=> $pkPos,
				self::IDENTITY			=> $isId
			);
		}
		return $desc;
	}
	
	
}
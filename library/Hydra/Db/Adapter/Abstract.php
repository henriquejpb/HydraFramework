<?php
abstract class Db_Adapter_Abstract {
	/**
	 * @var string
	 */
	const POSITIONAL_PARAMETERS = 'positional';

	/**
	 * @var string
	 */
	const NAMED_PARAMETERS 		= 'named';
	
	
	const SCHEMA_NAME 		= 'SCHEMA_NAME';
	
	const TABLE_NAME		= 'TABLE_NAME';
	
	const COLUMN_NAME 		= 'COLUMN_NAME';
	
	const COLUMN_POSITION 	= 'COLUMN_POSITION';
	
	const DATA_TYPE			= 'DATA_TYPE';
	
	const DEFAULT_VALUE		= 'DEFAULT';
	
	const NULLABLE			= 'NULLABLE';
	
	const LENGHT			= 'LENGHT';

	const SCALE				= 'SCALE';
	
	const PRECISION			= 'PRECISION';
	
	const UNSIGNED			= 'UNSIGNED';
	
	const PRIMARY			= 'PRIMARY';
	
	const PRIMARY_POSITION 	= 'PRIMARY_POSITION';
	
	const IDENTITY			= 'IDENTITY';

	/**
	 * Informa de os identificadores SQL (nomes de campos, tabelas, etc...) devem ser quotados.
	 * Ex.: SELECT * FROM tabela -> SELECT * FROM `tabela`
	 * @var boolean
	 */
	protected $_autoQuoteIdentifiers = true;

	/**
	 * Armazena as configura��es do Adapter fornecidas pelo usu�rio
	 * @var array
	 */
	protected $_config;

	/**
	 * Armazena a conex�o com o banco de dados
	 * @var null|resource|object
	 */
	protected $_connection;

	/**
	 * Informa qual o modo de fetch dos dados provindos do SGBD
	 * @var integer
	 */
	protected $_fetchMode = Db::FETCH_ASSOC;

	/**
	 * A classe de statement.
	 * @var string
	 */
	protected $_statementClass = 'Db_Statement_Abstract';

	/**
	 * Tipos de dados num�ricos, que n�o precisam ser quotados
	 * @var array
	 */
	protected $_numericDataTypes = array(
		Db::INT_TYPE => Db::INT_TYPE,
		Db::BIG_INT_TYPE => Db::BIG_INT_TYPE,
		Db::FLOAT_TYPE => Db::FLOAT_TYPE ,
	);

	/**
	 * Construtor
	 *
	 * @param array $config : array de configura��o de acesso ao banco de dados.
	 * As configura��es obrigat�rias s�o:
	 *
	 * host			=> (string) o nome do servidor do banco de dados
	 * username		=> (string) o nome do usu�rio do banco de dados
	 * password		=> (string) a senha do usu�rio do banco de dados
	 * dbname		=> (string) o nome do banco de dados desejado
	 *
	 * As configura��es opcionais s�o:
	 * port			=> (integer) a porta � qual se conectar [padr�o 3306]
	 * persistent	=> (boolean) indica se a conex�o deve ser persistente [padr�o false]
	 */
	public function __construct(array $config) {
		$this->_checkConfig($config);

		$this->_config = $config;
	}

	/**
	 * Checa as configura��es informadas pelo usu�rio
	 * @param array $config
	 * @return void
	 * @throws Db_Exception
	 */
	protected function _checkConfig(array $config) {
		if(
			!array_key_exists('host', $config) ||
			!array_key_exists('username', $config) ||
			!array_key_exists('password', $config) ||
			!array_key_exists('dbname', $config)
		) {
			throw new Db_Adapter_Exception(sprintf('Est�o faltando par�metros de configura��o para o Adapter "%s"', get_class($this)));
		}
	}

	/**
	 * Retorna o objeto ou recurso de conex�o com o banco de dados
	 * @return object|resource|null
	 */
	public function getConnection() {
		$this->_connect();
		return $this->_connection;
	}

	/**
	 * Retorna a configura��o do Adapter
	 * @return array;
	 */
	public function getConfig() {
		return $this->_config;
	}

	/**
	 * Retorna a classe de Statement padr�o
	 * @return string
	 */
	public function getStatementClass() {
		return $this->_statementClass;
	}

	/**
	 * Seta a classe de Statement padr�o
	 * @param class $class
	 * @return Db_Adapter_Abstract Fluent Interface
	 * @throws Db_Adapter_Exception
	 */
	public function setStatementClass($class) {
		if(class_exists($class)) {
			$this->_statementClass = $class;
			return $this;
		} else {
			throw new Db_Adapter_Exception(sprintf('A classe "%s" n�o existe', $class));
		}
	}

	/**
	 * Cria a conex�o com o banco de dados
	 * @return void
	 */
	abstract protected function _connect();

	/**
	 * Testa se a conex�o com o banco de dados est� aberta
	 * @return boolean
	 */
	abstract public function isConnected();

	/**
	 * Fecha a conex�o com o banco de dados
	 * @return void;
	 */
	abstract public function disconnect();

	/**
	 * Inicia uma transa��o no banco de dados
	 * @return void
	 */
	abstract public function beginTransaction();

	/**
	 * Salva uma transa��o
	 * @return void
	 */
	abstract public function commitTransaction();

	/**
	 * Cancela uma transa��o
	 * @return void
	 */
	abstract public function rollBackTransaction();

	/**
	 * Retorna um prepared stetement
	 * @param string|Db_Select $sql : a query SQL
	 * @return Db_Statement
	 */
	abstract public function prepare($sql);

	/**
	 * Prepara e executa um SQL statement com par�metros associados
	 * @param string|Db_Select $sql
	 * @param mixed $bindedParams
	 * @return Db_Statement_Abstract
	 */
	public function query($sql, $boundParams = array()) {
		$this->_connect();

		if($sql instanceof Db_Select) {
			if(empty($boundParams)) {
				$boundParams = $sql->getBoundParams();
			}

			$sql = $sql->assemble();
		}

		if(!is_array($boundParams)) {
			$boundParams = array($boundParams);
		}

		$stmt = $this->prepare($sql);
		$stmt->setFetchMode($this->_fetchMode);
		$stmt->execute($boundParams);

		return $stmt;
	}

	/**
	 * Insere na tabela $table os dados especificados em $boundParams
	 * @param string $table : a tabela na qual os dados ser�o inseridos
	 * @param array $boundParams : os dados a inserir, na forma array(coluna1 => valor1, coluna2 => valor2, ...)
	 * @throws Db_Adapter_Exception
	 * @return integer : o n�mero de linhas afetadas
	 */
	public function insert($table, array $boundParams) {
		$columns = array();
		$values = array();
		foreach($boundParams as $col => $val) {
			$columns[] = $this->quoteIdentifier($col);
			//Se temos uma instancia de Db_Expression, ela � inserida na query, sem placeholders intermedi�rios
			if($val instanceof Db_Expression) {
				$values[] = $val->__toString();
				unset($boundParams[$col]);
			} else {
				//Caso haja supoerte a par�metros posicionais, podemos setar '?' como placeholder
				if($this->supportsParameters(self::POSITIONAL_PARAMETERS)) {
					$values[] = '?';
				}
				//Se n�o houver, transformamos nome_campo em :nome_campo para posterior uso no prepared statement
				else if($this->supportsParameters(self::NAMED_PARAMETERS)) {
					$boundParams[':' . $col] = $val;
					$values[] = ':' . $col;
					unset($boundParams[$col]);
				} else {
					throw new Db_Adapter_Exception(sprintf('%s n�o suporta assossia��o posicional ou nominal de par�metros', get_class($this)));
				}
			}
		}

		$sql = 'INSERT INTO '
		. $this->quoteIdentifier($table)
		. ' (' . join(', ', $columns) . ') '
		. ' VALUES(' . join(', ', $values) . ')';

		if($this->supportsParameters(self::POSITIONAL_PARAMETERS)) {
			$boundParams = array_values($boundParams);
		}

		$stmt = $this->query($sql, $boundParams);
		return $stmt->rowCount();
	}

	/**
	 * Retorna o ID do �ltimo elemento inserido
	 * @param string $table [OPCIONAL] : o nome da tabela
	 * @param string $pk [OPCIONAL] : o nome da chave prim�ria
	 */
	abstract public function lastInsertId($table = null, $pk = null);
	
	/**
	 * Gera um novo valor para uma sequ�ncia espec�fica 
	 * no banco de dados e a retorna.
	 * 
	 * Isto � suportado apenas por alguns SGBDs,
	 * como Oracle, PostgreSQL, DB2, etc.
	 * Outros SGBDs retornam null
	 * @param string $sequenceName
	 * @return int|null
	 */
	public function nextSequenceId($sequenceName) {
		return null;
	}

	/**
	 * Atualiza os dados na tabela $table com os valores $boundParams sob a condi��o $where
	 * @param string $table : o nome da tabela para atualizar
	 * @param array $boundParams : os dados para sobrescrever os valores da tabela
	 * @param mixed $where : as condi��es para atuzaliza��o.
	 * Se for informado um array na forma:
	 * 			array(
	 * 					'campo1 = ?' => 'bla',
	 * 					'campo2 = ?' => 1
	 * 				);
	 * ser� gerada a seguinte cl�usula:
	 * 			WHERE campo1 = 'bla' AND campo2 = 1
	 *
	 * @throws Db_Adapter_Exception
	 * @return integer : o n�mero de linhas afetadas
	 */
	public function update($table, array $boundParams, $where){
		$set = array();
		foreach($boundParams as $col => $val) {
			//Se temos uma instancia de Db_Expression, ela � inserida na query, sem placeholders intermedi�rios
			if($val instanceof Db_Expression) {
				$val = $val->__toString();
				unset($boundParams[$col]);
			} else {
				if($this->supportsParameters(self::POSITIONAL_PARAMETERS)) {
					$val = '?';
				}
				else if($this->supportsParameters(self::NAMED_PARAMETERS)) {
					$boundParams[':' . $col] = $val;
					$val = ':' . $col;
					unset($boundParams[$col]);
				} else {
					throw new Db_Adapter_Exception(sprintf('%s n�o suporta assossia��o posicional ou nominal de par�metros', get_class($this)));
				}
			}
			$set[] = $this->quoteIdentifier($col) . ' = ' . $val;
		}

		$where = $this->_whereExpression($where);

		$sql = 'UPDATE '
		. $this->quoteIdentifier($table)
		. ' SET ' . join(', ', $set)
		. ($where ? ' WHERE ' . $where : '');

		if($this->supportsParameters(self::POSITIONAL_PARAMETERS)) {
			$boundParams = array_values($boundParams);
		}

		$stmt = $this->query($sql, $boundParams);
		return $stmt->rowCount();
	}

	/**
	 * Remove dados da tabela $table obedecendo as condi��o(�es) $where
	 * @param string $table : o nome da tabela
	 * @param mixed $where : as condi��es para atualiza��o. Ver m�todo Db_Adapter_Abstract::_whereExpression();
	 * @return integer : o n�mero de linhas afetadas
	 */
	public function delete($table, $where) {
		$where = $this->_whereExpression($where);

		$sql = 'DELETE FROM '
		. $this->quoteIdentifier($table)
		. ($where ? ' WHERE ' . $where : '');

		$stmt = $this->query($sql);
		return $stmt->rowCount();
	}

	/**
	 * Sintetiza uma cl�usula SQL WHERE a partir de $where
	 * @param mixed $where
	 * $where pode ser da forma:
	 *
	 * string:
	 * 		Cont�m qualquer cl�usula WHERE v�lida sem o identificador WHERE
	 * 		Exemplo:
	 * 			$where = 'campo1 = valor1' -> 'WHERE (campo1 = valor1)'
	 *
	 * array de strings [array(string (, string)*)]:
	 * 		Ser� gerada uma cl�usula WHERE unindo-se cada item do array por uma cl�usula AND
	 * 		Exemplo:
	 * 			$where = array('campo1 = valor1', 'campo2 = valor2 OR campo3 = valor3')
	 * 			-> 'WHERE (campo1 = valor1) AND (campo2 = valor2 OR campo3 = valor3)'
	 *
	 * array de pares condicional => par�metro associado:
	 * 		Funciona da mesma maneira que o array de strings. Internamente, ser� usado o m�todo Db_Adapter_Abstract::quoteInto()
	 * 		Exemplo:
	 * 			$where = array(
	 * 						'campo1 = ?' => 'x'
	 * 						'campo2 = ?' => 'y'
	 * 						)
	 * 			-> "WHERE (campo1 = 'x') AND (campo2 = 'y')"
	 *
	 * @return string : uma express�o SQL WHERE v�lida
	 */
	protected function _whereExpression($where) {
		if(empty($where)) {
			return $where;
		}

		if(!is_array($where)) {
			$where = array($where);
		}

		foreach($where as $key => &$value) {
			//Se $key � uma chave inteira, ou seja, n�o guarda uma condi��o...
			if(is_int($key)) {
				//Se $value for do tipo Db_Expression, convertemo-lo para string
				if($value instanceof Db_Expression) {
					$value = $value->__toString();
				}
				//... se n�o, $key guarda uma condi��o e $value um par�metro a ser associado
			} else {
				$value = $this->quoteInto($key, $value);
			}
			//Parentiza��o necess�ria caso haja cl�usulas OR dentro de $value
			$value = '(' . $value . ')';
		}

		return join(' AND ', $where);
	}

	/**
	 * Cria um novo objeto Db_Select para este Adapter
	 * @return Db_Select
	 */
	public function select() {
		return new Db_Select($this);
	}

	/**
	 * Adiciona uma cl�usula LIMIT ao select statement
	 * @param mixed $sql
	 * @param integer $count
	 * @param integer $offset
	 * @return string
	 */
	abstract public function limit($sql, $count, $offset = 0);

	/**
	 * Retorna o modo de fetch deste Adapter
	 * @return CONST Db::FETCH_*
	 */
	public function getFetchMode() {
		return $this->_fetchMode;
	}

	/**
	 * Seta o modo de fetch deste Adapter
	 * @param CONST Db::FETCH_* $mode : o novo modo de fetch
	 * @return void
	 */
	public function setFetchMode($mode) {
		switch($mode) {
			case Db::FETCH_ARRAY:
			case Db::FETCH_ASSOC:
			case Db::FETCH_COLUMN:
			case Db::FETCH_NUM:
			case Db::FETCH_OBJ:
				$this->_fetchMode = $mode;
			default:
				throw new Db_Adapter_Exception('Modo de fetch inv�lido');
		}
	}

	/**
	 * Busca todas as linhas de retorno da consulta como um array sequencial
	 * @param string|Db_Select $sql : um SQL Select Statement
	 * @param strint|array $boundParams : par�metros para substituir os placeholders
	 * @param CONST Db::FETCH_* $fetchMode : se diferente de null, sobrescreve o modo de fetch do Adapter
	 * @param string|int $col : a coluna de proje��o, caso $fetchMode == Db::FETCH_COLUMN
	 * @return array
	 */
	public function fetchAll($sql, $boundParams = array(), $fetchMode = null, $col = null) {
		if($fetchMode === null) {
			$fetchMode = $this->_fetchMode;
		}

		$stmt = $this->query($sql, $boundParams);
		return $stmt->fetchAll($fetchMode, $col);
	}

	/**
	 * Busca a primeira linha de retorno da consulta
	 * @param string|Db_Select $sql : um SQL Select Statement
	 * @param strint|array $boundParams : par�metros para substituir os placeholders
	 * @param CONST Db::FETCH_* $fetchMode : se diferente de null, sobrescreve o modo de fetch do Adapter
	 * @param string |int $col : a coluna de proje��o, caso $fetchMode == Db::FETCH_COL
	 * @return array|string
	 */
	public function fetchOne($sql, $boundParams = array(), $fetchMode = null, $col = null) {
		if($fetchMode === null) {
			$fetchMode = $this->_fetchMode;
		}

		$stmt = $this->query($sql, $boundParams);
		return $stmt->fetchOne($fetchMode, $col);
	}
	
	/**
	 * Busca e retorna a coluna especificada (proje��o) de cada linha do resultado
	 * @param string|Db_Select $sql
	 * @param string|int $column
	 * @param mixed $bounParams
	 * @return array
	 */
	public function fetchAllColumns($sql, $column, $boundParams = array()) {
		return $this->fetchAll($sql, $boundParams, Db::FETCH_COLUMN, $column);
	}
	
	/**
	 * Retorna a proje��o da coluna especificada na primeira linha do resultado
	 * @param string|Db_Select $sql
	 * @param string|int $column
	 * @param mixed $bounParams
	 * @return array|string
	 */
	public function fetchColumn($sql, $column, $boundParams = array()) {
		return $this->fetchOne($sql, $boundParams, Db::FETCH_COLUMN, $column);
	}
	

	/**
	 * Checa se o adapter suporta par�metros posicionais ou nominais
	 * @param Db_Adapter_Abstract::*_PARAMETERS $type : o tipo de par�metro suportado
	 * @return boolean
	 */
	abstract public function supportsParameters($type);

	/**
	 * Retorna uma lista de todas as tabelas no banco de dados
	 * @return array;
	 */
	abstract public function listTables();

	/**
	 * Retorna a descri��o das colunas de uma tabela.
	 *
	 * O valor de retorno � um array associativo pelo nome da coluna.
	 *
	 * Cada item do array � um outro array associativo,
	 * com as seguintes chaves:
	 *
	 * SCHEMA_NAME 		=> string : o nome do schema
	 * TABLE_NAME		=> string : o nome da tabela
	 * COLUMN_NAME		=> string : o nome da coluna
	 * COLUMN_POSITION	=> number : a posi��o ordinal da coluna na tabela
	 * DATA_TYPE		=> string : o tipo da coluna
	 * DEFAULT			=> mixed : o valor padr�o da coluna
	 * NULLABLE			=> string : true se a coluna pode ser nula
	 * LENGTH			=> number : o tamanho de um campo CHAR/VARCHAR
	 * SCALE			=> number : a escala de um campo NUMERIC/DECIMAL
	 * PRECISION		=> number : a precis�o de um campo NUMERIC/DECIMAL
	 * UNSIGNED			=> boolean : true se o campo � sem sinal
	 * PRIMARY			=> boolean : true se o campo � ou faz parte da chave prim�ria
	 * PRIMARY_POSITION	=> integer : a posi��o do campo dentro da chave prim�ria
	 *
	 * @param string $tableName
	 * @param string $schemaName [OPICIONAL]
	 * @return array;
	 */
	abstract public function describeTable($tableName, $schemaName = null);

	/**
	 * Quota um valor para ser usado dentro de um SQL Statement
	 * @param mixed $value : o valor a ser quotado
	 * @param constant Db::*_TYPE $type [OPCIONAL] : caso seja necess�rio for�ar o tipo de quoting. Ex.: inserir "2" em um campo text
	 */
	public function quote($value, $type = null) {
		//Inicia a conex�o (caso n�o esteja iniciada ainda)...
		$this->_connect();

		//Caso hajam subquerys
		if($value instanceof Db_Select) {
			return '(' . $value->assemble() . ')';
		}

		//Express�es do tipo CURDATE(), CAST(campo AS tipo)
		if($value instanceof Db_Expression) {
			//Compatibilidade com vers�es < 5.2.4
			return $value->__toString();
		}

		if(is_array($value)) {
			foreach ($value as &$singleVal) {
				$singleVal = $this->quote($singleVal, $type);
			}
			return join(', ', $value);
		}

		if($type !== null && array_key_exists($type, $this->_numericDataTypes)){
			$quotedVal = '0';
			switch($this->_numericDataTypes[$type]) {
				case Db::INT_TYPE:
					$quotedVal = (string) intval($value);
					break;
					// Inteiro de 64 bits (by Zend Framework)
				case Db::BIGINT_TYPE:
					// ANSI SQL-style hex literals (e.g. x'[\dA-F]+')
					// are not supported here, because these are string
					// literals, not numeric literals.
					if (preg_match('/^(
					                          [+-]?                  # optional sign
					                          (?:
					                            0[Xx][\da-fA-F]+     # ODBC-style hexadecimal
					                            |\d+                 # decimal or octal, or MySQL ZEROFILL decimal
					                            (?:[eE][+-]?\d+)?    # optional exponent on decimals or octals
					                          )
					                        )/x',
					(string) $value, $matches)) {
						$quotedValue = $matches[1];
					}
				case Db::FLOAT_TYPE:
					$quotedVal = sprintf('%F', $value);
			}
			return $quotedVal;
		}

		return $this->_doQuote($value);
	}

	/**
	 * Faz o quoting de uma string "crua"
	 *
	 * @param string $value : o valor a ser quotado
	 * @return string : a string devidamente quotada
	 */
	protected function _doQuote($value) {
		if(is_int($value)) {
			return $value;
		} else if(is_float($value)) {
			return sprintf('%f', $value);
		} else {
			return "'" . addcslashes($value, "\000\n\r\\'\"\032") . "'";
		}
	}

	/**
	 * Insere um valor quotado em uma express�o. A posi��o da string � marcada pelo placeholder '?'.
	 * Ex.: $this->quoteInto('WHERE id = ?', 4);
	 * @param string $text : A string contendo a ser retornada, ainda contendo o(s) placeholder(s)
	 * @param mixed $value : O valor a ser inserido na string
	 * @param constant Db::*_TYPE $type [OPCIONAL] : o tipo de quoting
	 * @param integer $count : a quantidade de vezes que $value ser� substitu�do em $text, se houver placeholders suficientes
	 */
	public function quoteInto($text, $value, $type = null, $count = 1) {
		while($count > 0 && strpos($text, '?') !== false) {
			$text = substr_replace($text, $this->quote($value, $type), strpos($text, '?'), strlen('?'));
			$count--;
		}
		return $text;
	}

	/**
	 * Quota um identificador
	 * @param string|array|Db_Expression $identifier : o identificador a ser quotado
	 * @param boolean $forced [OPCIONAL] : indica se o quotamento deve ser for�ado, mesmo que _autoQuoteIdentifiers seja false
	 * @return string : o identificador quotado
	 */
	public function quoteIdentifier($identifier, $forced = false) {
		return $this->_doQuoteIdentifierAs($identifier, null, $forced);
	}

	/**
	 * Quota um identificador de coluna e seu apelido (alias)
	 * @param string|array|Db_Expression $column : o identificador da coluna
	 * @param string $alias : um apelido para a coluna
	 * @param boolean $forced [OPCIONAL] : indica se o quotamento deve ser for�ado, mesmo que _autoQuoteIdentifiers seja false
	 * @return string : o identificador quotado
	 */
	public function quoteColumnAs($column, $alias, $forced = false) {
		return $this->_doQuoteIdentifierAs($column, $alias, $forced);
	}

	/**
	 * Quota um identificador de tabela e seu apelido (alias)
	 * @param string $table : o identificador da tabela
	 * @param string $alias : um apelido para a tabela
	 * @param boolean $forced [OPCIONAL] : indica se o quotamento deve ser for�ado, mesmo que _autoQuoteIdentifiers seja false
	 * @return string : o identificador quotado
	 */
	public function quoteTableAs($table, $alias, $forced = false) {
		return $this->_doQuoteIdentifierAs($table, $alias, $forced);
	}

	/**
	 * Efetivamente faz o quotamento de um identificador complexo
	 * @param string|array|Db_Expression $identifier
	 * @param string $alias : o apelido para o identificador
	 * @param boolean $forced [OPCIONAL] : indica se o quotamento deve ser for�ado, mesmo que _autoQuoteIdentifiers seja false
	 * @param string $as [OPCIONAL] : o token de apelidamento
	 */
	protected function _doQuoteIdentifierAs($identifier, $alias, $forced = false, $as = 'AS') {
		if($identifier instanceof Db_Expression) {
			$quoted = $identifier->__toString();
		} else if($identifier instanceof Db_Select) {
			$quoted = '(' . $identifier->assemble() . ')';
		} else {
			if(is_string($identifier)) {
				$identifier = explode('.', $identifier);
			}

			if(is_array($identifier)) {
				$segments = array();
				foreach($identifier as $piece) {
					if($piece instanceof Db_Expression) {
						$segments[] = $piece->__toString();
					} else {
						$segments[] = $this->_doQuoteIdentifier($piece, $forced);
					}
				}
				if($alias != null && end($identifier) == $alias) {
					$alias = null;
				}
				$quoted = join('.', $segments);
			} else {
				$quoted = $this->_doQuoteIdentifier($identifier, $forced);
			}
		}
		if($alias != null) {
			$quoted .= ' ' . $as . ' ' . $this->_doQuoteIdentifier($alias, $forced);
		}
		return $quoted;
	}

	/**
	 * Efetivamente quota um identificador simples (em formato de string)
	 * @param string $identifier : o identificador
	 * @param boolean $forced [OPCIONAL] : indica se o quotamento deve ser for�ado, mesmo que _autoQuoteIdentifiers seja false
	 */
	protected function _doQuoteIdentifier($identifier, $forced = false) {
		if($this->_autoQuoteIdentifiers === true || $forced === true) {
			$q = $this->getQuoteIdentifierSymbol();
			return ($q . str_replace($q, "{$q}{$q}", $identifier) . $q);
		}
		return $value;
	}

	/**
	 * Retorna o s�mbolo de quoting de um identificador
	 * @return string
	 */
	public function getQuoteIdentifierSymbol() {
		return '"';
	}
}
<?php
abstract class Hydra_Db_Adapter_Abstract {
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
	 * Armazena as configurações do Adapter fornecidas pelo usuário
	 * @var array
	 */
	protected $_config;

	/**
	 * Armazena a conexão com o banco de dados
	 * @var null|resource|object
	 */
	protected $_connection;

	/**
	 * Informa qual o modo de fetch dos dados provindos do SGBD
	 * @var integer
	 */
	protected $_fetchMode = Hydra_Db::FETCH_ASSOC;

	/**
	 * A classe de statement.
	 * @var string
	 */
	protected $_statementClass = 'Hydra_Db_Statement_Abstract';

	/**
	 * Tipos de dados numéricos, que não precisam ser quotados
	 * @var array
	 */
	protected $_numericDataTypes = array(
		Hydra_Db::INT_TYPE => Hydra_Db::INT_TYPE,
		Hydra_Db::BIG_INT_TYPE => Hydra_Db::BIG_INT_TYPE,
		Hydra_Db::FLOAT_TYPE => Hydra_Db::FLOAT_TYPE ,
	);

	/**
	 * Construtor
	 *
	 * @param array $config : array de configuração de acesso ao banco de dados.
	 * As configurações obrigatórias são:
	 *
	 * host			=> (string) o nome do servidor do banco de dados
	 * username		=> (string) o nome do usuário do banco de dados
	 * password		=> (string) a senha do usuário do banco de dados
	 * dbname		=> (string) o nome do banco de dados desejado
	 *
	 * As configurações opcionais são:
	 * port			=> (integer) a porta à qual se conectar [padrão 3306]
	 * persistent	=> (boolean) indica se a conexão deve ser persistente [padrão false]
	 */
	public function __construct(array $config) {
		$this->_checkConfig($config);

		$this->_config = $config;
	}

	/**
	 * Checa as configurações informadas pelo usuário
	 * @param array $config
	 * @return void
	 * @throws Hydra_Db_Exception
	 */
	protected function _checkConfig(array $config) {
		if(
			!array_key_exists('host', $config) ||
			!array_key_exists('username', $config) ||
			!array_key_exists('password', $config) ||
			!array_key_exists('dbname', $config)
		) {
			throw new Hydra_Db_Adapter_Exception(sprintf('Estão faltando parâmetros de configuração para o Adapter "%s"', get_class($this)));
		}
	}

	/**
	 * Retorna o objeto ou recurso de conexão com o banco de dados
	 * @return object|resource|null
	 */
	public function getConnection() {
		$this->_connect();
		return $this->_connection;
	}

	/**
	 * Retorna a configuração do Adapter
	 * @return array;
	 */
	public function getConfig() {
		return $this->_config;
	}

	/**
	 * Retorna a classe de Statement padrão
	 * @return string
	 */
	public function getStatementClass() {
		return $this->_statementClass;
	}

	/**
	 * Seta a classe de Statement padrão
	 * @param class $class
	 * @return Hydra_Db_Adapter_Abstract Fluent Interface
	 * @throws Hydra_Db_Adapter_Exception
	 */
	public function setStatementClass($class) {
		if(class_exists($class)) {
			$this->_statementClass = $class;
			return $this;
		} else {
			throw new Hydra_Db_Adapter_Exception(sprintf('A classe "%s" não existe', $class));
		}
	}

	/**
	 * Cria a conexão com o banco de dados
	 * @return void
	 */
	abstract protected function _connect();

	/**
	 * Testa se a conexão com o banco de dados está aberta
	 * @return boolean
	 */
	abstract public function isConnected();

	/**
	 * Fecha a conexão com o banco de dados
	 * @return void;
	 */
	abstract public function disconnect();

	/**
	 * Inicia uma transação no banco de dados
	 * @return void
	 */
	abstract public function beginTransaction();

	/**
	 * Salva uma transação
	 * @return void
	 */
	abstract public function commitTransaction();

	/**
	 * Cancela uma transação
	 * @return void
	 */
	abstract public function rollBackTransaction();

	/**
	 * Retorna um prepared stetement
	 * @param string|Hydra_Db_Select $sql : a query SQL
	 * @return Hydra_Db_Statement
	 */
	abstract public function prepare($sql);

	/**
	 * Prepara e executa um SQL statement com parâmetros associados
	 * @param string|Hydra_Db_Select $sql
	 * @param mixed $boundParams
	 * @return Hydra_Db_Statement_Abstract
	 */
	public function query($sql, $boundParams = array()) {
		$this->_connect();

		if($sql instanceof Hydra_Db_Select) {
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
	 * @param string $table : a tabela na qual os dados serão inseridos
	 * @param array $boundParams : os dados a inserir, na forma array(coluna1 => valor1, coluna2 => valor2, ...)
	 * @throws Hydra_Db_Adapter_Exception
	 * @return integer : o número de linhas afetadas
	 */
	public function insert($table, array $boundParams) {
		$columns = array();
		$values = array();
		
		foreach($boundParams as $col => $val) {
			$columns[] = $this->quoteIdentifier($col);
			//Se temos uma instancia de Hydra_Db_Expression, ela é inserida na query, sem placeholders intermediários
			if($val instanceof Hydra_Db_Expression) {
				$values[] = $val->__toString();
				unset($boundParams[$col]);
			} else {
				//Caso haja supoerte a parâmetros posicionais, podemos setar '?' como placeholder
				if($this->supportsParameters(self::POSITIONAL_PARAMETERS)) {
					$values[] = '?';
				}
				//Se não houver, transformamos nome_campo em :nome_campo para posterior uso no prepared statement
				else if($this->supportsParameters(self::NAMED_PARAMETERS)) {
					$boundParams[':' . $col] = $val;
					$values[] = ':' . $col;
					unset($boundParams[$col]);
				} else {
					throw new Hydra_Db_Adapter_Exception(sprintf('%s não suporta assossiação posicional ou nominal de parâmetros', get_class($this)));
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
	 * Retorna o ID do último elemento inserido.
	 *
	 * Caso não se conheça o nome da tabela, este valor deve ser null.
	 * Caso não se conheça o nome da sequência, o adapter tentará
	 * obter seu nome através dos metadados da tabela.
	 *
	 * @param string $tableName [OPCIONAL] : o nome da tabela
	 * @param string $seqName [OPCIONAL] : o nome da sequencia
	 * @return integer|null
	 */
	abstract public function lastInsertId($tableName = null, $seqName = null);

	/**
	 * Retorna o próximo ID a ser inserido em uma tabela.
	 *
	 * Caso não se conheça o nome da tabela, este valor deve ser null.
	 * Caso não se conheça o nome da sequência, o adapter tentará
	 * obter seu nome através dos metadados da tabela.
	 *
	 * @param string $tableName
	 * @param string $seqName
	 */
	public function nextInsertId($tableName = null, $seqName = null) {
		return null;
	}

	/**
	 * Gera um novo valor para uma sequência específica
	 * no banco de dados e a retorna.
	 *
	 * Isto é suportado apenas por alguns SGBDs,
	 * como Oracle, PostgreSQL, DB2, etc.
	 * Outros SGBDs retornam null.
	 *
	 * @param string $sequenceName
	 * @return int|null
	 */
	public function nextSequenceId($sequenceName) {
		return null;
	}

	/**
	 * Retorna o nome de uma sequência para um campo serial de uma tabela
	 *
	 * @param string $tableName [OPCIONAL] : o nome da tabela
	 * @param string $seqName [OPCIONAL] : o nome da sequência
	 * @param array $metadata [OPCIONAL] : os metadados da tabela, caso já tenham sido obtidos
	 * @return boolean|string
	 */
	public function getSequenceName($tableName = null, $seqName = null, 
			array $identityMetadata = array()) {
		return true;
	}

	/**
	 * Atualiza os dados na tabela $table com os valores $boundParams sob a condição $where
	 * @param string $table : o nome da tabela para atualizar
	 * @param array $boundParams : os dados para sobrescrever os valores da tabela
	 * @param mixed $where : as condições para atuzalização.
	 * Se for informado um array na forma:
	 * 			array(
	 * 					'campo1 = ?' => 'bla',
	 * 					'campo2 = ?' => 1
	 * 				);
	 * será gerada a seguinte cláusula:
	 * 			WHERE campo1 = 'bla' AND campo2 = 1
	 *
	 * @throws Hydra_Db_Adapter_Exception
	 * @return integer : o número de linhas afetadas
	 */
	public function update($table, array $boundParams, $where){
		$set = array();
		foreach($boundParams as $col => $val) {
			//Se temos uma instancia de Hydra_Db_Expression, ela é inserida na query, sem placeholders intermediários
			if($val instanceof Hydra_Db_Expression) {
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
					throw new Hydra_Db_Adapter_Exception(sprintf('%s não suporta assossiação posicional ou nominal de parâmetros', get_class($this)));
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
	 * Remove dados da tabela $table obedecendo as condição(ões) $where
	 * @param string $table : o nome da tabela
	 * @param mixed $where : as condições para atualização. Ver método Hydra_Db_Adapter_Abstract::_whereExpression();
	 * @return integer : o número de linhas afetadas
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
	 * Sintetiza uma cláusula SQL WHERE a partir de $where
	 * @param mixed $where
	 * $where pode ser da forma:
	 *
	 * string:
	 * 		Contém qualquer cláusula WHERE válida sem o identificador WHERE
	 * 		Exemplo:
	 * 			$where = 'campo1 = valor1' -> 'WHERE (campo1 = valor1)'
	 *
	 * array de strings [array(string (, string)*)]:
	 * 		Será gerada uma cláusula WHERE unindo-se cada item do array por uma cláusula AND
	 * 		Exemplo:
	 * 			$where = array('campo1 = valor1', 'campo2 = valor2 OR campo3 = valor3')
	 * 			-> 'WHERE (campo1 = valor1) AND (campo2 = valor2 OR campo3 = valor3)'
	 *
	 * array de pares condicional => parâmetro associado:
	 * 		Funciona da mesma maneira que o array de strings. Internamente, será usado o método Hydra_Db_Adapter_Abstract::quoteInto()
	 * 		Exemplo:
	 * 			$where = array(
	 * 						'campo1 = ?' => 'x'
	 * 						'campo2 = ?' => 'y'
	 * 						)
	 * 			-> "WHERE (campo1 = 'x') AND (campo2 = 'y')"
	 *
	 * @return string : uma expressão SQL WHERE válida
	 */
	protected function _whereExpression($where) {
		if(empty($where)) {
			return $where;
		}

		if(!is_array($where)) {
			$where = array($where);
		}

		foreach($where as $key => &$value) {
			//Se $key é uma chave inteira, ou seja, não guarda uma condição...
			if(is_int($key)) {
				//Se $value for do tipo Hydra_Db_Expression, convertemo-lo para string
				if($value instanceof Hydra_Db_Expression) {
					$value = $value->__toString();
				}
				//... se não, $key guarda uma condição e $value um parâmetro a ser associado
			} else {
				$value = $this->quoteInto($key, $value);
			}
			//Parentização necessária caso haja cláusulas OR dentro de $value
			$value = '(' . $value . ')';
		}

		return join(' AND ', $where);
	}

	/**
	 * Cria um novo objeto Hydra_Db_Select para este Adapter
	 * @return Hydra_Db_Select
	 */
	public function select() {
		return new Hydra_Db_Select($this);
	}

	/**
	 * Adiciona uma cláusula LIMIT ao select statement
	 * @param mixed $sql
	 * @param integer $count
	 * @param integer $offset
	 * @return string
	 */
	abstract public function limit($sql, $count, $offset = 0);

	/**
	 * Retorna o modo de fetch deste Adapter
	 * @return CONST Hydra_Db::FETCH_*
	 */
	public function getFetchMode() {
		return $this->_fetchMode;
	}

	/**
	 * Seta o modo de fetch deste Adapter
	 * @param CONST Hydra_Db::FETCH_* $mode : o novo modo de fetch
	 * @return void
	 */
	public function setFetchMode($mode) {
		switch($mode) {
			case Hydra_Db::FETCH_ARRAY:
			case Hydra_Db::FETCH_ASSOC:
			case Hydra_Db::FETCH_COLUMN:
			case Hydra_Db::FETCH_NUM:
			case Hydra_Db::FETCH_OBJ:
				$this->_fetchMode = $mode;
			default:
				throw new Hydra_Db_Adapter_Exception('Modo de fetch inválido');
		}
	}

	/**
	 * Busca todas as linhas de retorno da consulta como um array sequencial
	 * @param string|Hydra_Db_Select $sql : um SQL Select Statement
	 * @param strint|array $boundParams : parâmetros para substituir os placeholders
	 * @param CONST Hydra_Db::FETCH_* $fetchMode : se diferente de null, sobrescreve o modo de fetch do Adapter
	 * @param string|int $col : a coluna de projeção, caso $fetchMode == Hydra_Db::FETCH_COLUMN
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
	 * @param string|Hydra_Db_Select $sql : um SQL Select Statement
	 * @param strint|array $boundParams : parâmetros para substituir os placeholders
	 * @param CONST Hydra_Db::FETCH_* $fetchMode : se diferente de null, sobrescreve o modo de fetch do Adapter
	 * @param string |int $col : a coluna de projeção, caso $fetchMode == Hydra_Db::FETCH_COL
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
	 * Busca e retorna a coluna especificada (projeção) de cada linha do resultado
	 * @param string|Hydra_Db_Select $sql
	 * @param string|int $column
	 * @param mixed $bounParams
	 * @return array
	 */
	public function fetchAllColumns($sql, $column, $boundParams = array()) {
		return $this->fetchAll($sql, $boundParams, Hydra_Db::FETCH_COLUMN, $column);
	}

	/**
	 * Retorna a projeção da coluna especificada na primeira linha do resultado
	 * @param string|Hydra_Db_Select $sql
	 * @param string|int $column
	 * @param mixed $bounParams
	 * @return array|string
	 */
	public function fetchColumn($sql, $column, $boundParams = array()) {
		return $this->fetchOne($sql, $boundParams, Hydra_Db::FETCH_COLUMN, $column);
	}


	/**
	 * Checa se o adapter suporta parâmetros posicionais ou nominais
	 * @param Hydra_Db_Adapter_Abstract::*_PARAMETERS $type : o tipo de parâmetro suportado
	 * @return boolean
	 */
	abstract public function supportsParameters($type);

	/**
	 * Retorna uma lista de todas as tabelas no banco de dados
	 * @return array;
	 */
	abstract public function listTables();

	/**
	 * Retorna a descrição das colunas de uma tabela.
	 *
	 * O valor de retorno é um array associativo pelo nome da coluna.
	 *
	 * Cada item do array é um outro array associativo,
	 * com as seguintes chaves:
	 *
	 * SCHEMA_NAME 		=> string : o nome do schema
	 * TABLE_NAME		=> string : o nome da tabela
	 * COLUMN_NAME		=> string : o nome da coluna
	 * COLUMN_POSITION	=> number : a posição ordinal da coluna na tabela
	 * DATA_TYPE		=> string : o tipo da coluna
	 * DEFAULT			=> mixed : o valor padrão da coluna
	 * NULLABLE			=> string : true se a coluna pode ser nula
	 * LENGTH			=> number : o tamanho de um campo CHAR/VARCHAR
	 * SCALE			=> number : a escala de um campo NUMERIC/DECIMAL
	 * PRECISION		=> number : a precisão de um campo NUMERIC/DECIMAL
	 * UNSIGNED			=> boolean : true se o campo é sem sinal
	 * PRIMARY			=> boolean : true se o campo é ou faz parte da chave primária
	 * PRIMARY_POSITION	=> integer : a posição do campo dentro da chave primária
	 *
	 * @param string $tableName
	 * @param string $schemaName [OPICIONAL]
	 * @return array;
	 */
	abstract public function describeTable($tableName, $schemaName = null);

	/**
	 * Quota um valor para ser usado dentro de um SQL Statement
	 * @param mixed $value : o valor a ser quotado
	 * @param constant Hydra_Db::*_TYPE $type [OPCIONAL] : caso seja necessário forçar o tipo de quoting. Ex.: inserir "2" em um campo text
	 */
	public function quote($value, $type = null) {
		//Inicia a conexão (caso não esteja iniciada ainda)...
		$this->_connect();

		//Caso hajam subquerys
		if($value instanceof Hydra_Db_Select) {
			return '(' . $value->assemble() . ')';
		}

		//Expressões do tipo CURDATE(), CAST(campo AS tipo)
		if($value instanceof Hydra_Db_Expression) {
			//Compatibilidade com versões < 5.2.4
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
				case Hydra_Db::INT_TYPE:
					$quotedVal = (string) intval($value);
					break;
					// Inteiro de 64 bits (by Zend Framework)
				case Hydra_Db::BIGINT_TYPE:
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
				case Hydra_Db::FLOAT_TYPE:
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
	 * Insere um valor quotado em uma expressão. A posição da string é marcada pelo placeholder '?'.
	 * Ex.: $this->quoteInto('WHERE id = ?', 4);
	 * @param string $text : A string contendo a ser retornada, ainda contendo o(s) placeholder(s)
	 * @param mixed $value : O valor a ser inserido na string
	 * @param constant Hydra_Db::*_TYPE $type [OPCIONAL] : o tipo de quoting
	 * @param integer $count : a quantidade de vezes que $value será substituí­do em $text, se houver placeholders suficientes
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
	 * @param string|array|Hydra_Db_Expression $identifier : o identificador a ser quotado
	 * @param boolean $forced [OPCIONAL] : indica se o quotamento deve ser forçado, mesmo que _autoQuoteIdentifiers seja false
	 * @return string : o identificador quotado
	 */
	public function quoteIdentifier($identifier, $forced = false) {
		return $this->_doQuoteIdentifierAs($identifier, null, $forced);
	}

	/**
	 * Quota um identificador de coluna e seu apelido (alias)
	 * @param string|array|Hydra_Db_Expression $column : o identificador da coluna
	 * @param string $alias : um apelido para a coluna
	 * @param boolean $forced [OPCIONAL] : indica se o quotamento deve ser forçado, mesmo que _autoQuoteIdentifiers seja false
	 * @return string : o identificador quotado
	 */
	public function quoteColumnAs($column, $alias, $forced = false) {
		return $this->_doQuoteIdentifierAs($column, $alias, $forced);
	}

	/**
	 * Quota um identificador de tabela e seu apelido (alias)
	 * @param string $table : o identificador da tabela
	 * @param string $alias : um apelido para a tabela
	 * @param boolean $forced [OPCIONAL] : indica se o quotamento deve ser forçado, mesmo que _autoQuoteIdentifiers seja false
	 * @return string : o identificador quotado
	 */
	public function quoteTableAs($table, $alias, $forced = false) {
		return $this->_doQuoteIdentifierAs($table, $alias, $forced);
	}

	/**
	 * Efetivamente faz o quotamento de um identificador complexo
	 * @param string|array|Hydra_Db_Expression $identifier
	 * @param string $alias : o apelido para o identificador
	 * @param boolean $forced [OPCIONAL] : indica se o quotamento deve ser forçado, mesmo que _autoQuoteIdentifiers seja false
	 * @param string $as [OPCIONAL] : o token de apelidamento
	 */
	protected function _doQuoteIdentifierAs($identifier, $alias, $forced = false, $as = 'AS') {
		if($identifier instanceof Hydra_Db_Expression) {
			$quoted = $identifier->__toString();
		} else if($identifier instanceof Hydra_Db_Select) {
			$quoted = '(' . $identifier->assemble() . ')';
		} else {
			if(is_string($identifier)) {
				$identifier = explode('.', $identifier);
			}

			if(is_array($identifier)) {
				$segments = array();
				foreach($identifier as $piece) {
					if($piece instanceof Hydra_Db_Expression) {
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
	 * @param boolean $forced [OPCIONAL] : indica se o quotamento deve ser forçado, mesmo que _autoQuoteIdentifiers seja false
	 */
	protected function _doQuoteIdentifier($identifier, $forced = false) {
		if($this->_autoQuoteIdentifiers === true || $forced === true) {
			$q = $this->getQuoteIdentifierSymbol();
			return ($q . str_replace($q, "{$q}{$q}", $identifier) . $q);
		}
		return $value;
	}

	/**
	 * Retorna o símbolo de quoting de um identificador
	 * @return string
	 */
	public function getQuoteIdentifierSymbol() {
		return '"';
	}
}
<?php
/**
 * Gera��o de SQL SELECTS
 * @author <a href="mailto:rick.hjpbacelos@gmail.com">Henrique Barcelos</a>
 */
class Db_Select {
	const DISTINCT 		 = 'distinct';
	const COLUMNS 		 = 'columns';
	const FROM 			 = 'from';
	const UNION			 = 'union';
	const WHERE			 = 'where';
	const GROUP			 = 'group';
	const HAVING		 = 'having';
	const ORDER			 = 'order';
	const LIMIT_COUNT	 = 'limitCount';
	const LIMIT_OFFSET	 = 'limitOffset';
	const FOR_UPDATE	 = 'forUpdate';

	const INNER_JOIN   	 = 'inner join';
	const LEFT_JOIN      = 'left join';
	const RIGHT_JOIN     = 'right join';
	const FULL_JOIN      = 'full join';
	const CROSS_JOIN     = 'cross join';
	const NATURAL_JOIN   = 'natural join';

	const SQL_WILDCARD   = '*';
	const SQL_SELECT     = 'SELECT';
	const SQL_UNION      = 'UNION';
	const SQL_UNION_ALL  = 'UNION ALL';
	const SQL_FROM       = 'FROM';
	const SQL_WHERE      = 'WHERE';
	const SQL_DISTINCT   = 'DISTINCT';
	const SQL_GROUP_BY   = 'GROUP BY';
	const SQL_ORDER_BY   = 'ORDER BY';
	const SQL_HAVING     = 'HAVING';
	const SQL_FOR_UPDATE = 'FOR UPDATE';
	const SQL_AND        = 'AND';
	const SQL_AS         = 'AS';
	const SQL_OR         = 'OR';
	const SQL_ON         = 'ON';
	const SQL_ASC        = 'ASC';
	const SQL_DESC       = 'DESC';

	/**
	 * Par�metros associados � query
	 * @var array
	 */
	protected $_boundParams = array();

	/**
	 * @var Db_Adapter_Abstract
	 */
	protected $_adapter;

	/**
	 * O valor inicial das partes da query
	 * @var array
	 */
	protected static $_partsInit = array(
		self::DISTINCT     => false,
		self::COLUMNS      => array(),
		self::UNION        => array(),
		self::FROM         => array(),
		self::WHERE        => array(),
		self::GROUP        => array(),
		self::HAVING       => array(),
		self::ORDER        => array(),
		self::LIMIT_COUNT  => null,
		self::LIMIT_OFFSET => null,
		self::FOR_UPDATE   => false
	);

	/**
	 * Os tipos de join permitidos
	 * @var array
	 */
	protected static $_joinTypes = array(
		self::INNER_JOIN,
		self::LEFT_JOIN,
		self::RIGHT_JOIN,
		self::FULL_JOIN,
		self::CROSS_JOIN,
		self::NATURAL_JOIN,
	);

	/**
	 * Os tipos de union permitidos
	 * @var array
	 */
	protected static $_unionTypes = array(
		self::SQL_UNION,
		self::SQL_UNION_ALL
	);

	/**
	 * As partes da select statement
	 * @var array
	 */
	protected $_parts = array();

	/**
	 * Armazena as colunas da consulta, agrupadas por tabela
	 * @var array
	 */
	protected $_tableCols = array();

	/**
	 * Construtor
	 * @param Db_Adapter_Abstract $adapter
	 */
	public function __construct(Db_Adapter_Abstract $adapter) {
		$this->_adapter = $adapter;
		$this->_parts = self::$_partsInit;
	}

	/**
	 * Retorna os par�metros associados ao select statement
	 * @return array;
	 */
	public function getBoundParams() {
		return $this->_boundParams;
	}

	/**
	 * Retorna os par�metros associados ao select statement
	 * @param array $params
	 * @return void;
	 */
	public function setBoundParams($params) {
		$this->_boundParams = $params;
	}

	/**
	 * Faz a query selecionar apenas linhas distintas
	 * @param boolean $opt
	 * @return Db_Select : Fluent Interface
	 */
	public function distinct($opt = true) {
		$this->_parts[self::DISTINCT] = (bool) $opt;
		return $this;
	}

	/**
	 * Adiciona uma ou mais tabelas e as colunas desejadas a uma query
	 * @param array|string $tableName : string ou array da forma array('alias' => 'table')
	 * @param array|string|Db_Expression $tableCols
	 * @param string $schema
	 */
	public function from($tableName, $tableCols = array(), $schema = null) {
		return $this->_join(self::FROM, $tableName, null, $tableCols, $schema);
	}

	/**
	 * Seleciona as colunas a serem retornadas na query
	 * @param array|string|Db_Expression $columns
	 * @param string $tableName : o nome ou alias da tabela a qual os campos partencem
	 * @return Db_Select : Fluent Interface
	 */
	public function columns($columns = array(), $tableName = null) {
		if($tableName === null && !empty($this->_parts[self::FROM])) {
			/* Se n�o foi especificado o nome da tabela, pegamos o nome (ou alias)
			 * da primeira tabela selecionada com o m�todo from()
			*/
			$tableName = current(array_keys($this->_parts[self::FROM]));
		}

		if(!array_key_exists($tableName, $this->_parts[self::FROM])) {
			throw new Db_Select_Exception('Nenhuma tabela especificada para a cl�usula FROM');
		}

		$this->_tableCols($tableName, $columns);
		return $this;
	}

	/**
	 * Adiciona uma cl�usula UNION � query
	 * @param array $selects : array de objetos Db_Select ou de strings sql
	 * @param Db_Select::SQL_UNION_* $type : o tipo de union (UNION ou UNION ALL)
	 * @throws Db_Select_Exception
	 * @return Db_Select : Fluent Interface
	 */
	public function union(array $selects, $type = self::SQL_UNION) {
		if(!is_array($selects)) {
			throw new Db_Select_Exception(
				'union() aceita somente arrays de objetos Db_Select ou de senten�as sql (string)'
			);
		}

		if(!in_array($type, self::$_unionTypes)) {
			throw new Db_Select_Exception('Tipo de uni�o inv�lido: ' . $type);
		}

		foreach($selects as $each) {
			$this->_parts[self::UNION][] = array('target' => $each, 'unionType' => $type);
		}

		return $this;
	}

	/**
	 * Adiciona uma cl�usua UNION ALL � query
	 * @param array $selects
	 * @see Db_Select::union()
	 * @return Db_Select : Fluent Interface
	 */
	public function unionAll(array $selects) {
		return $this->union($selects, self::SQL_UNION_ALL);
	}


	/**
	 * Adiciona uma cl�usula JOIN � query
	 * @param string $tableName : o nome da tabela
	 * @param string $joinCond : a condi��o de jun��o (ON)
	 * @param array|string $columns : a(s) coluna(s) necess�ria(s) e/ou express�es da tabela
	 * @param string $schema : o nome do schema
	 * @return Db_Select : Fluent Interface
	 */
	public function join($tableName, $joinCond, $columns = array(), $schema = null) {
		return $this->innerJoin($tableName, $joinCond, $columns, $schema);
	}

	/**
	 * Adiciona uma cl�usula INNER JOIN � query
	 * @param string $tableName : o nome da tabela
	 * @param string $joinCond : a condi��o de jun��o (ON)
	 * @param array|string $columns : a(s) coluna(s) necess�ria(s) e/ou express�es da tabela
	 * @param string $schema : o nome do schema
	 * @return Db_Select : Fluent Interface
	 */
	public function innerJoin($tableName, $joinCond, $columns = array(), $schema = null) {
		return $this->_join(self::INNER_JOIN, $tableName, $joinCond, $columns, $schema);
	}


	/**
	 * Adiciona uma cl�usula LEFT [OUTER] JOIN � query
	 * @param string $tableName : o nome da tabela
	 * @param string $joinCond : a condi��o de jun��o (ON)
	 * @param array|string $columns : a(s) coluna(s) necess�ria(s) e/ou express�es da tabela
	 * @param string $schema : o nome do schema
	 * @return Db_Select : Fluent Interface
	 */
	public function leftJoin($tableName, $joinCond, $columns = self::SQL_WILDCARD, $schema = null) {
		return $this->_join(self::LEFT_JOIN, $tableName, $joinCond, $columns, $schema);
	}

	/**
	 * Adiciona uma cl�usula RIGHT [OUTER] JOIN � query
	 * @param string $tableName : o nome da tabela
	 * @param string $joinCond : a condi��o de jun��o (ON)
	 * @param array|string $columns : a(s) coluna(s) necess�ria(s) e/ou express�es da tabela
	 * @param string $schema : o nome do schema
	 * @return Db_Select : Fluent Interface
	 */
	public function rightJoin($tableName, $joinCond, $columns = self::SQL_WILDCARD, $schema = null) {
		return $this->_join(self::RIGHT_JOIN, $tableName, $joinCond, $columns, $schema);
	}

	/**
	 * Adiciona uma cl�usula FULL [OUTER] JOIN � query
	 * @param string $tableName : o nome da tabela
	 * @param string $joinCond : a condi��o de jun��o (ON)
	 * @param array|string $columns : a(s) coluna(s) necess�ria(s) e/ou express�es da tabela
	 * @param string $schema : o nome do schema
	 * @return Db_Select : Fluent Interface
	 */
	public function fullJoin($tableName, $joinCond, $columns = self::SQL_WILDCARD, $schema = null) {
		return $this->_join(self::FULL_JOIN, $tableName, $joinCond, $columns, $schema);
	}

	/**
	 * Adiciona uma cl�usula CROSS JOIN � query
	 * @param string $tableName : o nome da tabelaORDER
	 * @param array|string $columns : a(s) coluna(s) necess�ria(s) e/ou express�es da tabela
	 * @param string $schema : o nome do schema
	 * @return Db_Select : Fluent Interface
	 */
	public function crossJoin($tableName, $columns = self::SQL_WILDCARD, $schema = null) {
		return $this->_join(self::CROSS_JOIN, $tableName, null, $columns, $schema);
	}

	/**
	 * Adiciona uma cl�usula NATURAL INNER JOIN � query
	 * @param string $tableName : o nome da tabela
	 * @param array|string $columns : a(s) coluna(s) necess�ria(s) e/ou express�es da tabela
	 * @param string $schema : o nome do schema
	 * @return Db_Select : Fluent Interface
	 */
	public function naturalJoin($tableName, $columns = self::SQL_WILDCARD, $schema = null) {
		return $this->_join(self::CROSS_JOIN, $tableName, null, $columns, $schema);
	}

	/**
	 * Faz o trabalho sujo das opera��es JOIN e FROM
	 * @param string $type : o tipo de join
	 * @param string|array|Db_Select|Db_Expression $name : o nome da tabela
	 * @param string $cond : a condi��o de jun��o
	 * @param array|string $cols : as colunas da tabela para selecionar
	 * @param string $schema : o nome do schema da tabela
	 * @throws Db_Select_Exception
	 * @return Db_Select : Fluent Interface
	 */
	protected function _join($type, $name, $cond, $cols, $schema = null) {
		if(!in_array($type, self::$_joinTypes) && !$type = self::FROM) {
			throw new Db_Select_Exception(sprintf('"%s" n�o � um tipo de JOIN v�lido!', $type));
		}
		
		if(count($this->_parts[self::UNION])) {
			throw new Db_Select_Exception('Uso inadequado de JOIN e UNION na mesma query!');
		}

		if(empty($name)) {
			$alias = $tablename = '';
		} else if(is_array($name)) {
			$_alias = key($name);
			$_tableName = current($name);
			
			if(is_string($_alias) && preg_match('#^[A-Za-z]#', $_alias)){
				$tableName = $_tableName;
				$alias = $_alias;
			} else {
				$tableName = $_tableName;
				$alias = $this->_getUniqueAlias($tableName);
			}
		} else if($name instanceof Db_Expression || $name instanceof Db_Select) {
			$tableName = $name;
			$alias = $this->_getUniqueAlias('t');
		} else if(preg_match('#(\w+)(?:\s' . self::SQL_AS . ')\s(\w+)#i', $name, $matches)){
			$tableName = $matches[1];
			$alias = $matches[2];
		} else {
			$tableName = $name;
			$alias = $this->_getUniqueAlias($tableName);
		}

		if(!is_object($tableName) && strpos($tableName, '.') !== false) {
			list($schema, $tableName) = explode('.', $preg_matchtableName);
		}
		
		$lastFromAlias = null;
		if(!empty($alias)) {
			if(array_key_exists($alias, $this->_parts[self::FROM])) {
				throw new Db_Select_Exception(sprintf('Voc� n�o pode definir "%s" mais de uma vez', $alias));
			}

			$tmpFromParts = array();
			//Vamos colocar todos os FROM's no in�cio do array de partes...
			if($type == self::FROM) {
				//Para isso precisamos percorrer o array procurando pelo �ltimo FROM
				$tmpFromParts = $this->_parts[self::FROM];
				$this->_parts[self::FROM] = array();
				$goOn = true;
				while($tmpFromParts && $goOn){
					//Pegamos a chave do elemento atual do array
					$currentAlias = key($tmpFromParts);
					//Se chegamos ao �ltimo FROM...
					if($tmpFromParts[$currentAlias]['joinType'] != self::FROM){
						//Paramos de iterar sobre o array...
						$goOn = false;
						//Sen�o...
					} else {
						//O �ltmo alias de uma tabela em FROM � o atual...
						$lastFromAlias = $currentAlias;
						//Colocamos o elemento de volta no array...
						$this->_parts[self::FROM][$currentAlias] = array_shift($tmpFromParts);
					}
				}
			}

			$this->_parts[self::FROM][$alias] = array(
					'joinType'		=> $type,
					'schema'		=> $schema,
					'tableName'		=> $tableName,
					'tableAlias'	=> $alias,
					'joinCondition'	=> $cond
			);

			//Agora colocamos os elementos de volta no array $_parts
			while($tmpFromParts) {
				$currentAlias = key($tmpFromParts);
				$this->_parts[self::FROM][$currentAlias] = array_shift($tmpFromParts);
			}

			$this->_tableCols($alias, $cols, $lastFromAlias);
			return $this;
		}
	}


	/**
	 * Insere uma cl�usula WHERE na query. Se j� houver uma cl�usula pr�via, elas ser�o
	 * concatenadas pelo operador AND
	 *
	 * @param string $cond : a condicional
	 * @param mixed $boundValue : o valor ou array de valores para substituir os placeholder em $cond
	 * @param int $type [OPCIONAL] : o tipo do(s) valor(es) em $boundValue
	 * @return Db_Select : Fluent Interface
	 */
	public function where($cond, $boundValue = null, $type = null) {
		$this->_parts[self::WHERE][] = $this->_where($cond, $boundValue, $type, true);
		return $this;
	}

	/**
	 * Cria uma cl�usula WHERE
	 * @param string $cond
	 * @param mixed $boundValue [OPCIONAL]
	 * @param Db::*_TYPE $type
	 * @param bool $conjunction : se true, temos uma conjun��o, ou seja, cl�usulas concatenadas por AND
	 * 							  se false, temos uma disjun��o, cl�usulas concatenadas por OR
	 * @throws Db_Select_Exception
	 * @return string
	 */
	protected function _where($cond, $boundValue = null, $type = null, $conjunction = true) {
		if(!empty($this->_parts[self::UNION])) {
			throw new Db_Select_Exception(sprintf('Uso inv�lido da cl�usula WHERE com %s', self::SQL_UNION));
		}

		if($boundValue !== null) {
			$cond = $this->_adapter->quoteInto($cond, $boundValue);
		}

		$prev = '';
		if($this->_parts[self::WHERE]) {
			if($conjunction === true) {
				$prev = self::SQL_AND . ' ';
			} else {
				$prev = self::SQL_OR . ' ';
			}
		}

		return $prev . "({$cond})";
	}

	/**
	 * Semelhante ao m�todo where(), entretanto, se j� houver uma cl�usula pr�via,
	 * a concatena��o ser� feita pelo operador OR
	 *
	 * @param string $cond : a condicional
	 * @param mixed $boundValue : o valor ou array de valores para substituir os placeholder em $cond
	 * @param int $type [OPCIONAL] : o tipo do(s) valor(es) em $boundValue
	 * @return Db_Select : Fluent Interface
	 */
	public function orWhere($cond, $boundValue = null, $type = null) {
		$this->_parts[self::WHERE][] = $this->_where($cond, $boundValue, $type, false);
		return $this;
	}

	/**
	 * Adiciona uma cl�usula GROUP � query
	 * � poss�vel passar quantos par�metros quisermos, sendo que apenas um � obrigat�rio:
	 * Db_Select::group($col1, [$col2, [...]])
	 *  
	 * @param array|string $expr : a coluna ou express�o pela qual agrupar ou um array delas
	 * @param array|string $_ [OPCIONAL] : id�ntico a $expr
	 * @return Db_Select : Fluent Interface
	 * @throws Db_Select_Exception
	 */
	public function group($expr, $_ = null) {
		$by = array();
		$argv = func_get_args();
		foreach($argv as $arg) {
			if(is_array($arg)) {
				$by = array_merge($by, $arg);
			} else {
				$by[] = $arg;
			}
		}

		foreach($by as $each) {
			if(is_string($each)) {
				if (preg_match('/\(.*\)/', $each)) {
					$each = new Db_Expression($each);
				}
			}
				
			if($each instanceof Db_Expression) {
				$this->_parts[self::GROUP][] = $each->__toString();
			} else if(is_string($each)) {
				$this->_parts[self::GROUP][] = $each;
			} else {
				throw new Db_Select_Exception(sprintf('Express�o de agrupamento "%s" inv�lida!', $each));
			}
		}
		return $this;
	}

	/**
	 * Adiciona uma cl�usula HAVING � query. Se j� houver alguma,
	 * concatena atrav�s do operador AND
	 *
	 * Os par�metros seguem a mesma l�gica do m�todo where()
	 * @see Db_Select::where()
	 *
	 * @param string $cond
	 * @param mixed $boundParam
	 * @param int $type
	 * @return Db_Select : Fluent Interface
	 */
	public function having($cond, $boundParam = null, $type = null) {
		return $this->_having($cond, self::SQL_AND, $boundParam, $type);
	}

	/**
	 * Semelhante a having(), com a diferen�a que a concatena��o
	 * � feita atrav�s do operador OR, caso j� haja alguma cl�usula
	 *
	 * @see Db_Select::having()
	 *
	 * @param string $cond
	 * @param mixed $boundParam
	 * @param int $type
	 * @return Db_Select : Fluent Interface
	 */
	public function orHaving($cond, $boundParam = null, $type = null) {
		return $this->_having($cond, self::SQL_OR, $boundParam, $type);
	}

	/**
	 * Faz a adi��o de uma cl�usula having, de acordo
	 * com o operador de contacena��o informado
	 *
	 * @param string $cond
	 * @param mixed $boundParam
	 * @param int $type
	 * @return Db_Select : Fluent Interface
	 */
	protected function _having($cond, $concat, $boundParam = null, $type = null) {
		if($boundParam !== null) {
			$cond = $this->_adapter->quoteInto($cond, $boundParam, $type);
		}

		if($this->_parts[self::SQL_HAVING]) {
			$this->_parts[self::SQL_HAVING][] = $concat . '(' . $cond . ')';
		} else {
			$this->_parts[self::SQL_HAVING][] = $cond;
		}

		return $this;
	}

	/**
	 * Adiciona uma cl�usula ORDER BY � query
	 * 
	 * � poss�vel passar quantos par�metros quisermos, sendo que apenas um � obrigat�rio:
	 * Db_Select::order($col1, [$col2, [...]])
	 * 
	 * @param array|string|Db_Expression $expr : coluna ou express�o para ordena��o, ou um array delass
	 * @param array|string|Db_Expression $_ [OPCIONAL] : id�ntico a $expr
	 * @return Db_Select : Fluent Interface
	 */
	public function order($expr, $_ = null) {
		$by = array();
		$argv = func_get_args();
		foreach($argv as $arg) {
			if(is_array($arg)) {
				$by = array_merge($by, $arg);
			} else {
				$by[] = $arg;
			}
		}
		

		foreach($by as $order) {
			if($order instanceof Db_Expression) {
				$expr = $order->__toString();
				if(!empty($expr)) {
					$this->_parts[self::ORDER] = $order;
				}
			} else if(!empty($order)) {
				$sort = self::SQL_ASC;

				if(preg_match('#(.*)\s(' . self::SQL_ASC . '|' . self::SQL_DESC . ')$#i', $order, $matches)) {
					$order = trim($matches[1]);
					$sort = $matches[2];
				}

				if(preg_match('#\(.*\)#', $order)) {
					$order = new Db_Expression($order);
				}

				$this->_parts[self::ORDER][] = array('order' => $order, 'sort' => $sort);
			}
		}
		return $this;
	}

	/**
	 * Adiciona uma cl�usula LIMIT � query
	 * @param int $count : quantidade m�xima de linhas retornadas
	 * @param int $offset : linha inicial a ser retornada (a primeira � 0)
	 * @return Db_Select : Fluent Interface
	 */
	public function limit($count, $offset = 0) {
		$this->_parts[self::LIMIT_COUNT]  = (int) $count;
		$this->_parts[self::LIMIT_OFFSET] = (int) $offset;
		return $this;
	}

	/**
	 * Seta uma cl�usula LIMIT para pagina��o
	 * @param int $page : a p�gina a ser buscada
	 * @param int $rowCount : o n�mero de linhas na p�gina
	 * @return Db_Select : Fluent Interface
	 */
	public function limitPage($page, $rowCount) {
		$page = (int) $page > 0 ? (int) $page : 1;
		$rowCount = (int) $rowCount > 0 ? (int) $rowCount : 1;

		$this->_parts[self::LIMIT_COUNT]  = $rowCount;
		$this->_parts[self::LIMIT_OFFSET] = $rowCount * ($page - 1);
		return $this;
	}

	/**
	 * Faz da query uma senten�a SELECT FOR UPDATE
	 * @param boolean $opt [OPCIONAL] : se true, faz com que a query SELECT FOR UPDATE,
	 * 									se false, cancela este efeito
	 * @return Db_Select : Fluent Interface
	 */
	public function forUpdate($opt = true) {
		$this->_parts[self::FOR_UPDATE] = (bool) $opt;
		return $this;
	}

	/**
	 * Retorna uma parte do statement
	 * @param string $part : o nome da parte desejada
	 * @throws Db_Select_Exception
	 * @return mixed
	 */
	public function getPart($part) {
		if(!array_key_exists($part, $this->_parts)) {
			throw new Db_Select_Exception(sprintf('Parte "%s" inexistente em Db_Select', $part));
		}
		return $this->_parts[$part];
	}

	/**
	 * Executa a query com o statement deste objeto
	 * @param array $boundParams : par�metros associados (prepared statements)
	 * @param Db::FETCH_* $fetchMode : modo de fetch
	 * @return Db_Statement_Abstract
	 */
	public function query(array $boundParams = array(), $fetchMode = null) {
		if(!empty($boundParams)) {
			$this->setBoundParams($boundParams);
		}

		$stmt = $this->_adapter->query($this);
		if($fetchMode === null) {
			$fetchMode = $this->_adapter->getFetchMode();
		}
		$stmt->setFetchMode($fetchMode);
		return $stmt;
	}

	/**
	 * Monta a senten�a SELECT
	 * @return string
	 */
	public function assemble() {
		$sql = self::SQL_SELECT;
		foreach(array_keys($this->_parts) as $part) {
			$method = '_render' . ucfirst($part);
			if(method_exists($this, $method)){
				$sql = call_user_func(array($this, $method),$sql);
			}
		}
		return $sql;
	}

	/**
	 * Reseta o statement ou parte dele
	 * @param string $part : o nome da parte para resetar, se n�o informado, reseta todas as partes
	 * @return Db_Select : Fluent Interface
	 */
	public function reset($part = null) {
		if($part === null) {
			$this->_parts = self::$_partsInit;
			return $this;
		} else {
			try {
				$this->getPart($part);
				$this->_parts[$part] = self::$_partsInit[$part];
				return $this;
			} catch(Db_Select_Exception $e) {
				return $this;
			}
		}
	}

	/**
	 * Retorna o adapter relacionado a este objeto
	 * @return Db_Adapter_Abstract
	 */
	public function getAdapter() {
		return $this->_adapter;
	}
	
	/**
	 * Gera um alias �nico para um identificador
	 * @param mixed $tableName
	 * @return string
	 */
	private function _getUniqueAlias($tableName) {
		if(is_array($tableName)) {
			$alias = end($tableName);
		} else {
			$pieces = explode('.', $tableName);
			$alias = array_pop($pieces);
		}
		for($i = 2; array_key_exists($alias, $this->_parts[self::FROM]); $i++){
			$alias = $tableName . '_' . $i;
		}
		return $alias;
	}

	/**
	 * Mapeia tabelas e colunas em um array associativo
	 * @param string $tableAlias
	 * @param array|string $cols
	 * @param bool|string $afterTableAlias
	 * @return void
	 */
	protected function _tableCols($tableAlias, $cols, $afterTableAlias = null) {
		if($cols === null) {
			return;
		}
		if(!is_array($cols)) {
			$cols = array($cols);
		}

		if($tableAlias == null) {
			$tableAlias = '';
		}
		
		$columns = array();

		foreach($cols as $alias => $col) {
			$currentTableAlias = $tableAlias;
			if(is_string($col)) {
				if(preg_match('#(\w+)\s+' . self::SQL_AS . '(\w+)\s+#i', $col, $matches)) {
					$col = $matches[1];
					$alias = $maches[2];
				}

				if(preg_match('#\(.+\)#', $col)) {
					$col = new Db_Expression($col);
				} else if(preg_match('#(\w+)\.(\w+)#', $col, $matches)) {
					$currentTableAlias = $matches[1];
					$col = $matches[2];
				}
			}
			//(**)
			$columns[] = array(
				'tableAlias' => $currentTableAlias, 
				'colName' => $col, 
				'colAlias' => is_string($alias) ? $alias : null
			);
		}
		
		if(!empty($columns)) {
			//Indica de devemos adicionar os campos ao final da listagem ou depois
			//de um certo identificador
			if($afterTableAlias === true || is_string($afterTableAlias)) {
				$tmpColumns = $this->_parts[self::COLUMNS];
				$this->_parts[self::COLUMNS] = array();
			} else {
				$tmpColumns = array();
			}
				
			//Se vamos inserir depois de um identificador...
			if(is_string($afterTableAlias)) {
				$goOn = true;
				while($tmpColumns && $goOn) {
					//Vai retirando elementos do in�cio do array...
					$this->_parts[self::COLUMNS][] = $currColumn = array_shift($tmpColumns);
					//At� que o nome/alias da tabela seja igual ao do elemento atual.
					//Veja (**)
					if($currColumn['tableAlias'] == $afterTableAlias) {
						$goOn = false;
					}
				}
			}
				
			//Adiciona o restante dos elementos
			foreach($columns as $col) {
				array_push($this->_parts[self::COLUMNS], $col);
			}
				
			while($tmpColumns) {
				array_push($this->_parts[self::COLUMNS], array_shift($tmpColumns));
			}
		}
	}

	/**
	 * @return array
	 */
	protected function _getDummyTable() {
		return array();
	}

	/**
	 * Retorna o nome do schema quotado
	 * @param string $schema
	 * @return string
	 */
	protected function _getQuotedSchema($schema = null) {
		if($schema === null) {
			return null;
		}
		return $this->_adapter->quoteIdentifier($schema) . '.';
	}

	/**
	 * Retorna o nome da tabela quotado
	 * @param string $tableName
	 * @param string $alias
	 * @return string
	 */
	protected function _getQuotedTable($tableName, $alias = null) {
		$alias = $alias == $tableName ? null : $alias;
		return $this->_adapter->quoteTableAs($tableName, $alias);
	}

	/**
	 * Renderiza a cl�usula DISTINCS
	 * @param string $sql
	 * @return string
	 */
	protected function _renderDistinct($sql) {
		if($this->_parts[self::DISTINCT] === true) {
			$sql .= ' ' . self::SQL_DISTINCT;
		}
		return $sql;
	}

	/**
	 * Renderiza as colunas da query
	 * @param string $sql
	 * @return string
	 */
	protected function _renderColumns($sql) {
		if(empty($this->_parts[self::COLUMNS])) {
			$this->_parts[self::COLUMNS][] = array('tableAlias' => null, 
													'colName' 	=> self::SQL_WILDCARD,
													'colAlias' 	=> null);
		}
		
		$arrayColumns = array();
		foreach($this->_parts[self::COLUMNS] as $colEntry) {
			$tableAlias = $colEntry['tableAlias'];
			$column = $colEntry['colName'];
			$colAlias = $colEntry['colAlias'];
			
			if($column instanceof Db_Expression) {
				$arrayColumns[] = $this->_adapter->quoteColumnAs($column, $colAlias);
			} else {
				if($column == self::SQL_WILDCARD) {
					$column = new Db_Expression(self::SQL_WILDCARD);
					$colAlias = null;
				}
				if(empty($tableAlias)) {
					$arrayColumns[] = $this->_adapter->quoteColumnAs($column, $colAlias);
				} else {
					$arrayColumns[] = $this->_adapter->quoteColumnAs(array($tableAlias, $column), $colAlias);
				}
			}
		}

		return $sql .= ' ' . join(', ', $arrayColumns);
	}

	/**
	 * Renderiza FROM e JOIN's
	 * @param string $sql
	 * @return string
	 */
	protected function _renderFrom($sql) {
		/*
		 * Se n�o hover tabelas em FROM, usamos a solu��o dependente do SGBD
		* para query sem tabelas
		*/
		if(empty($this->_parts[self::FROM])) {
			$this->_parts[self::FROM] = $this->_getDummyTable();
		}

		$from = array();
		foreach($this->_parts[self::FROM] as $alias => $table) {
			$aux = '';
			$joinType = $table['joinType'] == self::FROM ? self::INNER_JOIN : $table['joinType'];
				
			if(!empty($from)) {
				$aux .= strtoupper($joinType) . ' ';
			}
				
			$aux .= $this->_getQuotedSchema($table['schema']);
			$aux .= $this->_getQuotedTable($table['tableName'], $alias);
				
			if(!empty($from) && !empty($table['joinCondition'])) {
				$aux .= ' ' . self::SQL_ON . ' ' . $table['joinCondition'];
			}
				
			$from[] = $aux;
		}

		if(!empty($from)) {
			$sql .= "\n" . self::SQL_FROM . ' ' . join("\n", $from);
		}
		
		return $sql;
	}

	/**
	 * Renderiza as cl�usulas UNION e UNION ALL
	 * @param string $sql
	 * @return string
	 */
	protected function _renderUnion($sql) {
		if(($parts = count($this->_parts[self::UNION])) > 0) {
			foreach($this->_parts[self::UNION] as $i => $union) {
				$target = $union['target'];
				$type = $union['unionType'];
				if($target instanceof Db_Select) {
					$target = $target->assemble();
				}
				$sql .= $target;
				if($i < ($parts - 1)) {
					$sql .= "\n" . $type . ' ';
				}
			}
		}

		return $sql;
	}

	protected function _renderWhere($sql) {
		if(!empty($this->_parts[self::WHERE])) {
			$sql .= "\n" . self::SQL_WHERE . ' ' . join("\n\t", $this->_parts[self::WHERE]);
		}

		return $sql;
	}

	/**
	 * Renderiza a cl�usula GROUP BY
	 * @param string $sql
	 * @return string
	 */
	protected function _renderGroup($sql) {
		if(!empty($this->_parts[self::GROUP])) {
			$group = array();
			foreach($this->_parts[self::GROUP] as $col) {
				$group[] = $this->_adapter->quoteIdentifier($col);
			}
				
			$sql .= "\n" . self::SQL_GROUP_BY . ' ' . join(",\n\t", $group);
		}

		return $sql;
	}

	/**
	 * Renderiza a cl�usula HAVING
	 * @param string $sql
	 * @return string
	 */
	protected function _renderHaving($sql) {
		if(!empty($this->_parts[self::HAVING])) {
			$sql .= "\n" . self::SQL_HAVING . ' ' . join("\n\t", $this->_parts[self::HAVING]);
		}
		
		return $sql;
	}
	
	/**
	 * Renderiza a cl�usula ORDER BY
	 * @param string $sql
	 * @return string
	 */
	protected function _renderOrder($sql) {
		if(!empty($this->_parts[self::ORDER])) {
			$order = array();
			foreach($this->_parts[self::ORDER] as $col) {
				//Se � um array, temos: array('order' => $exprOrder, 'sort' => $exprSort) 
				if(is_array($col)) {
					if(is_numeric($col['order'])) {
						$order[] = (int) trim($col['order']) . ' ' . $col['sort'];
					} else {
						$order[] = $this->_adapter->quoteIdentifier($col['order']) . ' ' . $col['sort'];
					}
				} else if(is_numeric($col['order'])) {
					$order[] = (int) trim($col['order']);
				} else {
					$order[] = $this->_adapter->quoteIdentifier($col);
				}
			}
			$sql .= "\n" . self::SQL_ORDER_BY . ' ' . join(",\n\t", $order);
		}

		return $sql;
	}
	
	/**
	 * Renderiza a cl�usula LIMIT
	 * @param string $sql
	 */
	protected function _renderLimitCount($sql) {
		$count = 0;
		$offset = 0;
		
		//Se h� uma cl�usula OFFSET...
		if(!empty($this->_parts[self::LIMIT_OFFSET])) {
			$offset = (int) $this->_parts[self::LIMIT_OFFSET];
			//Inicialmente setamos COUNT como o maior valor inteiro poss�vel
			$count = PHP_INT_MAX;
		}
		
		if(!empty($this->_parts[self::LIMIT_COUNT])) {
			$count = (int) $this->_parts[self::LIMIT_COUNT];
		}
		
		if($count > 0) {
			$sql = $this->_adapter->limit($sql, $count, $offset);
		}
		
		return $sql;
	}
	
	/**
	 * Renderiza a cl�usula FOR UPDATE
	 * @param string $sql
	 * @return string
	 */
	protected function _renderForUpdate($sql) {
		if($this->_parts[self::FOR_UPDATE] === true) {
			$sql .= "\n" . self::SQL_FOR_UPDATE;
		}
		return $sql;
	}
	
	/**
	 * M�todo m�gico para transforma��o de um objeto Db_Select em string
	 * @return string
	 */
	public function __toString() {
		try {
			$sql = $this->assemble();
		} catch(Exception $e) {
			trigger_error($e->getTraceAsString(), E_WARNING);
			$sql = '';
		}
		return (string) $sql;
	}
}
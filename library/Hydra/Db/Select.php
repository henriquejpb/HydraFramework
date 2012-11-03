<?php
/**
 * Geração de SQL SELECTS
 * @author <a href="mailto:rick.hjpbacelos@gmail.com">Henrique Barcelos</a>
 */
class Hydra_Db_Select {
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
	 * Parâmetros associados à query
	 * @var array
	 */
	protected $_boundParams = array();

	/**
	 * @var Hydra_Db_Adapter_Abstract
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
	 * @param Hydra_Db_Adapter_Abstract $adapter
	 */
	public function __construct(Hydra_Db_Adapter_Abstract $adapter) {
		$this->_adapter = $adapter;
		$this->_parts = self::$_partsInit;
	}

	/**
	 * Retorna os parâmetros associados ao select statement
	 * @return array;
	 */
	public function getBoundParams() {
		return $this->_boundParams;
	}

	/**
	 * Retorna os parâmetros associados ao select statement
	 * @param array $params
	 * @return void;
	 */
	public function setBoundParams($params) {
		$this->_boundParams = $params;
	}

	/**
	 * Faz a query selecionar apenas linhas distintas
	 * @param boolean $opt
	 * @return Hydra_Db_Select : Fluent Interface
	 */
	public function distinct($opt = true) {
		$this->_parts[self::DISTINCT] = (bool) $opt;
		return $this;
	}

	/**
	 * Adiciona uma ou mais tabelas e as colunas desejadas a uma query
	 * @param array|string $tableName : string ou array da forma array('alias' => 'table')
	 * @param array|string|Hydra_Db_Expression $tableCols
	 * @param string $schema
	 */
	public function from($tableName, $tableCols = array(), $schema = null) {
		return $this->_join(self::FROM, $tableName, null, $tableCols, $schema);
	}

	/**
	 * Seleciona as colunas a serem retornadas na query
	 * @param array|string|Hydra_Db_Expression $columns
	 * @param string $tableName : o nome ou alias da tabela a qual os campos partencem
	 * @return Hydra_Db_Select : Fluent Interface
	 */
	public function columns($columns = array(), $tableName = null) {
		if($tableName === null && !empty($this->_parts[self::FROM])) {
			/* Se não foi especificado o nome da tabela, pegamos o nome (ou alias)
			 * da primeira tabela selecionada com o método from()
			*/
			$tableName = current(array_keys($this->_parts[self::FROM]));
		}

		if(!array_key_exists($tableName, $this->_parts[self::FROM])) {
			throw new Hydra_Db_Select_Exception('Nenhuma tabela especificada para a cláusula FROM');
		}

		$this->_tableCols($tableName, $columns);
		return $this;
	}

	/**
	 * Adiciona uma cláusula UNION à query
	 * @param array $selects : array de objetos Hydra_Db_Select ou de strings sql
	 * @param Hydra_Db_Select::SQL_UNION_* $type : o tipo de union (UNION ou UNION ALL)
	 * @throws Hydra_Db_Select_Exception
	 * @return Hydra_Db_Select : Fluent Interface
	 */
	public function union(array $selects, $type = self::SQL_UNION) {
		if(!is_array($selects)) {
			throw new Hydra_Db_Select_Exception(
				'union() aceita somente arrays de objetos Hydra_Db_Select ou de sentenças sql (string)'
			);
		}

		if(!in_array($type, self::$_unionTypes)) {
			throw new Hydra_Db_Select_Exception('Tipo de união inválido: ' . $type);
		}

		foreach($selects as $each) {
			$this->_parts[self::UNION][] = array('target' => $each, 'unionType' => $type);
		}

		return $this;
	}

	/**
	 * Adiciona uma cláusua UNION ALL à query
	 * @param array $selects
	 * @see Hydra_Db_Select::union()
	 * @return Hydra_Db_Select : Fluent Interface
	 */
	public function unionAll(array $selects) {
		return $this->union($selects, self::SQL_UNION_ALL);
	}


	/**
	 * Adiciona uma cláusula JOIN à query
	 * @param string $tableName : o nome da tabela
	 * @param string $joinCond : a condição de junção (ON)
	 * @param array|string $columns : a(s) coluna(s) necessária(s) e/ou expressões da tabela
	 * @param string $schema : o nome do schema
	 * @return Hydra_Db_Select : Fluent Interface
	 */
	public function join($tableName, $joinCond, $columns = array(), $schema = null) {
		return $this->innerJoin($tableName, $joinCond, $columns, $schema);
	}

	/**
	 * Adiciona uma cláusula INNER JOIN à query
	 * @param string $tableName : o nome da tabela
	 * @param string $joinCond : a condição de junção (ON)
	 * @param array|string $columns : a(s) coluna(s) necessária(s) e/ou expressões da tabela
	 * @param string $schema : o nome do schema
	 * @return Hydra_Db_Select : Fluent Interface
	 */
	public function innerJoin($tableName, $joinCond, $columns = array(), $schema = null) {
		return $this->_join(self::INNER_JOIN, $tableName, $joinCond, $columns, $schema);
	}


	/**
	 * Adiciona uma cláusula LEFT [OUTER] JOIN à query
	 * @param string $tableName : o nome da tabela
	 * @param string $joinCond : a condição de junção (ON)
	 * @param array|string $columns : a(s) coluna(s) necessária(s) e/ou expressões da tabela
	 * @param string $schema : o nome do schema
	 * @return Hydra_Db_Select : Fluent Interface
	 */
	public function leftJoin($tableName, $joinCond, $columns = self::SQL_WILDCARD, $schema = null) {
		return $this->_join(self::LEFT_JOIN, $tableName, $joinCond, $columns, $schema);
	}

	/**
	 * Adiciona uma cláusula RIGHT [OUTER] JOIN à query
	 * @param string $tableName : o nome da tabela
	 * @param string $joinCond : a condição de junção (ON)
	 * @param array|string $columns : a(s) coluna(s) necessária(s) e/ou expressões da tabela
	 * @param string $schema : o nome do schema
	 * @return Hydra_Db_Select : Fluent Interface
	 */
	public function rightJoin($tableName, $joinCond, $columns = self::SQL_WILDCARD, $schema = null) {
		return $this->_join(self::RIGHT_JOIN, $tableName, $joinCond, $columns, $schema);
	}

	/**
	 * Adiciona uma cláusula FULL [OUTER] JOIN à query
	 * @param string $tableName : o nome da tabela
	 * @param string $joinCond : a condição de junção (ON)
	 * @param array|string $columns : a(s) coluna(s) necessária(s) e/ou expressões da tabela
	 * @param string $schema : o nome do schema
	 * @return Hydra_Db_Select : Fluent Interface
	 */
	public function fullJoin($tableName, $joinCond, $columns = self::SQL_WILDCARD, $schema = null) {
		return $this->_join(self::FULL_JOIN, $tableName, $joinCond, $columns, $schema);
	}

	/**
	 * Adiciona uma cláusula CROSS JOIN à query
	 * @param string $tableName : o nome da tabelaORDER
	 * @param array|string $columns : a(s) coluna(s) necessária(s) e/ou expressões da tabela
	 * @param string $schema : o nome do schema
	 * @return Hydra_Db_Select : Fluent Interface
	 */
	public function crossJoin($tableName, $columns = self::SQL_WILDCARD, $schema = null) {
		return $this->_join(self::CROSS_JOIN, $tableName, null, $columns, $schema);
	}

	/**
	 * Adiciona uma cláusula NATURAL INNER JOIN à query
	 * @param string $tableName : o nome da tabela
	 * @param array|string $columns : a(s) coluna(s) necessária(s) e/ou expressões da tabela
	 * @param string $schema : o nome do schema
	 * @return Hydra_Db_Select : Fluent Interface
	 */
	public function naturalJoin($tableName, $columns = self::SQL_WILDCARD, $schema = null) {
		return $this->_join(self::CROSS_JOIN, $tableName, null, $columns, $schema);
	}

	/**
	 * Faz o trabalho sujo das operações JOIN e FROM
	 * @param string $type : o tipo de join
	 * @param string|array|Hydra_Db_Select|Hydra_Db_Expression $name : o nome da tabela
	 * @param string $cond : a condição de junção
	 * @param array|string $cols : as colunas da tabela para selecionar
	 * @param string $schema : o nome do schema da tabela
	 * @throws Hydra_Db_Select_Exception
	 * @return Hydra_Db_Select : Fluent Interface
	 */
	protected function _join($type, $name, $cond, $cols, $schema = null) {
		if(!in_array($type, self::$_joinTypes) && !$type = self::FROM) {
			throw new Hydra_Db_Select_Exception(sprintf('"%s" não é um tipo de JOIN válido!', $type));
		}
		
		if(count($this->_parts[self::UNION])) {
			throw new Hydra_Db_Select_Exception('Uso inadequado de JOIN e UNION na mesma query!');
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
		} else if($name instanceof Hydra_Db_Expression || $name instanceof Hydra_Db_Select) {
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
				throw new Hydra_Db_Select_Exception(sprintf('Você não pode definir "%s" mais de uma vez', $alias));
			}

			$tmpFromParts = array();
			//Vamos colocar todos os FROM's no início do array de partes...
			if($type == self::FROM) {
				//Para isso precisamos percorrer o array procurando pelo último FROM
				$tmpFromParts = $this->_parts[self::FROM];
				$this->_parts[self::FROM] = array();
				$goOn = true;
				while($tmpFromParts && $goOn){
					//Pegamos a chave do elemento atual do array
					$currentAlias = key($tmpFromParts);
					//Se chegamos ao último FROM...
					if($tmpFromParts[$currentAlias]['joinType'] != self::FROM){
						//Paramos de iterar sobre o array...
						$goOn = false;
						//Senão...
					} else {
						//O últmo alias de uma tabela em FROM é o atual...
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
	 * Insere uma cláusula WHERE na query. Se já houver uma cláusula prévia, elas serão
	 * concatenadas pelo operador AND
	 *
	 * @param string $cond : a condicional
	 * @param mixed $boundValue : o valor ou array de valores para substituir os placeholder em $cond
	 * @param int $type [OPCIONAL] : o tipo do(s) valor(es) em $boundValue
	 * @return Hydra_Db_Select : Fluent Interface
	 */
	public function where($cond, $boundValue = null, $type = null) {
		$this->_parts[self::WHERE][] = $this->_where($cond, $boundValue, $type, true);
		return $this;
	}

	/**
	 * Cria uma cláusula WHERE
	 * @param string $cond
	 * @param mixed $boundValue [OPCIONAL]
	 * @param Hydra_Db::*_TYPE $type
	 * @param bool $conjunction : se true, temos uma conjunção, ou seja, cláusulas concatenadas por AND
	 * 							  se false, temos uma disjunção, cláusulas concatenadas por OR
	 * @throws Hydra_Db_Select_Exception
	 * @return string
	 */
	protected function _where($cond, $boundValue = null, $type = null, $conjunction = true) {
		if(!empty($this->_parts[self::UNION])) {
			throw new Hydra_Db_Select_Exception(sprintf('Uso inválido da cláusula WHERE com %s', self::SQL_UNION));
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
	 * Semelhante ao método where(), entretanto, se já houver uma cláusula prévia,
	 * a concatenação será feita pelo operador OR
	 *
	 * @param string $cond : a condicional
	 * @param mixed $boundValue : o valor ou array de valores para substituir os placeholder em $cond
	 * @param int $type [OPCIONAL] : o tipo do(s) valor(es) em $boundValue
	 * @return Hydra_Db_Select : Fluent Interface
	 */
	public function orWhere($cond, $boundValue = null, $type = null) {
		$this->_parts[self::WHERE][] = $this->_where($cond, $boundValue, $type, false);
		return $this;
	}

	/**
	 * Adiciona uma cláusula GROUP à query
	 * É possível passar quantos parâmetros quisermos, sendo que apenas um é obrigatório:
	 * Hydra_Db_Select::group($col1, [$col2, [...]])
	 *  
	 * @param array|string $expr : a coluna ou expressão pela qual agrupar ou um array delas
	 * @param array|string $_ [OPCIONAL] : idêntico a $expr
	 * @return Hydra_Db_Select : Fluent Interface
	 * @throws Hydra_Db_Select_Exception
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
					$each = new Hydra_Db_Expression($each);
				}
			}
				
			if($each instanceof Hydra_Db_Expression) {
				$this->_parts[self::GROUP][] = $each->__toString();
			} else if(is_string($each)) {
				$this->_parts[self::GROUP][] = $each;
			} else {
				throw new Hydra_Db_Select_Exception(sprintf('Expressão de agrupamento "%s" inválida!', $each));
			}
		}
		return $this;
	}

	/**
	 * Adiciona uma cláusula HAVING à query. Se já houver alguma,
	 * concatena através do operador AND
	 *
	 * Os parâmetros seguem a mesma lógica do método where()
	 * @see Hydra_Db_Select::where()
	 *
	 * @param string $cond
	 * @param mixed $boundParam
	 * @param int $type
	 * @return Hydra_Db_Select : Fluent Interface
	 */
	public function having($cond, $boundParam = null, $type = null) {
		return $this->_having($cond, self::SQL_AND, $boundParam, $type);
	}

	/**
	 * Semelhante a having(), com a diferença que a concatenação
	 * é feita através do operador OR, caso já haja alguma cláusula
	 *
	 * @see Hydra_Db_Select::having()
	 *
	 * @param string $cond
	 * @param mixed $boundParam
	 * @param int $type
	 * @return Hydra_Db_Select : Fluent Interface
	 */
	public function orHaving($cond, $boundParam = null, $type = null) {
		return $this->_having($cond, self::SQL_OR, $boundParam, $type);
	}

	/**
	 * Faz a adição de uma cláusula having, de acordo
	 * com o operador de contacenação informado
	 *
	 * @param string $cond
	 * @param mixed $boundParam
	 * @param int $type
	 * @return Hydra_Db_Select : Fluent Interface
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
	 * Adiciona uma cláusula ORDER BY à query
	 * 
	 * É possível passar quantos parâmetros quisermos, sendo que apenas um é obrigatório:
	 * Hydra_Db_Select::order($col1, [$col2, [...]])
	 * 
	 * @param array|string|Hydra_Db_Expression $expr : coluna ou expressão para ordenação, ou um array delass
	 * @param array|string|Hydra_Db_Expression $_ [OPCIONAL] : idêntico a $expr
	 * @return Hydra_Db_Select : Fluent Interface
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
			if($order instanceof Hydra_Db_Expression) {
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
					$order = new Hydra_Db_Expression($order);
				}

				$this->_parts[self::ORDER][] = array('order' => $order, 'sort' => $sort);
			}
		}
		return $this;
	}

	/**
	 * Adiciona uma cláusula LIMIT à query
	 * @param int $count : quantidade máxima de linhas retornadas
	 * @param int $offset : linha inicial a ser retornada (a primeira é 0)
	 * @return Hydra_Db_Select : Fluent Interface
	 */
	public function limit($count, $offset = 0) {
		$this->_parts[self::LIMIT_COUNT]  = (int) $count;
		$this->_parts[self::LIMIT_OFFSET] = (int) $offset;
		return $this;
	}

	/**
	 * Seta uma cláusula LIMIT para paginação
	 * @param int $page : a página a ser buscada
	 * @param int $rowCount : o número de linhas na página
	 * @return Hydra_Db_Select : Fluent Interface
	 */
	public function limitPage($page, $rowCount) {
		$page = (int) $page > 0 ? (int) $page : 1;
		$rowCount = (int) $rowCount > 0 ? (int) $rowCount : 1;

		$this->_parts[self::LIMIT_COUNT]  = $rowCount;
		$this->_parts[self::LIMIT_OFFSET] = $rowCount * ($page - 1);
		return $this;
	}

	/**
	 * Faz da query uma sentença SELECT FOR UPDATE
	 * @param boolean $opt [OPCIONAL] : se true, faz com que a query SELECT FOR UPDATE,
	 * 									se false, cancela este efeito
	 * @return Hydra_Db_Select : Fluent Interface
	 */
	public function forUpdate($opt = true) {
		$this->_parts[self::FOR_UPDATE] = (bool) $opt;
		return $this;
	}

	/**
	 * Retorna uma parte do statement
	 * @param string $part : o nome da parte desejada
	 * @throws Hydra_Db_Select_Exception
	 * @return mixed
	 */
	public function getPart($part) {
		if(!array_key_exists($part, $this->_parts)) {
			throw new Hydra_Db_Select_Exception(sprintf('Parte "%s" inexistente em Hydra_Db_Select', $part));
		}
		return $this->_parts[$part];
	}

	/**
	 * Executa a query com o statement deste objeto
	 * @param array $boundParams : parâmetros associados (prepared statements)
	 * @param Hydra_Db::FETCH_* $fetchMode : modo de fetch
	 * @return Hydra_Db_Statement_Abstract
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
	 * Monta a sentença SELECT
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
	 * @param string $part : o nome da parte para resetar, se não informado, reseta todas as partes
	 * @return Hydra_Db_Select : Fluent Interface
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
			} catch(Hydra_Db_Select_Exception $e) {
				return $this;
			}
		}
	}

	/**
	 * Retorna o adapter relacionado a este objeto
	 * @return Hydra_Db_Adapter_Abstract
	 */
	public function getAdapter() {
		return $this->_adapter;
	}
	
	/**
	 * Gera um alias único para um identificador
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
					$col = new Hydra_Db_Expression($col);
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
					//Vai retirando elementos do início do array...
					$this->_parts[self::COLUMNS][] = $currColumn = array_shift($tmpColumns);
					//Até que o nome/alias da tabela seja igual ao do elemento atual.
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
	 * Renderiza a cláusula DISTINCS
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
			
			if($column instanceof Hydra_Db_Expression) {
				$arrayColumns[] = $this->_adapter->quoteColumnAs($column, $colAlias);
			} else {
				if($column == self::SQL_WILDCARD) {
					$column = new Hydra_Db_Expression(self::SQL_WILDCARD);
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
		 * Se não hover tabelas em FROM, usamos a solução dependente do SGBD
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
	 * Renderiza as cláusulas UNION e UNION ALL
	 * @param string $sql
	 * @return string
	 */
	protected function _renderUnion($sql) {
		if(($parts = count($this->_parts[self::UNION])) > 0) {
			foreach($this->_parts[self::UNION] as $i => $union) {
				$target = $union['target'];
				$type = $union['unionType'];
				if($target instanceof Hydra_Db_Select) {
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
	 * Renderiza a cláusula GROUP BY
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
	 * Renderiza a cláusula HAVING
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
	 * Renderiza a cláusula ORDER BY
	 * @param string $sql
	 * @return string
	 */
	protected function _renderOrder($sql) {
		if(!empty($this->_parts[self::ORDER])) {
			$order = array();
			foreach($this->_parts[self::ORDER] as $col) {
				//Se é um array, temos: array('order' => $exprOrder, 'sort' => $exprSort) 
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
	 * Renderiza a cláusula LIMIT
	 * @param string $sql
	 */
	protected function _renderLimitCount($sql) {
		$count = 0;
		$offset = 0;
		
		//Se há uma cláusula OFFSET...
		if(!empty($this->_parts[self::LIMIT_OFFSET])) {
			$offset = (int) $this->_parts[self::LIMIT_OFFSET];
			//Inicialmente setamos COUNT como o maior valor inteiro possível
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
	 * Renderiza a cláusula FOR UPDATE
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
	 * Método mágico para transformação de um objeto Hydra_Db_Select em string
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
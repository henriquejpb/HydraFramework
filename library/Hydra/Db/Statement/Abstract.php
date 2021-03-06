<?php
/**
 * Representa um statement SQL
 * @author <a href="mailto:rick.hjpbacelos@gmail.com">Henrique Barcelos</a>
 */
abstract class Hydra_Db_Statement_Abstract implements Hydra_Db_Statement_Interface {

	/**
	 * O statement a ní­vel de driver
	 * @var object|resource
	 */
	protected $_stmt;

	/**
	 * O adapter ao qual o statement se refere
	 * @var Hydra_Db_Adapter_Abstract
	 */
	protected $_adapter;

	/**
	 * O modo de busca no banco de dados (parão = Hydra_Db::FETCH_ASSOC)
	 * @var Hydra_Db::FETCH_*
	 */
	protected $_fetchMode = Hydra_Db::FETCH_ASSOC;

	/**
	 * Associações í s colunas do resultado
	 * @var array
	 */
	protected $_boundColumns = array();

	/**
	 * Associações de parâmetros da query
	 * @var array
	 */
	protected $_boundParams = array();

	/**
	 * Partes da string em um array de placeholders
	 * @var array
	 */
	protected $_sqlSplit = array();

	/**
	 * Placeholder de parâmetros na sentença SQL por posição em $_sqlSplit
	 * @var array
	 */
	protected $_sqlParams = array();

	/**
	 * Construtor
	 *
	 * @param Hydra_Db_Adapter_Abstract $adapter : o adapter relacionado a este statement
	 * @param string|Hydra_Db_Select $sql : a sentença SQL
	 */
	public function __construct(Hydra_Db_Adapter_Abstract $adapter, $sql) {
		$this->_adapter = $adapter;
		if($sql instanceof Hydra_Db_Select) {
			$sql = $sql->assemble();
		}

		$this->_parseParameters($sql);
		$this->_prepare($sql);
	}

	/**
	 * Prepara uma sentença SQL a ní­vel de driver
	 * @param string $sql
	 * @return void
	 * @throws Hydra_Db_Statement_Exception
	 */
	abstract protected function _prepare($sql);

	/**
	 * Faz o parse dos parâmetros embutidos na sentença SQL
	 * @param string $sql
	 * @throws Hydra_Db_Statement_Exception
	 * @return void
	 */
	protected function _parseParameters($sql) {
		$sql = $this->_stripQuoted($sql);

		/*
		 * Quebra a sentença SQL em pedaços, separando os placeholders.
		 *
		 * Exemplo:
		 * 			SELECT * FROM teste WHERE var1 = :campo1 AND var2 = :campo2
		 *
		 * É quebrado em:
		 * 	Array(
		 * 		[0] => SELECT * FROM teste WHERE var1 =
		 * 		[1] => :campo1
		 * 		[2] =>  AND var2 =
		 * 		[3] => :campo2
		 * 	)
		 */
		$this->_sqlSplit = preg_split('#(\?|\:[A-Za-z0-9_]+)#', $sql, -1,
								PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

		//Limpa os parâmetros, se houver algum
		$this->_sqlParams = array();

		//Limpa os parâmetros associados, se houver
		$this->_bindParams = array();

		foreach($this->_sqlSplit as $key => $each) {
			if($each == '?' &&
				!$this->_adapter->supportsParameters(Hydra_Db_Adapter_Abstract::POSITIONAL_PARAMETERS)) {
				throw new Hydra_Db_Statement_Exception(sprintf('A variável de associação "%s" é inválida', $each));
			} else if($each[0] == ':' &&
				!$this->_adapter->supportsParameters(Hydra_Db_Adapter_Abstract::NAMED_PARAMETERS)) {
				throw new Hydra_Db_Statement_Exception(sprintf('A variável de associação "%s" é inválida', $each));
			}
			$this->_sqlParams[] = $each;
		}
	}

	/**
	 * Retira as partes quotadas da sentença
	 * @param string $sql
	 */
	protected function _stripQuoted($sql) {
		$d = $this->_adapter->getQuoteIdentifierSymbol();

		$de = $this->_adapter->quoteIdentifier($d);
		$de = substr($de, 1, 2);
		$de = str_replace('\\', '\\\\', $de);

		$sql = preg_replace("#{$d}({$de}|\\\\{2}|[^{$d}])*{$d}#", '', $sql);

		$q = $this->_adapter->quote('a');
		$q = $q[0];

		$qe = $this->_adapter->quoteIdentifier($q);
		$qe = substr($qe, 1, 2);
		$qe = str_replace('\\', '\\\\', $qe);

		$sql = preg_replace("#{$q}({$qe}|\\\\{2}|[^{$q}])*{$q}#", '', $sql);

		return $sql;
	}

	/**
	 * @see Hydra_Db_Statement_Interface::bindColumn()
	 */
	public function bindColumn($column, &$param, $type = null) {
		$this->_boundColumns[(string) $column] = &$param;
		return $this;
	}

	/**
	 * @see Hydra_Db_Statement_Interface::bindParam()
	 */
	public function bindParam($parameter, &$variable, $type = null, $length = null) {
		if(!is_int($parameter) || !is_string($parameter)) {
			throw new Hydra_Db_Statement_Exception(sprintf('Posição de associação "%s" inválida', $parameter));
		}

		$position = null;
		if(($intVal = (int) $parameter) > 0 &&
			$this->_adapter->supportsParameters(Hydra_Db_Adapter_Abstract::POSITIONAL_PARAMETERS) &&
			$intVal <= count($this->_sqlParams)) {
			$position = $intVal;
		} else if($this->_adapter->supportsParameters(Hydra_Db_Adapter_Abstract::NAMED_PARAMETERS)) {
			if($parameter[0] != ':') {
				$parameter = ':' . $parameter;
			}
			if(!in_array($parameter, $this->_sqlParams) !== false) {
				$position = $parameter;
			}
		}

		if($position === null) {
			throw new Hydra_Db_Statement_Exception(sprintf('Posição de associação "%s" inválida', $parameter));
		}

		$this->_boundParams[$position] =& $variable;
		return $this;
	}

	/**
	 * @see Hydra_Db_Statement_Interface::bindValue()
	 */
	public function bindValue($paramter, $value, $type = null) {
		return $this->bindParam($parameter, $value, $type);
	}

	/**
	 * @see Hydra_Db_Statement_Interface::fetchOne()
	 */
	public function fetchOne($mode = null, $col = null) {
		if($mode == Hydra_Db::FETCH_COLUMN && $col === null) {
			$col = 0;
		}

		if($mode == Hydra_Db::FETCH_COLUMN) {
			$row = $this->fetchColumn($col);
		} else {
			$row = $this->_doFetch($mode);
		}

		return $row;
	}

	/**
	 * @see Hydra_Db_Statement_Interface::fetchAll()
	 */
	public function fetchAll($mode = null, $col = null) {
		if($mode == Hydra_Db::FETCH_COLUMN && $col === null) {
			$col = 0;
		}

		$data = array();
		if($mode != Hydra_Db::FETCH_COLUMN) {
			while($row = $this->_doFetch($mode)) {
				$data[] = $row;
			}
		} else {
			while(($row = $this->fetchColumn($col)) !== null) {
				$data[] = $row;
			}
		}
		return $data;
	}

	/**
	 * @see Hydra_Db_Statement_Interface::fetchColumn()
	 */
	public function fetchColumn($col = 0) {
		$data = array();

		if(is_string($col)) {
			$row = $this->_doFetch(Hydra_Db::FETCH_ASSOC);
		} else {
			$col = (int) $col;
			$row = $this->_doFetch(Hydra_Db::FETCH_NUM);
		}

		if(!is_array($row) && !isset($row[$col])) {
			return null;
		}

		return $row[$col];
	}

	/**
	 * @see Hydra_Db_Statement_Interface::fetchObject()
	 */
	public function fetchObject($class = 'stdClass', array $config = array()) {
		if(!class_exists($class)){
			throw new Exception(sprintf('Classe "%s" não encontrada', $class));
		}

		$obj = new $class($config);
		if(! $obj instanceof stdClass ||
			! $obj instanceof Hydra_Db_Fetchable) {
			throw new Hydra_Db_Adapter_Exception('A classe para o método fetchObject deve ser
				stdClass ou implementar a inteface Hydra_Db_Fetchable_Interface');
		}

		$row = $this->_doFetch(Hydra_Db::FETCH_ASSOC);
		if(!is_array($row)){
			return null;
		}

		foreach($row as $key => $val) {
			$obj->set($key, $val);
		}

		return $obj;
	}

	/**
	 * Faz a busca de uma linha ou coluna no banco de dados
	 * @param Hydra_Db::FETCH_* $mode : o modo de busca
	 */
	abstract protected function _doFetch($mode = null);

	/**
	 * @see Hydra_Db_Statement_Interface::setFetchMode()
	 */
	public function setFetchMode($mode) {
		switch($mode) {
			case Hydra_Db::FETCH_ARRAY:
			case Hydra_Db::FETCH_ASSOC:
			case Hydra_Db::FETCH_NUM:
			case Hydra_Db::FETCH_OBJ:
				$this->_fetchMode = $mode;
				break;

			default:
				$this->closeCursor();
				throw new Hydra_Db_Statement_Exception('Mode de fetch inválido');
		}
	}

	/**
	 * Retorna o adapter deste objeto
	 * @return Hydra_Db_Adapter_Abstract
	 */
	public function getAdapter() {
		return $this->adapter;
	}

	/**
	 * Retorna o statement a ní­vel de driver deste objeto
	 * @return object|resource|null
	 */
	public function getStatement() {
		return $this->_stmt;
	}
}
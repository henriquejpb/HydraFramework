<?php
class Hydra_Db_Table_Row implements ArrayAccess, IteratorAggregate {
	/**
	 * Os dados das colunas da linha da tabela
	 * @var array
	 */
	protected $_data = array();

	/**
	 * Este atributo é setado como uma cópia dos dados
	 * quando estes são buscados na tabela do banco de dados
	 * ou especificado como uma nova tupla no construtor ou
	 * quando dados 'sujos' são enviados ao banco de dados.
	 * @var array
	 */
	protected $_cleanData = array();
	
	/**
	 * Rastreia as colunas onde os dados foram atualizados,
	 * para permitir operações de INSERT e UPDATE mais específicas
	 * @var array
	 */
	protected $_modifiedFields = array();

	/**
	 * Instância do objeto Hydra_Db_Table que criou este objeto
	 * @var Hydra_Db_Table
	 */
	protected $_table = null;

	/**
	 * Se TRUE, temos uma referência a um objeto Hydra_Db_Table.
	 * Será FALSE após a desserialização.
	 * @var boolean
	 */
	protected $_connected = true;

	/**
	 * Uma linha pode ser marcada como somente leitura se ela contém
	 * colunas que não são fisicamente representadas no schema da tabela
	 * (ex.: colunas avaliadas/Hydra_Db_Expressions).
	 *
	 * Isso também pode ser configurado em tempo de execução,
	 * para proteger os dados da linha.
	 * @var boolean
	 */
	protected $_readOnly = false;

	/**
	 * O nome da tabela do objeto Hydra_Db_table
	 * @var string
	 */
	protected $_tableName;

	/**
	 * As colunas que são chave primária da linha
	 * @var array
	 */
	protected $_primary;

	/**
	 * Construtor.
	 *
	 * Parâmetros de configuração suportados:
	 * - table		=>	(string) o nome da tabela ou a instância de Hydra_Db_Table
	 * - data		=>	(array) os dados das colunas nesta linha
	 * - stored		=>	(boolean) se os dados são provindos do banco de dados ou não
	 * - readOnly	=>	(boolean) se é permitido ou não alterar os dados desta linha
	 *
	 * @param array $config
	 * @throws Hydra_Db_Table_Row_Exception
	 */
	public function __construct(array $config = array()) {
		if(isset($config['table'])) {
			if($config['table'] instanceof Hydra_Db_Table) {
				$this->_table = $config['table'];
			} else if($config['table'] != null){
				$this->_table = $this->_getTableFromString($config['table']);
			}
			$this->_tableName = $this->_table->getName();
		}

		if(isset($config['data'])) {
			if(!is_array($config['data'])) {
				throw new Hydra_Db_Table_Row_Exception('Os dados precisam ser um array.');
			}
			$this->_data = $config['data'];
		}
		
		if(isset($config['stored']) && $config['stored'] === true) {
			$this->_cleanData = $this->_data;
		}

		if (isset($config['readOnly']) && $config['readOnly'] === true) {
			$this->setReadOnly(true);
		}

		if (($table = $this->getTable())) {
			$info = $table->info();
			$this->_primary = (array) $info['primary'];
		}

		$this->init();
	}

	/**
	 * Retorna o valor de um campo da tabela ou de um campo extra.
	 * A precedência é 
	 * 	campo > campo extra
	 *
	 * @param string $columnName
	 * @return mixed
	 * @throws Hydra_Db_Table_Row_Exception
	 */
	public function get($columnName) {
		if(!$this->exists($columnName)) {
			throw new Hydra_Db_Table_Row_Exception(sprintf('A coluna "%s" não existe na tabela "%s"', $columnName, $this->_tableName));
		}
		return $this->_data[$columnName];
	}

	/**
	 * Seta um valor para o campo da tabela.
	 * 
	 * @param string $columnName
	 * @param mixed $value
	 * @return Hydra_Db_Table_Row : fluent interface
	 * @throws Hydra_Db_Table_Row_Exception
	 */
	public function set($columnName, $value) {
		if(!$this->exists($columnName)) {
			throw new Hydra_Db_Table_Row_Exception(sprintf('A coluna "%s" não existe na tabela "%s"', $columnName, $this->_tableName));
		}
		
		$this->_data[$columnName] = $value;
		$this->_modifiedFields[$columnName] = true;
		
		return $this;
	}

	/**
	 * Verifica se o campo existe na tabela
	 *
	 * @param string $columnName
	 * @return boolean
	 */
	public function exists($columnName) {
		return isset($this->_data[$columnName]);
	}

	/**
	 * Remove o valor de um campo da tabela
	 *
	 * @param string $columnName
	 * @return Hydra_Db_Table_Row : fluent interface
	 * @throws Hydra_Db_Table_Row_Exception
	 */
	public function remove($columnName) {
		if(!$this->exists($columnName)) {
			throw new Hydra_Db_Table_Row_Exception(sprintf('A coluna "%s" não existe na tabela "%s"', $columnName, $this->_tableName));
		}

		if($this->isConnected() && in_array($columnName, $this->_table->info(Hydra_Db_Table::PRIMARY))) {
			throw new Hydra_Db_Table_Row_Exception(sprintf('A coluna "%s" é chave primária na tabela "%s" e não pode ser removida', $columnName, $this->_tableName));
		}
		
		unset($this->_data[$columnName]);
		return $this;
	}
	
	/**
	 * @param string $columnName
	 * @see Hydra_Db_Table_Row::get()
	 */
	public function __get($columnName) {
		return $this->get($columnName);
	}
	
	/**
	 * @param string $columnName
	 * @param mixed $value
	 */
	public function __set($columnName, $value) {
		return $this->set($columnName, $value);
	}
	
	/**
	 * @param string $columnName
	 */
	public function __isset($columnName) {
		return $this->exists($columnName);
	}
	
	/**
	 * @param string $columnName
	 */
	public function __unset($columnName) {
		return $this->remove($columnName);
	}
	
	/**
	 * Retorna os dados para a serialização.
	 * 
	 * @return array
	 */
	public function __sleep() {
		return array('_tableName', '_primary', '_data', '_cleanData', '_modifiedFields', '_readOnly');
	}
	
	/**
	 * Não é esperado que uma linha desserializada tenha
	 * acesso a uma conexão ativa com o banco de dados
	 * 
	 * @return void
	 */
	public function __wakeup() {
		$this->_connected = false;
	}
	
	/**
	 * @see ArrayAccess::offsetGet()
	 */
	public function offsetGet($offset) {
		return $this->get($offset);
	}
	
	/**
	 * @see ArrayAccess::offsetSet()
	 */
	public function offsetSet($offset, $value) {
		$this->set($offset, $value);
	}
	
	/**
	 * @see ArrayAccess::offsetExists()
	 */
	public function offsetExists($offset) {
		return $this->exists($offset);
	}
	
	/**
	 * @see ArrayAccess::offsetUnset()
	 */
	public function offsetUnset($offset) {
		return $this->remove($offset);
	}
	
	/**
	 * @see IteratorAggregate::getIterator()
	 */
	public function getIterator() {
		return new ArrayIterator((array) $this->_data);
	}
	
	/**
	 * Converte o objeto em um array.
	 * 
	 * @return array
	 */
	public function toArray() {
		return (array) $this->_data;
	}
	
	/**
	 * Seta os dados do objeto a partir de um array.
	 * 
	 * @param array $data
	 * @return Hydra_Db_Table_Row : fluent interface
	 */
	public function setFromArray(array $data) {
		foreach($data as $key => $val) {
			if(isset($this->_data[$key])) {
				$this->set($key, $val);
			}
		}
		
		return $this;
	}
	
	/**
	 * Inicializa o objeto.
	 * É chamado no final do construtor {@link __construct()}
	 */
	public function init() {
		
	}
	
	/**
	 * Retorna um objeto Hydra_Db_Table ou null, se a linha está 'desconectada'.
	 * 
	 * @return Hydra_Db_Table|null
	 */
	public function getTable() {
		return $this->_table;
	}
	
	/**
	 * Seta uma tabela para o objeto para restabelecer a conexão
	 * com o banco de dados para o objeto desserializado.
	 * 
	 * @param Hydra_Db_Table $table
	 * @return boolean
	 * @throws Hydra_Db_Table_Row_Exception
	 */
	public function setTable(Hydra_Db_Table $table = null) {
		if($table === null) {
			$this->_table = null;
			$this->_connected = false;
			return false;
		}
		
		if($table->getName() != $this->_tableName) {
			throw new Hydra_Db_Table_Row_Exception(sprintf('A tabela especificada "%s" não é a mesma configurada no objeto Hydra_Db_Table_Row ("%s")', 
													$table->getName(), 
													$this->_tableName
											));
		}
		
		$this->_table = $table;
		$this->_tableName = $table->getName();
		$info = $this->_table->info();
		
		$tableCols = $info['cols'];
		$rowCols = array_keys($this->_data);
		 
		if($tableCols != $rowCols) {
			throw new Hydra_Db_Table_Row_Exception(sprintf('As colunas da tabela (%s) não são as mesmas colunas da linha (%s)',
													join(', ', $tableCols),
													join(', ', $rowCols)
											));
		}
		
		$tablePk = $info['primary'];
		$rowPk = (array) $this->_primary;
		if(!array_intersect($rowPk, $tablePk) == $rowPk) {
			throw new Hydra_Db_Table_Row_Exception(sprintf('A chave primária da tabela (%s) não é a mesma da linha (%s)',
													join(', ', $tablePk),
													join(', ', $rowPk)
											));
		}
		
		$this->_connected = true;
		return true;
	}
	
	/**
	 * Retorna o nome da tabela à qual o objeto está vinculado.
	 * 
	 * @return string
	 */
	public function getTableName() {
		return $this->_tableName;
	}

	/**
	 * Testa o status de conexão do objeto.
	 * 
	 * @return boolean
	 */
	public function isConnected() {
		return $this->_connected;
	}
	
	/**
	 * Testa se o objeto é somente-leitura.
	 * 
	 * @return boolean
	 */
	public function isReadOnly() {
		return $this->_readOnly;
	}
	
	/**
	 * Seta o status de somente-leitura do objeto.
	 * 
	 * @param boolean $opt
	 * @return Hydra_Db_Table_Row : fluent interface
	 */
	public function setReadOnly($opt) {
		$this->_readOnly = (bool) $opt;
		return $this;
	}	
	
	/**
	 * Retorna uma instância do objeto Hydra_Db_Select
	 * criado pelo objeto Hydra_Db_Table pai deste objeto.
	 * 
	 * @param mixed $cols
	 * @return Hydra_Db_Select
	 * @throws Hydra_Db_Table_Row_Exception
	 */
	public function select($cols = array()) {
		$table = $this->_getRequiredTable();
		return $table->select($cols);
	}
	
	/**
	 * Salva as propriedades no banco de dados.
	 * 
	 * Realiza inserções e atualizações inteligentes e recarrega as 
	 * propriedades com os valores atualizados da tabela em caso de sucesso.
	 * 
	 * @return mixed : a chave primária do registro
	 * @throws Hydra_Db_Table_Exception
	 */
	public function save() {
		if($this->isReadOnly()) {
			throw new Hydra_Db_Table_Row_Exception('Este objeto Hydra_Db_Table_Row está marcado como somente-leitura.');
		}
		/* Se _cleanData está vazio,
		 * temos uma operação de inserção,
		 * se não, temos uma atualização.
		 */
		if(empty($this->_cleanData)) {
			return $this->_doInsert();
		} else {
			return $this->_doUpdate();
		}
	}
	
	/**
	 * Realiza a inserção dos dados da linha na tabela.
	 * 
	 * @return mixed : a chave-primária da linha inserida
	 */
	public function _doInsert() {
		// Lógica de pré-inserção
		$this->_preInsert();
		
		$data = array_intersect_key($this->_data, $this->_modifiedFields);
		$pk = $this->_getRequiredTable()->insert($data);

		if(is_array($pk)) {
			$newPk = $pk;
		} else {
			$tmpPk = (array) $this->_primary;
			$newPk = array(current($tmpPk) => $pk);
		}
		
		$this->_data = array_merge($this->_data, $newPk);
		
		// Lógica de pós-inserção
		$this->_postInsert();
		
		// Atualiza _cleanData para refletir os dados que foram inseridos
		$this->refresh();
		
		return $pk;
	}
	
	/**
	 * Atualiza os dados da linha na tabela.
	 * 
	 * @return mixed: a chave primária da linha alterada
	 */
	protected function _doUpdate() {
		/* 
		 * Cria uma expressão para a cláusula WHERE
		 * com base no valor da chave primária
		 */
		$where = $this->_getWhereQuery(false);

		// Lógica de pré-atualização
		$this->_preUpdate();
		
		// Descobre quais colunas foram modificadas.
		$diffData = array_intersect_key($this->_data, $this->_modifiedFields);
		
		$table = $this->_getRequiredTable();
		
		// Atualiza apenas se houver dados alterados
		if(!empty($diffData)) {
			$table->update($diffData, $where);
		}
		
		// Lógica de pós-atualização
		$this->_postUpdate();
		
		/* Atualiza os dados caso triggers no SGBD 
		 * tenham alterado o valor de qualquer coluna.
		 * Também reseta _cleanData 
		 */		
		$this->refresh();
		
		$pk = $this->_getPrimaryKey(true);
		if(count($pk) == 1) {
			return current($pk);
		}
		
		return $pk;
	}
	
	/**
	 * Remove a linha da tabela
	 * 
	 * @throws Hydra_Db_Table_Row_Exception
	 * @return int : o número de linhas removidas
	 */
	public function delete() {
		if($this->isReadOnly()) {
			throw new Hydra_Db_Table_Row_Exception('Este objeto Hydra_Db_Table_Row está marcado como somente-leitura.');
		}
		
		/* 
		 * Cria uma expressão para a cláusula WHERE
		 * com base no valor da chave primária
		 */
		$where = $this->_getWhereQuery(false);
		
		// Lógica de pré-remoção
		$this->_preDelete();
		
		$table = $this->_getRequiredTable();
		
		// Executa a remoção
		$result = $table->delete($where);
		
		// Lógica de pós-remoção
		$this->_postDelete();
		
		/*
		 * Seta todas as colunas com o valor NULL
		 */
		$this->_data = array_combine(
			array_keys($this->_data),
			array_fill(0, count($this->_data), null)
		);
		
		return $result;
	}
	
	/**
	 * Se o objeto Hydra_Db_Table for necessário para executar uma operação,
	 * deve-se invocar este método para buscá-lo.
	 * Ele lançará uma excessão caso a tabela não seja encontrada.
	 *
	 * @return Hydra_Db_Table
	 * @throws Hydra_Db_Table_Row_Exception
	 */
	protected function _getRequiredTable() {
		$table = $this->getTable();
		if(!$table instanceof Hydra_Db_Table) {
			throw new Hydra_Db_Table_Row_Exception('O objeto Hydra_Db_Table_Row não está associado a um objeto Hydra_Db_Table.');
		}
		return $table;
	}
	
	/**
	 * Retorna um array associativo contendo a chave primária da linha
	 * 
	 * @param boolean $useDirty
	 * @throws Hydra_Db_Table_Row_Exception
	 */
	protected function _getPrimaryKey($useDirty = false) {
		if(!is_array($this->_primary)) {
			throw new Hydra_Db_Table_Row_Exception('A chave primária deve estar setada como um array.');
		}
		
		$primary = array_flip($this->_primary);
		if($useDirty) {
			$array = array_intersect_key($this->_data, $primary);
		} else {
			$array = array_intersect_key($this->_cleanData, $primary);
		}
		
		if(count($primary) != count($array)) {
			throw new Hydra_Db_Table_Row_Exception(sprintf(
				'A tabela especificada "%s" não possui a mesma chave primária (%s) que a linha (%s).',
				$this->_tableName,
				join(', ', $primary),
				join(', ', $array)
			));
		}
		
		return $array;
	}
	
	/**
	 * Gera uma cláusula WHERE com base na chave primária da linha.
	 * 
	 * @param boolean $useDirty
	 * @return array : array de cláusulas WHERE
	 */
	protected function _getWhereQuery($useDirty = true) {
		$where = array();
		
		$adapter = $this->_table->getAdapter();

		$pk = $this->_getPrimaryKey($useDirty);
		
		$info = $this->_table->info();
		$tableName = $tableName = $adapter->quoteIdentifier($info[Hydra_Db_Table::NAME]);
		$metadata = $info[Hydra_Db_Table::METADATA];
		
		$where = array();
		foreach($pk as $col => $val) {
			$type = $metadata[$col]['DATA_TYPE'];
			$colName = $adapter->quoteIdentifier($col);
			$where[] = $adapter->quoteInto($tableName . '.' . $colName . ' = ?', $val);
		}
		return $where;
	}
	
	/**
	 * Atualiza os dados do objeto com os dados da linha
	 * da tabela no banco de dados.
	 * 
	 * @return void
	 * @throws Hydra_Db_Table_Row_Exception
	 */
	public function refresh() {
		$where = $this->_getWhereQuery();
		$row = $this->_getRequiredTable()->fetchOne($where);
		
		if($row === null) {
			throw new Hydra_Db_Table_Row_Exception('Não foi possível atualizar a linha da tabela. 
											Erro ao buscá-la no banco de dados.');
		}
		
		$this->_data = $row->toArray();
		$this->_cleanData = $this->_data;
		$this->_modifiedFields = array();
	}
	
	/**
	 * Lógica de pré-inserção
	 */
	protected function _preInsert() {
		
	}
	
	/**
	 * Lógica de pós-inserção
	 */
	protected function _postInsert() {
	
	}
	
	/**
	 * Lógica de pré-atualização
	 */
	protected function _preUpdate() {
	
	}
	
	/**
	 * Lógica de pós-atualização
	 */
	protected function _postUpdate() {
	
	}
	
	/**
	 * Lógica de pré-remoção
	 */
	protected function _preDelete() {
	
	}
	
	/**
	 * Lógica de pós-remoção
	 */
	protected function _postDelete() {
	
	}
	
	/**
	 * Prepara uma referência para uma tabela.
	 * 
	 * Assegura que todas as referências estão setadas
	 * e devidamente formatadas.
	 * 
	 * @param Hydra_Db_Table $dependent
	 * @param Hydra_Db_Table $parent
	 * @param string|null $ruleKey : caso NULL, a primeira referência encontrada será retornada
	 * @return array
	 */
	protected function _prepareReference(Hydra_Db_Table $dependent, Hydra_Db_Table $parent, $ruleKey = null) {
		$parentName = $parent->getName();
		$map = $dependent->getReference($parentName, $ruleKey);

		if(!isset($map[Hydra_Db_Table::REF_COLUMNS])) {
			$parentInfo = $parent->info();
			$map[Hydra_Db_Table::REF_COLUMNS] = array_values((array) $parentInfo['primary']);
		}
		
		$map[Hydra_Db_Table::COLUMNS] = (array) $map[Hydra_Db_Table::COLUMNS];
		$map[Hydra_Db_Table::REF_COLUMNS] = (array) $map[Hydra_Db_Table::REF_COLUMNS];
		
		return $map;
	}
	
	/**
	 * Encontra o conjunto de linhas dependente deste objeto.
	 * 
	 * @param Hydra_Db_Table|string $dependentTable
	 * @param string|null $ruleKey
	 * @param Hydra_Db_Select $select
	 * @return Hydra_Db_Table_Rowset
	 * @throws Hydra_Db_Table_Row_Exception
	 */
	public function findDependentRowset($dependentTable, $ruleKey = null, Hydra_Db_Select $select = null) {
		$adapter = $this->_getRequiredTable()->getAdapter();
		if(is_string($dependentTable)) {
			$dependentTable = $this->_getTableFromString($dependentTable);
		}

		
		if(!$dependentTable instanceof Hydra_Db_Table) {
			$type = gettype($dependentTable);
			if ($type == 'object') {
				$type = get_class($dependentTable);
			}
			throw new Hydra_Db_Table_Row_Exception('A tabela dependente deve ser do tipo Hydra_Db_Table, mas é do tipo ' . $type);			
		}
		
		if($select === null) {
			$select = $dependentTable->select();
		} else {
			$select->setTable($dependentTable);
		}
		
		$map = $this->_prepareReference($dependentTable, $this->_getRequiredTable(), $ruleKey);
		for($i = 0; $i < count($map[Hydra_Db_Table::COLUMNS]); $i++) {
			$parentColName = $map[Hydra_Db_Table::REF_COLUMNS][$i];
			$value = $this->_data[$parentColName];
			
			$dependentAdapter = $dependentTable->getAdapter();
			$dependentColName = $map[Hydra_Db_Table::COLUMNS][$i];
			$dependentCol = $dependentAdapter->quoteIdentifier($dependentColName);
			$dependentInfo = $dependentTable->info();
			
			$type = $dependentInfo[Hydra_Db_Table::METADATA][$dependentColName]['DATA_TYPE'];
			$select->where($dependentCol . ' = ?', $value, $type);
		}
		
		return $dependentTable->fetchAll($select);
	}
	
	/**
	 * Retorna a linha pai deste objeto.
	 * 
	 * @param Hydra_Db_Table|string $parentTable
	 * @param string $ruleKey
	 * @param Hydra_Db_Select $select
	 * @return Hydra_Db_Table_Row|null
	 * @throws Hydra_Db_Table_Row_Exception
	 */
	public function findParentRow($parentTable, $ruleKey = null, Hydra_Db_Select $select = null) {
		$adapter = $this->_getRequiredTable()->getAdapter();
		
		if(is_string($parentTable)) {
			$parentTable = $this->_getTableFromString($parentTable);
		}
		
		if(!$parentTable instanceof Hydra_Db_Table) {
			$type = gettype($parentTable);
			if ($type == 'object') {
				$type = get_class($parentTable);
			}
			throw new Hydra_Db_Table_Row_Exception('A tabela pai deve ser do tipo Hydra_Db_Table, mas é do tipo ' . $type);
		}
		
		if($select === null) {
			$select = $parentTable->select();
		} else {
			$select->setTable($parentTable);
		}
		
		$map = $this->_prepareReference($this->_getRequiredTable(), $parentTable, $ruleKey);
		for($i = 0; $i < count($map[Hydra_Db_Table::COLUMNS]); $i++) {
			$dependentColName = $map[Hydra_Db_Table::COLUMNS][$i];
			$value = $this->_data[$dependentColName];
			
			$parentAdapter = $parentTable->getAdapter();
			$parentColName = $map[Hydra_Db_Table::REF_COLUMNS][$i];
			$parentCol = $parentAdapter->quoteIdentifier($parentColName);
			$parentInfo = $parentTable->info();
			
			$type = $parentInfo[Hydra_Db_Table::METADATA][$parentColName]['DATA_TYPE'];
			$nullable = $parentInfo[Hydra_Db_Table::METADATA][$parentColName]['NULLABLE'];
			
			if($value === null) {
				if($nullable == true) {
					$select->where($parentCol . ' IS NULL');
				} else {
					return null;
				}
			} else {
				$select->where($parentCol . ' = ?', $value, $type);
			}
		}
		
		return $parentTable->fetchOne($select);
	}
	
	/**
	 * Retorna as linhas associadas ao objeto atual 
	 * em um relacionamento N:N
	 * 
	 * @param Hydra_Db_Table|string $matchTable : a tabela contendo os dados desejados
	 * @param Hydra_Db_Table|string $intersectionTable : a tabela de intersecção
	 * @param string $callerRefRule : o nome da regra de referência da tabela 
	 * 								  atual para a tabela de intersecção
	 * @param string $matchRefRule : o nome da regra de referência da tabela
	 * 								 de busca para a tabela de intersecção
	 * @param Hydra_Db_Select $select : um SQL SELECT statement personalizado 
	 * @return Hydra_Db_Table_Rowset
	 * @throws Hydra_Db_Table_Row_Exception
	 */
	public function findManyToManyRowset($matchTable, $intersectionTable, $callerRefRule = null, 
										 $matchRefRule = null, Hydra_Db_Select $select = null) {
		$adapter = $this->_getRequiredTable()->getAdapter();

		if(is_string($intersectionTable)) {
			$intersectionTable = $this->_getTableFromString($intersectionTable);
		}
		
		if(!$intersectionTable instanceof Hydra_Db_Table) {
			$type = gettype($intersectionTable);
			if ($type == 'object') {
				$type = get_class($intersectionTable);
			}
			throw new Hydra_Db_Table_Row_Exception('A tabela de intersecção deve ser do tipo Hydra_Db_Table, mas é do tipo ' . $type);
		}
		
		if(is_string($matchTable)) {
			$matchTable = $this->_getTableFromString($matchTable);
		}
		
		if(!$matchTable instanceof Hydra_Db_Table) {
			$type = gettype($matchTable);
			if ($type == 'object') {
				$type = get_class($matchTable);
			}
			throw new Hydra_Db_Table_Row_Exception('A tabela de comparação deve ser do tipo Hydra_Db_Table, mas é do tipo ' . $type);
		}
		
				
		$interInfo = $intersectionTable->info();
		$interAdapter = $intersectionTable->getAdapter();
		$interName = $interInfo[Hydra_Db_Table::NAME];
		$interSchema = isset($interInfo[Hydra_Db_Table::SCHEMA]) ? $interInfo[Hydra_Db_Table::SCHEMA] : null;
		
		$matchInfo = $matchTable->info();
		$matchName = $matchInfo[Hydra_Db_Table::NAME];
		$matchSchema = isset($matchInfo[Hydra_Db_Table::SCHEMA]) ? $matchInfo[Hydra_Db_Table::SCHEMA] : null;
		
		$matchMap = $this->_prepareReference($intersectionTable, $matchTable, $matchRefRule);
		$joinCond = array();
		for($i = 0; $i < count($matchMap[Hydra_Db_Table::COLUMNS]); $i++) {
			$interCol = $interAdapter->quoteIdentifier('i.' . $matchMap[Hydra_Db_Table::COLUMNS][$i]);
			$matchCol = $interAdapter->quoteIdentifier('m.' . $matchMap[Hydra_Db_Table::REF_COLUMNS][$i]);
			$joinCond[] = $interCol . ' = ' . $matchCol;
		}
		$joinCond = join(' AND ', $joinCond);
		
		if($select === null) {
			$select = new Hydra_Db_Select($matchTable->getAdapter());
		}
		$select->from(array('m' => $matchName), $matchTable->info(Hydra_Db_Table::COLS), $matchSchema)
			->innerJoin(array('i' => $interName), $joinCond, array(), $interSchema);
		
		$callerMap = $this->_prepareReference($intersectionTable, $this->_getRequiredTable(), $callerRefRule);
		for($i = 0; $i < count($callerMap[Hydra_Db_Table::COLUMNS]); $i++) {
			$callerColName = $callerMap[Hydra_Db_Table::REF_COLUMNS][$i];
			$value = $this->_data[$callerColName];
			
			$interColName = $callerMap[Hydra_Db_Table::COLUMNS][$i];
			$interCol = $interAdapter->quoteIdentifier('i.' . $interColName);
			$type = $interInfo[Hydra_Db_Table::METADATA][$interColName]['DATA_TYPE'];
			
			$select->where($interAdapter->quoteInto($interCol . ' = ?', $value, $type));
		}

		$stmt = $select->query();
		$data = $stmt->fetchAll(Hydra_Db::FETCH_ASSOC);
		
		$config = array(
			'table'		=>	$matchTable,
			'data'		=>	$data,
			'rowClass'	=>	$matchTable->getRowClass(),
			'readOnly'	=>	false,
			'stored'	=>	true			
		);
		
		$rowsetClass = $matchTable->getRowsetClass();
		$rowset = new $rowsetClass($config);
		return $rowset;
	}
	
	/**
	 * Retorna uma instância de Hydra_Db_Table ou classe derivada
	 * a partir de uma string contendo o nome da tabela ou classe.
	 * @param string $tableName
	 * @return Hydra_Db_Table
	 * @throws Hydra_Db_Table_Row_Exception
	 */
	protected function _getTableFromString($tableName) {
		try {
			// Caso a classe não exista, irá lançar uma exceção do tipo ReflectionException
			$ref = new ReflectionClass($tableName);
			if($ref->isSubclassOf('Hydra_Db_Table')) {
				return new $tableName($options);
			} else {
				throw new Hydra_Db_Table_Row_Exception('A classe ' . $tableName . ' não é subclasse de Hydra_Db_Table');
			}
		} catch (ReflectionException $e) {
			$options = array();
			if($table = $this->getTable()) {
				$options['db'] = $table->getAdapter();
			}
			$options['name'] = $tableName;
			
			$tb = new Hydra_Db_Table($options);
			return $tb;
		}
	}
}
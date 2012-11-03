<?php
class Hydra_Db_Table_Definition {
	/**
	 * Coleção de definições de tabela.
	 * 
	 * @var array
	 */
	private $_tableDefs = array();
	
	/**
	 * Construtor. 
	 * Seta uma ou mais definições de tabela 
	 * através de um array da forma:
	 * - nomeDaTabela	=>	opções
	 * 
	 * Para ver as definições possíveis, veja
	 * @see Hydra_Db_Table::__construct
	 * 
	 * @param array|null $definitions
	 */
	public function __construct($definitions = null) {
		if(is_array($definitions) || $definitions instanceof Traversable) {
			foreach($definitions as $table => $def) {
				$this->setTableDefinition($table, $def);
			}
		}
	}
	
	/**
	 * Seta a definição para a tabela $tableName.
	 * 
	 * @param string $tableName
	 * @param array $tableDef
	 * @return Hydra_Db_Table_Definition : fluent interface
	 */
	public function setTableDefinition($tableName, array $tableDef) {
		$tableDef[Hydra_Db_Table::DEFINITION] = $this;
		if(!isset($tableDef[Hydra_Db_Table::NAME])) {
			$tableDef[Hydra_Db_Table::NAME] = $tableName;
		}
		
		$this->_tableDefs[$tableName] = $tableDef;
		return $this;
	}

	/**
	 * Retorna a definição da tabela $tableName.
	 * 
	 * @param string $tableName
	 */
	public function getTableDefinition($tableName) {
		if(!isset($this->_tableDefs[$tableName])) {
			return null;
		}
		return $this->_tableDefs[$tableName];
	}
	
	/**
	 * Verifica se existe a definição para a tabela $tableName.
	 * 
	 * @param string $tableName
	 */
	public function hasTableDefinition($tableName) {
		return $this->getTableDefinition($tableName) != null;
	}
	
	/**
	 * Remove a definição da tabela $tableName.
	 * 
	 * @param string $tableName
	 */
	public function removeTableDefinition($tableName) {
		if(isset($this->_tableDefs[$tableName])) {
			unset($this->_tableDefs[$tableName]);
		}
		return $this;
	}	
}
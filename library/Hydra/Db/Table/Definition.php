<?php
class Db_Table_Definition {
	/**
	 * Cole��o de defini��es de tabela.
	 * 
	 * @var array
	 */
	private $_tableDefs = array();
	
	/**
	 * Construtor. 
	 * Seta uma ou mais defini��es de tabela 
	 * atrav�s de um array da forma:
	 * - nomeDaTabela	=>	op��es
	 * 
	 * Para ver as defini��es poss�veis, veja
	 * @see Db_Table::__construct
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
	 * Seta a defini��o para a tabela $tableName.
	 * 
	 * @param string $tableName
	 * @param array $tableDef
	 * @return Db_Table_Definition : fluent interface
	 */
	public function setTableDefinition($tableName, array $tableDef) {
		$tableDef[Db_Table::DEFINITION] = $this;
		if(!isset($tableDef[Db_Table::NAME])) {
			$tableDef[Db_Table::NAME] = $tableName;
		}
		
		$this->_tableDefs[$tableName] = $tableDef;
		return $this;
	}

	/**
	 * Retorna a defini��o da tabela $tableName.
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
	 * Verifica se existe a defini��o para a tabela $tableName.
	 * 
	 * @param string $tableName
	 */
	public function hasTableDefinition($tableName) {
		return $this->getTableDefinition($tableName) != null;
	}
	
	/**
	 * Remove a defini��o da tabela $tableName.
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
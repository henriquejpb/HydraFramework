<?php
/**
 * Classe para manipulação de um SQL SELECT para Db_Table
 * @author henrique
 */
class Db_Table_Select extends Db_Select {
	
	/**
	 * Informações sobre a tabela
	 * @var array
	 */
	protected $_info;
	
	/**
	 * Checar ou não a integridade da tabela.
	 * @var boolean
	 */
	protected $_integrityCheck = true;
	
	/**
	 * A instância da tabela que criou este objeto.
	 * @var Db_Table
	 */
	protected $_table;
	
	/**
	 * Construtor
	 * @param Db_Table $table
	 */
	public function __construct(Db_Table $table) {
		parent::__construct($table->getAdapter());
	}
	
	/**
	 * Retorna o nome da tabela que criou este objeto.
	 * @return Db_Table
	 */
	public function getTable(){
		return $this->_table;
	}
	
	/**
	 * Seta a tabela primária e obtém as informações sobre a tabela.
	 * @param Db_Table $table
	 * @return Db_Table_Select : fluent interface
	 */
	public function setTable(Db_Table $table) {
		$this->_info = $table->info();
		$this->_table = $table;
		
		$this->reset(self::FROM);
		$this->from($table->getName(), $table->info('cols'));
		return $this;
	}
	
	/**
	 * Seta a flag de checagem de integridade.
	 * Se a flag for FALSE, nenhuma checagem é realizada ao se fazer
	 * table joins, permitindo a criação de linhas de tabela 'híbridas'.
	 * 
	 * @param boolean $opt
	 * @return Db_Table_Select : fluent interface
	 */
	public function setIntegrityCheck($opt) {
		$this->_integrityCheck = (bool) $opt;
		return $this;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Db_Select::from()
	 */
	public function from($tableName, $tableCols = array(), $schema = null) {
		if($tableName instanceof Db_Table) {
			$table = $tableName;
			$tableName = $tableName->getName();
			$info = $table->info();
			$name = $info[Db_Table::NAME];
			
			if(isset($info[Db_Table::SCHEMA])) {
				$schema = $info[Db_Table::SCHEMA];
			}
			
			if(empty($tableCols)) {
				$tableCols = $info[Db_Table::COLS];
			}
		}
		
		return $this->innerJoin((string) $tableName, null, $tableCols, $schema);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Db_Select::assemble()
	 */
	public function assemble() {
		$fields  = $this->getPart(self::COLUMNS);
		
		if($this->_info === null) {
			throw new Db_Table_Select_Exception('Nenhuma informação sobre a tabela selecionada!');
		}
		
		$primaryTable = $this->_info[Db_Table::NAME];
		$schema  = $this->_info[Db_Table::SCHEMA];
		
		if(count($this->_parts[self::UNION]) == 0) {
			$from = $this->getPart(self::FROM);
			
			if($this->_integrityCheck !== false) {
				foreach($fields as $columnEntry) {
					$table 		= $columnEntry['tableAlias'];
					$colName 	= $columnEntry['colName'];
					$colAlias 	= $columnEntry['colAlias'];
					
					// Checa cada coluna para assegurar que ela só referencia a tabela primária...
					if($colName && (!isset($from[$table]) || $from[$table]['tableName'] != $primaryTable)) {
						throw new Db_Table_Select_Exception('Não pode haver join com outras tabelas em uma SELECT query!');	
					}
				}
			}
		}
		
		return parent::assemble();
	}
	
	public function isReadOnly() {
		$fields = $this->getPart(self::COLUMNS);
		$cols = $this->_info[Db_Table::COLS];
		
		if(empty($fields)) {
			return false;
		}
		
		foreach($fields as $colEntry) {
			$colName = $colEntry['colName'];
			$colAlias = $colEntry['colAlias'];

			if($colAlias !== null) {
				$colName = $colAlias;
			}
			
			if($colName instanceof Db_Expression || !in_array($colName, $cols)) {
				return true;
			}
		}
		return false;
	}
}
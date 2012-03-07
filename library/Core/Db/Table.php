<?php
/**
 * Interface com uma tabela do banco de dados.
 * @author henrique
 */
class Db_Table {
	const ADAPTER			= 'db';
	const SCHEMA			= 'schema';
	const NAME				= 'name';
	const PRIMARY			= 'primary';
	const COLS				= 'cols';
	const ROW_CLASS			= 'rowClass';
	const ROWSET_CLASS		= 'rowsetClass';
	const REFERENCE_MAP		= 'referenceMap';
	const DEPENDENT_TABLES	= 'dependentTables';
	const SEQUENCE			= 'sequence';
	const DEFAULT_VALUES	= 'defaultValues';

	const METADATA         		 	= 'metadata';
	const METADATA_CACHE_IN_CLASS	= 'metadataCacheInClass';
	const DEFINITION				= 'definition';

	const COLUMNS			= 'columns';
	const REF_TABLE			= 'refTable';
	const REF_COLUMNS		= 'refColumns';
	const ON_DELETE			= 'onDelete';
	const ON_UPDATE			= 'onUpdate';

	const INTEGRITY_CHECK	= 'integrityCheck';
	const CASCADE			= 'cascade';
	const RESTRICT			= 'restrict';
	const SET_NULL			= 'setNull';

	const DEFAULT_NONE		= 'defaultNone';
	const DEFAULT_CLASS		= 'defaultClass';
	const DEFAULT_ADAPTER	= 'defaultAdapter';

	const TABLE_CACHE_DIR	= 'table_metadata';

	/**
	 * Armazena um Db_Adapter padrão para os objetos Db_Table
	 * @var Db_Adapter_Abstract
	 */
	private static $_defaultAdapter;


	/**
	 * Armazena um Db_Adapter para o objeto
	 * @var Db_Adapter_Abstract
	 */
	private $_adapter;

	/**
	 * Armazena o schema da tabela
	 * @var string
	 */
	private $_schema;

	/**
	 * Armazena o nome da tabela
	 * @var string
	 */
	private $_name;

	/**
	 * Armazena as colunas da tabela, obtidas a
	 * partir do método Db_Adapter::describeTable()
	 * @var array
	 */
	private $_cols;

	/**
	 * Armazena a chave primária da tabela
	 * @var mixed
	 */
	private $_primary = null;

	/**
	 * Se a chave primária é composta e uma das colunas
	 * usa auto-incremnto ou sequência-gerada, setamos
	 * $identity para o índice ordinal do campo no
	 * array $_primary. O array $_primary começa em 1.
	 * @var integer
	 */
	private $_identity = 1;


	/**
	 * Define a lógica para novos valores na chave
	 * primária. Pode ser uma string ou booleano.
	 * @var mixed
	 */
	private $_sequence = true;

	/**
	 * Informação fornecida pelo método describeTable() do Adapter
	 * @var array
	 */
	private $_metadata = array();

	/**
	 * Flag: informa se devemos ou não cachear os metadados na classe
	 * @var boolean
	 */
	private $_metadataCacheInClass = true;

	/**
	 * Nome da classe TableRow
	 * @var string
	 */
	private $_rowClass = 'Db_Table_Row';

	/**
	 * Nome da classe TableRowset
	 * @var string
	 */
	private $_rowsetClass = 'Db_Table_Rowset';

	/**
	 * Array associativo contendo informações sobre regras de integridade.
	 * Existe uma entrada para cada chave estrangeira da tabela.
	 * Cada chave é um mnemônico para a regra de referência.
	 *
	 * Cada entrada é um array associativo contendo os seguintes índices:
	 * - columns		=>	array contendo os nomes das colunas na tabela-filha
	 * - refTable	=>	nome da classe na tabela-pai
	 * - refColumns		=>	array de nomes contendo os nomes das colunas na
	 * 						tabela-pai na mesma ordem que no índice 'columns'
	 * - onDelete		=>	"cascade" significa que deletar uma linha na
	 * 						tabela-pai causa uma deleção das linhas
	 * 						referenciadas na tabela-filha
	 * - onUpdate		=>	"cascade" significa que uma atualizar uma linha na
	 * 						tabela-pai c""ausa uma atualização das linhas
	 * 						referenciadas na tabela-filha
	 *
	 * @var array
	 */
	private $_referenceMap = array();

	/**
	 * Array contendo os nomes das tabelas "filhas" da atual, ou seja,
	 * aquelas que contém uma chave estrangeira para esta.
	 * @var array
	 */
	private $_dependentTables = array();

	/**
	 * Se TRUE, é possível configurar os triggers
	 * ON DELETE e ON UPDATE da tabela.
	 * Se FALSE, esses eventos serão ignorados.
	 *
	 * @var boolean
	 */
	private $_integrityCheck = false;

	/**
	 * Informa onde valores-padrão da tabela são encontrados
	 * @var string
	 */
	private $_defaultSource = self::DEFAULT_NONE;

	/**
	 * Armazena os valores pardrão para as coluans da tabela
	 * @var array
	 */
	private $_defaultValues = array();
	
	/**
	 * A definição da tabela.
	 * @var Db_Table_Definition
	 */
	private $_definition;
	
	/**
	 * A definição padrão para os objetos Db_Table
	 * @var Db_Table_Definition
	 */
	private static $_defaultDefinition;

	/**
	 * Construtor.
	 *
	 * Parâmetros de configuração:
	 * - db				 =>	instância de Db_Adapter
	 * - name			 =>	o nome da tabela
	 * - schema			 =>	o schema da tabela
	 * - primary		 =>	a chave primária da tabela (string | array)
	 * - rowClass		 =>	nome da classe TableRow
	 * - rowsetClass	 =>	nome da classe TableRowset
	 * - referenceMap	 =>	declaração das relações de integridade da tabela
	 * - dependentTables =>	array de tabelas-filhas
	 * - metadataCache	 =>	cache dos metadados
	 * - integrityCheck	 => se o objeto deve ou não verificar a integridade
	 * 						da tabela em remoções e atualizações.
	 *
	 * @param mixed $config : array de configurações, nome da tabela ou somente um Db_Adapter
	 */
	public function __construct($config = array()) {
		if($config instanceof Db_Adapter_Abstract) {
			$config = array(self::ADAPTER => $config);
		} else if(is_string($config)) {
			$config = array(self::NAME => $config);
		}

		if($config) {
			$this->setOptions($config);
		}
		
		$this->_setup();
		$this->init();
	}

	/**
	 * Seta opções de configuração da tabela
	 * @param array $options
	 * @return Db_Table : fluent interface
	 */
	public function setOptions(array $options) {
		foreach($options as $key => $value) {
			switch($key) {
				case self::ADAPTER:
					$this->_setAdapter($value);
					break;
				case self::SCHEMA:
					$this->_schema = (string) $value;
					break;
				case self::NAME:
					$this->_name = (string) $value;
					break;
				case self::PRIMARY:
					$this->_primary = (array) $primary;
					break;
				case self::DEFAULT_VALUES:
					$this->setDefaultValues((array) $value);
					break;
				case self::ROW_CLASS:
					$this->_rowClass = (string) $value;
					break;
				case self::ROWSET_CLASS:
					$this->_rowsetClass = (string) $value;
					break;
				case self::REFERENCE_MAP:
					$this->setReferences($value);
					break;
				case self::DEPENDENT_TABLES:
					$this->setDependentTables($value);
					break;
				case self::METADATA_CACHE_IN_CLASS:
					$this->setMetadataCacheInClass((bool) $value);
					break;
				case self::SEQUENCE:
					$this->_setSequence($value);
					break;
				case self::INTEGRITY_CHECK:
					$this->setIntegrityCheck((bool) $value);
				case self::DEFINITION:
					$this->setDefinition($value);
			}
		}

		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getName() {
		return $this->_name;
	}
	
	/**
	 * @return string
	 */
	public function getSchema() {
		return $this->_schema;
	}

	/**
	 * @param  string $classname
	 * @return Db_Table: fluent interface
	 */
	public function setRowClass($classname)	{
		$this->_rowClass = (string) $classname;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getRowClass() {
		return $this->_rowClass;
	}

	/**
	 * @param  string $classname
	 * @return Db_Table: fluent interface
	 */
	public function setRowsetClass($classname)	{
		$this->_rowClass = (string) $classname;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getRowsetClass() {
		return $this->_rowsetClass;
	}

	/**
	 * Adiciona uma referência para a tabela.
	 *
	 * @param string $ruleKey
	 * @param string|array $columns
	 * @param string $refTable
	 * @param string|array $refColumns
	 * @param string $onDelete
	 * @param string $onUpdate
	 * @return Db_Table : fluent interface
	 */
	public function addReference($ruleKey, $columns, $refTable, $refColumns, $onDelete = null, $onUpdate = null) {
		$reference = array(
		self::COLUMNS 		=>	(array) $columns,
		self::REF_TABLE		=>	$refTable,
		self::REF_COLUMNS	=>	$refColumns
		);

		if($onDelete != null) {
			$reference[self::ON_DELETE] = $onDelete;
		}

		if($onUpdate != null) {
			$reference[self::ON_UPDATE] = $onUpdate;
		}
		
		$this->_referenceMap[$ruleKey] = $reference;

		return $this;
	}

	/**
	 * @param array $references : array de referências para a tabela atual
	 * @see $_referenceMap
	 */
	public function setReferences(array $references) {
		$this->_referenceMap = $references;
		return $this;
	}

	/**
	 * Retorna uma referência desta tabela para a tabela $table.
	 * Se houver mais de uma dependência para a tabela $table,
	 * deve-se informar qual o nome da regra de referência.
	 * Caso contrário, sempre será retornada a primeira
	 * referência àquela tabela.
	 *
	 * @param string $table
	 * @param string $ruleKey
	 * @return array
	 * @throws Db_Table_Exception
	 */
	public function getReference($table, $ruleKey = null) {
		$refMap = $this->_getReferenceMapNormalized();

		if($ruleKey !== null) {
			if(!isset($refMap[$ruleKey])) {
				throw new Db_Table_Exception(sprintf('Nenhuma referência sob o nome "%s" da tabela "%s" para a tabela "%s".', $ruleKey, $this->_name, $table));
			}
			if($refMap[$ruleKey][self::REF_TABLE] != $table) {
				throw new Db_Table_Exception(sprintf('A regra de referência "%s" não referencia a tabela "%s".', $ruleKey, $table));
			}
			return $refMap[$ruleKey];
		}

		foreach($refMap as $reference) {
			if($reference[self::REF_TABLE] == $table) {
				return $reference;
			}
		}
		throw new Db_Table_Exception(sprintf('Não há referência da tabela "%s" para a tabela "%s".', $this->_name, $table));
	}

	/**
	 * Adiciona uma tabela dependente desta.
	 * 
	 * @param string $table : o nome da tabela dependente
	 * @return Db_Table : fluent interface
	 */
	public function addDependentTable($table) {
		if(!in_array($table, $this->_dependentTables)) {
			$this->_dependentTables[] = $table;
		}
		return $this;
	}

	/**
	 * @param array $depTabless
	 */
	public function setDependentTables(array $depTables) {
		$this->_dependentTables = $depTables;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getDependentTables() {
		return $this->_dependentTables;
	}

	/**
	 * Seta a fonte de valores-padrão para as colunas da tabela.
	 * 
	 * @param string $source
	 * @return Db_Table : fluent interface
	 */
	public function setDefaultSource($source) {
		switch($source) {
			case self::DEFAULT_ADAPTER:
			case self::DEFAULT_CLASS:
				$this->_defaultSource = $source;
				break;
			case self::DEFAULT_NONE:
			default:
				$this->_defaultSource = self::DEFAULT_NONE;
				break;
		}
		return $this;
	}

	/**
	 * Seta os valores-padrão para as colunas da tabela.
	 * 
	 * @param array $defaultValues
	 * @return Db_Table : fluent interface
	 */
	public function setDefaultValues(array $defaultValues) {
		foreach($defaultValues as $name => $value) {
			if(isset($this->_metadata[$name])) {
				$this->_defaultValues[$name] = $value;
			}
		}
		return $this;
	}

	/**
	 * Retorna os valores-padrão para as colunas da tabela.
	 * 
	 * @return array
	 */
	public function getDefaultValues() {
		return $this->_defaultValues;
	}

	/**
	 * Seta a checagem de integridade de dependências.
	 * 
	 * @param boolean $opt
	 * @return Db_Table : fluent interface
	 */
	public function setIntegrityCheck($opt) {
		$this->_integrityCheck = (bool) $opt;
		return $this;
	}

	/**
	 * Retorna a configuração da checagem de integridade.
	 * 
	 * @return boolean
	 */
	public function getIntegrityCheck() {
		return $this->_integrityCheck;
	}
	
	/**
	 * Seta um adapter padrão para todos os objetos Db_Table.
	 * 
	 * @param Db_Adapter_Abstract $adapter : o adapter
	 * @return void
	 */
	public static function setDefaultAdapter(Db_Adapter_Abstract $adapter) {
		self::$_defaultAdapter = $adapter;
	}

	/**
	 * Retorna o adapter padrão para todos os objetos Db_Table.
	 * 
	 * @return Db_Adapter_Abstract
	 */
	public static function getDefaultAdapter() {
		return self::$_defaultAdapter;
	}

	/**
	 * Seta um adapter para este objeto.
	 * 
	 * @param mixed $adapter : string ou Db_AdapterAbstract
	 * @return Db_Table : fluent interface
	 */
	private function _setAdapter(Db_Adapter_Abstract $adapter) {
		$this->_adapter = $adapter;
		return $this;
	}

	/**
	 * Retorna o adapter deste objeto.
	 * 
	 * @return Db_Adapter_Abstract
	 */
	public function getAdapter() {
		return $this->_adapter;
	}
	
	/**
	 * Seta a definição da tabela.
	 *
	 * @param Db_Table_Definition $definition
	 * @return Db_Table : fluent interface
	 */
	public function setDefinition(Db_Table_Definition $definition) {
		$this->_definition = $definition;
		return $this;
	}
	
	/**
	 * Retorna a definição da tabela.
	 *
	 * @return Db_Table_Definition
	 */
	public function getDefinition() {
		return $this->_definition;
	}
	
	/**
	 * Seta uma definição padrão para todos os objetos Db_Table.
	 * Cada objeto Db_Table_Definition pode armazenar definições de várias tabelas.
	 * 
	 * @param Db_Table_Definition $definition
	 * @return void
	 */
	public static function setDefaultDefinition(Db_Table_Definition $definition){
		self::$_defaultDefinition = $definition;
	}
	
	/**
	 * Retorna a definição padrão dos objetos Db_Table.
	 * 
	 * @return Db_Table_Definition
	 */
	public static function getDefaultDefinition() {
		return self::$_defaultDefinition;
	}

	/**
	 * Seta o atributo $_sequence, que define como uma nova
	 * chave primária deve ser gerada.
	 *
	 * - Se for uma string, então a string nomeia a sequência
	 * - Se for TRUE, utiliza auto-incremento ou algum mecanismo
	 *   de identidade
	 * - se for FALSE, então a chave é definida pelo usuário
	 *
	 * @param mixed $sequence
	 * @return void
	 */
	private function _setSequence($sequence) {
		$this->_sequence = $sequence;
	}

	/**
	 * Inicialização da tabela.
	 *
	 * @return void
	 */
	private function _setup() {
		$this->_setupDefinition();
		$this->_setupDatabaseAdapter();
		$this->_setupTableName();
	}

	/**
	 * Inicializa o adapter de conexão com o banco de dados.
	 *
	 * @return void
	 * @throws Db_Table_Exception
	 */
	private function _setupDatabaseAdapter() {
		if(!$this->_adapter) {
			$this->_adapter = self::getDefaultAdapter();
			if(!$this->_adapter instanceof Db_Adapter_Abstract) {
				throw new Db_Table_Exception('Nenhum adapter encontrado para ' . get_class($this));
			}
		}
	}

	/**
	 * Inicializa o nome da tabela.
	 *
	 * @return void
	 */
	private function _setupTableName() {
		if (!$this->_name) {
			$this->_name = get_class($this);
		} else if (strpos($this->_name, '.')) {
			list($this->_schema, $this->_name) = explode('.', $this->_name);
		}
	}
	
	/**
	 * Se nenhuma definição foi informada, utilizamos a definição padrão.
	 * 
	 * @return void.
	 */
	private function _setupDefinition() {
		if(!$this->_definition) {
			$this->_definition = self::getDefaultDefinition();
		}
		
		if($this->_definition instanceof Db_Table_Definition) {
			$options = $this->_definition->getTableDefinition($this->_name);
			if(is_array($options)) {
				$this->setOptions($options);
			}
		}
	}

	/**
	 * Inicializa os metadados da tabela.
	 *
	 * Se os metadados não puderem ser carregados do cache, o método
	 * describeTable() do adapter é chamado para buscar essa informação.
	 * Retorna true se e somente se os metadados forem carregados do cache
	 *
	 * @return boolean
	 * @throws Db_Table_Exception
	 */
	private function _setupMetadata() {
		if($this->isMetadataCacheInClass() && (count($this->_metadata) > 0)) {
			return true;
		}
		
		$cacheName = $this->_getCacheName();
		try {
			$cacheContents = Cache::get(self::TABLE_CACHE_DIR, $cacheName);
		} catch (Exception $e) {
			$cacheContents = null;
		}

		//Se o cache não existe...
		if($cacheContents === null) {
			$isMetadataFromCache = false;
			$this->_metadata = $this->_adapter->describeTable($this->_name);
			try {
				Cache::set(self::TABLE_CACHE_DIR, $cacheName, $this->_metadata, '+ 1 YEAR');
			} catch(Cache_Exception $e) {
				trigger_error(sprintf('Impossível salvar o arquivo de cache de metadados da tabela  "%s"', $this->_name), E_USER_NOTICE);
			} catch (Exception $e) {
				// Uma exceção do tipo Exception é lançada quando o cache está desabilitado.
			}
		} else {
			$this->_metadata = $cacheContents;
			$isMetadataFromCache = true;
		}

		return $isMetadataFromCache;
	}

	/**
	 * Retorna o nome do arquivo de cache da tabela.
	 *
	 * @return string
	 */
	private function _getCacheName() {
		$dbConfig = $this->_adapter->getConfig();

		$port = isset($dbConfig['port']) ? ':'.$dbConfig['port'] : null;

		$host = isset($dbConfig['host']) ? ':'.$dbConfig['host'] : null;

		// port:host/dbname:schema.table
		$cacheName = md5(
			$port . $host . '/'. $dbConfig['dbname'] . ':'
			. $this->_schema. '.' . $this->_name
		);

		return $cacheName;
	}

	/**
	 * Retorna se deve ser feito o cache dos metadados na classe.
	 *
	 * @return boolean
	 */
	public function isMetadataCacheInClass() {
		return $this->_metadataCacheInClass;
	}

	/**
	 * Retorna as colunas da tabela.
	 *
	 * @return array
	 */
	private function _getCols() {
		if($this->_cols === null) {
			$this->_setupMetadata();
			$this->_cols = array_keys($this->_metadata);
		}
		return $this->_cols;
	}

	/**
	 * Busca e configura a chave primária da tabela.
	 *
	 * @return void
	 * @throws Db_Table_Exception
	 */
	private function _setupPrimaryKey() {
		if(!$this->_primary) {
			$this->_setupMetadata();
			$this->_primary = array();
			foreach($this->_metadata as $col) {
				if($col['PRIMARY']) {
					$this->_primary[$col['PRIMARY_POSITION']] = $col['COLUMN_NAME'];
					if($col['IDENTITY']) {
						$this->_identity = $col['PRIMARY_POSITION'];
					}
				}
			}

			if(empty($this->_primary)) {
				throw new Db_Table_Exception(sprintf('Uma tabela deve conter uma chave primária, mas nenhuma foi encontrada em "%s"', $this->_name));
			}
		} else if(!is_array($this->_primary)) {
			$this->_primary = array(1 => $this->_primary);
		} else if(isset($this->_primary[0])) {
			array_unshift($this->_primary, null);
			unset($this->_primary[0]);
		}

		$cols = $this->_getCols();
		if(!array_intersect((array) $this->_primary, $cols) == (array) $this->_primary) {
			throw new Db_Table_Exception("As chaves primárias ("
			. implode(',', (array) $this->_primary)
			. ") não são colunas na tabela " . $this->_name . "("
			. implode(',', $cols)
			. ")");
		}

		try {
			if(class_exists('Db_Adapter_Pdo_Pgsql')) {
				$primary = (array) $this->_primary;
				$pkIdentity = $primary[(int) $this->_identity];
	
				/**
				 * Caso especial para PostgreSQL: uma chave SERIAL implícita usa
				 * um objeto-sequência cujo nome é "<table>_<column>_seq".
				 */
				if ($this->_sequence === true && $this->_adapter instanceof Db_Adapter_Pdo_Pgsql) {
					$this->_sequence = $this->_adapter->quoteIdentifier("{$this->_name}_{$pkIdentity}_seq");
					if ($this->_schema) {
						$this->_sequence = $this->_adapter->quoteIdentifier($this->_schema) . '.' . $this->_sequence;
					}
				}
			}
		} catch(Exception $e) {
			
		}
	}

	/**
	 * Normaliza o array de referências.
	 *
	 * @return array
	 */
	private function _getReferenceMapNormalized() {
		$refMapN = array();

		foreach($this->_referenceMap as $rule => $map) {
			$refMapN[$rule] = array();
				
			foreach($map as $key => $value) {
				switch($key) {
					case self::COLUMNS:
					case self::REF_COLUMNS:
						if(!is_array($value)) {
							$value = array($value);
						}
						break;
				}

				$refMapN[$rule][$key] = $value;
			}
		}

		return $refMapN;
	}

	/**
	 * Retorna as informações da tabela.
	 *
	 * @param string|null $key : qual informação retornar ou NULL
	 * @return mixed : array ou string
	 */
	public function info($key = null) {
		$this->_setupPrimaryKey();

		$info = array(
			self::SCHEMA           => $this->_schema,
			self::NAME             => $this->_name,
			self::COLS             => $this->_getCols(),
			self::PRIMARY          => (array) $this->_primary,
			self::METADATA         => $this->_metadata,
			self::ROW_CLASS        => $this->getRowClass(),
			self::ROWSET_CLASS     => $this->getRowsetClass(),
			self::REFERENCE_MAP    => $this->_referenceMap,
			self::DEPENDENT_TABLES => $this->_dependentTables,
			self::SEQUENCE         => $this->_sequence
		);

		if ($key === null) {
			return $info;
		}

		if (!array_key_exists($key, $info)) {
			return null;
		}

		return $info[$key];
	}

	/**
	 * Lógica de inicialização da tabela.
	 * Deve ser implementado por possíveis classes-filhas.
	 */
	public function init() {

	}

	/**
	 * Cria e retorna uma instâncida de Db_Table_Select;
	 * @param mixed $cols : array ou string
	 * @return Db_Table_Select
	 */
	public function select($cols = array()) {
		$select = new Db_Table_Select($this);
		$cols = empty($cols) || is_array($cols) ? $cols : array($cols);
		$select->setTable($this);
		if(!empty($cols)) {
			$select->columns($cols, $this->_name);
		}
		return $select;
	}

	/**
	 * Insere uma nova linha.
	 *
	 * @param array $data : valores para inserir (pares coluna => valor)
	 * @return mixed : a chave primária da linha inserida
	 */
	public function insert(array $data) {
		$this->_setupPrimaryKey();

		$primary = (array) $this->_primary;
		$pkIdentity = $primary[(int) $this->_identity == 0 ? 1 : $this->_identity];

		$pkSuppliedBySequence = false;
		if(is_string($this->_sequence) && !isset($data[$pkIdentity])) {
			$data[$pkIdentity] = $this->_adapter->nextSequenceId();
			$pkSuppliedBySequence = true;
		}

		if($pkSuppliedBySequence === false && isset($data[$pkIdentity])) {
			$pkValue = $data[$pkIdentity];
			if(empty($pkValue) || is_bool($pkValue)) {
				unset($data[$pkIdentity]);
			}
		}

		$tableSpec = $this->_getTableSpec();
		$this->_adapter->insert($tableSpec, $data);

		// Busca o último id inserido na tabela que foi gerado por
		// auto-incremento, a menos que seja especificado um valor
		// sobrescrevendo o valor do auto-incremento
		if($this->_sequence === true && !isset($data[$pkIdentity])) {
			$data[$pkIdentity] = $this->_adapter->lastInsertId();
		}

		$pkData = array_intersect($data, array_flip($primary));

		//Se a chave primária não é composta, retorna o próprio valor
		if(count($pkData) == 1) {
			reset($pkData);
			return current($pkData);
		}

		return $pkData;
	}

	/**
	 * Verifica se a coluna $column é identidade da tabela.
	 *
	 * @param string $column
	 */
	public function isIdentity($column) {
		$this->_setupPrimaryKey();
		if(!isset($this->_metadata[$column])) {
			throw new Db_Table_Exception(sprintf('Coluna "%s" não encontrada na tabela "%s".', $column, $this->_name));
		}

		return (bool) $this->_metadata[$column]['IDENTITY'];
	}

	/**
	 * Atualiza as linhas da tabela que satifazem a condição $cond.
	 *
	 * @param array $data : os dados para atualização
	 * @param array|string $cond : a condição para atualização
	 * @return integer : o número de linhas afetadas
	 */
	public function update(array $data, $cond) {
		$rowsAffected = 0;
		$tableSpec = $this->_getTableSpec();

		if($this->_integrityCheck === true && !empty($this->_dependentTables)) {
			$select = new Db_Table_Select($this);
			$oldData = (array) $this->_adapter->fetchAll($select);
			$pk = $this->info('PRIMARY');
				
			$oldPkData = array_intersect($oldData, array_flip($pk));
			$newPkData = array_intersect($data, array_flip($pk));
				
			foreach($this->_dependentTables as $depTable) {
				$childTable = new self(array(
					self::ADAPTER	=>	$this->_adapter,
					self::NAME		=>	$depTable,
					self::SCHEMA	=>	$this->_schema
				));
				$rowsAffected += $childTable->_cascadeUpdate($tableSpec, $oldPkData, $newPkData);
			}
		}

		$ret += $this->_adapter->update($tableSpec, $data, $cond);

		return $ret;
	}

	/**
	 * Chamado pela tabela-pai durante o método save().
	 *
	 * @param string $parentTableName
	 * @param array $oldPrimaryKey
	 * @param array $newPrimaryKey
	 * @return int : o número de linhas afetadas
	 */
	private function _cascadeUpdate($parentTableName, array $oldPrimaryKey, array $newPrimaryKey) {
		$this->_setupMetadata();
		$rowsAffected = 0;
		foreach($this->_getReferenceMapNormalized() as $map) {
			if($map[self::REF_TABLE] == $parentTableName && isset($map[self::ON_UPDATE])) {
				switch($map[self::ON_UPDATE]) {
					case self::CASCADE:
						$newRefs = array();
						$cond = array();
						for($i = 0; $i < count($map[self::COLUMNS]); $i++) {
							$col = $map[self::COLUMNS][$i];
							$refCol = $map[self::REF_COLUMNS][$i];
								
							if(isset($newPrimaryKey[$refCol])) {
								$newRefs[$col] = $newPrimaryKey[$refCol];
							}
								
							$type = $this->_metadata[$col]['DATA_TYPE'];
							$cond[] = $this->_adapter->quoteInto(
								$this->_adapter->quoteIdentifier($col) . ' = ?',
								$oldPrimaryKey[$refCol],
								$type
							);
						}
						$rowsAffected += $this->update($newRefs, $cond);
						break;

					default:
						break;
				}
			}
		}
		return $rowsAffected;
	}

	/**
	 * Remove as linhas da tabela que satisfaçam $cond.
	 *
	 * @param string $cond
	 * @return integer : o número de linhas removidas
	 */
	public function delete($cond) {
		$rowAffected = 0;
		$tableSpec = $this->_getTableSpec();

		if($this->_integrityCheck === true && !empty($this->_dependentTables)) {
			$select = new Db_Table_Select($this);
			$data = (array) $this->_adapter->fetchAll($select);
			$pk = $this->info('PRIMARY');

			$pkData = array_intersect($data, array_flip($pk));
			foreach($this->_dependentTables as $depTable) {
				$childTable = new self(array(
				self::ADAPTER	=>	$this->_adapter,
				self::NAME		=>	$depTable,
				self::SCHEMA	=>	$this->_schema
				));
				$rowsAffected += $childTable->_cascadeDelete($tableSpec, $pkData);
			}
		}

		$rowsAffected += $this->_adapter->delete($tableSpec, $cond);
		return $rowsAffected;
	}

	/**
	 * Chamado pela tabela-pai durante o método delete().
	 *
	 * @param string $parentTableName : o nome da tabela-pai
	 * @param array $primaryKey : a chave primária da linha deletada
	 * @return integer : o número de linhas removidas
	 */
	private function _cascadeDelete($parentTableName, array $primaryKey) {
		$this->_setupMetadata();
		$rowsAffected = 0;

		foreach($this->_getReferenceMapNormalized() as $map) {
			if($map[self::REF_TABLE] == $parentTableName && isset($map[self::ON_DELETE])) {
				switch($map[self::ON_DELETE]) {
					case self::CASCADE:
						$cond = array();
						for($i = 0; $i < count($map[self::COLUMNS]); $i++) {
							$col = $map[self::COLUMNS][$i];
							$refCol = $map[self::REF_COLUMNS][$i];
							$type = $this->_metadata[$col]['DATA_TYPE'];
								
							$cond[] = $this->_adapter->quoteInto(
								$this->_adapter->quoteIdentifier($col) . ' = ?',
								$primaryKey[$refCol],
								$type
							);
						}
						$rowsAffected += $this->delete($cond);
						break;
					default:
						break;
				}
			}
		}
		return $rowsAffected;
	}

	/**
	 * Retorna uma string no formato <schema>.<table_name> se o schema
	 * estiver setado ou no formato <table_name> caso contrário.
	 *
	 * @return string
	 */
	private function _getTableSpec() {
		return ($this->_schema ? $this->_schema . '.' : '') . $this->_name;
	}

	/**
	 * Busca linhas pela chave primária.
	 * Caso a chave seja composta, o argumento deve ser um array
	 * contendo o mesmo número de elementos que a chave e na mesma ordem.
	 *
	 * @param mixed $pk : a chave primária do registro a ser buscado
	 * @return Db_Table_Row
	 */
	public function getById($pk) {
		$this->_setupPrimaryKey();
		$keyNames = (array) $this->_primary;

		if(!is_array($pk)) {
			$pk = array($pk);
		}

		if(($n = count($pk)) != ($m = count($keyNames))) {
			throw new Db_Table_Exception(sprintf('A chave primária da tabela "%s" é composta por %d colunas.
														Foram passados %d valores para buscar.', $this->_name, $m, $n));
		}
		
		$condList = array();
		$numberTerms = 0;
		foreach($pk as $val) {
			$pos = key($keyNames);
			$col = current($keyNames);
			array_shift($keyNames);

			$type = $this->_metadata[$col]['DATA_TYPE'];
			$colName = $this->_adapter->quoteIdentifier($col);

			$condList[] = $this->_adapter->quoteInto($colName . ' = ?', $val, $type);
		}

		$cond = join(' AND ', $condList);
		return $this->fetchRow($cond);
	}

	/**
	 * Busca todas as linhas da tabela que satisfaçam os critérios.
	 * 
	 * @param string|array|Db_Select $where
	 * @param string|array $order
	 * @param int $count
	 * @param int $offset
	 * @return Db_Table_Rowset
	 */
	public function fetchAll($where = null, $order = null, $count = null, $offset = null) {
		if($where instanceof Db_Select) {
			$select = $where;
		} else {
			$select = $this->select();
				
			if($where !== null) {
				$this->_where($select, $where);
			}
				
			if($order !== null) {
				$this->_order($select, $order);
			}
				
			if ($count !== null || $offset !== null) {
				$select->limit($count, $offset);
			}			
		}
		
		$rows = $this->_fetch($select);
		$readOnly = $select instanceof Db_Table_Select ? $select->isReadOnly() : false;
		
		$data = array(
			'table'		=>	$this,
			'data'		=>	$rows,
			'readOnly'	=>	$readOnly,
			'rowClass'	=>	$this->getRowClass(),
			'stored'	=>	true
		);
		
		$rowsetClass = $this->getRowsetClass();
		return new $rowsetClass($data);
	}
	
	/**
	 * Busca uma linha na tabela que satisfaçãm os critérios.
	 *  
	 * @param string|array|Db_Select $where
	 * @param string|array $order
	 * @param int $offset
	 * @return Db_Table_Row|null : retorna a linha da tabela ou null caso não haja nenhuma.
	 */
	public function fetchRow($where = null, $order = null, $offset = null) {
		if($where instanceof Db_Select) {
			$select = $where->limit(1, $where->getPart(Db_Select::LIMIT_OFFSET));
		} else {
			$select = $this->select();
				
			if($where !== null) {
				$this->_where($select, $where);
			}
				
			if($order !== null) {
				$this->_order($select, $order);
			}
				
			$select->limit(1, (int) $offset);
		}
		
		$rows = $this->_fetch($select);
		if(empty($rows)) {
			return null;
		}
		
		$readOnly = $select instanceof Db_Table_Select ? $select->isReadOnly() : false;
		$data = array(
			'table'		=>	$this,
			'data'		=>	reset($rows),
			'readOnly'	=>	$readOnly
		);
		
		$rowClass = $this->getRowClass();
		return new $rowClass($data);
	}
	
	/**
	 * Cria uma nova linha para a tabela.
	 *  
	 * @param array $data : os dados para popular a nova linha
	 * @param string $defaultSource : fonte dos valores padrão para as colunas
	 */
	public function createRow(array $data = array(), $defaultSource = null) {
		$cols = $this->_getCols();
		$defaults = array_combine($cols, array_fill(0, count($cols), null));
		
		if($defaultSource === null) {
			$defaultSource = $this->_defaultSource;
		}
		
		//TODO: verificar
		if($defaultSource == self::DEFAULT_ADAPTER) {
			$this->_setupMetadata();
			foreach($this->_metadata as $key => $data) {
				if($data['DEFAULT'] != null) {
					$defaults[$key] = $data['DEFAULT'];
				}
			}
		} else if($defaultSource == self::DEFAULT_CLASS && !empty($this->_defaultValues)) {
			foreach($this->_defaultValues as $key => $val) {
				if(isset($defaults[$key])) {
					$defaults[$key] = $val;
				}
			}
		}
		
		$config = array(
			'table'		=>	$this,
			'data'		=>	$defaults,
			'readOnly'	=>	false,
			'stored'	=>	false
		);
		
		$rowClass = $this->getRowClass();
		$row = new $rowClass($config);
		$row->setFromArray($data);
		
		return $row;
	}
	
	/**
	 * Gera uma cláusula WHERE a partir de um array ou string.
	 * 
	 * @param Db_Select $select
	 * @param string|array $where
	 * @return Db_Select
	 */
	private function _where(Db_Select $select, $where) {
		$where = (array) $where;
		
		foreach($where as $key => $val) {
			if(is_int($key)) {
				// $val é a condição por si só...
				$select->where($val);
			} else {
				// $key é a condição com um placeholder
				// $val é o valor para ser quotado dentro de $key
				$select->where($key, $val);
			}
		}
		
		return $select;
	}
	
	/**
	 * Gera uma cláusula ORDER a partir de um array ou string.
	 * 
	 * @param Db_Select $select
	 * @param array|string $order
	 * @return Db_Select
	 */
	private function _order(Db_Select $select, $order) {
		$order = (array) $order;
		
		foreach($order as $val) {
			$select->order($val);
		}
		
		return $select;
	}
	
	/**
	 * Método de apoio para busca de linhas.
	 * 
	 * @param Db_Select $select
	 * @return array
	 */
	private function _fetch(Db_Select $select) {
		$stmt = $this->_adapter->query($select);
		$data = $stmt->fetchAll(Db::FETCH_ASSOC);
		return $data;
	}
	
	/**
	 * Converte a tabela em uma string, retornando o seu nome.
	 * 
	 * @return string
	 */
	public function __toString() {
		return $this->_name;
	}
}
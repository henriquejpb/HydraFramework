<?php
/**
 * Emula SQL Statements para Adapters que nуo possuem suporte a eles
 * @author henrique
 */
interface Db_Statement_Interface {
	
	/**
	 * Associa uma coluna do conjunto de resultados do statement a uma variсvel PHP
	 * 
	 * @param string $column : nome da coluna no conjunto de resultados (posicional ou nominal)
	 * @param mixed $param : a variсvel PHP contendo o valor
	 * @param mixed $type [OPCIONAL] : o tipo da variсvel
	 * @return Db_Statement_Interface : Fluent Interface
	 * @throws Db_Statement_Exception
	 */
	public function bindColumn($column, $param, $type = null);
	
	/**
	 * Associa um valor a um parтmetro
	 * 
	 * @param integer|string $parameter : o nome do parтmetro
	 * @param mixed $variable : variсvel PHP contendo o valor
	 * @param mixed $type [OPCIONAL] : o tipo do parтmetro SQL
	 * @param mixed $length [OPCIONAL] : o tamanho do parтmetro SQL
	 * @return Db_Statement_Interfacae : Fluent Interface
	 * @throws Db_Statement_Exception
	 */
	public function bindParam($parameter, $variable, $type = null, $length = null);
	
	/**
	 * Associa um valor a um parтmetro
	 * 
	 * @param string|integer $parameter : o nome do parтmetro
	 * @param mixed $value : o valor (escalar) para associaчуo
	 * @param mixed $type [OPCIONAL] : o tipo do parтmetro
	 * @return Db_Statement_Interface : Fluent Interface
	 * @throws Db_Statement_Exception
	 */
	public function bindValue($parameter, $value, $type = null);
	
	/**
	 * Fecha o cursor, permitindo que o statement possa ser executado novamente
	 * 
	 * @return Db_Statement_Interface : Fluent Interface
	 * @throws Db_Statement_Exception
	 */
	public function closeCursor();

	/**
	 * Retorna o nњmero de colunas no conjunto de resultados ou null se nуo houver metadados
	 * 
	 * @return integer|null
	 * @throws Db_Statement_Exception
	 */
	public function columnCount();
	
	/**
	 * Retorna o cѓdigo de erro da execuчуo do statement, se houver
	 * 
	 * @return string
	 */
	public function errorCode();
	
	/**
	 * Retorna a informaчуo do erro ocorrido na execuчуo, se houver
	 * 
	 * @return array;
	 */
	public function errorInfo();
	
	/**
	 * Executa o statement
	 * 
	 * @param array $params : parтmetros para a execuчуo
	 * @return bool
	 * @throws Db_Statement_Exception
	 */
	public function execute(array $params = array());
	
	/**
	 * Busca uma linha no conjunto de resultados da execuчуo
	 * 
	 * @param Db::FETCH_* $mode [OPCIONAL] : modo de busca
	 * @return mixed : array, objeto ou escalar, dependendo de $mode
	 * @throws Db_Statement_Exception
	 */
	public function fetchRow($mode = null, $col = null);
	
	/**
	 * Retorna todas as linhas do conjunto de resultados da execuчуo
	 * 
	 * @param Db::FETCH_* $mode [OPCIONAL] : modo de busca
	 * @param mixed $col [OPCIONAL] : a coluna a ser retornada
	 * @throws Db_Statement_Exception
	 * @return array : conjunto de linhas, cada uma no formato $mode
	 */
	public function fetchAll($mode = null, $col = null);
	
	/**
	 * Retorna uma coluna do conjunto de resultados da execuчуo
	 * 
	 * @param string|integer $col : o nome da coluna a ser retornada ou sua posiчуo
	 * @return mixed : o valor da primeira coluna da linha buscada
	 * @throws Db_Statement_Exception
	 */
	public function fetchColumn($col = 0);
	
	/**
	 * Busca uma linha no conjunto de resultados e a retorna como um objeto $class
	 * 
	 * @param className $class [OPCIONAL] : o nome da classe (padrуo = stdClass)
	 * @param array $config [OPCIONAL] : possэ­veis configuraчѕes para o objeto $class
	 * @return object
	 * @throws Db_Statement_Exception
	 */
	public function fetchObject($class = 'stdClass', array $config = array());
	
	/**
	 * Retorna o prѓximo conjunto de linhas de uma execuчуo que resultou em vсrios conjuntos de linha
	 * 
	 * @return mixed
	 * @throws Db_Statement_Exception
	 */
	public function nextRowset();
	
	/**
	 * Retorna o nњmero de linhas afetadas pela њltima operaчуo
	 * INSERT, UPDATE ou DELETE executada
	 * 
	 * @return int
	 */
	public function rowCount();
	
	/**
	 * Seta o modo de busca
	 * @param Db::FETCH_* $mode
	 */
	public function setFetchMode($mode);
}
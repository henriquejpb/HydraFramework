<?php
/**
 * Emula SQL Statements para Adapters que não possuem suporte a eles
 * @author <a href="mailto:rick.hjpbacelos@gmail.com">Henrique Barcelos</a>
 */
interface Db_Statement_Interface {
	
	/**
	 * Associa uma coluna do conjunto de resultados do statement a uma variável PHP
	 * 
	 * @param string $column : nome da coluna no conjunto de resultados (posicional ou nominal)
	 * @param mixed $param : a variável PHP contendo o valor
	 * @param mixed $type [OPCIONAL] : o tipo da variável
	 * @return Db_Statement_Interface : Fluent Interface
	 * @throws Db_Statement_Exception
	 */
	public function bindColumn($column, &$param, $type = null);
	
	/**
	 * Associa um valor a um parâmetro
	 * 
	 * @param integer|string $parameter : o nome do parâmetro
	 * @param mixed $variable : variável PHP contendo o valor
	 * @param mixed $type [OPCIONAL] : o tipo do parâmetro SQL
	 * @param mixed $length [OPCIONAL] : o tamanho do parâmetro SQL
	 * @return Db_Statement_Interfacae : Fluent Interface
	 * @throws Db_Statement_Exception
	 */
	public function bindParam($parameter, &$variable, $type = null, $length = null);
	
	/**
	 * Associa um valor a um parâmetro
	 * 
	 * @param string|integer $parameter : o nome do parâmetro
	 * @param mixed $value : o valor (escalar) para associação
	 * @param mixed $type [OPCIONAL] : o tipo do parâmetro
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
	 * Retorna o número de colunas no conjunto de resultados ou null se não houver metadados
	 * 
	 * @return integer|null
	 * @throws Db_Statement_Exception
	 */
	public function columnCount();
	
	/**
	 * Retorna o código de erro da execução do statement, se houver
	 * 
	 * @return string
	 */
	public function errorCode();
	
	/**
	 * Retorna a informação do erro ocorrido na execução, se houver
	 * 
	 * @return array;
	 */
	public function errorInfo();
	
	/**
	 * Executa o statement
	 * 
	 * @param array $params : parâmetros para a execução
	 * @return bool
	 * @throws Db_Statement_Exception
	 */
	public function execute(array $params = array());
	
	/**
	 * Busca uma linha no conjunto de resultados da execução
	 * 
	 * @param Db::FETCH_* $mode [OPCIONAL] : modo de busca
	 * @return mixed : array, objeto ou escalar, dependendo de $mode
	 * @throws Db_Statement_Exception
	 */
	public function fetchOne($mode = null, $col = null);
	
	/**
	 * Retorna todas as linhas do conjunto de resultados da execução
	 * 
	 * @param Db::FETCH_* $mode [OPCIONAL] : modo de busca
	 * @param mixed $col [OPCIONAL] : a coluna a ser retornada
	 * @throws Db_Statement_Exception
	 * @return array : conjunto de linhas, cada uma no formato $mode
	 */
	public function fetchAll($mode = null, $col = null);
	
	/**
	 * Retorna uma coluna do conjunto de resultados da execução
	 * 
	 * @param string|integer $col : o nome da coluna a ser retornada ou sua posição
	 * @return mixed : o valor da primeira coluna da linha buscada
	 * @throws Db_Statement_Exception
	 */
	public function fetchColumn($col = 0);
	
	/**
	 * Busca uma linha no conjunto de resultados e a retorna como um objeto $class
	 * 
	 * @param className $class [OPCIONAL] : o nome da classe (padrão = stdClass)
	 * @param array $config [OPCIONAL] : possí­veis configurações para o objeto $class
	 * @return object
	 * @throws Db_Statement_Exception
	 */
	public function fetchObject($class = 'stdClass', array $config = array());
	
	/**
	 * Retorna o próximo conjunto de linhas de uma execução que resultou em vários conjuntos de linha
	 * 
	 * @return mixed
	 * @throws Db_Statement_Exception
	 */
	public function nextRowset();
	
	/**
	 * Retorna o número de linhas afetadas pela última operação
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
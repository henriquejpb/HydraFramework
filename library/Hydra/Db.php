<?php
/**
 * Conex�o com SGBD's
 * @author <a href="mailto:rick.hjpbacelos@gmail.com">Henrique Barcelos</a>
 */
abstract class Db {
	const INT_TYPE    = 0;
    const BIGINT_TYPE = 1;
    const FLOAT_TYPE  = 2;

	/**
	 * Indica que os m�todos fetch* devem retornar arrays associativos nome_campo => valor_campo
	 * @var integer
	 */
	const FETCH_ASSOC = 100;

	/**
	 * Indica que os m�todos fetch* devem retornar arrays com �ndices num�ricos n => valor_campo
	 * @var integer
	 */
	const FETCH_NUM   = 101;

	/**
	 * Indica que os m�todos fetch* devem retornar arrays associativos e num�ricos
	 * @var integer
	 */
	const FETCH_ARRAY = 102;

	/**
	 * Indica que os m�todos fetch* devem retornar objetos stdClass
	 * @var integer
	 */
	const FETCH_OBJ	  = 103;

	/**
	 * Indica que os m�todos fetch* devem retornar uma �nica coluna
	 * @var integer
	 */
	const FETCH_COLUMN = 104;

	/**
	 * Retorna um Db_Adapter para conex�o com o banco de dados
	 * @param string $adapter : o nome do Adapter
	 * @param array $config : as configura��es do novo Adapter
	 * @throws Db_Exception
	 */
	public static function factory($adapter, array $config){
		$className = 'Db_Adapter_'.ucfirst($adapter);
		if(class_exists($className)){
			return new $className($config);
		} else {
			throw new Db_Exception(sprintf('Adapter "%s" inexistente!'));
		}
	}
}
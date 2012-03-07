<?php
/**
 * Representa uma expressão SQL 
 * @author <a href="mailto:rick.hjpbacelos@gmail.com">Henrique Barcelos</a>
 */
class Db_Expression {
	
	/**
	 * Armazena a expressão SQL
	 * @var string
	 */
	protected $_expression;

	/**
	 * Construtor
	 * @param string $expr : a string contendo a expressão SQL
	 */
	public function __construct($expr) {
		$this->_expression = $expr;
	}
	
	/**
	 * Método mágico para converter a expressão em string.
	 * echo $obj funciona a partir da versão 5.2.4
	 * 
	 * @return string
	 */
	public function __toString() {
		return $this->_expression;
	}
}
<?php
/**
 * Representa uma express�o SQL 
 * @author <a href="mailto:rick.hjpbacelos@gmail.com">Henrique Barcelos</a>
 */
class Db_Expression {
	
	/**
	 * Armazena a express�o SQL
	 * @var string
	 */
	protected $_expression;

	/**
	 * Construtor
	 * @param string $expr : a string contendo a express�o SQL
	 */
	public function __construct($expr) {
		$this->_expression = $expr;
	}
	
	/**
	 * M�todo m�gico para converter a express�o em string.
	 * echo $obj funciona a partir da vers�o 5.2.4
	 * 
	 * @return string
	 */
	public function __toString() {
		return $this->_expression;
	}
}
<?php
/**
 * @package View
 * @author <a href="rick.hjpbarcelos@gmail.com">Henrique Barcelos</a>
 *
 */
class View_Exception extends Exception {
	/**
	 * Armazena a instância da View responsável pelo lançamento da exceção.
	 * 
	 * @var View_Abstract
	 */
	private $_view;
	
	/**
	 * Seta a View responsável pelo lançamento da exceção.
	 * 
	 * @param View_Abstract $view
	 */
	public function setView(View_Abstract $view) {
		$this->_view = $view;
	}
	
	/**
	 * Retorna a View responsável pelo lançamento da exceção.
	 * 
	 * @return View_Abstract
	 */
	public function getView() {
		return $this->_view;
	}
}
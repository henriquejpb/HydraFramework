<?php
/**
 * @package View
 * @author <a href="rick.hjpbarcelos@gmail.com">Henrique Barcelos</a>
 *
 */
class View_Exception extends Exception {
	/**
	 * Armazena a inst�ncia da View respons�vel pelo lan�amento da exce��o.
	 * 
	 * @var View_Abstract
	 */
	private $_view;
	
	/**
	 * Seta a View respons�vel pelo lan�amento da exce��o.
	 * 
	 * @param View_Abstract $view
	 */
	public function setView(View_Abstract $view) {
		$this->_view = $view;
	}
	
	/**
	 * Retorna a View respons�vel pelo lan�amento da exce��o.
	 * 
	 * @return View_Abstract
	 */
	public function getView() {
		return $this->_view;
	}
}
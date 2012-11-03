<?php
/**
 * @package Hydra_View
 * @author <a href="rick.hjpbarcelos@gmail.com">Henrique Barcelos</a>
 *
 */
class Hydra_View_Exception extends Exception {
	/**
	 * Armazena a instância da Hydra_View responsável pelo lançamento da exceção.
	 * 
	 * @var Hydra_View_Abstract
	 */
	private $_view;
	
	/**
	 * Seta a Hydra_View responsável pelo lançamento da exceção.
	 * 
	 * @param Hydra_View_Abstract $view
	 */
	public function setView(Hydra_View_Abstract $view) {
		$this->_view = $view;
	}
	
	/**
	 * Retorna a Hydra_View responsável pelo lançamento da exceção.
	 * 
	 * @return Hydra_View_Abstract
	 */
	public function getView() {
		return $this->_view;
	}
}
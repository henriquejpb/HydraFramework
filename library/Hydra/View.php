<?php
class Hydra_View extends Hydra_View_Abstract {
	
	/**
	 * Factory Method.
	 *
	 * @param string $spec
	 * @param array $vars
	 * @param array $opt
	 * @see Hydra_View_Abstract::__construct
	 */
	public static function factory($spec, array $vars = array(), array $opt = array()) {
		return new self($spec, $vars, $opt);
	}
	
	/**
	 * @see Hydra_View_Abstract::_run()
	 */
	protected function _run() {
		foreach($this->_vars as $key => &$each) {
			if($each instanceof Hydra_View_Abstract) {
				$each = $each->render();
			}
		}
		include $this->_templatePath;
	}
}
<?php
/**
 * Prot�tipo de uma classe de localiza��o
 * @package Core
 * @author henrique
 */
class Localization {
	/**
	 * A zona do tempo
	 * @var string
	 */
	private $_timezone;
	
	/**
	 * A localiza��o
	 * @var array
	 */
	private $_locale = array();
	
	public function __construct($timezone, $locale) {
		$this->setTimezone($timezone);
		$this->setLocale($locale);
	}
	
	public function setTimezone($timezone) {
		$this->_timezone = (string) $timezone;
	}
	
	public function getTimezone() {
		return $this->_timezone;
	}
	
	public function setLocale($locale) {
		$this->_locale = (array) $locale;
	}
	
	public function getLocale() {
		return $this->_locale;
	}
}
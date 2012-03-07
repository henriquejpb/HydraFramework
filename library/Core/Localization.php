<?php
/**
 * Protótipo de uma classe de localização
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
	 * A localização
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
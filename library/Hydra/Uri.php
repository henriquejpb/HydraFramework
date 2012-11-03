<?php
class Hydra_Uri {
	/**
	 * O scheme da URL (http, ftp, ssl, etc.)
	 * @var string
	 */
	private $_scheme = 'http';
	
	/**
	 * O host da URL
	 * @var string
	 */
	private $_host;
	
	/**
	 * O caminho da URL após o host
	 * @var string
	 */
	private $_path;
	
	/**
	 * Parâmetros de routing da URL, que devem ser
	 * tratados separadamente dos parâmetros de query
	 * @var array
	 */
	private $_params = array();
	
	/**
	 * A porta utilizada para a requisição.
	 * @var int
	 */
	private $_port;
		
	/**
	 * A query string da URL
	 * @var array
	 */
	private $_query = array();
	
	/**
	 * O hash da URL (parte apos o '#')
	 * @var string
	 */
	private $_hash = '';
	
	/**
	 * Construtor.
	 * 
	 * @param string $url : o endereço da url
	 */
	public function __construct($url) {
		$info = parse_url($url);
		if(isset($info['scheme'])) {
			$this->_scheme = $info['scheme']; 
		}
		
		if(isset($info['host'])) {
			$this->_host = $info['host'];
		} else {
			$this->_host = Hydra_Environment::getVar('HTTP_HOST');
		}
		
		if(isset($info['path'])) {
			$this->_path = $info['path'];
			if(strlen($this->_path) == 0) {
				$this->_path = '/';
			}
		}
		
		if(isset($info['query'])) {
			$this->_query = self::queryFromString($info['query']);
		}
		
		if(isset($info['fragment'])) {
			$this->_hash = $info['fragment'];
		}
	}
	
	/**
	 * Seta ou retorna parâmetros de routing na URL.
	 * Se nenhum parâmetro é passado, retorna a lista com todos os parâmetros existentes
	 * Se um parâmetro é passado, age como getter.
	 * Se os dois parâmetros são passados, age como setter.
	 * 
	 * @param string|null $key
	 * @param mixed $value
	 */
	public function params($key = null, $value = null) {
		return $this->_setOrGet('_params', $key, $value);
	}
	
	/**
	 * Seta ou retorna parâmetros da query na URL.
	 * 
	 * @param string|null $key
	 * @param mixed $value
	 */
	public function query($key = null, $value = null) {
		return $this->_setOrGet('_query', $key, $value);
	}
	
	/**
	 * Seta ou retorna valores da propriedade $property.
	 * 
	 * @param string $property
	 * @param string|null $key
	 * @param mixed $value
	 * @return mixed :
	 * <ul>
	 * 	<li>array : o valor de $property, caso $key e $value sejam NULL</li>
	 * 	<li>mixed : o valor de $property[$key], ou NULL se não existir, caso $value seja NULL</li>
	 * 	<li>Url : fluent interface, caso a setagem do parâmetro ocorra com sucesso</li>
	 * 	<li>null : caso haja falha na setagem do parâmetro</li>
	 * </ul>
	 */
	private function _setOrGet($property, $key, $value) {
		// Ambos vazios, retorna todos os parâmetros
		if($key === null && $value === null) {
			return $this->_params;
		}

		// Se $value não foi informado, age como um getter
		if($value === null) {
			return isset($this->{$property}[$key]) ? $this->{$property}[$key] : null;
		}
		
		// Impede a criação de parâmetros vazios
		if(!empty($key) && !empty($value)) {
			$this->{$property}[$key] = (string) $value;
			return $this;
		}
		
		return null;
	}
	
	/**
	 * Seta ou retorna o scheme da URL.
	 * 
	 * @param string|null $hash
	 * @return mixed : string se age como getter, Url (fluent interface) se age como setter 
	 */
	public function scheme($scheme = null) {
		if($scheme === null) {
			return $this->_scheme;
		}
		
		$this->_scheme = (string) $scheme;
		return $this;
	}
	
	/**
	 * Retorna o host da URL.
	 * 
	 * @return string
	 */
	public function host() {
		return $this->_host;
	}
	
	/**
	 * Retorna o path da URL.
	 * 
	 * @return string
	 */
	public function path() {
		return $this->_path;
	}
	
	/**
	 * Seta ou retorna o hash da URL.
	 * 
	 * @param string|null $hash
	 * @return mixed : string se age como getter, Url (fluent interface) se age como setter 
	 */
	public function hash($hash = null) {
		if($hash === null) {
			return $this->_hash;
		}
		
		$this->_hash = (string) $hash;
		return $this; 
	}
	
	/**
	 * Renderiza a URL.
	 * 
	 * @param boolean $local : tenta renderizar uma URL local se for possível.
	 * @return string
	 */
	public function render($local = true) {
		if($local === true && Hydra_Environment::getVar('HTTP_HOST') == $this->_host) {
			$url = '/';
		} else {
			$url = $this->_renderHost();
		}
		$url .= $this->_renderPath() . 
			   $this->_renderParams() . $this->_renderQuery() . 
			   $this->_renderHash();
		$url = preg_replace('#(?<!:)//#', '/', $url);
		return $url;
	}
	
	/**
	 * Método mágico para transformação do objeto em string.
	 * 
	 * @return string
	 */
	public function __toString() {
		return $this->render();
	}
	
	/**
	 * Renderiza o host da url.
	 * 
	 * @return string
	 */
	private function _renderHost() {
		if(empty($this->_host)) {
			return '';
		}
		return $this->_scheme . '://' . $this->_host;
	}
	
	/**
	 * Renderiza o path da url.
	 * 
	 * @return string
	 */
	private function _renderPath() {
		if(strlen($this->_path) == 0 || $this->_path == '/') {
			return '/';
		}
		if($this->_path[0] != '/') {
			$this->_path = '/' . $this->_path;
		}
		return $this->_path;
	}
	
	/**
	 * Renderiza a query da URL.
	 * 
	 * @return string
	 */
	private function _renderQuery() {
		if(empty($this->_query)) {
			return '';
		}
		return '?' . http_build_query($this->_query, 'data');
	}
	
	/**
	 * Rendereiza o hash da URL.
	 * 
	 * @return string
	 */
	private function _renderHash() {
		return (empty($this->_hash) ? '' : '#' . $this->_hash);
	}
	
	/**
	 * Renderiza os parâmetros de routing da URL.
	 * 
	 * @return string;
	 */
	private function _renderParams() {
		$str = '';
		foreach($this->_params as $key => $value) {
			$str .= '/' . $key . ':' . $value;
		}
		return $str;
	}
	
	/**
	 * Cria um array com base em uma query string.
	 * 
	 * @param string $str
	 * @return array
	 */
	public static function queryFromString($str) {
		$query = array();
		
		$str = ltrim($str, '?');
		parse_str($str, $query);
		
		return $query;
	}
}
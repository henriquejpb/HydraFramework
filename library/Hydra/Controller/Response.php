<?php
/**
 * Representa a resposta de uma requisi��o.
 * @author <a href="mailto:rick.hjpbacelos@gmail.com">Henrique Barcelos</a>
 */
class Controller_Response {
	/**
	 * Armazena os headers a serem enviados na resposta.
	 *
	 * @var array
	 */
	private $_headers = array();

	/**
	 * Armazena os headers padr�o a serem enviados na resposta.
	 *
	 * @var array
	 */
	private static $_defaultHeaders = array();

	/**
	 * Cont�m o corpo da resposta.
	 *
	 * @var array
	 */
	private $_body = array();

	/**
	 * O c�digo do status da resposta.
	 *
	 * @var integer
	 */
	private $_responseCode = 200;

	/**
	 * Pilha de exce��es.
	 *
	 * @var array
	 */
	private $_exceptions = array();

	/**
	 * Se devemos ou n�o renderizar exce��es.
	 *
	 * @var boolean
	 */
	private $_renderExceptions = false;

	/**
	 * Se devemos ou n�o lan�ar uma exce��o caso alguma opera��o sobre
	 * headers tente ser executada depois dos mesmos terem sido enviados.
	 *
	 * @var unknown_type
	 */
	private $_headersSentThrowsException = true;

	/**
	 * Construtor.
	 *
	 * @param integer $code : o c�digo da resposta HTTP.
	 */
	public function __construct($headers = array(), $body = null, $code = null) {
		$this->_headers = self::$_defaultHeaders;
		$headers  = (array) $headers;
		
		foreach($headers as $name => $value) {
			if(is_numeric($name)) {
				$this->setHeader($value);
			} else {
				$this->setHeader($name, $value);
			}
		}
		
		if($body !== null) {
			$this->setBody($body);
		}
		
		if($code !== null) {
			$this->setHttpResponseCode($code);
		}
	}

	/**
	 * Seta um header para a resposta.
	 * Usando apenas 1 par�metro, o header deve estar na forma:
	 * 		Header-Name:header_value
	 *
	 * @param string $name
	 * @param string|null $value
	 * @throws Controller_Response_Exception
	 * @return Controller_Response : fluent interface
	 */
	public function setHeader($name, $value = null) {
		$this->canSendHeaders(true);
		self::_doSetHeader($this->_headers, $name, $value);
		return $this;		
	}
	
	/**
	 * Seta um header padr�o para todos os objetos Controller_Response.
	 * 
	 * @return void
	 */
	public static function setDefaultHeader() {
		self::_doSetHeader(self::$_defaultHeaders, $name, $value);
	}
	
	/**
	 * Faz a inser��o de um header.
	 * 
	 * @param reference $var
	 * @param string $name
	 * @param string $value
	 * @throws Controller_Response_Exception
	 * @return void
	 */
	private static function _doSetHeader(&$var, $name, $value) {
		if($value === null) {
			$pieces = explode(':', $name);
			if(count($pieces) != 2) {
				throw new Controller_Response_Exception(sprintf('Header "%s" inv�lido', $name));
			}
			$name = trim($pieces[0]);
			$value = trim($pieces[1]);
		}
		$name = self::_normalizeHeader($name);
		$var[$name] = $value;
	}
	
	/**
	 * Normaliza o nome de um header para o padr�o X-Capitalized-Header.
	 *
	 * @param string $name
	 * @return string;
	 */
	private static function _normalizeHeader($name) {
		$headerName = str_replace(array('-', '_'), ' ', $name);
		$headerName = ucwords(strtolower($headerName));
		$headerName = str_replace(' ', '-', $headerName);
		return $headerName;
	}

	/**
	 * Se $headers � NULL, retorna os headers da resposta.
	 * Se $headers � uma string, procura o valor do header dentro do atributo $_headers e o retorna se encontrar.
	 * Se $headers � um array, returna um array com os valores encontrados.
	 *
	 * @param array|string|NULL $headers
	 * @return array|string|NULL
	 */
	public function getHeaders($headers = null) {
		if($headers === null) {
			return $this->_headers;
		}

		if(is_string($headers)) {
			$headers = self::_normalizeHeader($headers);
			return isset($this->_headers[$headers]) ? $this->_headers[$headers] : null;
		} elseif(is_array($headers)) {
			$ret = array();
			foreach($headers as $h) {
				if(($value = $this->getHeaders($h)) !== null) {
					$ret[$h] = $value;
				}
			}
			return $ret;
		}
		return null;
	}

	/**
	 * Limpa os headers da resposta.
	 * 
	 * @param boolean $keepDefault : se os headers padr�o devem ou n�o ser mantidos. 
	 * @return Controller_Response : fluent interface
	 */
	public function clearHeaders($keepDefault = true) {
		if($keepDefault === false){
			$this->_headers = array();
		} else {
			$this->_headers = self::$_defaultHeaders;
		}

		return $this;
	}
	
	/**
	 * Remove um header da resposta.
	 * 
	 * @param string $name : o nome do header
	 * @return Controller_Response : fluent interface
	 */
	public function clearHeader($name) {
		$name = $this->_normalizeHeader($name);
		if(isset($this->_headers[$name])) {
			unset($this->_headers[$name]);
		}
		return $this;
	}

	/**
	 * Seta uma URL de redirecionamento.
	 * Seta o header 'Location' quee redireciona para a URL informada.
	 *
	 * @param string|Url $url
	 * @param integer $code
	 * @return Controller_Response : fluent interface
	 */
	public function setRedirect($url, $code = 301) {
		$this->canSendHeaders(true);
		$this->setHeader('Location', $url)
		->setHttpResponseCode($code);

		return $this;
	}

	/**
	 * Verifica se a resposta � de redirecionamento.
	 *
	 * @return boolean
	 */
	public function isRedirect() {
		return $this->_responseCode >= 300 && $this->_responseCode <= 307;
	}
	
	/**
	 * Seta um c�digo de status para a resposta HTTP.
	 * 
	 * @param integer $code
	 * @throws Controller_Response_Exception : se o c�digo for inv�lido
	 * @return Controller_Response : fluent interface
	 */
	public function setHttpResposeCode($code) {
		$code = (int) $code;
		if($code < 100 || $code > 599) {
			throw new Controller_Response_Exception(sprintf('O c�digo de resposta HTTP %d � inv�lido', $code));
		}
		
		$this->_responseCode = $code;
		return $this;
	}
	
	/**
	 * Retorna o c�digo de status da resposta HTTP.
	 * 
	 * @return integer
	 */
	public function getHttpResposeCode() {
		return $this->_responseCode;
	}
	
	/**
	 * Verifica se ainda � poss�vel enviar headers de resposta,
	 * ou seja, se a sa�da para o navegador ainda n�o foi iniciada.
	 * 
	 * @param boolean $throwException : se TRUE, uma exce��o � lan�ada uma exce��o 
	 * 									caso n�o seja poss�vel enviar headers
	 * @throws Controller_Response_Exception : se os headers j� foram enviados e $throwExcetion e 
	 * 									$this->_headersSentThrowException forem TRUE
	 * @return boolean
	 */
	public function canSendHeaders($throwException = false) {
		$sent = headers_sent($file, $line);
		if($sent && $throw && $this->_headersSentThrowsException) {
			throw new Controller_Response_Exception(sprintf('N�o � poss�vel enviar headers; Sa�da iniciada em %s, linha %d', $file, $line));
		}
		return !$sent;
	}
	
	/**
	 * Envia os headers da resposta.
	 * 
	 * @return Controller_Response : fluent interface
	 */
	public function sendHeaders() {
		if(empty($this->_headers)) {
			return $this;
		}
		
		$this->canSendHeaders(true);
		
		foreach($this->_headers as $header => $value) {
			header($header . ':' . $value);
		} 
		
		if($this->_responseCode != 200) {
			header('HTTP/1.1 ' . $this->_responseCode);
		}
		
		return $this;
	}
	
	/**
	 * Seta o conte�do do corpo da resposta.
	 * 
	 * Se $name n�o � informado, resetamos o corpo da resposta
	 * e colocamos $content no segmento 'default'.
	 * 
	 * Se $name � uma string, adicionamos ao array do corpo o 
	 * $content sob a chave $name.
	 * 
	 * @param string $content
	 * @param string|null $name
	 * @return Controller_Response : fluent interface
	 */
	public function setBody($content, $name = null) {
		if($name == null) {
			$this->_body = array('default' => (string) $content);
		} else {
			$this->_body[(string) $name] = (string) $content;
		}
		
		return $this;
	}
	
	/**
	 * Adiciona $content ao fim do segmento $name.
	 * Se $name n�o � informado, utilizamos o segmento 'default'.
	 * 
	 * @param string $content
	 * @param string|null $name
	 * @return Controller_Response : fluent interface
	 */
	public function appendBody($content, $name = null) {
		if($name == null) {
			$name = 'default';
		}
		
		if(isset($this->_body[$name])) {
			$this->_body[$name] .= $content;
		} else {
			$this->append($name, $content);
		}
		
		return $this;
	}
	
	/**
	 * Limpa o corpo da resposta ou apenas um segmento,
	 * caso $name seja informado.
	 * 
	 * @param string|null $name
	 * @return boolean : FALSE caso o segmento $name n�o exista 	 
	 */
	public function clearBody($name = null) {
		if($name !== null) {
			$name = (string) $name;
			if(isset($this->_body[$name])) {
				unset($this->_body[$name]);
				return true;
			}
			return false;
		}
	
		unset($this->_body);
		$this->_body = array();
		return true;
	}
	
	/**
	 * Retorna o conte�do do corpo da resposta.
	 * 
	 * Se $spec � FALSE, retorna os valores concatenados do array do corpo da resposta;
	 * Se $spec � TRUE, retorna o pr�prio array do corpo da resposta;
	 * Se $spec � o nome de um segmento do corpo, o conte�do do segmento � adicionado.
	 * 
	 * @param boolean|string $spec
	 * @return string|array|null
	 */
	public function getBody($spec = false) {
		if($spec === false) {
			ob_start();
			$this->outputBody();
			return ob_get_clean();
		} else if($spec === true) {
			return $this->_body;
		} else if(isset($this->_body[(string) $spec])) {
			return $this->_body[(string) $spec];
		}
		
		return null;
	}
	
	/**
	 * Adiciona um segmento nomeado ao fim do array do corpo da resposta.
	 * Se o segmento j� existe, o seu conte�do ser� substitu�do.
	 * 
	 * @param string $name
	 * @param string $content
	 * @throws Controller_Response_Exception : caso $name n�o seja uma string
	 * @return Controller_Response : fluent interface
	 */
	public function append($name, $content) {
		if(!is_string($name)) {
			throw new Controller_Response_Exception('Chave de segmento de corpo inv�lida! 
											Esperado string, dado ' . gettype($name));
		}
		
		// Se o segmento $name j� existe, iremos substituir seu conte�do.
		if(isset($this->_body[$name])) {
			unset($this->_body[$name]);
		}
		
		$this->_body[$name] = (string) $content;
		return $this;
	}
	
	/**
	 * Adiciona um segmento nomeado ao in�cio do array do corpo da resposta.
	 * Se o segmento j� existe, o seu conte�do ser� substitu�do.
	 *
	 * @param string $name
	 * @param string $content
	 * @throws Controller_Response_Exception : caso $name n�o seja uma string
	 * @return Controller_Response : fluent interface
	 */
	public function prepend($name, $content) {
		if(!is_string($name)) {
			throw new Controller_Response_Exception('Chave de segmento de corpo inv�lida!
													Esperado string, dado ' . gettype($name));
		}
		
		// Se o segmento $name j� existe, iremos substituir seu conte�do.
		if(isset($this->_body[$name])) {
			unset($this->_body[$name]);
		}
		
		$new = array($name => (string) $content);
		$this->_body[$name] = $new + $this->_body;
		return $this;
	}
	
	/**
	 * Insere um segmento nomeado no array de conte�do do corpo da resposta.
	 * 
	 * @param string $name : o nome do segmento
	 * @param string $content : o conte�do a ser adicionado
	 * @param string $parent [OPTIONAL] : o segmento pai do segmento inserido
	 * @param boolean $beforeParent : se o segmento pai for informado, este
	 * 		argumento indica se o conte�do deve ser inserido antes ou depois
	 * 		do segmento pai.
	 * @throws Controller_Response_Exception : se $name n�o for uma string ou se $parent
	 * 		for diferente de NULL e n�o for uma string
	 * @return Controller_Response : fluent interface
	 */
	public function insert($name, $content, $parent = null, $beforeParent = false) {
		if(!is_string($name)) {
			throw new Controller_Response_Exception('Chave de segmento de corpo inv�lida!
																Esperado string, dado ' . gettype($name));
		}
		
		if($parent !== null && !is_string($parent)) {
			throw new Controller_Response_Exception('Chave de segmento pai inv�lida!
																Esperado string, dado ' . gettype($name));
		}
		
		if(isset($body[$name])) {
			unset($body[$name]);
		}
		
		if($parent === null || !isset($this->_body[$parent])) {
			$this->append($name, $content);
		}
		
		$ins = array($name => (string) $content);
		$keys = array_keys($this->_body);
		
		$loc = array_search($parent, $keys);
		if($beforeParent === false) {
			$loc++;
		}
		
		// Se estamos inserindo no come�o do array...
		if($loc == 0) {
			$this->_body = $ins + $this->_body;
		} 
		// Se estamos inserindo no final do array... 
		else if($loc >= count($this->_body)) {
			$this->_body += $ins;
		}
		// Caso contr�rio, precisamos inserir numa posi��o espec�fica... 
		else {
			$pre = array_slice($this->_body, 0, $loc, true);
			$post = array_slice($this->_body, $loc, null, true);
			$this->_body = $pre + $ins + $post;
		}
		
		return $this;
	}
	
	/**
	 * Fornece a sa�da para o navegador, mostrano o conte�do do corpo da resposta.
	 * 
	 * @return void
	 */
	public function outputBody() {
		$body = implode('', $this->_body);
		echo $body;
	}
	
	/**
	 * Seta uma exce��o na resposta.
	 * 
	 * @param Exception $e
	 * @return Controller_Response : fluent interface
	 */
	public function setException(Exception $e) {
		array_unshift($this->_exceptions, $e);
		return $this;
	}
	
	/**
	 * Retorna a pilha de exce��es.
	 * 
	 * @return array
	 */
	public function getException() {
		return $this->_exceptions;
	}
	
	/**
	 * Verifica se a resposta tem exce��es registradas.
	 * 
	 * @return boolean
	 */
	public function isException() {
		return !empty($this->_exceptions);
	}
	
	/**
	 * Verifica se existem exce��es do tipo $type na respota.
	 * 
	 * @param string $type
	 * @return boolean
	 */
	public function hasExceptionOfType($type) {
		foreach($this->_exceptions as $e) {
			if($e instanceof $type) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Verifica se existem exce��es com a mensagem $message na resposta.
	 * 
	 * @param string $message
	 * @return boolean
	 */
	public function hasExceptionOfMessage($message) {
		foreach($this->_exceptions as $e) {
			if($e->getMessage() == $message) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Se devemos ou n�o renderizar as exce��es.
	 * 
	 * Se nenhum argumento � passado, retorna o valor da flag;
	 * Se o argumento for booleano, seta a flag e retorna o valor setado.
	 * 
	 * @param boolean|null $flag [OPCIONAL]
	 * @return boolean
	 */
	public function renderExceptions($flag = null) {
		if($flag !== null) {
			$this->_renderExceptions = (bool) $flag;
		}
		
		return $this->_renderExceptions;
	}
	
	/**
	 * Envia a resposta para o navegador, incluindo os headers e 
	 * renderizando as exce��es, se requisitado.
	 * 
	 * @return void
	 */
	public function send() {
		$this->sendHeaders();
		
		if($this->isException() && $this->renderExceptions()) {
			$exceptions = '';
			foreach($this->getException() as $e) {
				$exceptions .= $e->__toString() . PHP_EOL;
			}
			echo $exceptions;
			return;
		}
		
		$this->outputBody();
	}
	
	/**
	 * Converte o objeto para string
	 * @return string
	 */
	public function __toString() {
		ob_start();
		$this->send();
		return ob_get_clean();
	}
}

<?php
/**
 * Representa uma requisição.
 * @author <a href="mailto:rick.hjpbacelos@gmail.com">Henrique Barcelos</a>
 */
class Controller_Request {
	/**
	 * Scheme para http
	 */
	const SCHEME_HTTP  = 'http';
	
	/**
	 * Scheme para https
	 */
	const SCHEME_HTTPS = 'https';
	
	/**
	 * Parâmetros de routing parseados a partir da URL.
	 * 
	 * @var array
	 */
	private $_params = array(
		'module' => null,
		'controller' => null,
		'action' => null
	);
	
	/**
	 * Parâmetros POST da requisição.
	 * 
	 * @var array
	 */
	private $_post = array();
	
	/**
	 * Parâmetros GET da requisição (via query string).
	 * 
	 * @var array
	 */
	private $_get = array();
	
	/**
	 * Arquivos enviados junto com a requisição.
	 * 
	 * @var array
	 */
	private $_files = array();
	
	/**
	 * Os cookies enviados junto com a requisição.
	 * 
	 * @var array
	 */
	private $_cookies = array();
	
	/**
	 * Se a requisição atual já foi despachada ou não.
	 * 
	 * @var boolean
	 */
	private $_dispatched = false;
	
	/**
	 * A URI da requisição.
	 * 
	 * @var string
	 */
	private $_uri;
	
	/**
	 * A URL base da aplicação.
	 * 
	 * @var string
	 */
	private $_baseUrl;
	
	/**
	 * O caminho base da requisição.
	 * 
	 * @var string
	 */
	private $_basePath;
	
	/**
	 * O corpo da requisição.
	 * 
	 * @var string
	 */
	private $_rawBody;
	
	/**
	 * O User Agent da requisição
	 * @var Http_UserAgent
	 */
	private $_userAgent;
	
	/**
	* Os detectores padrão usados no método is().
	* @see Request::addDetector
	* @var array
	*/
	private $_detectors = array(
		'get' => array('env' => 'REQUEST_METHOD', 'value' => 'GET'),
		'post' => array('env' => 'REQUEST_METHOD', 'value' => 'POST'),
		'put' => array('env' => 'REQUEST_METHOD', 'value' => 'PUT'),
		'delete' => array('env' => 'REQUEST_METHOD', 'value' => 'DELETE'),
		'head' => array('env' => 'REQUEST_METHOD', 'value' => 'HEAD'),
		'options' => array('env' => 'REQUEST_METHOD', 'value' => 'OPTIONS'),
		'secure' => array('env' => 'HTTPS', 'value' => 1),
		'ajax' => array('env' => 'Controller_X_REQUESTED_WITH', 'value' => 'XMLHttpRequest'),
		'flash' => array('env' => 'Controller_USER_AGENT', 'pattern' => '/^(Shockwave|Adobe) Flash/'),
		'mobile' => array('env' => 'Controller_USER_AGENT', 'options' => array(
			'Android', 'AvantGo', 'BlackBerry', 'DoCoMo', 'Fennec', 'iPod', 'iPhone', 'iPad',
			'J2ME', 'MIDP', 'NetFront', 'Nokia', 'Opera Mini', 'Opera Mobi', 'PalmOS', 'PalmSource',
			'portalmmm', 'Plucker', 'ReqwirelessWeb', 'SonyEricsson', 'Symbian', 'UP\\.Browser',
			'webOS', 'Windows CE', 'Windows Phone OS', 'Xiino'
		))
	);
	
	/**
	 * Construtor.
	 * 
	 * @param string $uri : URI da requisição
	 * @param array|null $useSuperGlobals : quais variáveis super-globais 
	 * 		que serão utilizadas na requisição:
	 * 			- POST
	 * 			- GET
	 * 			- FILES
	 * 			- COOKIES 
	 */
	public function __construct($uri = null, $useSuperGlobals = array('get', 'post', 'files', 'cookies')) {
		$this->setUri($uri);
		$this->_userAgent = new Http_UserAgent();
		
		$parseEnvironment = is_array($useSuperGlobals) && !empty($useSuperGlobals);
		
		if($parseEnvironment) {
			$useSuperGlobals = array_map('strtolower', $useSuperGlobals);
			if(in_array('get', $useSuperGlobals)) {
				$this->_proccessGet();
			}
			if(in_array('post', $useSuperGlobals)) {
				$this->_proccessPost();
			}
			if(in_array('files', $useSuperGlobals)) {
				$this->_proccessFiles();
			}
			if(in_array('cookies', $useSuperGlobals)) {
				$this->_proccessCookies();
			}
		}
	}
	
	/**
	 * Processa os dados GET da requisição, colocando-os neste objeto.
	 * 
	 * @return void
	 */
	private function _proccessGet() {
		foreach($_GET as $key => $value) {
			if(!empty($value)) {
				$this->_get[$key] = $value;
			}
		}
	}
	
	/**
	 * Processa os dados POST da requisição, colocando-os neste objeto.
	 * 
	 * @return void
	 */
	private function _proccessPost() {
		foreach($_POST as $key => $value) {
			if(!empty($value)) {
				$this->_post[$key] = $value;
			}
		}
	}
	
	/**
	 * Processa os dados de $_FILES, colocando-os neste objeto.
	 * Se tivermos um upload múltiplo, os dados serão transpostos, ficando da forma:
	 * array (
	 * 		0 => array (
	 * 			'name' => 'file.txt'
	 * 			'tmp_name' => 'afd2213a121.tmp'
	 * 			'mime' => 'text/plain'
	 * 			'size' => '1024'
	 * 			'error' => 0
	 * 		),
	 * 		1 => array (
	 * 			'name' => 'file.jpg'
	 * 			'tmp_name' => 'bfc2419a329.tmp'
	 * 			'mime' => 'image/jpeg'
	 * 			'size' => '104321'
	 * 			'error' => 0
	 * 		),
	 * 		...
	 * )
	 * 
	 * @return void
	 */
	private function _proccessFiles() {
		if(isset($_FILES)){
			foreach($_FILES as $key => $value) {
				// Transpõe o array $_FILES em caso de upload múltiplo.
				if(is_array(current($value))) {
					foreach($value as $fileKey => $fileVal) {
						for($i = 0; $i < count($fileVal); $i++) {
							$this->_files[$key][$i][$fileKey] = $fileVal[$i];
						}
					}
				} else {
					$this->_files[$key] = $value;
				}
			}
		}
	}
	
	/**
	 * Processa dos dados de $_COOKIE, colocando-os dentro do objeto.
	 * 
	 * @return void
	 */
	private function _proccessCookies() {
		if(isset($_COOKIE)) {
			foreach($_COOKIE as $key => $value) {
				if(!empty($value)) {
					$this->_cookies[$key] = $value;
				}
			}
		}		
	}
	
	/**
	 * Seta a URI da requisição. Se nenhum parâmetro for informado, 
	 * tenta obter esse valor a partir das variáveis do servidor.
	 * 
	 * @param string|null $reqUri
	 * @return Request : fluent interface
	 */
	public function setUri($reqUri = null) {
		if($reqUri === null) {
			if($envUri = $this->getServer('Controller_X_REWRITE_URL')) {
				$reqUri = $envUri;
			} elseif($this->getServer('IIS_WasUrlRewritten') == 1 &&
					 $envUri = $this->getServer('ENCODED_URL')) {
				$reqUri = $envUri;
			} elseif($envUri = $this->getServer('REQUEST_URI')) {
				$reqUri = $envUri;
			} 
		} 
		
		if(!is_string($reqUri)) {
			trigger_error('Impossível determinar a URI da requisição atual!', E_USER_NOTICE);
			return $this;
		}
		
		$uri = new Uri($reqUri);
		$this->_get = array_merge($this->_get, $uri->query());
		$this->_uri = $uri->path();
		
		return $this;
	}
	
	/**
	 * Retorna a URI da requisição.
	 * 
	 * @return string
	 */
	public function getUri() {
		if(empty($this->_uri)) {
			$this->setUri();
		}
		return $this->_uri;
	}
	
	/**
	 * Seta a URL base da aplicação.
	 * 
	 * @param string|null $baseUrl
	 */
	public function setBaseUrl($baseUrl = null) {
		if($baseUrl !== null && !is_string($baseUrl)) {
			return $this;
		}
		
		if($baseUrl == null) {
			$fileName = basename($this->getServer('SCRIPT_FILENAME'));
			
			$scriptName = $this->getServer('SCRIPT_NAME');
			$phpSelf = $this->getServer('PHP_SELF');
			$origScriptName = $this->getServer('ORIG_SCRIPT_NAME');
			
			if($scriptName && basename($scriptName) == $fileName) {
				$baseUrl = $scriptName;
			} elseif($phpSelf && basename($phpSelf) == $filename) {
				$baseUrl = $phpSelf;
			} else if($origScriptName && basename($origScriptName) == $filename) {
				$baseUrl = $origScriptName;
			} else {
				$path = $phpSelf;
				$file = $filename;
				
				$segs = array_reverse(explode('/', rtrim($file, '/')));
				$index = 0;
				$last = count($segs);
				$baseUrl = '';
				
				do {
					$seg = $segs[$index];
					$baseUrl .= '/' . $seg . $baseUrl;
					$index++;
				} while($last > $index && strpos($path, $baseUrl) != 0);
			}
		
		
			$requestUri = $this->getUri();
			if(strpos($requestUri, $baseUrl) === 0) {
				$this->_baseUrl = $baseUrl;
				return $this;
			}
			
			if(strpos($requestUri, dirname($baseUrl)) === 0) {
				$this->_baseUrl = rtrim(dirname($baseUrl), '/');
				return $this;
			}
			
			$truncReqUri = reset(explode('?', $requestUri));
			
			$baseName = baseName($baseUrl);
			if(empty($basename) || !strpos($truncReqUri, $basename)) {
				$this->_baseUrl = '';
				return $this;
			} 
			
			if(strlen($requestUri) >= strlen($baseUrl) 
			   && ($pos = strpos($requestUri, $baseUrl)) != 0) {
					$baseUrl = substr($requestUri, 0, $pos + strlen($baseUrl));
			}
		}
		
		$this->_baseUrl = rtrim($baseUrl, '/');
		return $this;
	}
	
	/**
	 * Retorna a URL base da requisição.
	 * 
	 * @param boolean $raw : se FALSE, a url deve ser codificada com urlencode
	 * @return string
	 */
	public function getBaseUrl($raw = true) {
		if(empty($this->_baseUrl)) {
			$this->setBaseUrl();
		}
		return $raw === true ? $this->_baseUrl : urlencode($this->_baseUrl);
	}
	
	/**
	 * Seta o caminho base da requisição.
	 * 
	 * @param string|null $path
	 * @return Request
	 */
	public function setBasePath($path = null) {
		if($path === null) {
			$baseUrl = $this->getBaseUrl();
			if(empty($baseUrl)) {
				$this->_basePath = '';
				return $this;
			}
			
			$fileName = basename($this->getServer('SCRIPT_FILENAME'));
			if(basename($baseUrl) == $fileName) {
				$path = dirname($baseUrl);
			} else {
				$path = $baseUrl;
			}
		}
		
		// Diretórios no Windows podem ser separados por \
		if(stripos(PHP_OS, 'WIN') === 0) {
			$path = str_replace('\\', '/', $path);
		}
		
		$this->_basePath = rtrim($path, '/');
		return $this;
	}
	
	/**
	 * Retorna o caminho base da requisição.
	 * 
	 * @return string
	 */
	public function getBasePath() {
		if($this->_basePath === null) {
			$this->setBasePath();
		}
		
		return $this->_basePath;
	}
	
	/**
	 * Seta um parâmetro de routing na requisição.
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return Request : fluent interface
	 */
	public function setParam($key, $value) {
		$this->_params[(string) $key] = $value;
		return $this;
	}
	
	/**
	 * Retorna um parâmetro de routing da requisição.
	 * 
	 * @param string $key
	 * @param mixed $default : o valor padrão de retorno, caso não exista o parâmetro $key
	 */
	public function getParam($key, $default = null) {
		$key = (string) $key;
		return $this->hasParam($key) ? $this->_params[$key] : $default;
	}
	
	/**
	 * Verifica se um dado parâmetro de routing existe.
	 * 
	 * @param string $key
	 * @return boolean
	 */
	public function hasParam($key) {
		$key = (string) $key;
		return isset($this->_params[$key]);
	}
	
	/**
	 * Remove um parâmetro de routing.
	 * 
	 * @param string $key
	 * @return boolean
	 */
	public function unsetParam($key) {
		if($this->hasParam($key)) {
			unset($this->_params[$key]);
			return true;
		}
		return false;
	}
	
	/**
	 * Seta um parâmetro POST na requisição.
	 * 
	 * @param string $spec : se é uma string, o parâmetro $value é obrigatório
	 * 						 se é um array, deve ser associativo nome => valor
	 * @param mixed $value
	 * @return Request : fluent interface
	 */
	public function setPost($spec, $value = null) {
		return $this->_setVar('_post', $spec, $value);
	}
	
	/**
	 * Retorna um parâmetro POST da requisição ou todos eles, se $key for NULL
	 * 
	 * @param string|null $key
	 * @param mixed $default : o valor padrão de retorno, caso não exista o parâmetro $key
	 * @return array|mixed|null
	 */
	public function getPost($key = null, $default = null) {
		return $this->_getVar('_post', $key, $default);
	}
	
	/**
	 * Seta um parâmetro GET na requisição.
	 * 
	 * @param mixed $spec: se é uma string, o parâmetro $value é obrigatório
	 * 						se é um array, deve ser associativo nome => valor
	 * @param string $value
	 * @return Request
	 */
	public function setQuery($spec, $value = null) {
		return $this->_setVar('_query', $spec, $value);
	}
	
	/**
	 * Retorna um parâmetro POST na requisição.
	 * 
	 * @param string|null $key
	 * @param mixed $default : o valor padrão de retorno, caso não exista o parâmetro $key
	 * @return array|mixed|null
	 */
	public function getQuery($key = null, $default = null) {
		return $this->_getVar('_query', $key, $default);
	}
	
	/**
	 * Retorna um cookie da requisição.
	 * 
	 * @param string|null $key
	 * @param mixed $default : o valor padrão de retorno, caso não exista o parâmetro $key
	 * @return array|mixed|null
	 */
	public function getCookie($key = null, $default = null) {
		return $this->_getVar('_cookie', $key, $default);
	}
	
	/**
	 * Retorna uma variável da requisição (_get, _post, _cookie ou _files).
	 * 
	 * @param string $varName
	 * @param string|null $key
	 * @param mixed $default
	 * @return array|mixed|null
	 */
	private function _getVar($varName, $key, $default) {
		if($key === null) {
			return $this->{$varName};
		}
		$key = (string) $key;
		return isset($this->{$varName}[$key]) ? $this->{$varName}[$key] : $default;
	}
	
	/**
	 * Seta uma variável da requisição (_get ou _post)
	 * 
	 * @param string $varName
	 * @param string|array $spec : se é uma string, o parâmetro $value é obrigatório
	 * 							   se é um array, deve ser associativo nome => valor
	 * @param mixed $value
	 * @throws Request_Exception : se $spec não é um array e $value é NULL
	 * @return Request : fluent interface
	 */
	private function _setVar($varName, $spec, $value) {
		if($value === null){
			if(is_array($spec)) {
				foreach($spec as $key => $value) {
					$this->_setVar($vaName, $key, $value);
				}
				return $this;
			} else {
				throw new Request_Exception(sprintf('Argumentos inválidos para o médodo %s;	deve ser um
															array de valores ou um par chave/valor', __FUNCTION__));
			}
		}
		
		$this->{$varName}[(string) $spec] = $value;
		return $this;
	}
	
	/**
	 * Retorna o método da requisição.
	 * @return string|null
	 */
	public function getMethod() {
		return $this->getServer('REQUEST_METHOD');
	}
	
	/**
	 * Faz uso dos detectores para características da requisição.
	 * @param string $type
	 */
	public function is($type) {
		$type = strtolower($type);
		if(!isset($this->_detectors[$type])) {
			return false;
		}
	
		$detector = $this->_detectors[$type];
		$envVar = $this->getServer($detector['env']);
	
		if(isset($detector['env'])) {
			if(isset($detector['value'])) {
				return ($envVar == $detector['value']);
			}
				
			if(isset($detector['pattern'])) {
				return (bool) preg_match($detector['pattern'], $envVar);
			}
				
			if(isset($detector['options'])) {
				$pattern = '/' . join('|', $detect['options']) . '/i';
				return (bool) preg_match($pattern, $envVar);
			}
		}
	
		if(isset($detector['callback']) && is_callable($detector['callback'])) {
			return call_user_func($detect['callback'], $this);
		}
	
		return false;
	}
	
	/**
	 * Adiciona um detector na lista de detectores que a requisição pode utilizar.
	 *
	 * Existem 4 formatos diferentes para a criação de detectores:
	 * <ul>
	 * 	<li>
	 * 		addDetector('post', array('env' => 'REQUEST_METHOD', 'value' => 'POST'))
	 * 		Comparação com alguma variável do ambiente.
	 * 	</li>
	 * 	<li>
	 * 		addDetector('iphone', array('env' => 'Controller_USER_AGENT', 'pattern' => '/iPhone/i'))
	 * 		Comparação com alguma variável do ambiente através de uma expressão regular.
	 * 	</li>
	 * 	<li>
	 * 		addDetector('mobile', array('env' => 'Controller_USER_AGENT', 'options' => array('Fennec', 'Opera Mini'))
	 * 		Comparação com uma lista de valores, com a qual é gerada uma expressão regular para comparação.
	 * 	</li>
	 * <li>
	 * 		addDetector('custom', array('env' => 'Controller_USER_AGENT', 'callback' => 'someFunction')
	 * 		Utiliza o callback informado para manipular a checagem. O único argumento passado
	 * 		para o callback é o objeto Controller_Request. O tipo de retorno deve ser booleano.
	 * 	</li>
	 * </ul>
	 *
	 *
	 * @param string $name
	 * @param array $options
	 */
	public function addDetector($name, $options) {
		$name = strtolower($name);
		$this->_detectors[$name] = $options;
	}
	
	/**
	 * Realiza a leitura do conteúdo de 'php://input'. 
	 * Útil quando interagimos com requisições JSON ou XML.
	 * 
	 * Conseguindo um input com uma função de decodificação:
	 * $request->input('json_decode');
	 * 
	 * Utilizando um callback com parâmetros:
	 * $request->input('someFunction', $arg1, [[$arg2], $arg3, ...]);
	 * 
	 * @param string $callback
	 * @param mixed $arg1 [OPCIONAL]
	 * @param mixed $_ [OPCIONAL]
	 * @return string
	 */
	public function getRawBody($callback) {
		$body = $this->_readInput();
		if(is_callable($callback)){
			$argv = func_get_args();
			if(!empty($argv)) {
				$callback = array_shift($argv);
				array_unshift($argv, $body);
				return call_user_func_array($callback, $argv);
			}
		}
		return $body;
	}
	
	/**
	 * Lê o conteúdo de 'php://input'
	 * @return string
	 */
	private function _readInput() {
		if(empty($this->_rawBody)) {
			try {
				$handler = fopen('php://input', 'r');
				$contents = stream_get_contents($handler);
			} catch(ErrorException $e) {
				$contents = '';
			}
			$this->_rawBody = $contents;
		}
		return $this->_rawBody;
	}
	
	/**
	 * Retorna um header da requisição.
	 * 
	 * @param string $header
	 * @throws Request_Exception
	 * @return string|null
	 */
	public function getHeader($header) {
		if(empty($header)) {
			throw new Request_Exception('O nome do header HTTP é necessário!');
		}
		
		$varName = 'Controller_' . strtoupper(str_replace('-', '_', $header));
		return $this->getServer($varName);
	}
	
	/**
	 * Retorna o scheme da requisição.
	 * 
	 * @return string
	 */
	public function getScheme() {
		return $this->getServer('HTTPS') == 'on' ? self::SCHEME_HTTPS : self::SCHEME_HTTP;
	}
	
	/**
	 * Retorna uma variável do servidor.
	 * 
	 * @param string $varName
	 * @return string|null
	 */
	public function getServer($varName) {
		return Environment::getVar($varName);
	}
	
	/**
	 * Retorna o host da requisição.
	 * 
	 * @return string
	 */
	public function getHost() {
		static $host;
		
		if(empty($host)) {
			$host = $this->getServer('Controller_HOST');
			if($host !== null) {
				return $host;
			}
			
			$scheme = $this->getScheme();
			$name   = $this->getServer('SERVER_NAME');
			$port   = $this->getServer('SERVER_PORT');
			
			if($name === null) {
				$host = '';
			} else if($scheme == self::SCHEME_HTTP && $port = 80 || $scheme == self::SCHEME_HTTPS && $port = 443) {
				$host = $name;
			} else {
				$host = $name . ':' . $port;
			}
		}
		return $host;
	}
	
	/**
	 * Retorna o IP do cliente.
	 * 
	 * @param boolean $secure : TRUE se houver suspeita que o cliente pode alterar seu próprio IP
	 * @return string|null
	 */
	public function getClientIp($secure = true) {
		static $clientIp;
		if(empty($clientIp)) {
			$clientIp = $this->getServer('REMOTE_ADDR');
			if($secure){ 
				if(($ip = $this->getServer('Controller_CLIENT_IP')) !== null) {
					$clientIp = $ip;
				} else if(($ip = $this->getServer('Controller_X_FORWARDED_FOR')) !== null) {
					$clientIp = $ip;
				}
			} 
		}
		
		return $clientIp;
	}
	
	/**
	 * Retorna a URL de referência.
	 * 
	 * @return string
	 */
	public function getReferer() {
		static $ref;
		if(empty($ref)) {
			$ref = Environment::getVar('Controller_REFERER');
			$forwarded = Environment::getVar('Controller_X_FORWARDED_HOST');
			if($forwarded) {
				$ref = $forwarded;
			}
			
			if(!$ref) {
				$ref = $this->_baseUrl;
			}
		}

		return $ref;
	}

	/**
	 * Retorna informações sobre o user agent da requisição.
	 * @param string|array|null $value : qual informação retornar. Valores possíveis:
	 * <ul>
	 * 	<li>browser</li>
	 * 	<li>version</li>
	 * 	<li>plataform</li>
	 * 	<li>robot</li>
	 *  	<li>raw</li>
	 * </ul>
	 *
	 * Se nenhum valor for informado, serão retornados todos os possíveis.
	 *
	 * @return string|array
	*/
	public function getUserAgent($value = null) {
		return $this->_userAgent->getInfo($value);		
	}
	
	/**
	 * Verifica se a requisição já foi despachada.
	 * 
	 * @return boolean
	 */
	public function isDispatched() {
		return $this->_dispatched;
	}
	
	/**
	 * Seta a flag indicando se a requisição já foi despachada.
	 * 
	 * @param boolen $opt
	 * @return Request : fluent interface
	 */
	public function setDispatched($opt) {
		$this->_dispatched = (bool) $opt;
		return $this;
	}
}
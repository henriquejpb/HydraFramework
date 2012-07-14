<?php
/**
 * Representa o n�cleo do sistema.
 * 
 * @package Core
 * @author <a href="mailto:rick.hjpbacelos@gmail.com">Henrique Barcelos</a>
 */

//Depend�ncias diretas de Core.

require_once 'Localization.php';
require_once 'Loader.php';

class Core {
	const VERSION = '1.0';
	
	const DS = DIRECTORY_SEPARATOR;
	const PS = PATH_SEPARATOR;
	
	const ENVIRONMENT 	= 'environment';
	const PRODUCTION 	= 1;
	const DEVELOPMENT 	= 0;
	
	const BASE_URL 		= 'baseUrl';
	const ROOT			= 'root';
	
	const APP_ROOT		= 'appRoot';
	const CORE_LIB		= 'coreLib';
	
	const SYSTEM_DIR	= 'systemDir';
	const LIBRARY_DIR	= 'libraryDir';
	const CONFIG_DIR	= 'configDir';
	const INIT_DIR		= 'initDir';
	const DEF_DIR		= 'defDir';
	
	const LOCALIZATION	= 'localization';
	const LIBRARIES		= 'libraries';

	const LOGGING		= 'logging';
	const LOG_DIR		= 'logDir';
	
	const CACHE_LIFE	= 'cacheLife';
	const CACHING		= 'caching';
	const CACHE_DIR		= 'cacheDir';
	
	const CHARSET		= 'charset';
	const CONTENT_TYPE	= 'contentType';
	const INDEX_FILE	= 'indexFile';
	
	/**
	 * Armazena o objeto respons�vel por carregar as classes da aplica��o.
	 * 
	 * @var Loader
	 */
	private $_loader;
	
	/**
	 * O ambiente em que a aplica��o est� rodando (DEVELOPMENT ou PRODUCTION).
	 * @var int
	 */
	private $_environment = self::DEVELOPMENT;
	
	/**
	 * Armazena o subdiret�rio do sistema.
	 * @var string
	 */
	private $_systemDir = 'system/';
	
	/**
	 * Armazena o subdiret�rio das bibliotecas
	 * @var unknown_type
	 */
	private $_libraryDir = 'library/Hydra';
	
	/**
	 * Armazena o diret�rio de log.
	 * @var string
	 */
	private $_logDir = 'system/log/';
	
	/**
	 * Se o log da aplica��o ser� gravado em disco ou n�o
	 * @var boolean
	 */
	private $_logging = true;
	
	/**
	 * Armazena o diret�rio de cache.
	 * @var string
	 */
	private $_cacheDir = 'system/cache/';
	
	/**
	 * Armazena o tempo de vida padr�o do cache (em segundos ou tempo relativo)
	 * @var string|integer
	 */
	private $_cacheLife = '+ 1 WEEK';
	
	/**
	 * Se a aplica��o far� ou n�o uso de cache
	 * @var boolean
	 */
	private $_caching = true;
	
	/**
	 * O diret�rio que cont�m configura��es do sistema.
	 * @var string
	 */
	private $_configDir = 'system/config/';
	
	/**
	 * O diret�rio que cont�m as inicializa��es do sistema.
	 * @var string
	 */
	private $_initDir = 'system/init/';
	
	/**
	 * O diret�rio que cont�m as defini��es do sistema.
	 * @var string
	 */
	private $_defDir = 'system/def/';
	
	/**
	 * O charset da aplica��o.
	 * @var string
	 */
	private $_charset = 'iso-8859-1';
	
	/**
	 * Os nomes das bibliotecas utilizadas no sistema.
	 * @var array
	 */
	private $_libraries = array();
	
	/**
	 * O diret�rio raiz de toda a aplica��o
	 * @var string
	 */
	private $_root;
	
	/**
	 * O diret�rio ra�z espec�fico da aplica��o.
	 * @var string
	 */
	private $_appRoot = 'app/';
	
	/**
	 * Armazena o nome da biblioteca principal da aplica��o
	 * @var string
	 */
	private $_coreLib = 'Hydra';
	
	/**
	 * Armazena as configura��es de localiza��o da aplica��o
	 * @var Localization
	 */
	private $_localization;
	
	/**
	 * Inst�ncia singleton de Core.
	 * @var Core
	 */
	private static $_instance;
	
	/**
	 * Construtor.
	 * 
	 * As configura��es poss�veis s�o:
	 * - *systemDir 	(string) : o diret�rio destinado aos recursos do sistema (log, cache, etc)
	 * - *appRoot	(string) : o diret�rio ra�z da aplica��o
	 * 
	 * - libraries	(array) : bibliotecas de scripts
	 * - *modules	(array) : os m�dulos da aplica��o
	 * - *mainModule (array) : o m�dulo principal da aplica��o
	 * 
	 * - cacheDir	(string) : o diret�rio para cache (subdiret�rio de systemDir)
	 * - cacheLife	(int) : tempo de vida dos arquivos de cache
	 * - caching	(boolean) : se o sistema deve ou n�o fazer uso de cache
	 * 
	 * - logDir		(string) : o diret�rio para log (subdiret�rio de systemDir)
	 * - logging	(boolean) : se o sistema deve ou n�o manter logs
	 * 
	 * - charset 	(string) : o charset utilizado na aplica��o
	 *   
	 * @param array $options
	 */
	private function __construct($options) {
		if(!is_array($options)) {
			$options = array(self::APP_ROOT => $options);
		}
		
		$this->_verifyConfig($options);
		
		foreach($options as $key => $value) {
			switch($key) {
				case self::ROOT:
					$this->_root = (string) $value;
					break;
				case self::SYSTEM_DIR:
					$this->_systemDir = (string) $value;
					break;
				case self::APP_ROOT:
					$this->setAppRoot($value);
					break;
				case self::LIBRARIES:
					$this->setLibraries((array) $value);
					break;
				case self::CACHE_DIR:
					$this->_cacheDir = (string) $value;
					break;
				case self::CACHE_LIFE:
					$this->setCacheLife($value);
					break;
				case self::CACHING:
					$this->_caching = (bool) $value;
					break;
				case self::LOG_DIR:
					$this->_logDir = (string) $value;
					break;
				case self::LOGGING:
					$this->_logging = (bool) $value;
					break;
				case self::CHARSET:
					$this->setCharset($value);
					break;
				case self::SYSTEM_DIR:
					$this->_systemDir = (string) $value;
					break;
				case self::LIBRARY_DIR:
					$this->_libraryDir = (string) $value;
					break;
				case self::INIT_DIR:
						$this->_initDir = (string) $value;
						break;
				case self::DEF_DIR:
						$this->_defDir = (string) $value;
						break;
				case self::CORE_LIB:
					$this->setCoreLib($value);
					break;
				case self::LOCALIZATION:
					$this->setLocalization($value);
					break;
			}
		}
		
		$this->_init();
	}
	
	/**
	 * Inicializa a aplica��o.
	 * 
	 * @return void
	 */
	private function _init() {
		$this->_setupApplicationDirectories();
		$this->_setupLocalization();
		$this->_setupEnvironment();
		$this->_setupAutoload();		
	}
	
	private function _setupAutoload() {
		$this->_loader = new Loader($this->_libraryDir);
	}
	
	/**
	 * Configura os diret�rios da aplica��o.
	 * @return void
	 */
	private function _setupApplicationDirectories() {
		$this->setRoot($this->_root);
		$this->setSystemDir($this->_systemDir);
		$this->setLibraryDir($this->_libraryDir);
		$this->setCacheDir($this->_cacheDir);
		$this->setLogDir($this->_logDir);
	}
	
	/**
	 * Configura a localiza��o da aplica��o
	 * @return void
	 */
	private function _setupLocalization() {
		$loc = $this->_localization;
		if($loc instanceof Localization) {
			date_default_timezone_set($loc->getTimezone());
			call_user_func_array('setlocale', array_merge(array(LC_ALL), $loc->getLocale()));
		}
	}
	
	/**
	 * Retorna a inst�ncia singleton da classe.
	 * @param array $options
	 */
	public static function getInstance($options = array()) {
		if(! self::$_instance instanceof self) {
			self::$_instance = new self($options);
		}
		return self::$_instance;
	}
	
	
	/**
	 * Verifica as configura��es passadas ao construtor.
	 * 
	 * @param array $config
	 * @throws Exception
	 */
	private function _verifyConfig(array &$config) {
		if(!isset($config[self::ROOT])) {
			throw new Exception('O diret�rio raiz da aplica��o n�o foi definido!');
		}

		if(isset($config[self::CACHING]) && $config[self::CACHING] === TRUE
			&& !isset($config[self::CACHE_DIR])) {
			throw new Exception('O diret�rio de cache da aplica��o n�o foi definido!');
		}
		
		if(isset($config[self::LOGGING]) && $config[self::LOGGING] === TRUE
			&& !isset($config[self::LOG_DIR])) {
			throw new Exception('O diret�rio de log da aplica��o n�o foi definido!');
		}
	}
	
	/**
	 * Seta o charset da aplica��o.
	 * 
	 * @param string $charset
	 * @return Core : fluent interface
	 */
	public function setCharset($charset) {
		$this->_charset = (string) $value;
		return $this;
	}
	
	/**
	 * Retorna o charset da aplica��o.
	 * 
	 * @return string
	 */
	public function getCharset() {
		return $this->_charset;
	}
	
	/**
	 * Seta o diret�rio raiz de toda a aplica��o.
	 * (� o diret�rio que cont�m a aplica��o, a library, os diret�rios do sitema, etc.)
	 * 
	 * @param string $dir
	 * @throws Exception
	 */
	public function setRoot($dir) {
		if(!is_dir($dir)) {
			throw new Exception(sprintf('O diret�rio raiz informado %s n�o � um diret�rio v�lido.', $dir));
		}
		
		$this->_root = realpath($dir) . self::DS;
		return $this;
	}
	
	/**
	 * Retorna o diret�rio raiz de toda a aplica��o.
	 * 
	 * @return string
	 */
	public function getRoot() {
		return $this->_root;
	}
	
	/**
	 * Seta o diret�rio raiz espec�fico da aplica��o.
	 * (Aqui ficam os diret�rios para Controllers, Views, Models, 
	 *  scripts, imagens, etc., espec�ficos da 
	 * 
	 * @param string $dir
	 * @return Core : fluent interface
	 * @throws Exception : caso o diret�rio n�o seja v�lido
	 */
	public function setAppRoot($dir) {
		$this->_setPropertyDir('_appRoot', $dir);
		return $this;
	}
	
	/**
	 * Retorna o diret�rio raiz espec�fico da aplica��o
	 * 
	 * @return string
	 */
	public function getAppRoot() {
		return $this->_appRoot;
	}
	
	/**
	 * @param string $dir
	 * @return Core : fluent interface
	 */
	public function setCacheDir($dir) {
		$this->_setPropertyDir('_cacheDir', $dir);
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getCacheDir() {
		return $this->_cacheDir;
	}
	
	/**
	 * @return boolean
	 */
	public function isCaching() {
		return $this->_caching;
	}
	
	/**
	 * @param string|int $exp
	 * @return Core : fluent interface
	 * @throws Exception
	 */
	public function setCacheLife($exp) {
		if(is_int($exp) || is_string($exp)) {
			$this->_cacheLife = $exp;
		} else {
			throw new Exception('O argumento de ' . __FUNCTION__ . ' deve ser int ou string!');
		}
		return $this;
	}
	
	/**
	 * @param string $dir
	 * @return Core : fluent interface
	 */
	public function setLogDir($dir) {
		$this->_setPropertyDir('_logDir', $dir);
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getLogDir() {
		return $this->_logDir;
	}
	
	/**
	 * @return boolean
	 */
	public function isLogging() {
		return $this->_logging;
	}
	
	/**
	 * @param string $dir
	 */
	public function setLibraryDir($dir) {
		$this->_setPropertyDir('_libraryDir', $dir);
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getLibraryDir() {
		return $this->_libraryDir;
	}
	
	/**
	 * @param string $dir
	 * @return Core : fluent interface
	 */
	public function setSystemDir($dir) {
		$this->_setPropertyDir('_systemDir', $dir);
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getSystemDir() {
		return $this->_systemDir;
	}
	
	/**
	 * @param string
	 */
	public function setConfigDir($dir) {
		$this->_setPropertyDir('_configDir', $dir);
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getConfigDir() {
		return $this->_configDir;
	}
	
	/**
	 * @param string
	 */
	public function setInitDir($dir) {
		$this->_setPropertyDir('_initDir', $dir);
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getInitDir() {
		return $this->_initDir;
	}
	
	/**
	 * @param string
	 */
	public function setDefDir($dir) {
		$this->_setPropertyDir('_defDir', $dir);
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getDefDir() {
		return $this->_configDir;
	}
	
	/**
	 * Seta o diret�rio para uma propriedade (_cacheDir, _logDir, etc).
	 * Todos os diret�rios devem ser subdiret�rios de _appRoot.
	 * 
	 * @param string $property : o nome da propriedade
	 * @param string $dir : o diret�rio a ser setado.
	 * @throws Exception : caso o diret�rio n�o seja v�lido
	 */
	private function _setPropertyDir($property, $dir) {
		$fullPath = $this->_root . self::DS . $dir;
		if(!is_dir($fullPath)) {
			if(!mkdir($fullPath, 0777, true)) {
				throw new Exception(sprintf('O diret�rio informado %s n�o � um diret�rio v�lido e n�o p�de ser criado.', $fullPath));
			}
		}
		$this->$property = realpath($dir) . self::DS;
	}
	
	/**
	 * Seta o nome da biblioteca padr�o.
	 * 
	 * @param string $lib
	 * @return Core : fluent interface
	 */
	public function setCoreLib($lib) {
		$this->_coreLib = (string) $lib;
		return $this;
	}
	
	/**
	 * Retorna o nome da biblioteca padr�o.
	 * @return string
	 */
	public function getCoreLib() {
		return $this->_coreLib;
	}

	/**
	 * Seta uma localiza��o para a aplica��o.
	 * 
	 * @param Localization $loc
	 * @return Core : fluent interface
	 */
	public function setLocalization(Localization $loc) {
		$this->_localization = $loc;
		$this->_setupLocalization();
		return $this;
	}
	
	/**
	 * Retorna a localiza��o da aplica��o.
	 * 
	 * @return Localization
	 */
	public function getLocalization() {
		return $this->_localization;
	}
	
	/**
	 * Seta o ambiente da aplica��o
	 * @param int $env : constantes Core::PRODUCTIOIN ou Core::DEVELOPMENT
	 */
	public function setEnvironment($env) {
		if($env == self::PRODUCTION || $env == self::DEVELOPMENT) {
			$this->_environment = $env;
			$this->_setupEnvironment();
		}
	}
	
	/**
	 * Seta o ambiente de desenvolvimento.
	 * 
	 * @return void
	 */
	private function _setupEnvironment() {
		if ($this->_environment == self::DEVELOPMENT) {
			error_reporting(E_ALL);
			ini_set('display_errors','On');
		} else {
			error_reporting(E_ALL);
			ini_set('display_errors','Off');
			ini_set('log_errors', 'On');
			ini_set('error_log', $this->getLogDir().'error.log');
		}
	}
	
	/**
	 * Retorna um arquivo de inicializa��o.
	 *
	 * @param string $fileName
	 * @param string $ext
	 * @throws Exception
	 */
	public function getInitFile($fileName, $ext = 'php') {
		$filePath = $this->_initDir . $fileName . '.' . preg_replace('/^\./', '', $ext);
		if(!is_file($filePath)) {
			throw new Exception(sprintf('Arquivo de inicializa��o "%s" inexistente.', $filePath));
		}
	
		return realpath($filePath);
	}
	
	/**
	 * Retorna um arquivo de configura��o.
	 *
	 * @param string $fileName
	 * @param string $ext
	 * @throws Exception
	 */
	public function getConfigFile($fileName, $ext = 'php') {
		$filePath = $this->_configDir . $fileName . '.' . preg_replace('/^\./', '', $ext);
		if(!is_file($filePath)) {
			throw new Exception(sprintf('Arquivo de configura��o "%s" inexistente.', $filePath));
		}
		
		return realpath($filePath);
	}
	
	/**
	 * Retorna um arquivo de defini��es.
	 *  
	 * @param string $fileName
	 * @param string $ext
	 * @throws Exception
	 */
	public function getDefFile($fileName, $ext = 'php') {
		$filePath = $this->_defDir . $fileName . '.' . preg_replace('/^\./', '', $ext);
		if(!is_file($filePath)) {
			throw new Exception(sprintf('Arquivo de defini��o "%s" inexistente.', $filePath));
		}
		
		return realpath($filePath);
	}
}
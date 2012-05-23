<?php
/**
 * Representa o núcleo do sistema.
 * 
 * @package Core
 * @author <a href="mailto:rick.hjpbacelos@gmail.com">Henrique Barcelos</a>
 */

//Dependências diretas de Core.

require_once 'Localization.php';

class Core {
	const VERSION = '1.0';
	
	const DS = DIRECTORY_SEPARATOR;
	const PS = PATH_SEPARATOR;
	
	const ENVIRONMENT = 'environment';
	const PRODUCTION 	= 1;
	const DEVELOPMENT 	= 0;
	
	const BASE_URL 		= 'baseUrl';
	const ROOT			= 'root';
	
	const APP_ROOT		= 'appRoot';
	const CORE_LIB		= 'coreLib';
	
	const SYSTEM_DIR	= 'systemDir';
	const LIBRARY_DIR	= 'libraryDir';
	const CONFIG_DIR	= 'configDir';
	
	const LOCALIZATION		= 'localization';
	const LIBRARIES			= 'libraries';

	const LOGGING		= 'logging';
	const LOG_DIR		= 'logDir';
	
	const CACHE_LIFE	= 'cacheLife';
	const CACHING		= 'caching';
	const CACHE_DIR		= 'cacheDir';
	
	const CHARSET		= 'charset';
	const CONTENT_TYPE	= 'contentType';
	const INDEX_FILE	= 'indexFile';
	
	/**
	 * O ambiente em que a aplicação está rodando (DEVELOPMENT ou PRODUCTION).
	 * @var int
	 */
	private $_environment = self::DEVELOPMENT;
	
	/**
	 * Armazena o subdiretório do sistema.
	 * @var string
	 */
	private $_systemDir = 'system/';
	
	/**
	 * Armazena o subdiretório das bibliotecas
	 * @var unknown_type
	 */
	private $_libraryDir = 'library/';
	
	/**
	 * Armazena o diretório de log.
	 * @var string
	 */
	private $_logDir = 'system/log/';
	
	/**
	 * Se o log da aplicação será gravado em disco ou não
	 * @var boolean
	 */
	private $_logging = true;
	
	/**
	 * Armazena o diretório de cache.
	 * @var string
	 */
	private $_cacheDir = 'system/cache/';
	
	/**
	 * Armazena o tempo de vida padrão do cache (em segundos ou tempo relativo)
	 * @var string|integer
	 */
	private $_cacheLife = '+ 1 WEEK';
	
	/**
	 * Se a aplicação fará ou não uso de cache
	 * @var boolean
	 */
	private $_caching = true;
	
	/**
	 * O diretório que contém configurações do sistema.
	 * @var string
	 */
	private $_configDir = 'system/config/';
	
	/**
	 * O charset da aplicação.
	 * @var string
	 */
	private $_charset = 'iso-8859-1';
	
	/**
	 * Os nomes das bibliotecas utilizadas no sistema.
	 * @var array
	 */
	private $_libraries = array();
	
	/**
	 * O diretório raíz da aplicação.
	 * @var string
	 */
	private $_appRoot;
	
	/**
	 * Armazena o nome da biblioteca principal da aplicação
	 * @var string
	 */
	private $_coreLib = 'Hydra';
	
	/**
	 * Armazena as configurações de localização da aplicação
	 * @var Localization
	 */
	private $_localization;
	
	/**
	 * Instância singleton de Core.
	 * @var Core
	 */
	private static $_instance;
	
	/**
	 * Construtor.
	 * 
	 * As configurações possíveis são:
	 * - *systemDir 	(string) : o diretório destinado aos recursos do sistema (log, cache, etc)
	 * - *appRoot	(string) : o diretório raíz da aplicação
	 * 
	 * - libraries	(array) : bibliotecas de scripts
	 * - *modules	(array) : os módulos da aplicação
	 * - *mainModule (array) : o módulo principal da aplicação
	 * 
	 * - cacheDir	(string) : o diretório para cache (subdiretório de systemDir)
	 * - cacheLife	(int) : tempo de vida dos arquivos de cache
	 * - caching	(boolean) : se o sistema deve ou não fazer uso de cache
	 * 
	 * - logDir		(string) : o diretório para log (subdiretório de systemDir)
	 * - logging	(boolean) : se o sistema deve ou não manter logs
	 * 
	 * - charset 	(string) : o charset utilizado na aplicação
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
				case self::CORE_LIB:
					$this->setCoreLib($value);
					break;
				case self::LOCALIZATION:
					$this->setLocalization($value);
					break;
			}
		}
		
		$this->_setupApplicationDirectories();
		$this->_setupLocalization();
		$this->_setupEnvironment();
	}
	
	/**
	 * Configura os diretórios da aplicação.
	 * @return void
	 */
	private function _setupApplicationDirectories() {
		$this->setSystemDir($this->_systemDir);
		$this->setLibraryDir($this->_libraryDir);
		$this->setCacheDir($this->_cacheDir);
		$this->setLogDir($this->_logDir);
	}
	
	/**
	 * Configura a localização da aplicação
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
	 * Retorna a instância singleton da classe.
	 * @param array $options
	 */
	public static function getInstance($options = array()) {
		if(! self::$_instance instanceof self) {
			self::$_instance = new self($options);
		}
		return self::$_instance;
	}
	
	
	/**
	 * Verifica as configurações passadas ao construtor.
	 * 
	 * @param array $config
	 * @throws Exception
	 */
	private function _verifyConfig(array &$config) {
		if(!isset($config[self::APP_ROOT])) {
			throw new Exception('O diretório raiz da aplicação não foi definido!');
		}

		if(isset($config[self::CACHING]) && $config[self::CACHING] === TRUE
			&& !isset($config[self::CACHE_DIR])) {
			throw new Exception('O diretório de cache da aplicação não foi definido!');
		}
		
		if(isset($config[self::LOGGING]) && $config[self::LOGGING] === TRUE
			&& !isset($config[self::LOG_DIR])) {
			throw new Exception('O diretório de log da aplicação não foi definido!');
		}
	}
	
	/**
	 * Seta o charset da aplicação.
	 * 
	 * @param string $charset
	 * @return Core : fluent interface
	 */
	public function setCharset($charset) {
		$this->_charset = (string) $value;
		return $this;
	}
	
	/**
	 * Retorna o charset da aplicação.
	 * 
	 * @return string
	 */
	public function getCharset() {
		return $this->_charset;
	}
	
	/**
	 * @param string $dir
	 * @return Core : fluent interface
	 * @throws Exception : caso o diretório não seja válido
	 */
	public function setAppRoot($dir) {
		if(!is_dir($dir)) {
			throw new Exception(sprintf('O diretório raiz informado %s não é um diretório válido.', $dir));
		}
		
		$this->_appRoot = realpath($dir) . self::DS;
		return $this;
	}
	
	/**
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
	
	public function getConfigDir() {
		return $this->_configDir;
	}
	
	/**
	 * Seta o diretório para uma propriedade (_cacheDir, _logDir, etc).
	 * Todos os diretórios devem ser subdiretórios de _appRoot.
	 * 
	 * @param string $property : o nome da propriedade
	 * @param string $dir : o diretório a ser setado.
	 * @throws Exception : caso o diretório não seja válido
	 */
	private function _setPropertyDir($property, $dir) {
		$fullPath = $this->_appRoot . self::DS . $dir;
		if(!is_dir($fullPath)) {
			if(!mkdir($fullPath, 0777, true)) {
				throw new Exception(sprintf('O diretório informado %s não é um diretório válido e não pôde ser criado.', $fullPath));
			}
		}
		$this->$property = realpath($dir) . self::DS;
	}
	
	/**
	 * Seta o nome da biblioteca padrão.
	 * 
	 * @param string $lib
	 * @return Core : fluent interface
	 */
	public function setCoreLib($lib) {
		$this->_coreLib = (string) $lib;
		return $this;
	}
	
	/**
	 * Retorna o nome da biblioteca padrão.
	 * @return string
	 */
	public function getCoreLib() {
		return $this->_coreLib;
	}

	/**
	 * Seta uma localização para a aplicação.
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
	 * Retorna a localização da aplicação.
	 * 
	 * @return Localization
	 */
	public function getLocalization() {
		return $this->_localization;
	}
	
	/**
	 * Seta o ambiente da aplicação
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
	 * Retorna o conteúdo de um arquivo de configuração.
	 *  
	 * @param string $fileName
	 * @param string $ext
	 * @throws Exception
	 */
	public function getConfigFile($fileName, $ext = 'php') {
		$filePath = $this->_configDir . $fileName . '.' . preg_replace('/^\./', '', $ext);
		if(!is_file($filePath)) {
			throw new Exception(sprintf('Arquivo de configuração "%s" inexistente.', $filePath));
		}
		
		return include realpath($filePath);
	}
}
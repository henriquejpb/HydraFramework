<?php
/**
 * @author henrique
 * @version 0.1 beta
 * 
 * Implementa��o da base de uma View Engine
 */
abstract class View_Abstract {
	/**
	 * Armazena as vari�veis que ser�o passadas ao template.
	 * 
	 * @var array
	 */
	protected $_vars = array();

	/**
	 * Armazena o nome do template utilizado na view.
	 * 
	 * @var string
	 */
	protected $_template;
	
	/**
	 * Armazena o caminho real para o template.
	 * 
	 * @var string
	 */
	protected $_templatePath;
	
	/**
	 * Armazena o caminho base onde se encontram os templates.
	 * 
	 * @var string
	 */
	protected $_path;
	
	/**
	 * Armazena um valor padr�o par ao caminho base dos templates.
	 * 
	 * @var string
	 */
	protected static $_defaultPath;
	
	/**
	 * Armazena a extens�o dos arquivos de template.
	 * 
	 * @var string
	 */
	protected $_templateExtension = 'phtml';
	
	/**
	 * Armazena a extens�o padr�o dos arquivos de template.
	 * 
	 * @var string
	 */
	protected static $_defaultTemplateExtension = 'phtml';
	
	/**
	 * Se TRUE, uma exce��o ser� disparada ao tentar 
	 * acessar uma vari�vel inexistente.
	 * 
	 * @var boolean
	 */
	protected $_strictVars = true;
	
	/**
	 * @param string $spec : o nome do template (sem extens�o)
	 * @param array $vars : as vari�veis a serem passadas ao template
	 * @param array $opt : um array associativo. Chaves:
	 * 		path => o caminho para o diret�rio do template. Se n�o informado, o padr�o ser� usado
	 * 		templateExtension => a extens�o dos arquivos de template. O padr�o � 'phtml'
	 */
	public function __construct($spec, array $vars = array(), array $opt = array()) {
		if(isset($opt['path'])) {
			$this->setPath($opt['path']);
		} else {
			$this->_path = self::$_defaultPath;
		}
		
		if(isset($opt['templateExtension'])) {
			$this->setTemplateExtension($opt['templateExtension']);
		} else {
			$this->_templateExtension = self::$_defaultTemplateExtension;
		}
		
		$this->assign($vars);
		$this->setTemplate($spec);
	}
	
	/**
	 * Factory Method.
	 * 
	 * @param string $spec
	 * @param array $vars
	 * @param array $opt
	 * @see View_Abstract::__construct
	 */
	abstract public static function factory($spec, array $vars = array(), array $opt = array());
	
	/**
	 * @param string $var
	 * @param mixed $value
	 */
	public function __set($var, $value) {
		$this->assign($var, $value);
	}
	
	/**
	 * @param string $var
	 * @return mixed
	 */
	public function __get($var) {
		return $this->getVar($var);
	}
	
	/**
	 * Seta o arquivo de template para a view.
	 * 
	 * Para templates dentro de m�dulos, utilize a nota��o:
	 * 		<code>module.templateName</code>
	 * Exemplo:
	 * 		<code>$view->setTemplate('user.list')</code>
	 * 
	 * @param string $spec : o nome do template.
	 * @return Abstract_View : fluent interface
	 */
	public function setTemplate($spec) {
		$this->_template = (string) $spec;
		$tplPath = $this->_path . $this->_normalizeTemplateName($spec) . '.' . $this->_templateExtension;
		if(!FileSystem_File::isFile($tplPath)) {
			$e = new View_Exception(sprintf('O template %s n�o � um arquivo v�lido!', $tplPath));
			$e->setView($this);
			throw $e;
		}
		
		$this->_templatePath = $tplPath;
		return $this;
	}
	
	/**
	 * Substitui o . por / ou \ para encontrar o caminho do template.
	 * 
	 * @param string $spec
	 * @return string
	 */
	protected function _normalizeTemplateName($spec) {
		return str_replace('.', FileSystem::SEPARATOR, $spec);
	}
	
	/**
	 * Retorna o nome do template.
	 * 
	 * @return string
	 */
	public function getTemplate() {
		return $this->_template;
	}
	
	/**
	 * Seta o caminho para o template.
	 * 
	 * @param string $path
	 * @return View_Abstract : fluent interface
	 * @throws View_Exception : caso $path n�o aponte para um diret�rio
	 */
	public function setPath($path) {
		self::_verifyPath($path);
		$this->_path = (string) new FileSystem_Directory($path);
		return $this;
	}
	
	/**
	 * Retorna o caminho para o template.
	 * 
	 * @param string $path
	 */
	public function getPath($path) {
		return $this->_path;
	}
	
	/**
	 * Seta a extens�o para o template.
	 * 
	 * @param unknown_type $ext
	 * @return View_Abstract
	 */
	public function setTemplateExtension($ext) {
		$this->_templateExtension = (string) $ext;
		return $this;
	}
	
	/**
	 * Retorna a extens�o do template.
	 * 
	 * @return string
	 */
	public function getTemplateExtension() {
		return $this->_templateExtension;
	}
	
	/**
	 * Atribui uma vari�vel de template.
	 * 
	 * @param string|array $var
	 * @param mixed $value
	 */
	public function assign($var, $value = null) {
		if(is_array($var)) {
			foreach($var as $key => $value) {
				$this->_vars[(string) $key] = $value;
			}
		} else {
			$this->_vars[(string) $var] = $value;
		}
		return $this;
	}
	
	/**
	 * Retorna uma vari�vel.
	 * 
	 * @param string $var
	 * @return mixed
	 * @throws View_Exception : caso a vari�vel n�o exista e strictVars seja TRUE
	 */
	public function getVar($var) {
		$var = (string) $var;
		if(isset($this->_vars[$var])) {
			return $this->_vars[$var];
		} else if($this->_strictVars === true) {
			throw new View_Exception(sprintf('A vari�vel "%s" 
					n�o existe para este template', $var));
		} else {
			return null;
		}
	}
	
	/**
	 * Verifica se a vari�vel de template com nome $var existe.
	 * 
	 * @param string $var
	 */
	public function hasVar($var) {
		return isset($this->_vars[(string) $var]);
	}
	
	/**
	 * Retorna as vari�veis de template.
	 * 
	 * @return string
	 */
	public function getVars() {
		return $this->_vars;
	}
	
	/**
	 * Limpa as vari�veis de template.
	 * 
	 * @return View_Abstract : fluent interface
	 */
	public function clearVars() {
		unsert($this->_vars);
		$this->_vars = array();
		return $this;
	}
	
	/**
	 * Renderiza o template.
	 * 
	 * @return string
	 */
	public function render() {
		ob_start();
		$this->_run();
		return ob_get_clean();
	}
	
	/**
	 * Executa a View.
	 * 
	 * @return void
	 */
	abstract protected function _run();
	
	/**
	 * Seta o diter�rio padr�o para os templates.
	 *
	 * @param string $path
	 * @return void
	 * @throws View_Exception
	 */
	public static function setDefaultPath($path) {
		self::_verifyPath($path);
		self::$_defaultPath = (string) new FileSystem_Directory($path);
	}
	
	/**
	 * Verifica o caminho para os templates, lan�ando uma exce��o se n�o for v�lido.
	 *
	 * @param string $path
	 * @throws View_Exception
	 */
	protected static function _verifyPath($path) {
		if(!FileSystem_Directory::isDir($path)) {
			$e = new View_Exception('O diret�rio para de templates n�o � v�lido');
			$e->setView($this);
			throw $e;
		}
	}
	
	/**
	 * Seta a extens�o padr�o para os templates.
	 * 
	 * @param string $ext
	 * @return void
	 */
	public static function setDefaultTemplateExtension($ext) {
		self::$_defaultTemplateExtension = (string) $ext;
	}
	
	/**
	 * Retorna a extens�o padr�o para os templates.
	 * 
	 * @return string
	 */
	public static function getDefaultTemplateExtension() {
		return self::$_defaultTemplateExtension;
	}
	
	/**
	 * Seta a flag que indica se deve ser lan�ada uma exce��o
	 * ao tentar pegar o valor de uma vari�vel inexistente.
	 * 
	 * @param boolean $opt
	 */
	public function strictVars($opt) {
		$this->_strictVars = (bool) $opt;
	}
	
	/**
	 * Renderiza a View.
	 * 
	 * @return string
	 */
	public function __toString() {
		return $this->render();
	}
}
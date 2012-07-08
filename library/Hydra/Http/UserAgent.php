<?php
/**
 * Armazena informações sobre o USER AGENT da requisição.
 * @author <a href="mailto:rick.hjpbacelos@gmail.com">Henrique Barcelos</a>
 */
class Http_UserAgent {
	/**
	 * Armazena as informações sobre o USER AGENT da requisição
	 * @var array
	 */
	private static $info;

	/**
	 * Armazena os dados do arquivo de configuração contendo os USER AGENTS conhecidos
	 * @var array
	 */
	private static $data;

	/**
	 * Construtor.
	 */
	public function __construct() {
		try {
			if(empty(self::$data)) {
				self::$data = include Core::getInstance()->getDefFile('user_agents');
			}
		} catch (Exception $e) {
				
		}
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
	 * @uses Core::getDefFile
	 */
	public function getInfo($value = null) {
		$userAgent = Environment::getVar('HTTP_USER_AGENT');
		if($value == 'raw' || empty(self::$data)) {
			return $userAgent;
		}

		if(empty($value)) {
			$value = array_keys(self::$data);
		}

		if(is_array($value)) {
			$ret = array();
			foreach($value as $each) {
				$ret[$each] = $this->getInfo($each);
			}
			return $ret;
		}

		if(isset(self::$info[$value])) {
			return self::$info[$value];
		}

		if($value == 'browser' || $value == 'version') {
			$browsers = self::$data['browser'];

			foreach($browsers as $search => $name) {
				if(stripos($userAgent, $search) !== false) {
					self::$info['browser'] = $name;

					if(preg_match('#' . preg_quote($search) . '[^0-9.]*+([0-9.][0-9.a-z]*)#i', $userAgent, $matches)) {
						self::$info['version'] = $matches[1];
					} else {
						self::$info['version'] = null;
					}
					return self::$info[$value];
				}
			}
		} elseif(isset(self::$data[$value])) {
			$group = self::$data[$value];
			foreach($group as $search => $name) {
				if(stripos($userAgent, $search) !== false) {
					return self::$info[$value] = $name;
				}
			}
		}

		return self::$info[$value] = null;
	}
}
<?php
/**
 * Camada básica se funções de segurança.
 * @author henrique
 */
abstract class Security {
	/**
	 * Retira barras invertidas de todos os valores.
	 * 
	 * @param mixed $data : array, IteratorAggregate ou string
	 * @return mixed : os dados com as barras removidas.
	 */
	public static function stripSlashesDeep($data) {
		if(is_array($data) || $data instanceof IteratorAggregate) {
			foreach($data as &$each) {
				$each = self::stripSlashesDeep($each);	
			}
		} else if(is_string($data)) {
			$data = stripslashes($data);
		}
		return $data;
	}
}
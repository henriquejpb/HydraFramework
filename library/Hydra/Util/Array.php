<?php
/**
 * Array melhorado, com suporte � nota��o com ponto.
 * Exemplo:
 * 		<code>$data['a.b.c'] = 1</code>
 * Resultar� em:
 * 		<code>
 * 		array (
 * 			a => array (
 * 				b => array (
 * 					c => 1
 * 				)
 * 			)
 * 		)
 * 		</code>
 *
 * @author <a href="mailto:rick.hjpbarcelos@gmail.com">Henrique Barcelos</a>
 * @package Util
 * @name Util_Array
 * @version 0.1
 * 
 */
class Util_Array implements ArrayAccess, IteratorAggregate, Countable, Serializable {
	/**
	 * Armazena os dados do array.
	 * 
	 * @var array
	 */
	protected $_data = array();
	
	/**
	 * Define se uma exce��o deve ser lan�ada caso haja a tentativa de
	 * acessar um �ndice inv�lido do array.
	 * 
	 * @var boolean
	 */
	protected $_invalidOffsetThrowsException = false;
	
	/**
	 * Armazena o valor cacheado da contagem de elementos do array.
	 * 
	 * @var int
	 */
	protected $_countCache;

	/**
	 * Converte um array em nota��o com ponto para um array PHP.
	 * Exemplo:
	 * 		<code>
	 * 			array('a.b.c' => 'foo', 'a.b.d' => 'bar')
	 * 		</code>
	 * 		Ser� convertido em:
	 * 		<code>
	 * 			array (	
	 * 				a => array (
	 * 					b => array (
	 * 						c => 'foo',
	 * 						d => 'bar'
	 * 					)
	 * 				)
	 * 			)
	 * 		</code>			
	 * 
	 * @param array $in
	 * @return array
	 */
	public static function fromArray(array $in) {
		$out = new self();
		foreach($in as $key => $value) {
			$out->offsetSet($key, $value);
		}
		return $out;
	}
	
	/**
	 * Seta a flag que indica se uma exce��o deve ser lan�ada
	 * caso tente-se acessar um �ndice inv�lido do array.
	 * 
	 * @param bool $opt
	 * @return Util_Array : fluent interface
	 */
	public function invalidOffsetThrowsException($opt) {
		$this->_invalidOffsetThrowsException = (bool) $opt;
		return $this;
	}
	
	/**
	 * M�todo de acesso da interface ArrayAccess.
	 * Permite setar Util_Array da forma indexada:
	 * 		<code>$foo['bar'] = $baz</code>
	 * 
	 * @param string $offset
	 * @param mixed $newval
	 * @return Util_Array
	 * 
	 * @see ArrayAccess::offsetSet
	 */
	public function offsetSet($offset, $newval) {
		$this->_recursiveSet(explode('.', $offset), $newval);
		$this->_countCache = null;
		return $this;
	} 
	
	/**
	 * M�todo de acesso da interface ArrayAccess.
	 * Permite acessar Util_Array da forma indexada:
	 * 		<code>$foo['bar']</code>
	 * 
	 * @param string $offset
	 * @return mixed
	 * @throws Exception caso $offset n�o exista e a flag 
	 * 		Util_Array::_invalidOffsetThrowsException seja TRUE
	 */
	public function offsetGet($offset) {
		return $this->_recursiveGet(explode('.', $offset));
	}
	
	/**
	 * M�todo de acesso da interface ArrayAccess.
	 * Permite remover um elemento de Util_Array da forma indexada:
	 * 		<code>unset($foo['bar'])</code>
	 * 
	 * @param string $offset
	 * @return Util_Array
	 * @throws Exception caso $offset n�o exista e a flag 
	 * 		Util_Array::_invalidOffsetThrowsException seja TRUE
	 */
	public function offsetUnset($offset) {
		$this->_recursiveUnset(explode('.', $offset));
		$this->_countCache = null;
		return $this;
	}
	
	/**
	 * M�todo de acesso da interface ArrayAccess.
	 * � invocado ao ser utilizado as fun��es isset e empty:
	 * 		<code>if(isset($array['bla'])) ...</code>
	 * 
	 * @param string $offset
	 * @return boolean
	 */
	public function offsetExists($offset) {
		try {
			return ($this->offsetGet($offset) !== null);
		} catch (Exception $e) {
			return false;
		}
	}

	/**
	 * Implementa��o da interface IteratorAggregate.
	 *  
	 * @return Iterator
	 */
	public function getIterator() {
		return new RecursiveIteratorIterator(new RecursiveArrayIterator($this->_data));
	}
	
	/**
	 * Implementa��o da interface Countable
	 * 
	 * @return int
	 */
	public function count() {
		if($this->_countCache === null) {
			$count = 0;
			print_r($this->getIterator());
			foreach($this->getIterator() as $iteration) {
				$count++;
			}
			$this->_countCache = $count;
		}
		return $this->_countCache;
	}
	
	/**
	 * Implementa��o da interface Serializable.
	 * Ir� serializar os dados do array.
	 * 
	 * @return string
	 */
	public function serialize() {
		return serialize($this->_data);
	}
	
	/**
	 * Implementa��o da interface Serializable.
	 * A partir dos dados serializados, ir� setar
	 * os dados do array.
	 * 
	 * @param string $serialized
	 * @return Util_Array : fluent interface
	 */
	public function unserialize($serialized) {
		$this->_data  = unserialize($serialized);
		return $this;
	}

	/**
	 * A partir de um conjunto de chaves, seta um array recursivamente.
	 * Exemplo:
	 * 		Para setar o array:
	 * 		<code>
	 * 			$data['foo']['bar']['baz'] = 'bazzinga'
	 * 		</code>
	 * 		Fazemos:
	 * 		<code>
	 * 			Util_Array::_recursiveSet( 
	 * 				array('foo', 'bar', 'baz'), 'bazzinga')
	 * 		</code>  
	 * 
	 * @param array $keys
	 * @param mixed $value
	 * @return void
	 */
	protected function _recursiveSet(array $keys, $value) {
		// Para operar sobre os dados, precisaremos utilizar suas refer�ncias...
		$data =& $this->_data;
		/*
		 * O �ltimo elemento do array $keys � o n�-folha, 
		 * ou seja, � ele que ir� armazenar $value.
		 * Por este motivo, separaremos a �ltima chave das demais.
		 * 
		 * array_pop remove o �ltimo elemento de um array e o retorna.
		 */
		$last = array_pop($keys);
		foreach($keys as $innerKey) {
			/*
			 * Caso as chaves intermedi�rias n�o existam ou n�o sejam arrays,
			 * faz-se necess�rio cri�-las ou converte-las em um array vazio.
			 * Exemplo:
			 * 
			 * 		A partir de um array vazio:
			 * 			array()
			 * 
			 * 		Ao setar o �ndice 'a.b.c', precisamos antes de 'c',
			 * 		criar os �ndices 'a' e 'b', ficando dessa forma:
			 * 			array(
			 * 				a => array(
			 * 					b => array(
			 * 						c => value
			 * 					)
			 * 				)
			 * 			)
			 * 
			 */
			if(!isset($data[$innerKey]) || !is_array($data[$innerKey])) {
				// Declaramos ou substitu�mos o �ndice no array...
				$data[$innerKey] = array();
			}
			/*
			 * Movemos o "ponteiro" para um n�vel abaixo no array
			 */
			$data =& $data[$innerKey];
		}
		/*
		 * Ap�s iterarmos sobre todas as chaves intermedi�rias do array,
		 * movendo o ponteiro corretamente, o n�-folha agora pode ser setado 
		 * corretamente pois o valor de sua chave foi salvo anteriormente.
		 */
		$data[$last] = $value;
	}
	
	/**
	 * A partir de um conjunto de chaves, obtemos o valor do array.
	 * Exemplo:
	 * 		Para obtermos o valor de:
	 * 		<code>
	 * 			$data['foo']['bar']['baz'];
	 * 		</code>
	 * 		Fazemos:
	 * 		<code>
	 * 			Array_Util::_recursiveGet(
	 * 				array('foo', 'bar', 'baz'))
	 * 		</code>
	 * 
	 * @param array $keys
	 * @return mixed
	 * @throws Exception se alguma das chaves n�o existir e
	 * 		Util_Array::_invalidOffsetThrowsException for TRUE
	 */
	protected function _recursiveGet(array $keys) {
		// Para operar sobre os dados, precisaremos utilizar suas refer�ncias...
		$data =& $this->_data;
		// Copiamos o vetor $keys, pois podemos precisar do mesmo intacto...
		$keysCopy = $keys;
		// Para cada n� (chave) da ramifica��o do array, fazemos...
		do {
			/*
			 * A pesquisa pelas chaves se dar� da esquerda para a direita.
			 * Chaves mais � esquerda est�o mais pr�ximas da raiz.
			 * Chaves mais � direita est�o mais pr�ximas das folhas.
			 * Ou seja, estamos fazendo uma busca em profundidade.
			 * 
			 * array_shift ir� remover o elemento mais � 
			 * esquerda do array e retorn�-lo.
			 */ 
			$innerKey = array_shift($keysCopy);
			// Caso a chave-"interna" exista no ramo...
			if(isset($data[$innerKey])) {
				/*
				 * 1. Caso tente-se acessar um n�vel N menor que
				 * a profundidade P da ramifica��o do array.
				 * 
				 * Neste caso, h� 2 possibilidades:
				 * 	1.1.A busca ainda n�o terminou, ainda restam
				 * 		chaves-'filhas' do elemento atual que 
				 * 		ainda ser�o percorridas pelo loop.
				 * 	1.2.A busca terminou, na pr�xima passagem
				 * 		sairemos do loop e o valor ser� retornado.
				 */
				if(is_array($data[$innerKey])) {
					/*
					 * O que fazemos aqui � mover o "ponteiro" dos dados
					 * um n�vel abaixo na hierarquia do array.
					 */
					$data =& $data[$innerKey];
				}
				/*
				 * 2. Caso tente-se acessar o �ltimo n�vel N da
				 * ramifica��o do array, teremos um n�-folha.
				 * Logo, devemos retorn�-lo.
				 * 
				 * Exemplo:
				 * 		Dados:
				 * 			array(
				 * 				a => 'foo',
				 * 				b => 'bar'
				 * 			)
				 * 		Tentativa:
				 * 			Util_Array::_recursiveGet('a');
				 */ 
				elseif(empty($keysCopy)) {
					return $data[$innerKey];
				}
				/*
				 * 3. Caso tente-se acessar um n�vel N maior que 
				 * a profundidade P da ramifica��o do array.
				 * 
				 * Exemplo:
				 * 		Dados:
				 * 			array(
				 * 				a => 'foo',
				 * 				b => 'bar'
				 * 			)
				 * 		Tentativa:
				 * 			Util_Array::_recursiveGet('a.c');
				 * 
				 * Note que chegaremos ao n�-folha do array, 
				 * entretanto, ainda h� chaves para buscar.
				 * Isso indica que aquela posi��o que estamos
				 * tentando buscar � inv�lida.			
				 */ 
				else {
					return $this->_invalidOffsetAccess(join('.', $keys));
				}
			}
			// Caso contr�rio, a chave � inv�lida. 
			else {
				return $this->_invalidOffsetAccess(join('.', $keys));
			}
		} while(!empty($keysCopy));
		// Retorno do caso 1.2
		return $data;
	}
	
	/**
	 * A partir de um conjunto de chaves, removemos valores de um array.
	 * 
	 * Exemplo:
	 * 		Para removermos o valor de:
	 * 		<code>
	 * 			$data['foo']['bar']['baz'];
	 * 		</code>
	 * 		Fazemos:
	 * 		<code>
	 * 			Array_Util::_recursiveUnset(
	 * 				array('foo', 'bar', 'baz'))
	 * 		</code>
	 * 
	 * @param array $keys
	 * @return void
	 * @throws Exception se alguma das chaves n�o existir e
	 * 		Util_Array::_invalidOffsetThrowsException for TRUE
	 */
	protected function _recursiveUnset(array $keys) {
		$data =& $this->_data;
		$last = array_pop($keys);
		foreach($keys as $innerKey) {
			if(isset($data[$innerKey]) && is_array($data[$innerKey])) {
				$data =& $data[$innerKey];
			} else {
				$this->_invalidOffsetAccess(join('.', $keys));
			} 
		}
		unset($data[$last]);
	}
	
	/**
	 * Verificar� a a��o a ser feita caso tente-se acessar
	 * um offset inv�lido no array.
	 * 
	 * Se a propriedade Util_Array::_invalidOffsetThrowsException
	 * for TRUE, uma exce��o ser� lan�ada.
	 * Caso contr�rio, o valor NULL ser� retornado.
	 * 
	 * @param string $offset
	 * @return null
	 * @throws Exception
	 * 
	 * @see Util_Array::invalidOffsetThrowsException
	 */
	private function _invalidOffsetAccess($offset) {
		if($this->_invalidOffsetThrowsException) {
			throw new Exception('A chave "' . $offset . '" n�o existe no array.');
		} else {
			return null;
		}
	}
}
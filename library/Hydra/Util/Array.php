<?php
/**
 * Array melhorado, com suporte à notação com ponto.
 * Exemplo:
 * 		<code>$data['a.b.c'] = 1</code>
 * Resultará em:
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
	 * Constante que armazena o separador para a notação mais curta.
	 * 
	 * @var string
	 */
	const SEPARATOR = '.';
	
	/**
	 * Armazena os dados do array.
	 * 
	 * @var array
	 */
	protected $_data = array();
	
	/**
	 * Define se uma exceção deve ser lançada caso haja a tentativa de
	 * acessar um índice inválido do array.
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
	 * Converte um array em notação com ponto para um array PHP.
	 * Atua como um Factory Method.
	 * 
	 * Exemplo:
	 * 		<code>
	 * 			array('a.b.c' => 'foo', 'a.b.d' => 'bar')
	 * 		</code>
	 * 		Será convertido em:
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
	 * Seta a flag que indica se uma exceção deve ser lançada
	 * caso tente-se acessar um índice inválido do array.
	 * 
	 * @param bool $opt
	 * @return Util_Array : fluent interface
	 */
	public function invalidOffsetThrowsException($opt) {
		$this->_invalidOffsetThrowsException = (bool) $opt;
		return $this;
	}
	
	/**
	 * Método de acesso da interface ArrayAccess.
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
		$this->_recursiveSet($this->_data, explode(self::SEPARATOR, $offset), $newval);
		$this->_countCache = null;
		return $this;
	} 
	
	/**
	 * Método de acesso da interface ArrayAccess.
	 * Permite acessar Util_Array da forma indexada:
	 * 		<code>$foo['bar']</code>
	 * 
	 * @param string $offset
	 * @return mixed
	 * @throws Exception caso $offset não exista e a flag 
	 * 		Util_Array::_invalidOffsetThrowsException seja TRUE
	 */
	public function offsetGet($offset) {
		return $this->_recursiveGet(explode(self::SEPARATOR, $offset));
	}
	
	/**
	 * Método de acesso da interface ArrayAccess.
	 * Permite remover um elemento de Util_Array da forma indexada:
	 * 		<code>unset($foo['bar'])</code>
	 * 
	 * @param string $offset
	 * @return Util_Array
	 * @throws Exception caso $offset não exista e a flag 
	 * 		Util_Array::_invalidOffsetThrowsException seja TRUE
	 */
	public function offsetUnset($offset) {
		$this->_recursiveUnset(explode(self::SEPARATOR, $offset));
		$this->_countCache = null;
		return $this;
	}
	
	/**
	 * Método de acesso da interface ArrayAccess.
	 * É invocado ao ser utilizado as funções isset e empty:
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
	 * Implementação da interface IteratorAggregate.
	 *  
	 * @return Iterator
	 */
	public function getIterator() {
		return new ArrayIterator($this->_data);
	}
	
	/**
	 * Implementação da interface Countable
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
	 * Implementação da interface Serializable.
	 * Irá serializar os dados do array.
	 * 
	 * @return string
	 */
	public function serialize() {
		return serialize($this->_data);
	}
	
	/**
	 * Implementação da interface Serializable.
	 * A partir dos dados serializados, irá setar
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
	 * 			Util_Array::_recursiveSet(array(),
	 * 				array('foo', 'bar', 'baz'), 'bazzinga')
	 * 		</code>  
	 * 
	 * @param array &$data : referência para o array contendo os dados
	 * @param array $keys : array de chaves para setar o valor $value 
	 * @param mixed $value : o valor a setar
	 * @return void
	 */
	protected function _recursiveSet(array &$data, array $keys, $value) {
		/*
		 * O último elemento do array $keys é o nó-folha, 
		 * ou seja, é ele que irá armazenar $value.
		 * Por este motivo, separaremos a última chave das demais.
		 * 
		 * array_pop remove o último elemento de um array e o retorna.
		 */
		$last = array_pop($keys);
		foreach($keys as $innerKey) {
			/*
			 * Caso as chaves intermediárias não existam ou não sejam arrays,
			 * faz-se necessário criá-las ou converte-las em um array vazio.
			 * Exemplo:
			 * 
			 * 		A partir de um array vazio:
			 * 			array()
			 * 
			 * 		Ao setar o índice 'a.b.c', precisamos antes de 'c',
			 * 		criar os índices 'a' e 'b', ficando dessa forma:
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
				// Declaramos ou substituímos o índice no array...
				$data[$innerKey] = array();
			}
			/*
			 * Movemos o "ponteiro" para um nível abaixo no array
			 */
			$data =& $data[$innerKey];
		}
		/*
		 * Após iterarmos sobre todas as chaves intermediárias do array,
		 * movendo o ponteiro corretamente, o nó-folha agora pode ser setado 
		 * corretamente pois o valor de sua chave foi salvo anteriormente.
		 */
		
		
		// Caso tenhamos um array em $value, é necessário repetir todo o processo.
		if(is_array($value)) {
			if(!isset($data[$last])) {
				$data[$last] = array();
			}
			foreach($value as $key => $val) {
				$this->_recursiveSet($data[$last], explode(self::SEPARATOR, $key), $val);
			}
		}
		// Caso contrário, apenas setamos o valor 
		else {
			$data[$last] = $value;
		}
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
	 * @throws Exception se alguma das chaves não existir e
	 * 		Util_Array::_invalidOffsetThrowsException for TRUE
	 */
	protected function _recursiveGet(array $keys) {
		// Para operar sobre os dados, precisaremos utilizar suas referências...
		$data =& $this->_data;
		// Copiamos o vetor $keys, pois podemos precisar do mesmo intacto...
		$keysCopy = $keys;
		// Para cada nó (chave) da ramificação do array, fazemos...
		do {
			/*
			 * A pesquisa pelas chaves se dará da esquerda para a direita.
			 * Chaves mais à esquerda estão mais próximas da raiz.
			 * Chaves mais à direita estão mais próximas das folhas.
			 * Ou seja, estamos fazendo uma busca em profundidade.
			 * 
			 * array_shift irá remover o elemento mais à 
			 * esquerda do array e retorná-lo.
			 */ 
			$innerKey = array_shift($keysCopy);
			// Caso a chave-"interna" exista no ramo...
			if(isset($data[$innerKey])) {
				/*
				 * 1. Caso tente-se acessar um nível N menor que
				 * a profundidade P da ramificação do array.
				 * 
				 * Neste caso, há 2 possibilidades:
				 * 	1.1.A busca ainda não terminou, ainda restam
				 * 		chaves-'filhas' do elemento atual que 
				 * 		ainda serão percorridas pelo loop.
				 * 	1.2.A busca terminou, na próxima passagem
				 * 		sairemos do loop e o valor será retornado.
				 */
				if(is_array($data[$innerKey])) {
					/*
					 * O que fazemos aqui é mover o "ponteiro" dos dados
					 * um nível abaixo na hierarquia do array.
					 */
					$data =& $data[$innerKey];
				}
				/*
				 * 2. Caso tente-se acessar o último nível N da
				 * ramificação do array, teremos um nó-folha.
				 * Logo, devemos retorná-lo.
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
				 * 3. Caso tente-se acessar um nível N maior que 
				 * a profundidade P da ramificação do array.
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
				 * Note que chegaremos ao nó-folha do array, 
				 * entretanto, ainda há chaves para buscar.
				 * Isso indica que aquela posição que estamos
				 * tentando buscar é inválida.			
				 */ 
				else {
					return $this->_invalidOffsetAccess(join(self::SEPARATOR, $keys));
				}
			}
			// Caso contrário, a chave é inválida. 
			else {
				return $this->_invalidOffsetAccess(join(self::SEPARATOR, $keys));
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
	 * @throws Exception se alguma das chaves não existir e
	 * 		Util_Array::_invalidOffsetThrowsException for TRUE
	 */
	protected function _recursiveUnset(array $keys) {
		$data =& $this->_data;
		$last = array_pop($keys);
		foreach($keys as $innerKey) {
			if(isset($data[$innerKey]) && is_array($data[$innerKey])) {
				$data =& $data[$innerKey];
			} else {
				$this->_invalidOffsetAccess(join(self::SEPARATOR, $keys));
			} 
		}
		unset($data[$last]);
	}
	
	/**
	 * Verificará a ação a ser feita caso tente-se acessar
	 * um offset inválido no array.
	 * 
	 * Se a propriedade Util_Array::_invalidOffsetThrowsException
	 * for TRUE, uma exceção será lançada.
	 * Caso contrário, o valor NULL será retornado.
	 * 
	 * @param string $offset
	 * @return null
	 * @throws Exception
	 * 
	 * @see Util_Array::invalidOffsetThrowsException
	 */
	protected function _invalidOffsetAccess($offset) {
		if($this->_invalidOffsetThrowsException) {
			throw new Exception('A chave "' . $offset . '" não existe no array.');
		} else {
			return null;
		}
	}
}
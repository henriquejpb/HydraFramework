<?php
/**
 * @package Hydra/Util/DottalNotation
 * @category Util
 * @example
 * 		<p>
 * 			Permite realizar operações sobre arrays multinível como:
 * 			<pre>
 * 				<code>
 *	 			$array = [
 * 					'a' => [
 *	 					'b' => [
 * 							'c' => 1
 * 						]
 * 					]
 *
 * 				]
 * 				</code>
 *			</pre>
 *			Utilizando a notação dotal, dessa forma:
 *			<pre>
 *				<code>
 *					$value = $adapter->get($array, 'a.b.c'); // $value = 1
 *				</code>
 *			<pre>
 * 		</p>
 * @author <a href="mailto:rick.hjpbarcelos@gmail.com">Henrique Barcelos</a>
 */
class Hydra_Util_DottalNotation_Adapter {
	const SEPARATOR = '.';

	/**
	 * A partir de um conjunto de chaves, seta um array recursivamente.
	 * Exemplo:
	 * 		Para setar o array:
	 * 		<code>
	 * 			$data['foo']['bar']['baz'] = 'bazzinga'
	 * 		</code>
	 * 		Fazemos:
	 * 		<code>
	 * 			$adapter->set(array(),
	 * 				'foo.bar.baz', 'bazzinga')
	 * 		</code>
	 *
	 * @param array &$data : referência para o array contendo os dados
	 * @param string $key : a chave no formato dottal
	 * @param mixed $value : o valor a setar
	 * @return void
	 */
	public function set(array &$data, $key, $value) {
		$this->_doSet($data, explode(self::SEPARATOR, $key), $value);
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
	 * 			$adapter->get('foo.bar.baz')
	 * 		</code>
	 *
	 * @param array $data : o array contendo os dados
	 * @param string $key : a chave no formato dottal
	 * @return mixed
	 */
	public function get(array $data, $key) {
		return $this->_doGet($data, explode(self::SEPARATOR, $key));
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
	 * 			$adapter->remove('foo.bar.baz')
	 * 		</code>
	 *
	 * @param array $data : a referência para o array contendo os dados
	 * @param string $key : a chave no formato dottal
	 * @return void
	 */
	public function remove(array &$data, $key) {
		return $this->_doRemove($data, explode(self::SEPARATOR, $key));
	}

	/**
	 * Determina se um conjunto de chaves está setado.
	 *
	 * Exemplo:
	 * 		Para verificarmos se a posição abaixo está setada:
	 * 		<code>
	 * 			$data['foo']['bar']['baz'];
	 * 		</code>
	 * 		Fazemos:
	 * 		<code>
	 * 			$adapter->exists('foo.bar.baz')
	 * 		</code>
	 *
	 * @param array $data : o array contendo os dados
	 * @param string $key : a chave no formato dottal
	 * @param unknown $key
	 *
	 */
	public function exists(array $data, $key) {
		try {
			$this->get($data, $key);
		} catch(Hydra_Util_DottalNotation_Exception $e) {
			return false;
		}
	}

	/**
	 * Seta o dado $value na posição identificada por $keys.
	 *
	 * @param array $data
	 * @param array $keys
	 * @param unknown $value
	 */
	private function _doSet(array &$data, array $keys, $value) {
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
				$this->_doSet($data[$last], explode(self::SEPARATOR, $key), $val);
			}
		}
		// Caso contrário, apenas setamos o valor
		else {
			$data[$last] = $value;
		}
	}

	/**
	 * Faz a busca e retorna o dado sob o conjunto de chaves $keys.
	 *
	 * @param array $data
	 * @param array $keys
	 * @return mixed
	 * @throws Hydra_Util_DottalNotation_Exception caso $keys não seja uma posição válida
	 */
	private function _doGet(array &$data, array $keys) {
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
				* 			Hydra_Util_Array::_doGet('a');
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
				* 			Hydra_Util_Array::_doGet('a.c');
				*
				* Note que chegaremos ao nó-folha do array,
				* entretanto, ainda há chaves para buscar.
				* Isso indica que aquela posição que estamos
				* tentando buscar é inválida.
				*/
				else {
					throw new Hydra_Util_DottalNotation_Exception(sprintf('Índice \'%s\' inválido!',
							join(self::SEPARATOR, $keys)));
				}
			}
			// Caso contrário, a chave é inválida.
			else {
				throw new Hydra_Util_DottalNotation_Exception(sprintf('Índice \'%s\' inválido!',
							join(self::SEPARATOR, $keys)));
			}
		} while(!empty($keysCopy));
		// Retorno do caso 1.2
		return $data;
	}

	/**
	 * Faz a busca e remove o dado sob o conjunto de chaves $keys.
	 *
	 * @param array $data
	 * @param array $keys
	 * @throws Hydra_Util_DottalNotation_Exception caso $keys não seja uma posição válida
	 */
	private function _doRemove(array &$data, array $keys) {
		$keysCopy = $keys;
		$last = array_pop($keysCopy);
		foreach($keysCopy as $innerKey) {
			if(isset($data[$innerKey]) && is_array($data[$innerKey])) {
				$data =& $data[$innerKey];
			} else {
				throw new Hydra_Util_DottalNotation_Exception(sprintf('Índice \'%s\' inválido!',
							join(self::SEPARATOR, $keys)));
			}
		}
		unset($data[$last]);
	}
}
<?php
/**
 * Define a interface para classes que podem ser utilizadas com o método
 * Db_Statement_Interface::fetchObject.
 *
 * @author <a href="mailto:rick.hjpbacelos@gmail.com">Henrique Barcelos</a>
 */
interface Db_Fetchable_Interface {
	/**
	 * Retorna um valor sob a chave $key ou NULL, caso não exista.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function get($key);

	/**
	 * Seta um valor sob a chave $key.
	 *
	 * @param string $key
	 * @param mixed $newVal
	 * @return Db_Fetchable_Interface : fluent interface
	 * @throws Db_Fetchable_Exception caso não exista a chave $key
	 */
	public function set($key, $newVal);

	/**
	 * Converte o objeto em um array.
	 *
	 * @return array
	 */
	public function toArray();

	/**
	 * Seta o objeto a partir de um array.
	 *
	 * @param array $input
	 * @return Db_Fetchable_Interface : fluent interface
	 */
	public function fromArray(array $input);
}

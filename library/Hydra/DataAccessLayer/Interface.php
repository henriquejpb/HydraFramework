<?php
/**
 * Interface para camada de acesso a dados.
 * 
 * Criado em: 07/03/2012.
 * 
 * @version 0.1
 * @author <a href="mailto:rick.hjpbacelos@gmail.com">Henrique Barcelos</a>
 */
interface DataAccessLayer_Interface {
	/**
	 * Insere dados na base persistente.
	 * 
	 * @param array $data : dados para inserção
	 * @return mixed : o identificador ("chave-primária") dos dados inseridos.
	 */
	public function insert(array $data);
	
	/**
	 * Atualiza dados na base persistente, com base na condição $cond.
	 * 
	 * @param array $data : dados para a atualização
	 * @param mixed $cond : a condição para atualização
	 * @return int : o número de registros afetados 
	 */
	public function update(array $data, $cond);
	
	/**
	 * Remove dados da base persistente, com base na condição $cond.
	 * 
	 * @param mixed $cond : a condição para remoção
	 */
	public function delete($cond);
	
	/**
	 * Retorna um registro da base persistente com base no seu identificador único.
	 * 
	 * @param mixed $id : o identificador ("chave-primária") do registro
	 * @return Traversable
	 */
	public function getById($id);
	
	/**
	 * Retorna um conjunto de registros da base persistente.
	 * 
	 * @param mixed $where : condição para a busca na base
	 * @param mixed $order : como ordenar os registros buscados
	 * @param int $count : quantos registros buscar
	 * @param int $offset : a partir de qual registro começar a busca
	 * @return Traversable
	 */
	public function fetchAll($where = null, $order = null, $count = null, $offset = null);
	
	/**
	 * Retorna um registro da base persistente.
	 * 
	 * @param mixed $where : condição para a busca na base
	 * @param mixed $order : como ordenar a busca
	 * @param int $offset : o registro a partir do qual começar a busca
	 * @return mixed : o registro buscado ou null, caso nenhum atenda as especificações
	 */
	public function fetchOne($where = null, $order = null, $offset = null);
	
	/**
	 * Cria um registro para ser inserido na base persistente.
	 * @param array $data : os dados para inserir no registro criado
	 * @param mixed $defaultSource : a origem dos dados, caso necessário algum preenchimento 
	 * 								 adicional
	 */
	public function createEntry(array $data = array(), $defaultSource = null);
	
	/**
	 * Verifica se um dado identificador é "chave-primária".
	 * 
	 * @param string $identifier : o nome do identificador
	 * @return boolean
	 * @throws DataAcessLayer_Exception : caso o identificador não exista na estrutura de
	 * 									   armazenamento.
	 */
	public function isIdentity($identifier);
}
<?php
/**
 * Classe que permite manipular um objeto MultiBond
 * O MultiBond é o objeto utilizado para acessar os Objects, suas propriedades e seus vínculos na estrutura de dados
 * O relacionamento entre dois ou mais Objects no sistema se dá pela estrutura a seguir:
 *
 *     Object <---> Tie <---> Bond <---> Tie <---> Object
 *
 * Para vincular um terceiro Object a este relacionamento, basta incluir um novo Tie.
 * O Bond pode e deve ser compartilhado sempre que for possível e coerente para a relação.
 * Por exemplo, vamos mapear como seria o relacionamento entre Membros de um Grupo de Trabalho:
 *
 *                                                            <---> Tie:Member <---> Object:User
 *     Object:Workgroup <---> Tie:Group <---> Bond:Membership <---> Tie:Member <---> Object:User
 *                                                            <---> Tie:Member <---> Object:User
 */

require_once($_SERVER['DOCUMENT_ROOT'].'/shared/libraries/php/core.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/shared/data/multibond/Filter.php');

class MultiBond {

	const NON_DELETED 	= 0;
	const DELETED 		= 1;
	const ALL 			= 2;

	private $db;
	private $GLOBALNamespace;

	public function __construct() {

		$this->GLOBALNamespace = NULL;
		$this->db = NULL;
	}

	/**
	 * expõe para leitura algumas propriedades desta classe definidas como protected ou private
	 * faz tratamentos e validação de alguns valores antes de retornar
	 * @return mixed property value if valid; NULL otherwise
	 */
	public function __get ($propertyName) {

		if ($propertyName === 'db') {
			return $this->$propertyName;
		}
		else {
			return NULL;
		}
    }

	/**
	 * expõe para gravação algumas propriedades desta classe definidas como protected ou private
	 * faz tratamentos e validação dos valores antes de atribuir às propriedades
	 * @return boolean
	 */
	public function __set($propertyName, $value) {

		if ($propertyName === 'db') {
			return setDatabase($db);
		}
		else {
			return false;
		}
	}

	/**
	 * Define a conexão com banco de dados utilizada por esta instância do MultiBond
	 */
	public function setDatabase($db) {

		if (!($db instanceof mysqli)) return false;
		$this->db = $db;
		return true;
	}

	/**
	 * Define o namespace utilizado por esta instância do MultiBond
	 * GLOBAL é um namespace reservado pelo sistema, com valor interno igual a 0,
	 * utilizado para SCHEMAS compartilhados entre todos os namespaces
	 *
	 * @param string $sNamespace
	 * @return boolean
	 */
	public function setNamespace($sNamespace=NULL) {

		if (is_null($this->db)) return false;

		$sNamespace = strtoupper($sNamespace) === 'NULL' ? NULL : strtoupper($sNamespace);

		// se o sNamespace for nulo, encerramos por aqui com erro
		if (is_null($sNamespace)) {
			$this->GLOBALNamespace = NULL;
			return false;
		}

		// tratamento do sNamespace 'GLOBAL', reservado pelo sistema
		if ($sNamespace === 'GLOBAL') {
			$this->GLOBALNamespace = 0;
			return true;
		}

		// tentamos identificar o id correspondente ao sNamespace fornecido
		$query  = 'SELECT id FROM tbGLOBALNamespace WHERE sNamespace = '.prepareSQL($sNamespace).' LIMIT 0,1; ';
		$result = $this->db->query(utf8_decode($query));

		if (!$result || $result->num_rows == 0) {
			$this->GLOBALNamespace = NULL;
			return false;
		}

		while($row = $result->fetch_object()) {
			$this->GLOBALNamespace = isset($row->id) ? toUTF8($row->id) : NULL;
		}
		$result->free();

		return true;
	}

	/**
	 * Retorna a lista de namespaces existentes que podem ser utilizados por esta instância do MultiBond
	 */
	public function getNamespaceList() {

		$response = array();

		if (is_null($this->db)) return $response;

		$query  = 'SELECT sNamespace FROM tbGLOBALNamespace; ';
		$result = $this->db->query(utf8_decode($query));

		if ($result) {
			while($row = $result->fetch_object()) {
				$response[] = isset($row->sNamespace) ? toUTF8(strtoupper($row->sNamespace)) : NULL;
			}
			$result->free();
		}

		return $response;
	}

	/**
	 * Retorna o nome do namespace utilizado por esta instância do MultiBond
	 *
	 * @return string|null
	 */
	public function getNamespace() {

		$response = NULL;

		if (is_null($this->db))              return $response;
		if (is_null($this->GLOBALNamespace)) return $response;
		if ($this->GLOBALNamespace === 0)    return 'GLOBAL';

		$query  = 'SELECT sNamespace FROM tbGLOBALNamespace WHERE id = '.prepareSQL($this->GLOBALNamespace).' LIMIT 0,1; ';
		$result = $this->db->query(utf8_decode($query));

		if ($result) {
			if($result->num_rows !== 1) return $response;
			while($row = $result->fetch_object()) {
				$response = isset($row->sNamespace) ? toUTF8(strtoupper($row->sNamespace)) : NULL;
			}
			$result->free();
		}

		return $response;
	}

	/**
	 * Retorna o id de um determinado namespace, se ele existir
	 * não afeta ou leva em consideração o namespace utilizado por esta instância do MultiBond
	 * TO-DO: TORNAR ESTE UM MÉTODO STATIC?
	 * @return int|null
	 */
	public function getNamespaceId($sNamespace) {

		$id = NULL;

		$sNamespace = strtoupper($sNamespace) === 'NULL' ? NULL : strtoupper($sNamespace);

		if (is_null($this->db))       return $id;
		if (is_null($sNamespace))     return $id;
		if ($sNamespace == 'GLOBAL')  return 0;

		$query  = 'SELECT id FROM tbGLOBALNamespace WHERE sNamespace = '.prepareSQL($sNamespace).' LIMIT 0,1; ';
		$result = $this->db->query(utf8_decode($query));

		if ($result){
			if($result->num_rows !== 1) return $id;
			while($row = $result->fetch_object()) {
				$id = isset($row->id) ? toUTF8($row->id) : NULL;
			}
			$result->free();
		}
		return $id;
	}

	/**
	 * Retorna o nome (string) de um determinado namespace, pelo id, se ele existir
	 * não afeta ou leva em consideração o namespace utilizado por esta instância do MultiBond
	 * TO-DO: TORNAR ESTE UM MÉTODO STATIC?
	 * @return string|null
	 */
	public function getNamespaceName($idNamespace) {

		$name = NULL;

		if (is_null($this->db))    return $name;
		if (is_null($idNamespace)) return $name;
		if ($idNamespace == 0)     return 'GLOBAL';

		$query  = 'SELECT sNamespace FROM tbGLOBALNamespace WHERE id = '.prepareSQL($idNamespace).' LIMIT 0,1; ';
		$result = $this->db->query(utf8_decode($query));

		if ($result) {
			if($result->num_rows !== 1) return $name;
			while($row = $result->fetch_object()) {
				$name = isset($row->sNamespace) ? toUTF8(strtoupper($row->sNamespace)) : NULL;
			}
			$result->free();
		}

		return $name;
	}

	/**
	 * Retorna se um determinado namespace é válido
	 * Para ser válido, o namespace com o nome especificado precisa existir
	 * Não afeta ou leva em consideração o namespace utilizado por esta instância do MultiBond
	 * TO-DO: TORNAR ESTE UM MÉTODO STATIC?
	 * @return boolean
	 */
	public function validateNamespace($sNamespace) {

		$valid = false;

		$sNamespace = strtoupper($sNamespace) === 'NULL' ? NULL : strtoupper($sNamespace);

		if (is_null($this->db))       return $valid;
		if (is_null($sNamespace))     return $valid;
		if ($sNamespace === 'GLOBAL') return true;

		$query  = 'SELECT id FROM tbGLOBALNamespace WHERE sNamespace = '.prepareSQL($sNamespace).' LIMIT 0,1; ';
		$result = $this->db->query(utf8_decode($query));

		if ($result) {
			$valid = ($result->num_rows == 1);
			$result->free();
		}

		return $valid;
	}

	/**
	 * Retorna a lista de SCHEMAObject,
	 * existentes no namespace atual ou existentes no namespace 'GLOBAL'
	 * é obrigatório que exista um namespace definido para esta instância do MultiBond
	 *
	 * @param boolean $hideGLOBAL indica se deve retornar a lista do SCHEMA deste namespace sem incluir o namespace GLOBAL
	 * @return array|null
	 */
	public function getSCHEMAObjectList($hideGLOBAL=false) {

		if (is_null($this->db))              return NULL;
		if (is_null($this->GLOBALNamespace)) return NULL;

		$hideGLOBAL = filter_var($hideGLOBAL, FILTER_VALIDATE_BOOLEAN);

		$response = array();

		$query='
		SELECT
			id,
			IF(idGLOBALNamespace IS NULL, 0, idGLOBALNamespace) AS idGLOBALNamespace,
			sKey,
			sComment
		FROM tbSCHEMAObject
		WHERE 1=1 ';

		if ($hideGLOBAL && $this->GLOBALNamespace !== 0) {
			$query.='AND (idGLOBALNamespace = '.prepareSQL($this->GLOBALNamespace).');';
		} else if ($hideGLOBAL && $this->GLOBALNamespace === 0) {
			$query.='AND (idGLOBALNamespace IS NULL);';
		} else {
			$query.='AND (idGLOBALNamespace = '.prepareSQL($this->GLOBALNamespace).' OR idGLOBALNamespace IS NULL);';
		}

		$result = $this->db->query(utf8_decode($query));
		if (!$result) return NULL;

		while($row = $result->fetch_object()) {
			$response[$row->id] = toUTF8($row);
		}

		$result->free();

		return $response;
	}

	/**
	 * Retorna o id (ou lista de ids) de um SCHEMAObject,
	 * existente no namespace atual ou existente no namespace 'GLOBAL'
	 * é obrigatório que exista um namespace definido para esta instância do MultiBond
	 *
	 * @param array|string $sSCHEMA nome ou lista de SCHEMAObject buscados
	 * @return array|null
	 */
	public function getSCHEMAObjectId($sSCHEMA=NULL) {

		$response = array();
		$found    = array();

		if (is_null($this->db)) 							return NULL;
		if (is_null($this->GLOBALNamespace)) 			    return NULL;
		if (is_null($sSCHEMA) || empty($sSCHEMA)) return $response;


		if (!is_array($sSCHEMA)) $sSCHEMA = array($sSCHEMA);

		$query = '
		SELECT id, sKey
		FROM tbSCHEMAObject
		WHERE (idGLOBALNamespace = '.prepareSQL($this->GLOBALNamespace).'
		OR idGLOBALNamespace IS NULL) ';

		$glue = ' AND (';
		foreach ($sSCHEMA as $o) {
			$query .= $glue.'sKey = '.prepareSQL($o);
			$glue = ' OR ';
		}
		$query .= ');';


		$result = $this->db->query(utf8_decode($query));
		if (!$result) return $response;

		// na array $found, armazenamos sKey e id dos SCHEMA encontrados
		while($row = $result->fetch_object()) {
			$found[$row->sKey] = $row->id;
		}

		// usamos a array $sSCHEMA para ordenar os resultados,
		// na mesma ordem da array recebida pelo parâmetro,
		// enviando NULL para os ids inexistentes
		foreach ($sSCHEMA as $k) {
			$v = array();
			if (array_key_exists($k, $found)) {
				$v[] = $found[$k];
			}
			$response[] = $v;
		}

		$result->free();

		return $response;
	}

	/**
	 * Retorna o nome (ou lista de nomes) de um idSCHEMAObject,
	 * existente no namespace atual ou existente no namespace 'GLOBAL'
	 * é obrigatório que exista um namespace definido para esta instância do MultiBond
	 *
	 * @param array|int $idSCHEMA nome ou lista de idSCHEMAObject buscados
	 * @return array|null
	 */
	public function getSCHEMAObjectName($idSCHEMA=NULL) {

		$response = array();
		$found    = array();

		if (is_null($this->db)) return NULL;
		if (is_null($this->GLOBALNamespace)) return NULL;
		if (is_null($idSCHEMA) || empty($idSCHEMA)) return $response;


		if (!is_array($idSCHEMA)) $idSCHEMA = array($idSCHEMA);

		$query = '
		SELECT id, sKey
		FROM tbSCHEMAObject
		WHERE (
			idGLOBALNamespace = '.prepareSQL($this->GLOBALNamespace).'
			OR idGLOBALNamespace IS NULL
			) ';

		$glue = ' AND (';
		foreach ($idSCHEMA as $o) {
			$query .= $glue.'id = '.prepareSQL($o);
			$glue = ' OR ';
		}
		$query .= ');';


		$result = $this->db->query(utf8_decode($query));
		if (!$result) return $response;

		// na array $found, armazenamos sKey e id dos SCHEMA encontrados
		while($row = $result->fetch_object()) {
			$found[$row->id] = $row->sKey;
		}

		// usamos a array $idSCHEMA para ordenar os resultados,
		// na mesma ordem da array recebida pelo parâmetro,
		// enviando NULL para os sKeys inexistentes
		foreach ($idSCHEMA as $k) {
			$v = NULL;
			if (array_key_exists($k, $found)) {
				$v = $found[$k];
			}
			$response[] = $v;
		}

		$result->free();

		return $response;
	}

	/**
	 * Retorna o nome de um SCHEMAObject, a partir do id de um Object conhecido,
	 * apenas se o id do Object conhecido existir no namespace atual
	 * (isso porque tratamos de um Object específico, que deve estar em um namespace diferente do GLOBAL)
	 * é obrigatório que exista um namespace definido para esta instância do MultiBond
	 *
	 * @param array|int $idObject id do Object do qual queremos saber o SCHEMAObject
	 * @return string|null
	 */
	public function getObjectSCHEMA($idObject=NULL) {

		$response = NULL;

		if (is_null($this->db)) return $response;
		if (is_null($this->GLOBALNamespace)) return $response;
		if (is_null($idObject) || empty($idObject)) return $response;


		$query = '
		SELECT so.id, so.sKey
		FROM tbSCHEMAObject so
		INNER JOIN tbDATAObject ob
		ON ob.idSCHEMAObject = so.id
		AND ob.id='.prepareSQL($idObject).'
		AND ob.idGLOBALNamespace='.prepareSQL($this->GLOBALNamespace).'
		WHERE (so.idGLOBALNamespace = '.prepareSQL($this->GLOBALNamespace).'
		OR so.idGLOBALNamespace IS NULL); ';


		$result = $this->db->query(utf8_decode($query));
		if (!$result) return $response;

		while($row = $result->fetch_object()) {
			$response = $row->sKey;
		}
		$result->free();

		return $response;

	}

	/**
	 * Retorna a lista de SCHEMABond,
	 * existentes no namespace atual ou existentes no namespace 'GLOBAL'
	 * é obrigatório que exista um namespace definido para esta instância do MultiBond
	 *
	 * @param boolean $hideGLOBAL indica se deve retornar a lista do SCHEMA deste namespace sem incluir o namespace GLOBAL
	 * @return array|null
	 */
	public function getSCHEMABondList($hideGLOBAL=false) {

		if (is_null($this->db)) return NULL;
		if (is_null($this->GLOBALNamespace)) return NULL;

		$hideGLOBAL = filter_var($hideGLOBAL, FILTER_VALIDATE_BOOLEAN);

		$response = array();

		$query = '
		SELECT
			id,
			IF(idGLOBALNamespace IS NULL, 0, idGLOBALNamespace) AS idGLOBALNamespace,
			sKey,
			sComment
		FROM tbSCHEMABond
		WHERE 1=1 ';

		if ($hideGLOBAL && $this->GLOBALNamespace !== 0) {
			$query.='AND (idGLOBALNamespace = '.prepareSQL($this->GLOBALNamespace).');';
		} else if ($hideGLOBAL && $this->GLOBALNamespace === 0) {
			$query.='AND (idGLOBALNamespace IS NULL);';
		} else {
			$query.='AND (idGLOBALNamespace = '.prepareSQL($this->GLOBALNamespace).' OR idGLOBALNamespace IS NULL);';
		}


		$result = $this->db->query(utf8_decode($query));
		if (!$result) return NULL;

		while($row = $result->fetch_object()) {
			$response[$row->id] = toUTF8($row);
		}
		$result->free();

		return $response;
	}

	/**
	 * Retorna o id (ou lista de ids) de um SCHEMABond,
	 * existentes no namespace atual ou existentes no namespace 'GLOBAL'
	 * é obrigatório que exista um namespace definido para esta instância do MultiBond
	 *
	 * @param array|string $sSCHEMA nome ou lista de sSCHEMA buscados
	 * @return array|null
	 */
	public function getSCHEMABondId($sSCHEMA=NULL) {

		$response = array();
		$found    = array();

		if (is_null($this->db))              return NULL;
		if (is_null($this->GLOBALNamespace)) return NULL;
		if (is_null($sSCHEMA) || empty($sSCHEMA)) return $response;


		if (!is_array($sSCHEMA)) $sSCHEMA = array($sSCHEMA);

		$query = '
		SELECT id, sKey
		FROM tbSCHEMABond
		WHERE (idGLOBALNamespace = '.prepareSQL($this->GLOBALNamespace).'
		OR idGLOBALNamespace IS NULL) ';

		$glue = ' AND (';
		foreach ($sSCHEMA as $ob) {
			$query .= $glue.'sKey = '.prepareSQL($ob);
			$glue = ' OR ';
		}
		$query .= ');';


		$result = $this->db->query(utf8_decode($query));
		if (!$result) return $response;

		// na array $found, armazenamos sKey e id dos SCHEMA encontrados
		while($row = $result->fetch_object()) {
			$found[$row->sKey] = $row->id;
		}


		// usamos a array $sSCHEMA para ordenar os resultados,
		// na mesma ordem da array recebida pelo parâmetro,
		// enviando NULL para os ids inexistentes
		foreach ($sSCHEMA as $k) {
			$v = array();
			if (array_key_exists($k, $found)) {
				$v[] = $found[$k];
			}
			$response[] = $v;
		}

		$result->free();

		return $response;
	}

	/**
	 * Retorna o nome (ou lista de nomes) de um SCHEMABond,
	 * existentes no namespace atual ou existentes no namespace 'GLOBAL'
	 * é obrigatório que exista um namespace definido para esta instância do MultiBond
	 *
	 * @param array|int $idSCHEMA id ou lista de idSCHEMA buscados
	 * @return array|null
	 */
	public function getSCHEMABondName($idSCHEMA=NULL) {

		$response = array();
		$found    = array();

		if (is_null($this->db))              return NULL;
		if (is_null($this->GLOBALNamespace)) return NULL;
		if (is_null($idSCHEMA) || empty($idSCHEMA)) return $response;


		if (!is_array($idSCHEMA)) $idSCHEMA = array($idSCHEMA);

		$query = '
		SELECT id, sKey
		FROM tbSCHEMABond
		WHERE (idGLOBALNamespace = '.prepareSQL($this->GLOBALNamespace).'
		OR idGLOBALNamespace IS NULL) ';

		$glue = ' AND (';
		foreach ($idSCHEMA as $b) {
			$query .= $glue.'id = '.prepareSQL($b);
			$glue = ' OR ';
		}
		$query .= ');';

		$result = $this->db->query(utf8_decode($query));
		if (!$result) return $response;

		// na array $found, armazenamos sKey e id dos SCHEMA encontrados
		while($row = $result->fetch_object()) {
			$found[$row->id] = $row->sKey;
		}


		// usamos a array $idSCHEMA para ordenar os resultados,
		// na mesma ordem da array recebida pelo parâmetro,
		// enviando NULL para os ids inexistentes
		foreach ($idSCHEMA as $k) {
			$v = NULL;
			if (array_key_exists($k, $found)) {
				$v = $found[$k];
			}
			$response[] = $v;
		}

		$result->free();

		return $response;
	}

	/**
	 * Retorna a lista de SCHEMATie,
	 * existentes no namespace atual ou existentes no namespace 'GLOBAL'
	 * é obrigatório que exista um namespace definido para esta instância do MultiBond
	 *
	 * @param boolean $hideGLOBAL indica se deve retornar a lista do SCHEMA deste namespace sem incluir o namespace GLOBAL
	 * @return array|null
	 */
	public function getSCHEMATieList($hideGLOBAL=false) {

		if (is_null($this->db))              return NULL;
		if (is_null($this->GLOBALNamespace)) return NULL;

		$hideGLOBAL = filter_var($hideGLOBAL, FILTER_VALIDATE_BOOLEAN);

		$response = array();

		$query = '
		SELECT
			id,
			IF(idGLOBALNamespace IS NULL, 0, idGLOBALNamespace) AS idGLOBALNamespace,
			sKey,
			sComment
		FROM tbSCHEMATie
		WHERE 1=1 ';


		if ($hideGLOBAL && $this->GLOBALNamespace !== 0) {
			$query.='AND (idGLOBALNamespace = '.prepareSQL($this->GLOBALNamespace).');';
		} else if ($hideGLOBAL && $this->GLOBALNamespace === 0) {
			$query.='AND (idGLOBALNamespace IS NULL);';
		} else {
			$query.='AND (idGLOBALNamespace = '.prepareSQL($this->GLOBALNamespace).' OR idGLOBALNamespace IS NULL);';
		}


		$result = $this->db->query(utf8_decode($query));
		if (!$result) return NULL;

		while($row = $result->fetch_object()) {
			$response[$row->id] = toUTF8($row);
		}
		$result->free();

		return $response;
	}

	/**
	 * Retorna o id (ou lista de ids) de um SCHEMATie,
	 * existentes no namespace atual ou existentes no namespace 'GLOBAL'
	 * é obrigatório que exista um namespace definido para esta instância do MultiBond
	 *
	 * @param array|string $sSCHEMA    token ou lista de tokens dos Tie pesquisadas
	 * @return array|null
	 */
	public function getSCHEMATieId($sSCHEMA=NULL) {

		$response = array();
		$found    = array();

		if (is_null($this->db)) return NULL;
		if (is_null($this->GLOBALNamespace)) return NULL;
		if (is_null($sSCHEMA) || empty($sSCHEMA)) return $response;


		// o parâmetro sSCHEMA é obrigatório,
		// e a partir deste ponto é necessário que seja convertido em Array
		if (!is_array($sSCHEMA)) $sSCHEMA = array($sSCHEMA);


		$query = '
		SELECT id, sKey
		FROM tbSCHEMATie
		WHERE (idGLOBALNamespace = '.prepareSQL($this->GLOBALNamespace).'
		OR idGLOBALNamespace IS NULL) ';

		$glue = ' AND (';
		foreach ($sSCHEMA as $ob) {
			$query .= $glue.'sKey = '.prepareSQL($ob);
			$glue = ' OR ';
		}
		$query .= ');';


		$result = $this->db->query(utf8_decode($query));
		if (!$result) return $response;

		// na array $found, armazenamos sKey e id dos SCHEMA encontrados
		while($row = $result->fetch_object()) {
			$found[$row->sKey] = $row->id;
		}


		// usamos a array $sSCHEMA para ordenar os resultados,
		// na mesma ordem da array recebida pelo parâmetro,
		// enviando NULL para os ids inexistentes
		foreach ($sSCHEMA as $k) {
			$v = array();
			if (array_key_exists($k, $found)) {
				$v[] = $found[$k];
			}
			$response[] = $v;
		}

		$result->free();

		return $response;
	}


	/**
	 * Retorna o nome (ou lista de nomes) de um schemaTie,
	 * existentes no namespace atual ou existentes no namespace 'GLOBAL'
	 * é obrigatório que exista um namespace definido para esta instância do MultiBond
	 *
	 * @param array|int $idSCHEMA id ou lista de idSCHEMA buscados
	 * @return array|null
	 */
	public function getSCHEMATieName($idSCHEMA=NULL) {

		$response = array();
		$found    = array();

		if (is_null($this->db))                                       return NULL;
		if (is_null($this->GLOBALNamespace))                          return NULL;
		if (is_null($idSCHEMA) || empty($idSCHEMA)) return $response;


		if (!is_array($idSCHEMA)) $idSCHEMA = array($idSCHEMA);


		$query = '
		SELECT id, sKey
		FROM tbSCHEMATie
		WHERE (idGLOBALNamespace = '.prepareSQL($this->GLOBALNamespace).'
		OR idGLOBALNamespace IS NULL) ';

		$glue = ' AND (';
		foreach ($idSCHEMA as $ob) {
			$query .= $glue.'id = '.prepareSQL($ob);
			$glue = ' OR ';
		}
		$query .= ');';

		$result = $this->db->query(utf8_decode($query));
		if (!$result) return $response;

		// na array $found, armazenamos sKey e id dos SCHEMA encontrados
		while($row = $result->fetch_object()) {
			$found[$row->id] = $row->sKey;
		}


		// usamos a array $idSCHEMA para ordenar os resultados,
		// na mesma ordem da array recebida pelo parâmetro,
		// enviando NULL para os ids inexistentes
		foreach ($idSCHEMA as $k) {
			$v = NULL;
			if (array_key_exists($k, $found)) {
				$v = $found[$k];
			}
			$response[] = $v;
		}

		$result->free();

		return $response;
	}


	/**
	 * Retorna a lista de SCHEMAProperties de um determinado tipo de Object,
	 * existentes no namespace atual ou existentes no namespace 'GLOBAL'
	 * é obrigatório que exista um namespace definido para esta instância do MultiBond
	 *
	 * @param string $SCHEMAObject  nome do SCHEMAObject buscado, restringindo a lista de SCHEMAProperties
	 * @param boolean $hideGLOBAL  indica se deve retornar a lista do SCHEMA deste namespace sem incluir o namespace GLOBAL
	 * @return array|null
	 */
	public function getSCHEMAPropertyList($SCHEMAObject=NULL, $hideGLOBAL=false) {

		$response = array();

		if (is_null($this->db))                             return NULL;
		if (is_null($this->GLOBALNamespace))                return NULL;
		if (is_null($SCHEMAObject) || empty($SCHEMAObject)) return $response;


		$hideGLOBAL = filter_var($hideGLOBAL, FILTER_VALIDATE_BOOLEAN);


		$SCHEMAObject = $this->getSCHEMAObjectId($SCHEMAObject);
		$SCHEMAObject = isset($SCHEMAObject[0]) && count($SCHEMAObject[0])===1 ? $SCHEMAObject[0][0] : NULL;

		// neste caso, se $SCHEMAObject for NULL,
		// significa que está sendo procurada a propriedade de um objeto não válido
		// ou que foram passados mais de um objeto como parâmetro
		if (is_null($SCHEMAObject)) return NULL;


		$query = '
		SELECT
			id,
			IF(idGLOBALNamespace IS NULL, 0, idGLOBALNamespace) AS idGLOBALNamespace,
			idSCHEMAObject,
			sKey,
			sDataType,
			fMultiple,
			sComment
		FROM tbSCHEMAProperty
		WHERE idSCHEMAObject = '.prepareSQL($SCHEMAObject).' ';


		if ($hideGLOBAL && $this->GLOBALNamespace !== 0) {
			$query.='AND (idGLOBALNamespace = '.prepareSQL($this->GLOBALNamespace).');';
		} else if ($hideGLOBAL && $this->GLOBALNamespace === 0) {
			$query.='AND (idGLOBALNamespace IS NULL);';
		} else {
			$query.='AND (idGLOBALNamespace = '.prepareSQL($this->GLOBALNamespace).' OR idGLOBALNamespace IS NULL);';
		}

		$result = $this->db->query(utf8_decode($query));
		if (!$result) return NULL;

		while($row = $result->fetch_object()) {
			$response[$row->id] = toUTF8($row);
		}
		$result->free();

		return $response;
	}

	/**
	 * Retorna o id (ou lista de ids) de um SCHEMAProperty de um determinado tipo de Object,
	 * existentes no namespace atual ou existentes no namespace 'GLOBAL'
	 * é obrigatório que exista um namespace definido para esta instância do MultiBond
	 *
	 * @param array|string $SCHEMAProperty token ou lista de tokens das propriedades pesquisadas
	 * @param array|string $SCHEMAObject   token ou lista de tokens dos tipos de objetos aos quais pertencem as propriedades (opcional, usando *)
	 * @return array|null
	 */
	public function getSCHEMAPropertyId($SCHEMAProperty=NULL, $SCHEMAObject='') {

		$response = array();
		$found    = array();

		if (is_null($this->db))              return NULL;
		if (is_null($this->GLOBALNamespace)) return NULL;
		if (is_null($SCHEMAProperty) || empty($SCHEMAProperty)) return $response;


		// o parâmetro SCHEMAProperty é obrigatório,
		// e a partir deste ponto, é necessário que seja convertido em Array
		if (!is_array($SCHEMAProperty)) $SCHEMAProperty = array($SCHEMAProperty);


		// o parâmetro SCHEMAObject é obrigatório,
		// para listar todos os registros, exigimos o parâmetro como '*'
		// que então convertemos para '' (vazio)
		if (trim($SCHEMAObject) == '') return NULL;
		if (trim($SCHEMAObject) == '*') $SCHEMAObject = '';


		// começa a verificar pelo parâmetro $SCHEMAObject
		// 1) se $SCHEMAObject for uma string, vamos assumir que todos os tokens de $SCHEMAProperty se referem ao mesmo objeto
		//    e criar uma ARRAY $SCHEMAObject com o mesmo número de ítens da ARRAY $SCHEMAProperty;
		// 2) se $SCHEMAObject for uma array, ela deve possuir o mesmo número de ítens da ARRAY $SCHEMAProperty.
		// 3) se ele não existir, vamos assumir que os tokens de $SCHEMAProperty serão trazidos, independente do tipo de objeto

		if (!is_null($SCHEMAObject) && is_string($SCHEMAObject)) {

			$tempSCHEMAObject = array();
			for ($i=0; $i<count($SCHEMAProperty); $i++) {
				$tempSCHEMAObject[] = $SCHEMAObject;
			}
			$SCHEMAObject = $tempSCHEMAObject;

		} else if (!is_null($SCHEMAObject) && is_array($SCHEMAObject)) {

			// número incorreto de parâmetros na array $SCHEMAObject!
			if (count($SCHEMAProperty) !== count($SCHEMAObject)) return $response;

		} else {
			$SCHEMAObject = NULL;
		}


		// agora temos dois cenários possíveis:
		// 1) SCHEMAObject é uma Array; ou
		// 2) SCHEMAObject é NULL.

		// para a array $SCHEMAObject, que atualmente armazena as keys de Object,
		// são buscados os ids, armazenados na mesma array, substituindo os valores por seus respectivos ids
		if (!is_null($SCHEMAObject)) $SCHEMAObject = $this->getSCHEMAObjectId($SCHEMAObject);


		$query = '
		SELECT id, sKey, idSCHEMAObject
		FROM tbSCHEMAProperty
		WHERE (idGLOBALNamespace = '.prepareSQL($this->GLOBALNamespace).' OR idGLOBALNamespace IS NULL)
		';

		$glue = 'AND (';
		for ($i=0; $i<count($SCHEMAProperty); $i++) {

			$p = $SCHEMAProperty[$i];
			$o = $SCHEMAObject[$i][0];

			if (is_null($o)) { $query .= $glue.'(sKey = '.prepareSQL($p).')'; }
			else 			 { $query .= $glue.'(sKey = '.prepareSQL($p).' AND idSCHEMAObject = '.prepareSQL($o).')'; }

			$glue = ' OR ';
		}
		$query .= ');';


		$result = $this->db->query(utf8_decode($query));
		if (!$result) return $response;

		// na array $found, armazenamos os dados dos SCHEMA encontrados
		while($row = $result->fetch_object()) {
			$found[$row->id] = $row;
		}

		// usamos a array $SCHEMAProperty para ordenar os resultados, respondendo NULL para os ids inexistentes
		for ($i=0; $i<count($SCHEMAProperty); $i++) {

			$p = $SCHEMAProperty[$i];
			$o = $SCHEMAObject[$i][0];


			$itemFound = array();
			foreach($found as $f) {

				if (!is_null($o)) {
					if ($f->sKey == $p && $f->idSCHEMAObject == $o) {
						$itemFound[] = $f->id;
						break;
					}
				} else if (is_null($o)) {
					if ($f->sKey == $p) {
						$itemFound[] = $f->id;
					}
				}
			}
			$response[] = $itemFound;

		}

		$result->free();

		return $response;
	}

	/**
	 * Retorna o nome (ou lista de nomes) de um SCHEMAProperty de um determinado tipo de Object,
	 * existentes no namespace atual ou existentes no namespace 'GLOBAL'
	 * é obrigatório que exista um namespace definido para esta instância do MultiBond
	 *
	 * @param array|int
	 * @return array|null
	 */
	public function getSCHEMAPropertyName($idSCHEMAProperty=NULL) {

		$response = array();
		$found    = array();

		if (is_null($this->db))             return NULL;
		if (is_null($this->GLOBALNamespace))return NULL;
		if (is_null($idSCHEMAProperty) || empty($idSCHEMAProperty)) return $response;


		if (!is_array($idSCHEMAProperty)) $idSCHEMAProperty = array($idSCHEMAProperty);


		$query = '
		SELECT id, sKey
		FROM tbSCHEMAProperty
		WHERE (idGLOBALNamespace = '.prepareSQL($this->GLOBALNamespace).'
		OR idGLOBALNamespace IS NULL) ';

		$glue = ' AND (';
		foreach ($idSCHEMAProperty as $p) {
			$query .= $glue.'id = '.prepareSQL($p);
			$glue = ' OR ';
		}
		$query .= ');';

		$result = $this->db->query(utf8_decode($query));
		if (!$result) return $response;

		// na array $found, armazenamos sKey e id dos SCHEMA encontrados
		while($row = $result->fetch_object()) {
			$found[$row->id] = $row->sKey;
		}


		// usamos a array $idSCHEMAProperty para ordenar os resultados,
		// na mesma ordem da array recebida pelo parâmetro,
		// enviando NULL para os ids inexistentes
		foreach ($idSCHEMAProperty as $k) {
			$v = NULL;
			if (array_key_exists($k, $found)) {
				$v = $found[$k];
			}
			$response[] = $v;
		}

		$result->free();

		return $response;
	}

	/**
	 * Carrega uma lista de ids de Objects associados a um Object conhecido
	 * é necessário especificar o tipo de vínculo (SCHEMABond) entre os objetos, além do tipo de laço (SCHEMATie) de cada "perna" do vínculo
	 *
	 * @param int $id                id do objeto a partir do qual buscamos os vínculos
	 * @param array|string $thisTie  (SCHEMATie)    tipo de laço que liga o Object de referência (opcional, usando *)
	 * @param array|string $bondType (SCHEMABond)   tipo de vínculo entre os objetos (opcional, usando *)
	 * @param array|string $thatTie  (SCHEMATie)    tipo de laço que liga o Object buscado (opcional, usando *)
	 * @param array|string $thatType (SCHEMAObject) tipo de Object buscado (opcional, usando *)
	 * @param string $filterParam
	 * @param string $order
	 * @param int $offset
	 * @param int $rowcount
	 * @return array|null
	 */
	public function getBondedObjects(array $args=array()) {

		// default parameters values, to be merged with the received parameters
		$default_values = array(
			'id'          => NULL,
			'thisTie'     => '',
			'bondType'    => '',
			'thatTie'     => '',
			'thatType'    => '*',
			'filterParam' => '',
			'order'       => '',
			'offset'      => 0,
			'rowcount'    => 0
		);

		$args = array_merge($default_values, $args);

		return $this->_queryBondedObjects('get', $args);
	}

	/**
	 * Carrega uma lista com o mapeamento completo das associações a este Object
	 * é necessário especificar o tipo de vínculo (SCHEMABond) entre os objetos, além do tipo de laço (SCHEMATie) de cada "perna" do vínculo
	 *
	 * @param int $id                id do objeto a partir do qual buscamos os vínculos
	 * @param array|string $thisTie  (SCHEMATie)    tipo de laço que liga o Object de referência (opcional, usando *)
	 * @param array|string $bondType (SCHEMABond)   tipo de vínculo entre os objetos (opcional, usando *)
	 * @param array|string $thatTie  (SCHEMATie)    tipo de laço que liga o Object buscado (opcional, usando *)
	 * @param array|string $thatType (SCHEMAObject) tipo de Object buscado (opcional, usando *)
	 * @param string $filterParam    (opcional, usando *)
	 * @param string $order          (opcional)
	 * @param int $offset            (opcional)
	 * @param int $rowcount          (opcional, porém automático quando a consulta for retornar um número excessivo de registros -- $id for uma array)
	 * @param array $intersect       lista de ids de Objects para usar como intersecção na pesquisa (opcional)
	 * @return array|null
	 */
	public function mapBondedObjects(array $args=array()) {

		// default parameters values, to be merged with the received parameters
		$default_values = array(
			'id'          => NULL,
			'thisTie'     => '',
			'bondType'    => '',
			'thatTie'     => '',
			'thatType'    => '',
			'filterParam' => '',
			'order'       => '',
			'offset'      => 0,
			'rowcount'    => 0,
			'intersect'   => NULL
		);

		$args = array_merge($default_values, $args);

		return $this->_queryBondedObjects('map', $args);
	}

	/**
	 * Conta o total de vínculos (Bond) de um determinado tipo (SCHEMABond) de um Object
	 *
	 * @param int $id                id do objeto a partir do qual buscamos os vínculos
	 * @param array|string $thisTie  (SCHEMATie)  tipo de laço que liga o Object de referência (opcional, usando *)
	 * @param array|string $bondType (SCHEMABond) tipo de vínculo entre os objetos (opcional, usando *)
	 * @param array|string $thatTie  (SCHEMATie)  tipo de laço que liga o Object buscado (opcional, usando *)
	 * @param string $filterParam
	 * @return Int
	 */
	public function countBondedObjects(array $args=array()) {

		// default parameters values, to be merged with the received parameters
		$default_values = array(
			'id'          => NULL,
			'thisTie'     => '',
			'bondType'    => '',
			'thatTie'     => '',
			'thatType'    => '*',
			'filterParam' => ''
		);

		$args = array_merge($default_values, $args);

		return $this->_queryBondedObjects('count', $args);
	}



	// TO-DO: incluir no build os parâmetros da busca por propriedades do object

	private function _buildQueryBondedObjects(array $args=array()) {

		// default parameters values, to be merged with the received parameters
		$default_values = array(
			'id'          => NULL,
			'thisTie'     => '',
			'bondType'    => '',
			'thatTie'     => '',
			'thatType'    => '',
			'filterParam' => '',
			'order'       => '',
			'offset'      => 0,
			'rowcount'    => 0,
			'intersect'   => NULL
		);

		$args = array_merge($default_values, $args);

		// Agora os valores recebidos foram normatizados.
		// Prosseguimos com a execução do método

		$id          = $args['id'];
		$thisTie     = $args['thisTie'];
		$bondType    = $args['bondType'];
		$thatTie     = $args['thatTie'];
		$thatType    = $args['thatType'];
		$filterParam = $args['filterParam'];
		$order       = $args['order'];
		$offset      = $args['offset'];
		$rowcount    = $args['rowcount'];
		$intersect   = $args['intersect'];


		// o parâmetro thisTie é obrigatório,
		// para listar todos os registros, exigimos o parâmetro como '*'
		// que então convertemos para '' (vazio)
		if (is_string($thisTie) && trim($thisTie) == '') return NULL;
		if (is_string($thisTie) && trim($thisTie) == '*') $thisTie = '';


		// o parâmetro bondType é obrigatório,
		// para listar todos os registros, exigimos o parâmetro como '*'
		// que então convertemos para '' (vazio)
		if (is_string($bondType) && trim($bondType) == '') return NULL;
		if (is_string($bondType) && trim($bondType) == '*') $bondType = '';


		// o parâmetro thatTie é obrigatório,
		// para listar todos os registros, exigimos o parâmetro como '*'
		// que então convertemos para '' (vazio)
		if (is_string($thatTie) && trim($thatTie) == '') return NULL;
		if (is_string($thatTie) && trim($thatTie) == '*') $thatTie = '';


		// o parâmetro thatType é obrigatório,
		// para listar todos os registros, exigimos o parâmetro como '*'
		// que então convertemos para '' (vazio)
		if (is_string($thatType) && trim($thatType) == '') return NULL;
		if (is_string($thatType) && trim($thatType) == '*') $thatType = '';


		// o filtro não pode ser passado em branco
		// para listar todos os registros, exigimos o parâmetro como '*'
		// que então convertemos para '' (vazio)
		if (trim($filterParam) == '') return NULL;
		if (trim($filterParam) == '*') $filterParam = '';


		if (is_null($this->db))              return NULL;
		if (is_null($id))                    return NULL;
		if (is_null($this->GLOBALNamespace)) return NULL;


		$filter     = new Filter();
		$idIsArray 	= is_array($id);
		$id = is_array($id) ? implode(',', $id) : $id;




		//	o parâmetro thisTie pode ser uma string ou uma array de strings de SCHEMATie
		//	cada string é convertida para seu id equivalente
		if ($thisTie !== '') {
			$thisTie = $this->getSCHEMATieId($thisTie, '*', '*');
			$temp_thisTie = array();
			foreach($thisTie as $roles) {
				foreach($roles as $r) {
					$temp_thisTie[] = $r;
				}
			}
			// se não encontrar o SCHEMA, retorna NULL, pois o vínculo pedido não existe!
			if (count($temp_thisTie) == 0) return NULL;
			$thisTie = $temp_thisTie;
			$thisTie = implode(',',$thisTie);
		}


		//	o parâmetro bondType pode ser uma string ou uma array de strings de SCHEMABond
		//	cada string é convertida para seu id equivalente
		if ($bondType !== '') {
			$bondType = $this->getSCHEMABondId($bondType);
			$temp_bondType = array();
			foreach($bondType as $types) {
				foreach($types as $t) {
					$temp_bondType[] = $t;
				}
			}
			// se não encontrar o SCHEMA, retorna NULL, pois o vínculo pedido não existe!
			if (count($temp_bondType) == 0) return NULL;
			$bondType = $temp_bondType;
			$bondType = implode(',',$bondType);
		}


		//	o parâmetro thatTie pode ser uma string ou uma array de strings de SCHEMATie
		//	cada string é convertida para seu id equivalente
		if ($thatTie !== '') {
			$thatTie = $this->getSCHEMATieId($thatTie, '*', '*');
			$temp_thatTie = array();
			foreach($thatTie as $roles) {
				foreach($roles as $r) {
					$temp_thatTie[] = $r;
				}
			}
			// se não encontrar o SCHEMA, retorna NULL, pois o vínculo pedido não existe!
			if (count($temp_thatTie) == 0) return NULL;
			$thatTie = $temp_thatTie;
			$thatTie = implode(',',$thatTie);
		}


		//	o parâmetro thatType pode ser uma string ou uma array de strings de SCHEMATie
		//	cada string é convertida para seu id equivalente
		if ($thatType !== '') {
			$thatType = $this->getSCHEMAObjectId($thatType, '*', '*');
			$temp_thatType = array();
			foreach($thatType as $roles) {
				foreach($roles as $r) {
					$temp_thatType[] = $r;
				}
			}
			// se não encontrar o SCHEMA, retorna NULL, pois o vínculo pedido não existe!
			if (count($temp_thatType) == 0) return NULL;
			$thatType = $temp_thatType;
			$thatType = implode(',',$thatType);
		}

		// montagem do WHERE da query, a partir dos tipos de associação e roles dos objetos pesquisados
		$where  = '';
		$where .= $thisTie  == '' ? '' : ('AND `this`.`idSCHEMATie` IN ('.$thisTie.')  ');
		$where .= $bondType == '' ? '' : ('AND `bond`.`idSCHEMABond`IN ('.$bondType.') ');
		$where .= $thatTie  == '' ? '' : ('AND `that`.`idSCHEMATie` IN ('.$thatTie.')  ');
		$where .= $thatType == '' ? '' : ('AND `o`.`idSCHEMAObject` IN ('.$thatType.') ');

		$where .= 'AND `o`.`idGLOBALNamespace` = '.prepareSQL($this->GLOBALNamespace).' ';
		$where .= 'AND `o`.`fArchived` = 0 ';


		// montagem dos critérios de pesquisa, através do objeto Filter
		if ($filterParam != '') {

			$fields = array();
			$fi = $this->getSCHEMAPropertyList( $args['thatType'] );
			foreach ($fi as $f) {
				$fields[$f['sKey']] = array('sKey'=>$f['sKey'], 'sDataType'=>$f['sDataType']);
			}

			$filter->expression = $filterParam;
			$filter->acceptedFields = $fields;
			$parsedExpression = $filter->parseExpression();

			if ($filter->error) {
				return NULL;
			}

		} else {
			$filter->expression = '';
			$filter->acceptedFields = NULL;
			$parsedExpression = '';
		}


		// montagem da query
		$query = " SELECT `o`.`id` AS `id`, ";

		if ($filter) {
			if (!$filter->error) {
				foreach($filter->usedFields as $field) {

					$sKey      = $field['sKey'];
					$sDataType = $field['sDataType'];

					$query .= " `op_$sKey`.`".$sDataType."Value` AS `$sKey`, ";
				}
			}
		}

		$query .= "
				`o`.`idSCHEMAObject`,
				`thatType`.`sKey`     AS `sSCHEMAObject`,
				`bond`.`id`           AS `bond_id`,
				`bond`.`idSCHEMABond` AS `bond_iType`,
				`schema_bond`.`sKey`  AS `bond_sType`,
				`this`.`id`           AS `this_id`,
				`this`.`idSCHEMATie`  AS `this_iType`,
				`schema_this`.`sKey`  AS `this_sType`,
				`that`.`id`           AS `that_id`,
				`that`.`idSCHEMATie`  AS `that_iType`,
				`schema_that`.`sKey`  AS `that_sType`

			FROM `tbDATATie` `this`

			INNER JOIN `tbSCHEMATie` `schema_this`
			ON `this`.`idSCHEMATie` = `schema_this`.`id`

			INNER JOIN `tbSCHEMATie` `this_schema`
			ON `this`.`idSCHEMATie` = `this_schema`.`id`

			INNER JOIN `tbDATABond` `bond`
			ON `this`.`idBond` = `bond`.`id`

			INNER JOIN `tbSCHEMABond` `schema_bond`
			ON `bond`.`idSCHEMABond` = `schema_bond`.`id`

			INNER JOIN `tbDATATie` `that`
			ON `bond`.`id` = `that`.`idBond`

			INNER JOIN `tbSCHEMATie` `schema_that`
			ON `that`.`idSCHEMATie` = `schema_that`.`id`

			INNER JOIN `tbSCHEMATie` `that_schema`
			ON `that`.`idSCHEMATie` = `that_schema`.`id`

			INNER JOIN `tbDATAObject` `o`
			ON `that`.`idObject` = `o`.`id`
			AND `o`.`id` <> `this`.`idObject`

			INNER JOIN `tbSCHEMAObject` `thatType`
			ON `o`.`idSCHEMAObject` = `thatType`.`id`
			";


		if ($filter) {
			if (!$filter->error) {
				foreach($filter->usedFields as $field) {

					$sKey      = $field['sKey'];
					$sDataType = $field['sDataType'];

					$query .= "
					LEFT OUTER JOIN `tbDATAProperty` AS `op_$sKey`
					ON  `op_$sKey`.`idObject` = `o`.`id`

					INNER JOIN `tbSCHEMAProperty` AS `p_$sKey`
					ON  `p_$sKey`.`id` = `op_$sKey`.`idSCHEMAProperty`
					AND `p_$sKey`.`sKey` = '$sKey'
					";
				}
			}
		}

		$query .= " WHERE `this`.`idObject` IN (".$id.") ".$where." ";

		if ( !is_null($intersect) ) {
			//$intersect = prepareSQL(array($intersect));
			$intersect = is_array($intersect) ? implode(',',$intersect) : $intersect;
			$query .= " AND `o`.`id` IN (" . $intersect . ") ";
		}

		return $query;

	}




	/**
	 * Função comum aos métodos getBondedObjects(), mapBondedObjects() e countBondedObjects(),
	 * que realiza uma query no banco de dados buscando (ou contando) vínculos a partir de um Object conhecido
	 *
	 * @param int $id                id do objeto a partir do qual buscamos os vínculos
	 * @param array|string $thisTie  (SCHEMATie)    tipo de laço que liga o Object de referência (opcional, usando *)
	 * @param array|string $bondType (SCHEMABond)   tipo de vínculo entre os objetos (opcional, usando *)
	 * @param array|string $thatTie  (SCHEMATie)    tipo de laço que liga o Object buscado (opcional, usando *)
	 * @param array|string $thatType (SCHEMAObject) tipo de Object buscado (opcional, usando *)
	 * @param string $filterParam
	 * @param string $order
	 * @param int $offset
	 * @param int $rowcount
	 * @return array|null
	 */
	private function _queryBondedObjects($type=NULL, array $args=array()) {

		if ($type !== 'get' && $type !== 'map' && $type !== 'count') return NULL;

		// default parameters values, to be merged with the received parameters
		$default_values = array(
			'id'          => NULL,
			'thisTie'     => '',
			'bondType'    => '',
			'thatTie'     => '',
			'thatType'    => '',
			'filterParam' => '',
			'order'       => '',
			'offset'      => 0,
			'rowcount'    => 0,
			'intersect'   => NULL
		);

		$args = array_merge($default_values, $args);

		// Agora os valores recebidos foram normatizados.
		// Prosseguimos com a execução do método

		$id          = $args['id'];
		$thisTie     = $args['thisTie'];
		$bondType    = $args['bondType'];
		$thatTie     = $args['thatTie'];
		$thatType    = $args['thatType'];
		$filterParam = $args['filterParam'];
		$order       = $args['order'];
		$offset      = $args['offset'];
		$rowcount    = $args['rowcount'];
		$intersect   = $args['intersect'];


		// o parâmetro thisTie é obrigatório,
		// para listar todos os registros, exigimos o parâmetro como '*'
		// que então convertemos para '' (vazio)
		if (is_string($thisTie) && trim($thisTie) == '') return NULL;
		if (is_string($thisTie) && trim($thisTie) == '*') $thisTie = '';


		// o parâmetro bondType é obrigatório,
		// para listar todos os registros, exigimos o parâmetro como '*'
		// que então convertemos para '' (vazio)
		if (is_string($bondType) && trim($bondType) == '') return NULL;
		if (is_string($bondType) && trim($bondType) == '*') $bondType = '';


		// o parâmetro thatTie é obrigatório,
		// para listar todos os registros, exigimos o parâmetro como '*'
		// que então convertemos para '' (vazio)
		if (is_string($thatTie) && trim($thatTie) == '') return NULL;
		if (is_string($thatTie) && trim($thatTie) == '*') $thatTie = '';


		// o parâmetro thatType é obrigatório,
		// para listar todos os registros, exigimos o parâmetro como '*'
		// que então convertemos para '' (vazio)
		if (is_string($thatType) && trim($thatType) == '') return NULL;
		if (is_string($thatType) && trim($thatType) == '*') $thatType = '';


		// o filtro não pode ser passado em branco
		// para listar todos os registros, exigimos o parâmetro como '*'
		// que então convertemos para '' (vazio)
		if (trim($filterParam) == '') return NULL;
		if (trim($filterParam) == '*') $filterParam = '';


		if (is_null($this->db))              return NULL;
		if (is_null($id))                    return NULL;
		if (is_null($this->GLOBALNamespace)) return NULL;


		$filter     = new Filter();
		$idIsArray 	= is_array($id);
		$id = is_array($id) ? implode(',', $id) : $id;




		//	o parâmetro thisTie pode ser uma string ou uma array de strings de SCHEMATie
		//	cada string é convertida para seu id equivalente
		if ($thisTie !== '') {
			$thisTie = $this->getSCHEMATieId($thisTie, '*', '*');
			$temp_thisTie = array();
			foreach($thisTie as $roles) {
				foreach($roles as $r) {
					$temp_thisTie[] = $r;
				}
			}
			// se não encontrar o SCHEMA, retorna NULL, pois o vínculo pedido não existe!
			if (count($temp_thisTie) == 0) return NULL;
			$thisTie = $temp_thisTie;
			$thisTie = implode(',',$thisTie);
		}


		//	o parâmetro bondType pode ser uma string ou uma array de strings de SCHEMABond
		//	cada string é convertida para seu id equivalente
		if ($bondType !== '') {
			$bondType = $this->getSCHEMABondId($bondType);
			$temp_bondType = array();
			foreach($bondType as $types) {
				foreach($types as $t) {
					$temp_bondType[] = $t;
				}
			}
			// se não encontrar o SCHEMA, retorna NULL, pois o vínculo pedido não existe!
			if (count($temp_bondType) == 0) return NULL;
			$bondType = $temp_bondType;
			$bondType = implode(',',$bondType);
		}


		//	o parâmetro thatTie pode ser uma string ou uma array de strings de SCHEMATie
		//	cada string é convertida para seu id equivalente
		if ($thatTie !== '') {
			$thatTie = $this->getSCHEMATieId($thatTie, '*', '*');
			$temp_thatTie = array();
			foreach($thatTie as $roles) {
				foreach($roles as $r) {
					$temp_thatTie[] = $r;
				}
			}
			// se não encontrar o SCHEMA, retorna NULL, pois o vínculo pedido não existe!
			if (count($temp_thatTie) == 0) return NULL;
			$thatTie = $temp_thatTie;
			$thatTie = implode(',',$thatTie);
		}


		//	o parâmetro thatType pode ser uma string ou uma array de strings de SCHEMATie
		//	cada string é convertida para seu id equivalente
		if ($thatType !== '') {
			$thatType = $this->getSCHEMAObjectId($thatType, '*', '*');
			$temp_thatType = array();
			foreach($thatType as $roles) {
				foreach($roles as $r) {
					$temp_thatType[] = $r;
				}
			}
			// se não encontrar o SCHEMA, retorna NULL, pois o vínculo pedido não existe!
			if (count($temp_thatType) == 0) return NULL;
			$thatType = $temp_thatType;
			$thatType = implode(',',$thatType);
		}

		// montagem do WHERE da query, a partir dos tipos de associação e roles dos objetos pesquisados
		$where  = '';
		$where .= $thisTie  == '' ? '' : ('AND `this`.`idSCHEMATie` IN ('.$thisTie.')  ');
		$where .= $bondType == '' ? '' : ('AND `bond`.`idSCHEMABond`IN ('.$bondType.') ');
		$where .= $thatTie  == '' ? '' : ('AND `that`.`idSCHEMATie` IN ('.$thatTie.')  ');
		$where .= $thatType == '' ? '' : ('AND `o`.`idSCHEMAObject` IN ('.$thatType.') ');

		$where .= 'AND `o`.`idGLOBALNamespace` = '.prepareSQL($this->GLOBALNamespace).' ';
		$where .= 'AND `o`.`fArchived` = 0 ';


		// montagem dos critérios de pesquisa, através do objeto Filter
		if ($filterParam != '') {

			$fields = array();
			$fi = $this->getSCHEMAPropertyList( $args['thatType'] );
			foreach ($fi as $f) {
				$fields[$f['sKey']] = array('sKey'=>$f['sKey'], 'sDataType'=>$f['sDataType']);
			}

			$filter->expression = $filterParam;
			$filter->acceptedFields = $fields;
			$parsedExpression = $filter->parseExpression();

			if ($filter->error) {
				return NULL;
			}

		} else {
			$filter->expression = '';
			$filter->acceptedFields = NULL;
			$parsedExpression = '';
		}


		// montagem da query
		// usa o parâmetro $type para identificar qual o tipo de chamada feita a esta query



//$GLOBALS["debug"]->add(NULL, '_buildQueryBondedObjects' );
//$GLOBALS["debug"]->add(NULL, $this->_buildQueryBondedObjects($args) );




		if ($type === 'get')
			$query = 'SELECT `id`, `bond_iType`, `this_iType`,  `that_iType` ';
		else if ($type === 'map')
			$query = 'SELECT * ';
		else if ($type === 'count')
			$query = 'SELECT COUNT(DISTINCT `id`) AS `iTotal` ';


		$query .= " FROM ( ";

		$query .= $this->_buildQueryBondedObjects($args);

//			SELECT `o`.`id` AS `id`, ";
//
//		if ($filter) {
//			if (!$filter->error) {
//				foreach($filter->usedFields as $field) {
//
//					$sKey      = $field['sKey'];
//					$sDataType = $field['sDataType'];
//
//					$query .= " `op_$sKey`.`".$sDataType."Value` AS `$sKey`, ";
//				}
//			}
//		}
//
//		$query .= "
//				`o`.`idSCHEMAObject`,
//				`thatType`.`sKey`     AS `sSCHEMAObject`,
//				`bond`.`id`           AS `bond_id`,
//				`bond`.`idSCHEMABond` AS `bond_iType`,
//				`schema_bond`.`sKey`  AS `bond_sType`,
//				`this`.`id`           AS `this_id`,
//				`this`.`idSCHEMATie`  AS `this_iType`,
//				`schema_this`.`sKey`  AS `this_sType`,
//				`that`.`id`           AS `that_id`,
//				`that`.`idSCHEMATie`  AS `that_iType`,
//				`schema_that`.`sKey`  AS `that_sType`
//
//			FROM `tbDATATie` `this`
//
//			INNER JOIN `tbSCHEMATie` `schema_this`
//			ON `this`.`idSCHEMATie` = `schema_this`.`id`
//
//			INNER JOIN `tbSCHEMATie` `this_schema`
//			ON `this`.`idSCHEMATie` = `this_schema`.`id`
//
//			INNER JOIN `tbDATABond` `bond`
//			ON `this`.`idBond` = `bond`.`id`
//
//			INNER JOIN `tbSCHEMABond` `schema_bond`
//			ON `bond`.`idSCHEMABond` = `schema_bond`.`id`
//
//			INNER JOIN `tbDATATie` `that`
//			ON `bond`.`id` = `that`.`idBond`
//
//			INNER JOIN `tbSCHEMATie` `schema_that`
//			ON `that`.`idSCHEMATie` = `schema_that`.`id`
//
//			INNER JOIN `tbSCHEMATie` `that_schema`
//			ON `that`.`idSCHEMATie` = `that_schema`.`id`
//
//			INNER JOIN `tbDATAObject` `o`
//			ON `that`.`idObject` = `o`.`id`
//			AND `o`.`id` <> `this`.`idObject`
//
//			INNER JOIN `tbSCHEMAObject` `thatType`
//			ON `o`.`idSCHEMAObject` = `thatType`.`id`
//			";
//
//
//		if ($filter) {
//			if (!$filter->error) {
//				foreach($filter->usedFields as $field) {
//
//					$sKey      = $field['sKey'];
//					$sDataType = $field['sDataType'];
//
//					$query .= "
//					LEFT OUTER JOIN `tbDATAProperty` AS `op_$sKey`
//					ON  `op_$sKey`.`idObject` = `o`.`id`
//
//					INNER JOIN `tbSCHEMAProperty` AS `p_$sKey`
//					ON  `p_$sKey`.`id` = `op_$sKey`.`idSCHEMAProperty`
//					AND `p_$sKey`.`sKey` = '$sKey' ";
//				}
//			}
//		}
//
//		$query .= " WHERE `this`.`idObject` IN (".$id.") ".$where." ";
//
//		if ( !is_null($intersect) ) {
//			$intersect = prepareSQL(array($intersect));
//			$query .= " AND `o`.`id` IN (" . implode(',',$intersect) . ") ";
//		}

		$query .= " ) `o` ";

		if ($filter) {
			if (!$filter->error && $parsedExpression !== '') {
				$query .= " WHERE $parsedExpression ";
			}
		}


		// montagem do ORDER BY
		if ( ($type === 'get' || $type === 'map') ) {
			if ($order != '') { $query .= " ORDER BY ".$order.", `id` DESC, `bond_iType`, `this_iType`, `that_iType` "; }
			else { $query .= " ORDER BY `id` DESC, `bond_iType`, `this_iType`, `that_iType` "; }
		}


		//	montagem do LIMIT, caso:
		//	1) o parâmetro 'id' seja uma array, para evitar que seja retornado um número
		//	   excessivo de informações do banco de dados, comprometendo desempenho; OU
		//	2) os parâmetros 'rowcount' e 'offset' tenham sido recebidos pelo método E
		//  3) seja uma query do tipo 'get' ou 'map' ('count' não precisa de limit)
		if ( ($type === 'get' || $type === 'map') ) {
			if ( $idIsArray ) {
				if  (intval($rowcount) > 0) {
					$query .= " LIMIT ".$offset.", ".$rowcount;
				} else {
					$query .= " LIMIT 0, 100";
				}
			} else {
				if  (intval($rowcount) > 0) {
					$query .= " LIMIT ".$offset.", ".$rowcount;
				}
			}
		}
		$query .= "; ";


		$result = $this->db->query(utf8_decode($query));
		if (!$result) return NULL;


		if ($type === 'get') {
			$associations = array();
			while($row = $result->fetch_object()) { $associations[] = $row->id; }
			$result->free();
			return $associations;

		} else if ($type === 'map') {
			$associations = array();
			while($row = $result->fetch_object()) {

				$assoc = array();

				$assoc['thisTie']['id']        = $row->this_id;
				$assoc['thisTie']['schema']    = $row->this_sType;
				$assoc['bond']['id']           = $row->bond_id;
				$assoc['bond']['schema']       = $row->bond_sType;
				$assoc['thatTie']['id']        = $row->that_id;
				$assoc['thatTie']['schema']    = $row->that_sType;
				$assoc['thatObject']['id']     = $row->id;
				$assoc['thatObject']['schema'] = $row->sSCHEMAObject;

				$associations[] = $assoc;
			}
			$result->free();
			return $associations;

		} else if ($type === 'count') {
			$iTotal = 0;
			while($row = $result->fetch_object()) { $iTotal = $row->iTotal; }
			$result->free();
			return $iTotal;
		}

	}





	/**
	 * Verifica o status de um vínculo (Bond) entre dois objetos
	 * @param int    $thisObject  id do objeto a partir do qual verificamos o vínculo
	 * @param string $thisTie  (SCHEMATie)  tipo de laço que liga o Object de referência (opcional, usando *)
	 * @param string $bondType (SCHEMABond) tipo de vínculo entre os objetos (opcional, usando *)
	 * @param string $thatTie  (SCHEMATie)  tipo de laço que liga o Object buscado (opcional, usando *)
	 * @param int $thatObject  id do objeto até o qual verificamos o vínculo
	 * @return boolean
	 */
	public function isBonded(array $args=array()) {

		// default parameters values, to be merged with the received parameters
		$default_values = array(
			'thisObject'  => NULL,
			'thisTie'     => '',
			'bondType'    => '',
			'thatTie'     => '',
			'thatObject'  => NULL
		);

		$args = array_merge($default_values, $args);


		$thisObject = $args['thisObject'];
		$thisTie    = $args['thisTie'];
		$bondType   = $args['bondType'];
		$thatTie    = $args['thatTie'];
		$thatObject = $args['thatObject'];

		// Agora os valores recebidos foram normatizados.
		// Prosseguimos com a execução do método


		if (is_null($this->db))  return NULL;
//		if (is_array($thisTie))  return NULL;
//		if (is_array($bondType)) return NULL;
//		if (is_array($thatTie))  return NULL;


		// o parâmetro thisTie é obrigatório,
		// para listar todos os registros, exigimos o parâmetro como '*'
		// que então convertemos para '' (vazio)
		if (is_string($thisTie) && trim($thisTie) == '') return NULL;
		if (is_string($thisTie) && trim($thisTie) == '*') $thisTie = '';


		// o parâmetro bondType é obrigatório,
		// para listar todos os registros, exigimos o parâmetro como '*'
		// que então convertemos para '' (vazio)
		if (is_string($bondType) && trim($bondType) == '') return NULL;
		if (is_string($bondType) && trim($bondType) == '*') $bondType = '';


		// o parâmetro thatTie é obrigatório,
		// para listar todos os registros, exigimos o parâmetro como '*'
		// que então convertemos para '' (vazio)
		if (is_string($thatTie) && trim($thatTie) == '') return NULL;
		if (is_string($thatTie) && trim($thatTie) == '*') $thatTie = '';


		if (is_null($thisObject) || is_null($thatObject)) return NULL;


		//	o parâmetro thisTie deve ser uma string de SCHEMATie
		//	cada string é convertida para seu id equivalente

		if ($thisTie !== '') {

			$thisTie = $this->getSCHEMATieId($thisTie, '*', '*');

			$temp_thisTie = array();
			foreach($thisTie as $roles) {
				foreach($roles as $r) {
					$temp_thisTie[] = $r;
				}
			}
			$thisTie = $temp_thisTie;
			$thisTie = implode(',',$thisTie);

		}




		//	o parâmetro bondType deve ser uma string de SCHEMABond
		//	cada string é convertida para seu id equivalente

		if ($bondType !== '') {
			$bondType = $this->getSCHEMABondId($bondType);
			$temp_bondType = array();
			foreach($bondType as $types) {
				foreach($types as $t) {
					$temp_bondType[] = $t;
				}
			}
			$bondType = $temp_bondType;
			$bondType = implode(',',$bondType);
		}


		//	o parâmetro thatTie deve ser uma string de SCHEMATie
		//	cada string é convertida para seu id equivalente

		if ($thatTie !== '') {
			$thatTie = $this->getSCHEMATieId($thatTie, '*', '*');
			$temp_thatTie = array();
			foreach($thatTie as $roles) {
				foreach($roles as $r) {
					$temp_thatTie[] = $r;
				}
			}
			$thatTie = $temp_thatTie;
			$thatTie = implode(',',$thatTie);
		}



		$query = '
		SELECT
			oathis.idSCHEMATie AS thisTie,
			oathat.idSCHEMATie AS thatTie

		FROM tbDATABond bond

		INNER JOIN tbDATATie oathis
		ON (
			bond.id = oathis.idBond
			AND oathis.idObject = '.$thisObject.' ';

		if ($thisTie!=='') {
		$query .= 'AND oathis.idSCHEMATie IN ('.$thisTie.') ';
		}
		$query .= ')

		INNER JOIN tbDATATie oathat
		ON (
			bond.id = oathat.idBond
			AND oathat.idObject = '.$thatObject.' ';

		if ($thisTie!=='') {
		$query .= 'AND oathat.idSCHEMATie IN ('.$thatTie.') ';
		}
		$query .= ')';


		if ($bondType!=='') {
			$query .= '
		WHERE bond.idSCHEMABond IN ('.$bondType.'); ';
		}


//echo "<pre>";
//print_r($query);
//echo "</pre>";


		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;

		$status = array();
		while($row = $result->fetch_object()) {
			$status = $row;
		}
		$result->free();


		if (count($status) > 0) {
			return true;
		} else {
			return false;
		}
	}







	/**
	 * Verifica o status de um laço (Tie) entre um Object e um Bond
	 * @param int    $idObject  id do objeto a partir do qual verificamos o vínculo
	 * @param string $tieType   tipo de laço que liga o Object de referência (SCHEMATie) (opcional, usando *)
	 * @param int    $idBond    id do vínculo
	 * @return boolean
	 */
	public function isTied(array $args=array()) {

		// default parameters values, to be merged with the received parameters
		$default_values = array(
			'idObject' => NULL,
			'tieType'  => '',
			'idBond'   => NULL
		);

		$args = array_merge($default_values, $args);

		$idObject = $args['idObject'];
		$tieType  = $args['tieType'];
		$idBond   = $args['idBond'];

		// Agora os valores recebidos foram normatizados.
		// Prosseguimos com a execução do método


		if (is_null($this->db)) return NULL;
		if (is_null($idObject)) return NULL;
		if (is_null($idBond))   return NULL;


		// o parâmetro tieType é obrigatório,
		// para listar todos os registros, exigimos o parâmetro como '*'
		// que então convertemos para '' (vazio)
		if (trim($tieType) == '') return NULL;
		if (trim($tieType) == '*') $tieType = '';




		$query = '
		SELECT
			oathis.idSCHEMATie AS tieType

		FROM tbDATABond bond

		INNER JOIN tbDATATie oathis
		ON (
			bond.id = oathis.idBond
			AND oathis.idObject = '.$idObject.'
			AND oathis.idBond = '.$idBond.'
			';
		if ($tieType!=='') {
		$query .= 'AND oathis.idSCHEMATie IN ('.$tieType.') ';
		}
		$query .= ');';


		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;

		$status = array();
		while($row = $result->fetch_object()) {
			$status = $row;
		}
		$result->free();

		return (count($status) > 0);

	}






	/**
	 * Carrega uma lista de ids de Objects de um determinado tipo (SCHEMAObject)
	 * @param string $objectType   tipo de objeto buscado (SCHEMAObject) (opcional, usando *)
	 * @param int $page            paginação da lista de resultados
	 * @param int $limit           número de resultados por página
	 * @param string $order        ordenação da pesquisa (opcional)
	 * @param string $filterParam  filtro para restringir a pesquisa (opcional, usando *)
	 * @param array $intersect     lista de ids de objetos para usar como intersecção na pesquisa (opcional)
	 * @return array|boolean
	 */
	public function getObjects(array $args=array()) {

		// default parameters values, to be merged with the received parameters
		$default_values = array(
			'objectType'  => '',
			'page'        => 1,
			'limit'       => NULL,
			'order'       => NULL,
			'filterParam' => '',
			'intersect'	  => NULL
		);

		$args = array_merge($default_values, $args);

		$objectType  = $args['objectType'];
		$page        = $args['page'];
		$limit       = $args['limit'];
		$order       = $args['order'];
		$filterParam = $args['filterParam'];
		$intersect   = $args['intersect'];

		// Agora os valores recebidos foram normatizados.
		// Prosseguimos com a execução do método


		// o parâmetro objectType é obrigatório,
		// para listar todos os registros, exigimos o parâmetro como '*'
		// que então convertemos para '' (vazio)
		if (trim($objectType) == '') return false;
		if (trim($objectType) == '*') $objectType = '';


		// o filtro não pode ser passado em branco
		// para listar todos os registros, exigimos o parâmetro como '*'
		// que então convertemos para '' (vazio)
		if (trim($filterParam) == '') return false;
		if (trim($filterParam) == '*') $filterParam = '';


		if (is_null($this->db)) return false;

		$filter = new Filter();

		//	o parâmetro objectType pode ser uma string ou uma array de strings de SCHEMAObject
		//	cada string é convertida para seu id equivalente

		$strObjectType = $objectType;

		if ($objectType !== '') {
			$temp_objectType = array();
			$objectType = $this->getSCHEMAObjectId($objectType);

			if ($objectType) {
				foreach($objectType as $roles) {
					foreach($roles as $r) {
						$temp_objectType[] = $r;
					}
				}
			}

			$objectType = $temp_objectType;
			$objectType = implode(',',$objectType);
		}

		// montagem dos critérios de pesquisa, através do objeto Filter
		if ($filterParam != '') {

			$fields = array();
			$fi = $this->getSCHEMAPropertyList($strObjectType);

			if ($fi) {
				foreach ($fi as $f) {
					$fields[$f['sKey']] = array('sKey'=>$f['sKey'], 'sDataType'=>$f['sDataType']);
				}
			}

			$filter->expression 		= $filterParam;
			$filter->acceptedFields 	= $fields;
			$parsedExpression 			= $filter->parseExpression();

		} else {
			$filter->expression     = '';
			$filter->acceptedFields = NULL;
			$parsedExpression       = '';
		}


		$query = "
		SELECT id
		FROM (
			SELECT
				`o`.`id`, ";

		if ($filter) {
			if (!$filter->error) {
				foreach($filter->usedFields as $field) {

					$sKey      = $field['sKey'];
					$sDataType = $field['sDataType'];

					$query .= "
					`op_$sKey`.`".$sDataType."Value` AS `$sKey`, ";
				}
			}
		}

		$query .= "
				`o`.`tsCreation`,
				`o`.`tsLastUpdate`,
				`o`.`idSCHEMAObject`

			FROM `tbDATAObject` AS `o`
			";

		if ($filter) {
			if (!$filter->error) {
				foreach($filter->usedFields as $field) {

					$sKey      = $field['sKey'];
					$sDataType = $field['sDataType'];

					$query .= "
					LEFT OUTER JOIN `tbDATAProperty` AS `op_$sKey`
					ON  `op_$sKey`.`idObject` = `o`.`id`

					INNER JOIN `tbSCHEMAProperty` AS `p_$sKey`
					ON  `p_$sKey`.`id` = `op_$sKey`.`idSCHEMAProperty`
					AND `p_$sKey`.`sKey` = '$sKey'
					";
				}
			}
		}

		$query .= "	WHERE `o`.`idSCHEMAObject` IN ($objectType) ";
		$query .= " AND `o`.`idGLOBALNamespace` = ".prepareSQL($this->GLOBALNamespace)." ";

		if (!is_null($intersect) && is_array($intersect)) {
			$query .= " AND `o`.`id` IN (" . implode(',',$intersect) . ") ";
		}

		$query .= ") `o` ";

		if ($filter) {
			if (!$filter->error && $parsedExpression !== '') {
				$query .= " WHERE $parsedExpression ";
			}
		}


		// montagem do ORDER BY
		if (!is_null($order)) { $query .= " ORDER BY ".$order.", `id` DESC "; }
		else { $query .= " ORDER BY `id` DESC "; }


		if (!is_null($limit)) {
			$limit  = intval($limit);
			$offset = $limit * ($page - 1);
			$query .= "	LIMIT $offset, $limit; ";
		}

		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;

		$objects = array();
		while($row = $result->fetch_object()) {
			$objects[] = $row->id;
		}
		$result->free();

		return $objects;
	}





	/**
	 * Conta o total de ids de Objects de um determinado tipo (SCHEMAObject)
	 * @param string $objectType   tipo de objeto buscado (SCHEMAObject) (opcional, usando *)
	 * @param string $filterParam  filtro para restringir a pesquisa (opcional, usando *)
	 * @param array $intersect     lista de ids de objetos para usar como intersecção na pesquisa (opcional)
	 * @return int
	 */
	public function countObjects(array $args=array()) {

		// default parameters values, to be merged with the received parameters
		$default_values = array(
			'objectType'  => '',
			'filterParam' => '',
			'intersect'   => NULL
		);

		$args = array_merge($default_values, $args);

		$objectType  = $args['objectType'];
		$filterParam = $args['filterParam'];
		$intersect   = $args['intersect'];

		// Agora os valores recebidos foram normatizados.
		// Prosseguimos com a execução do método


		// o parâmetro objectType é obrigatório,
		// para listar todos os registros, exigimos o parâmetro como '*'
		// que então convertemos para '' (vazio)
		if (trim($objectType) == '') return false;
		if (trim($objectType) == '*') $objectType = '';


		// o filtro não pode ser passado em branco
		// para listar todos os registros, exigimos o parâmetro como '*'
		// que então convertemos para '' (vazio)
		if (trim($filterParam) == '') return NULL;
		if (trim($filterParam) == '*') $filterParam = '';


		if (is_null($this->db))   return false;
		if (is_null($objectType)) return false;

		$filter = new Filter();

		//	o parâmetro objectType pode ser uma string ou uma array de strings de SCHEMAObject
		//	cada string é convertida para seu id equivalente

		$strObjectType = $objectType;

		if ($objectType !== '') {
			$objectType = $this->getSCHEMAObjectId($objectType);
			$temp_objectType = array();
			foreach($objectType as $roles) {
				foreach($roles as $r) {
					$temp_objectType[] = $r;
				}
			}
			$objectType = $temp_objectType;
			$objectType = implode(',',$objectType);
		}

		// montagem dos critérios de pesquisa, através do objeto Filter
		if ($filterParam != '') {

			$fields = array();
			$fi = $this->getSCHEMAPropertyList($strObjectType);
			foreach ($fi as $f) {
				$fields[$f['sKey']] = array('sKey'=>$f['sKey'], 'sDataType'=>$f['sDataType']);
			}

			$filter->expression = $filterParam;
			$filter->acceptedFields = $fields;
			$parsedExpression = $filter->parseExpression();

		} else {
			$filter->expression = '';
			$filter->acceptedFields = NULL;
			$parsedExpression = '';
		}

		$query = "
		SELECT COUNT(DISTINCT id) AS iTotal
		FROM (
			SELECT
				`o`.`id`, ";

		if ($filter) {
			if (!$filter->error) {
				foreach($filter->usedFields as $field) {

					$sKey      = $field['sKey'];
					$sDataType = $field['sDataType'];

					$query .= "
					`op_$sKey`.`".$sDataType."Value` AS `$sKey`, ";
				}
			}
		}

		$query .= "
				`o`.`tsCreation`,
				`o`.`tsLastUpdate`,
				`o`.`idSCHEMAObject`

			FROM `tbDATAObject` AS `o` ";

		if ($filter) {
			if (!$filter->error) {
				foreach($filter->usedFields as $field) {

					$sKey      = $field['sKey'];
					$sDataType = $field['sDataType'];

					$query .= "
					LEFT OUTER JOIN `tbDATAProperty` AS `op_$sKey`
					ON  `op_$sKey`.`idObject` = `o`.`id`

					INNER JOIN `tbSCHEMAProperty` AS `p_$sKey`
					ON  `p_$sKey`.`id` = `op_$sKey`.`idSCHEMAProperty`
					AND `p_$sKey`.`sKey` = '$sKey'
					";
				}
			}
		}

		$query .= "	WHERE `o`.`idSCHEMAObject` IN ($objectType) ";

		if (!is_null($intersect) && is_array($intersect)) {
			$query .= " AND `o`.`id` IN (" . implode(',',$intersect) . ") ";
		}

		$query .= "
		) `o` ";

		if ($filter) {
			if (!$filter->error && $parsedExpression !== '') {
				$query .= " WHERE $parsedExpression ";
			}
		}


		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;

		$iTotal = 0;
		while($row = $result->fetch_object()) {
			$iTotal = $row->iTotal;
		}
		$result->free();
		return $iTotal;
	}





	/**
	 * Carrega a lista de ids de Bonds de um determinado tipo (SCHEMABond) a partir de um Object conhecido
	 * @param int $idObject    id do objeto conhecido
	 * @param string $SCHEMABond tipo de bond buscado (SCHEMABond) (opcional, usando *)
	 * @return Array|Boolean
	 */
	public function getBondsByObject($idObject=NULL, $SCHEMABond='') {

		if (is_null($this->db)) return false;
		if (is_null($idObject)) return false;


		// o parâmetro SCHEMABond é obrigatório,
		// para listar todos os registros, exigimos o parâmetro como '*'
		// que então convertemos para '' (vazio)
		if ( is_string($SCHEMABond) && trim($SCHEMABond) == ''  ) return false;
		if ( is_string($SCHEMABond) && trim($SCHEMABond) == '*' ) $SCHEMABond = '';


		//	o parâmetro SCHEMABond pode ser uma string ou uma array de strings de SCHEMABond
		//	cada string é convertida para seu id equivalente

		if ($SCHEMABond !== '') {
			$SCHEMABond = $this->getSCHEMABondId($SCHEMABond);
			$temp_SCHEMABond = array();
			foreach($SCHEMABond as $types) {
				foreach($types as $t) {
					$temp_SCHEMABond[] = $t;
				}
			}
			$SCHEMABond = $temp_SCHEMABond;
			$SCHEMABond = implode(',',$SCHEMABond);
		}


		$query = '
		SELECT b.id
		FROM tbDATABond AS b
		INNER JOIN tbDATATie ob
		ON (ob.idBond = b.id AND ob.idObject = '.prepareSQL($idObject).')
		WHERE 1=1 ';

		if (!is_null($SCHEMABond)) {
			$query .= 'AND b.idSCHEMABond IN ('.prepareSQL($SCHEMABond).') ';
		}

		$query .= 'GROUP BY b.id ORDER BY b.id; ';

		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;

		$bonds = array();
		while($row = $result->fetch_object()) {
			$bonds[] = $row->id;
		}
		$result->free();

		return $bonds;
	}





	/**
	 * Carrega a lista de ids de Bonds existentes entre dois Objects
	 *
	 * @param $id           Objeto conhecido, do qual se quer encontrar os Bonds
	 * @param $thisTie      Laço -- ou papel -- do Object de referência (SCHEMATie) (opcional, usando *)
	 * @param $bondType     Tipo de vínculo (SCHEMABond) (opcional, usando *)
	 * @param $thatTie      Laço -- ou papel -- do Object buscado (SCHEMATie) (opcional, usando *)
	 * @param $thatObject   Objeto conhecido, ao qual se liga o primeiro Objeto através dos Bonds
	 * @param $order
	 * @param $offset
	 * @param $rowcount
	 * @return array|boolean
	 */
	public function getBonds(array $args=array()) {

		// default parameters values, to be merged with the received parameters
		$default_values = array(
			'id'         => NULL,
			'thisTie'    => '',
			'bondType'   => '',
			'thatTie'    => '',
			'thatObject' => NULL,
			'order'      => '',
			'offset'     => 0,
			'rowcount'   => 0
		);

		$args = array_merge($default_values, $args);

		$id         = $args['id'];
		$thisTie    = $args['thisTie'];
		$bondType   = $args['bondType'];
		$thatTie    = $args['thatTie'];
		$thatObject = $args['thatObject'];
		$order      = $args['order'];
		$offset     = $args['offset'];
		$rowcount   = $args['rowcount'];



		// Agora os valores recebidos foram normatizados.
		// Prosseguimos com a execução do método


		// o parâmetro thisTie é obrigatório,
		// para listar todos os registros, exigimos o parâmetro como '*'
		// que então convertemos para '' (vazio)
		if (trim($thisTie) == '') return NULL;
		if (trim($thisTie) == '*') $thisTie = '';


		// o parâmetro bondType é obrigatório,
		// para listar todos os registros, exigimos o parâmetro como '*'
		// que então convertemos para '' (vazio)
		if (trim($bondType) == '') return NULL;
		if (trim($bondType) == '*') $bondType = '';


		// o parâmetro thatTie é obrigatório,
		// para listar todos os registros, exigimos o parâmetro como '*'
		// que então convertemos para '' (vazio)
		if (trim($thatTie) == '') return NULL;
		if (trim($thatTie) == '*') $thatTie = '';


		if (is_null($this->db))   return false;
		if (is_null($id)) 	      return false;
		if (is_null($thatObject)) return false;


		$idIsArray 	= is_array($id)         ? true : false;
		$id 		= is_array($id)         ? implode(',',$id) : $id;
		$thatObject = is_array($thatObject) ? implode(',',$thatObject) : $thatObject;


		//	o parâmetro thisTie pode ser uma string ou uma array de strings de SCHEMATie
		//	cada string é convertida para seu id equivalente

		if ($thisTie !== '') {
			$thisTie = $this->getSCHEMATieId($thisTie, '*', '*');
			$temp_thisTie = array();
			foreach($thisTie as $roles) {
				foreach($roles as $r) {
					$temp_thisTie[] = $r;
				}
			}
			$thisTie = $temp_thisTie;
			$thisTie = implode(',',$thisTie);
		}


		//	o parâmetro bondType pode ser uma string ou uma array de strings de SCHEMABond
		//	cada string é convertida para seu id equivalente

		if ($bondType !== '') {
			$bondType = $this->getSCHEMABondId($bondType);
			$temp_bondType = array();
			foreach($bondType as $types) {
				foreach($types as $t) {
					$temp_bondType[] = $t;
				}
			}
			$bondType = $temp_bondType;
			$bondType = implode(',',$bondType);
		}


		//	o parâmetro thatTie pode ser uma string ou uma array de strings de SCHEMATie
		//	cada string é convertida para seu id equivalente

		if ($thatTie !== '') {
			$thatTie = $this->getSCHEMATieId($thatTie, '*', '*');
			$temp_thatTie = array();
			foreach($thatTie as $roles) {
				foreach($roles as $r) {
					$temp_thatTie[] = $r;
				}
			}
			$thatTie = $temp_thatTie;
			$thatTie = implode(',',$thatTie);
		}



		// montagem do WHERE da query, a partir dos tipos de associação e roles dos objetos pesquisados
		$where  = '';
		$where .= $thisTie  == '' ? '' : ('AND `this`.`idSCHEMATie`  IN ('.$thisTie.') ');
		$where .= $bondType == '' ? '' : ('AND `bond`.`idSCHEMABond` IN ('.$bondType.') ');
		$where .= $thatTie  == '' ? '' : ('AND `that`.`idSCHEMATie`  IN ('.$thatTie.') ');
		$where .= 'AND `obj`.`fArchived` = 0 ';



		$query = '
		SELECT bond.id AS id
		FROM tbDATATie this

		INNER JOIN tbDATABond bond
		ON bond.id = this.idBond

		INNER JOIN tbDATATie that
		ON (
			that.idBond = bond.id
			AND that.idObject IN ('.$thatObject.')
			)

		INNER JOIN tbDATAObject obj
		ON (
			obj.id = that.idObject
			AND obj.id <> this.idObject
			) ';

		$query .= 'WHERE this.idObject IN ('.$id.') '.$where.' ';



		// montagem do ORDER BY
		if ($order != '') {
			$query .= 'ORDER BY '.$order.', bond.idSCHEMABond, this.idSCHEMATie, that.idSCHEMATie ';

		} else {
			$query .= 'ORDER BY bond.idSCHEMABond, this.idSCHEMATie, that.idSCHEMATie ';
		}


		//	montagem do LIMIT, caso:
		//	1) o parâmetro 'id' seja uma array, para evitar que seja retornado um número
		//	   excessivo de informações do banco de dados, comprometendo desempenho;
		//	OU
		//	2) os parâmetros 'rowcount' e 'offset' tenham sido recebidos pelo método
		if ($idIsArray == true) {

			if (intval($rowcount) > 0) {
				$query .= ' LIMIT '.$offset.', '.$rowcount;
			} else {
				$query .= ' LIMIT 0, 20';
			}

		} else {
			if (intval($rowcount) > 0) {
				$query .= ' LIMIT '.$offset.', '.$rowcount;
			}
		}
		$query .= '; ';



		$result = $this->db->query(utf8_decode($query));
		if (!$result) return NULL;

		$associations = array();
		while($row = $result->fetch_object()) {
			$associations[] = $row->id;
		}
		$result->free();

		return $associations;
	}





	/**
	 * Carrega uma lista de laços (SCHEMATie) que amarram um Object a um Bond
	 * @param $tieType   papel do Object de referência (SCHEMATie) (opcional, usando *)
	 * @param $idObject  Objeto conhecido, do qual se quer encontrar os laços
	 * @param $idBond    Bond conhecido, do qual se quer encontrar os laços
	 * @return array|boolean
	 */
	public function getTies(array $args=array()) {

		// default parameters values, to be merged with the received parameters
		$default_values = array(
			'tieType'  => '',
			'idObject' => NULL,
			'idBond'   => NULL
		);

		$args = array_merge($default_values, $args);

		$tieType  = $args['tieType'];
		$idObject = $args['idObject'];
		$idBond   = $args['idBond'];

		// Agora os valores recebidos foram normatizados.
		// Prosseguimos com a execução do método


		// o parâmetro tieType é obrigatório,
		// para listar todos os registros, exigimos o parâmetro como '*'
		// que então convertemos para '' (vazio)
		if (trim($tieType) == '') return false;
		if (trim($tieType) == '*') $tieType = '';


		if (is_null($this->db)) return false;
		if (is_null($idObject)) return false;
		if (is_null($idBond))   return false;


		//	o parâmetro tieType pode ser uma string ou uma array de strings de SCHEMATie
		//	cada string é convertida para seu id equivalente

		if ($tieType !== '') {
			$tieType = $this->getSCHEMATieId($tieType, '*', '*');
			$temp_tieType = array();
			foreach($tieType as $objBonds) {
				foreach($objBonds as $b) {
					$temp_tieType[] = $b;
				}
			}
			$tieType = $temp_tieType;
			$tieType = implode(',', $tieType);
		}


		$query = '
		SELECT this.id AS id
		FROM tbDATATie this
		WHERE 1=1 ';

		if ($tieType !== '')     $query .= 'AND this.idSCHEMATie IN ('.$tieType.') ';
		if (!is_null($idBond))   $query .= 'AND this.idBond = '.$idBond.' ';
		if (!is_null($idObject)) $query .= 'AND this.idObject = '.$idObject.' ';

		$query .= '; ';


		// TO-DO: ORDER BY aqui não está implementado!
		// TO-DO: LIMIT aqui não está implementado!


		$result = $this->db->query(utf8_decode($query));
		if (!$result) return NULL;

		$associations = array();
		while($row = $result->fetch_object()) {
			$associations[] = $row->id;
		}
		$result->free();

		return $associations;

	}





	/**
	 * Carrega a lista de ids de Bonds de um determinado tipo (SCHEMABond) a partir de um Tie conhecido
	 * @param int $idTie       id do Tie conhecido
	 * @param string $bondType tipo de bond buscado (SCHEMABond) (opcional, usando *)
	 * @return Array
	 */
	public function getBondsByTie($idTie=NULL, $bondType='') {

		if (is_null($this->db)) return NULL;
		if (is_null($idTie)) return NULL;


		// o parâmetro bondType é obrigatório,
		// para listar todos os registros, exigimos o parâmetro como '*'
		// que então convertemos para '' (vazio)
		if (trim($bondType) == '') return false;
		if (trim($bondType) == '*') $bondType = '';


		//	o parâmetro bondType pode ser uma string ou uma array de strings de SCHEMABond
		//	cada string é convertida para seu id equivalente

		if ($bondType !== '') {
			$bondType = $this->getSCHEMABondId($bondType);
			$temp_bondType = array();
			foreach($bondType as $types) {
				foreach($types as $t) {
					$temp_bondType[] = $t;
				}
			}
			$bondType = $temp_bondType;
			$bondType = implode(',', $bondType);
		}


		$query = '
		SELECT b.id
		FROM tbDATABond AS b
		INNER JOIN tbDATATie ob
		ON (ob.idBond = b.id ';

		if (!is_null($idTie)) {
			$query .= 'AND ob.id = '.$idTie.' ';
		}

		$query .= ') WHERE 1=1 ';

		if (!is_null($bondType)) {
			$query .= 'AND b.idSCHEMABond IN ('.$bondType.') ';
		}

		$query .= 'GROUP BY b.id ORDER BY b.id; ';

		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;

		$bonds = array();
		while($row = $result->fetch_object()) {
			$bonds[] = $row->id;
		}
		$result->free();

		return $bonds;
	}









	/**
	 * Deleta os Bonds existentes entre Objects.
	 * No processo, deleta também os Ties usados nos vínculos com estes Bonds.
	 *
	 * @param int|array  $id         Objeto conhecido, do qual se quer encontrar os Bonds
	 * @param string     $thisTie    Laço -- ou papel -- do Object de referência (SCHEMATie) (opcional, usando *)
	 * @param string     $bondType   Tipo de vínculo (SCHEMABond) (opcional, usando *)
	 * @param string     $thatTie    Laço -- ou papel -- do Object buscado (SCHEMATie) (opcional, usando *)
	 * @param int|array  $thatObject Objeto conhecido, ao qual se liga o primeiro Objeto através dos Bonds
	 * @return boolean
	 */
	public function deleteBonds(array $args=array()) {

		// default parameters values, to be merged with the received parameters
		$default_values = array(
			'id'         => NULL,
			'thisTie'    => '',
			'bondType'   => '',
			'thatTie'    => '',
			'thatObject' => NULL
		);

		$args = array_merge($default_values, $args);

		$id         = $args['id'];
		$thisTie    = $args['thisTie'];
		$bondType   = $args['bondType'];
		$thatTie    = $args['thatTie'];
		$thatObject = $args['thatObject'];

		// Agora os valores recebidos foram normatizados.
		// Prosseguimos com a execução do método



		if (is_null($this->db))   return false;
		if (is_null($id)) 	      return false;
		if (is_null($thatObject)) return false;


		// o parâmetro thisTie é obrigatório,
		// para listar todos os registros, exigimos o parâmetro como '*'
		// que então convertemos para '' (vazio)
		if (trim($thisTie) == '') return false;
		if (trim($thisTie) == '*') $thisTie = '';


		// o parâmetro bondType é obrigatório,
		// para listar todos os registros, exigimos o parâmetro como '*'
		// que então convertemos para '' (vazio)
		if (trim($bondType) == '') return false;
		if (trim($bondType) == '*') $bondType = '';


		// o parâmetro thatTie é obrigatório,
		// para listar todos os registros, exigimos o parâmetro como '*'
		// que então convertemos para '' (vazio)
		if (trim($thatTie) == '') return false;
		if (trim($thatTie) == '*') $thatTie = '';


		if (!is_array($id)) $id = array($id);
		$id = prepareSQL($id);
		$id = implode(',', $id);

		if (!is_array($thatObject)) $thatObject = array($thatObject);
		$thatObject = prepareSQL($thatObject);
		$thatObject = implode(',', $thatObject);




		//	o parâmetro thisTie pode ser uma string ou uma array de strings de SCHEMATie
		//	cada string é convertida para seu id equivalente
		if ($thisTie !== '') {
			$thisTie = $this->getSCHEMATieId($thisTie, '*', '*');
			$temp_thisTie = array();
			foreach($thisTie as $roles) {
				foreach($roles as $r) {
					$temp_thisTie[] = $r;
				}
			}
			$thisTie = $temp_thisTie;
			$thisTie = implode(',',$thisTie);
		}


		//	o parâmetro bondType pode ser uma string ou uma array de strings de SCHEMABond
		//	cada string é convertida para seu id equivalente
		if ($bondType !== '') {
			$bondType = $this->getSCHEMABondId($bondType);
			$temp_bondType = array();
			foreach($bondType as $types) {
				foreach($types as $t) {
					$temp_bondType[] = $t;
				}
			}
			$bondType = $temp_bondType;
			$bondType = implode(',',$bondType);
		}


		//	o parâmetro thatTie pode ser uma string ou uma array de strings de SCHEMATie
		//	cada string é convertida para seu id equivalente
		if ($thatTie !== '') {
			$thatTie = $this->getSCHEMATieId($thatTie, '*', '*');
			$temp_thatTie = array();
			foreach($thatTie as $roles) {
				foreach($roles as $r) {
					$temp_thatTie[] = $r;
				}
			}
			$thatTie = $temp_thatTie;
			$thatTie = implode(',',$thatTie);
		}



		// montagem do WHERE da query, a partir dos tipos de associação e roles dos objetos pesquisados
		$where  = '';
		$where .= $thisTie  == '' ? '' : ('AND `this`.`idSCHEMATie`  IN ('.$thisTie.') ');
		$where .= $bondType == '' ? '' : ('AND `bond`.`idSCHEMABond` IN ('.$bondType.') ');
		$where .= $thatTie  == '' ? '' : ('AND `that`.`idSCHEMATie`  IN ('.$thatTie.') ');
		$where .= 'AND `obj`.`fArchived` = 0 ';


		$query = '
		SELECT bond.id AS id
		FROM tbDATATie this
		INNER JOIN tbDATABond bond
		ON bond.id = this.idBond

		INNER JOIN tbDATATie that
		ON (that.idBond = bond.id
			AND that.idObject IN ('.$thatObject.'))

		INNER JOIN tbDATAObject obj
		ON (obj.id = that.idObject
			AND obj.id <> this.idObject)

		WHERE this.idObject IN ('.$id.') '.$where.'; ';


		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;

		$idBonds = array();
		while($row = $result->fetch_object()) {
			$idBonds[] = $row->id;
		}
		$result->free();


		return $this->deleteBondsById($idBonds);
	}



	/**
	 * Deleta uma lista conhecida de Bonds.
	 * No processo, deleta também todos os Ties usados nos vínculos com estes Bonds.
	 *
	 * @param int|array $id  lista de ids de Bonds que devem ser excluídos
	 * @return boolean
	 */
	public function deleteBondsById($id=NULL) {

		if (is_null($this->db)) return false;
		if (is_null($id)) 	    return false;

		if (!is_array($id)) $id = array($id);
		$id = prepareSQL($id);
		$id = implode(',', $id);


		// dados da transação
		$tr = $this->transaction();
		if (!$tr) return false;

		$iTransaction = $tr['iTransaction'];
		$tsNow = $tr['tsNow'];


		// atualiza os Bonds com a transação para o log
		$query = '
		UPDATE tbDATABond SET
			uniqueUserId = '.prepareSQL($_SESSION['uniqueUserId']).',
			tsLastUpdate = '.prepareSQL($tsNow).',
			iTransaction = '.prepareSQL($iTransaction).',
			sAction = "D"
		WHERE id IN ('.$id.')
		AND idGLOBALNamespace = '.prepareSQL($this->GLOBALNamespace).'; ';

		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;


		// procura pelos Ties que estão ligados a estes Bonds,
		// que ficarão 'soltos' após a exclusão dos Bonds indicados
		$query = 'SELECT id FROM tbDATATie WHERE idBond IN ('.$id.');';
		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;

		$idTies = array();
		while($row = $result->fetch_object()) {
			$idTies[] = $row->id;
		}
		$result->free();
		$idTies = prepareSQL($idTies);
		$idTies = implode(',', $idTies);


		// atualiza os Ties que ficarão 'soltos' com a transação para o log
		$query = '
		UPDATE tbDATATie SET
			uniqueUserId = '.prepareSQL($_SESSION['uniqueUserId']).',
			tsLastUpdate = '.prepareSQL($tsNow).',
			iTransaction = '.prepareSQL($iTransaction).',
			sAction = "D"
		WHERE id IN ('.$idTies.')
		AND idGLOBALNamespace = '.prepareSQL($this->GLOBALNamespace).'; ';

		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;


		// grava log de tudo (Bonds e Ties)
		// a exclusão definitiva dos itens marcados como sAction='D' será feita pelo processo de log
		$success = $this->log($iTransaction, $tsNow);

		return $success;
	}



	/**
	 * Deleta os Ties que amarram um Object a um Bond
	 * No processo, deleta também os Bonds usados nos vínculos com estes Ties, se após a
	 * exclusão os Bonds ficarem "soltos", sem nenhum outro laço que lhe dê significado.
	 * utiliza o método deleteTiesById() uma vez que encontra os ids de Ties a serem excluídos.
	 *
	 * @param int|array  $idObject  Objeto conhecido, do qual se quer encontrar os laços
	 * @param int|array  $idBond    Bond conhecido, do qual se quer encontrar os laços
	 * @param string     $tieType   papel do Object de referência (SCHEMATie) (opcional, usando *)
	 * @return boolean
	 */
	public function deleteTies(array $args=array()) {

		// default parameters values, to be merged with the received parameters
		$default_values = array(
			'idObject' => NULL,
			'idBond'   => NULL,
			'tieType'  => ''
		);

		$args = array_merge($default_values, $args);

		$idObject = $args['idObject'];
		$idBond   = $args['idBond'];
		$tieType  = $args['tieType'];

		// Agora os valores recebidos foram normatizados.
		// Prosseguimos com a execução do método


		if (is_null($this->db)) return false;
		if (is_null($idObject)) return false;
		if (is_null($idBond))   return false;


		// o parâmetro tieType é obrigatório,
		// para listar todos os registros, exigimos o parâmetro como '*'
		// que então convertemos para '' (vazio)
		if (is_string($tieType) && trim($tieType) == '')  return false;
		if (is_string($tieType) && trim($tieType) == '*') $tieType = '';


		//	o parâmetro tieType pode ser uma string ou uma array de strings de SCHEMATie
		//	cada string é convertida para seu id equivalente

		if ($tieType !== '') {
			$tieType = $this->getSCHEMATieId($tieType, '*', '*');
			$temp_tieType = array();
			foreach($tieType as $objBonds) {
				foreach($objBonds as $b) {
					$temp_tieType[] = $b;
				}
			}
			$tieType = $temp_tieType;
			$tieType = prepareSQL($tieType);
			$tieType = implode(',', $tieType);
		}



		if (!is_array($idBond)) $idBond = array($idBond);
		$idBond = prepareSQL($idBond);
		$idBond = implode(',', $idBond);


		if (!is_array($idObject)) $idObject = array($idObject);
		$idObject = prepareSQL($idObject);
		$idObject = implode(',', $idObject);



		$query = '
		SELECT this.id AS id
		FROM tbDATATie this
		WHERE 1=1 ';
		if ($tieType !== '')     $query .= 'AND this.idSCHEMATie IN ('.$tieType.') ';
		if (!is_null($idBond))   $query .= 'AND this.idBond IN ('.$idBond.') ';
		if (!is_null($idObject)) $query .= 'AND this.idObject IN ('.$idObject.') ';
		$query .= '; ';


		$result = $this->db->query(utf8_decode($query));
		if (!$result) return NULL;

		$idTies = array();
		while($row = $result->fetch_object()) {
			$idTies[] = $row->id;
		}
		$result->free();


		return $this->deleteTiesById($idTies);
	}



	/**
	 * Deleta uma lista conhecida de Ties.
	 * No processo, deleta também os Bonds usados nos vínculos com estes Ties, se após a
	 * exclusão os Bonds ficarem "soltos", sem nenhum outro laço que lhe dê significado.
	 *
	 * @param int|array $id  lista de ids de Ties que devem ser excluídos
	 * @return boolean
	 */
	public function deleteTiesById($id=NULL) {

		if (is_null($this->db))   return false;
		if (is_null($id)) 	      return false;

		if (!is_array($id)) $id = array($id);
		$id = prepareSQL($id);
		$id = implode(',', $id);


		// dados da transação
		$tr = $this->transaction();
		if (!$tr) return false;

		$iTransaction = $tr['iTransaction'];
		$tsNow = $tr['tsNow'];


		// atualiza os Ties com a transação para o log
		$query = '
		UPDATE tbDATATie SET
			uniqueUserId = '.prepareSQL($_SESSION['uniqueUserId']).',
			tsLastUpdate = '.prepareSQL($tsNow).',
			iTransaction = '.prepareSQL($iTransaction).',
			sAction = "D"
		WHERE id IN ('.$id.')
		AND idGLOBALNamespace = '.prepareSQL($this->GLOBALNamespace).'; ';

		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;



		// procura pelos Bonds que estão ligados APENAS a estes Ties,
		// que ficarão 'soltos' após a exclusão dos Ties indicados
		$query = '
		SELECT bonds.id FROM (
			SELECT
				b.id AS id,
				COUNT(t.id) AS tiecount
			FROM tbDATABond b

			INNER JOIN (
				SELECT b1.id
				FROM tbDATABond b1

				INNER JOIN tbDATATie t1
				ON (b1.id = t1.idBond AND t1.id IN ('.$id.'))
				GROUP BY b1.id
			) AS bt
			ON b.id = bt.id

			INNER JOIN tbDATATie t
			ON (b.id = t.idBond AND t.id NOT IN ('.$id.'))
		) bonds WHERE bonds.tiecount <= 1;';



		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;

		$idBonds = array();
		while($row = $result->fetch_object()) {
			$idBonds[] = $row->id;
		}
		$result->free();


		if (count($idBonds) > 0) {

			$idBonds = prepareSQL($idBonds);
			$idBonds = implode(',', $idBonds);


			// atualiza os Bonds que ficaram 'soltos' com a transação para o log
			$query = '
			UPDATE tbDATABond SET
				uniqueUserId = '.prepareSQL($_SESSION['uniqueUserId']).',
				tsLastUpdate = '.prepareSQL($tsNow).',
				iTransaction = '.prepareSQL($iTransaction).',
				sAction = "D"
			WHERE id IN ('.$idBonds.')
			AND idGLOBALNamespace = '.prepareSQL($this->GLOBALNamespace).'; ';

			$result = $this->db->query(utf8_decode($query));
			if (!$result) return false;
		}

		// grava log de tudo e exclui os registros marcados com sAction="D"
		$success = $this->log($iTransaction, $tsNow);

		return $success;
	}













	/**
	 * Cria um novo Namespace para o MultiBond
	 * @return boolean
	 */
	public function createGLOBALNamespace($GLOBALNamespace=NULL) {

		$GLOBALNamespace = filter_var($GLOBALNamespace, FILTER_SANITIZE_MAGIC_QUOTES);
		$GLOBALNamespace = filter_var($GLOBALNamespace, FILTER_SANITIZE_SPECIAL_CHARS);

		if (is_null($GLOBALNamespace)) return false;

		// GLOBAL é um namespace reservado pelo sistema, para SCHEMAS compartilhados entre todos os demais namespaces
		if (strtoupper($GLOBALNamespace) === 'GLOBAL') return false;

		$alreadyExists = $this->validateNamespace($GLOBALNamespace);
		if ($alreadyExists) return false;

		$query  = 'INSERT INTO tbGLOBALNamespace (sNamespace, fArchived) VALUES ('.prepareSQL($GLOBALNamespace).', 0); ';
		$result = $this->db->query(utf8_decode($query));

		if (!$result) return false;

		return true;
	}



	/**
	 * Altera o nome de um Namespace
	 * @return boolean
	 */
	public function updateGLOBALNamespace($id=NULL, $GLOBALNamespace=NULL) {

		$id      	     = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
		$GLOBALNamespace = filter_var($GLOBALNamespace, FILTER_SANITIZE_MAGIC_QUOTES);
		$GLOBALNamespace = filter_var($GLOBALNamespace, FILTER_SANITIZE_SPECIAL_CHARS);

		if (is_null($id))              return false;
		if (is_null($GLOBALNamespace)) return false;

		// GLOBAL é um namespace reservado pelo sistema, para SCHEMAS compartilhados entre todos os demais namespaces
		if (strtoupper($GLOBALNamespace) === 'GLOBAL') return false;

		// valida se o nome que está sendo gravado neste namespace já existe em outro namespace
		$alreadyExists = $this->validateNamespace($GLOBALNamespace);
		$idGLOBALNamespace = $this->getNamespaceId($GLOBALNamespace);
		if ($alreadyExists && $idGLOBALNamespace != $id) return false;

		$query  = 'UPDATE tbGLOBALNamespace SET sNamespace = '.prepareSQL($GLOBALNamespace).' WHERE id = '.prepareSQL($id).'; ';

		$result = $this->db->query(utf8_decode($query));

		if (!$result) return false;

		return true;
	}









	/**
	 * Verifica se um determinado SCHEMA pode ser criado no namespace
	 * @param string $SCHEMA              sKey (nome, token) do SCHEMA a ser criado
	 * @param string $SCHEMATable         tabela onde o SCHEMA será criado
	 * @param int|NULL $idGLOBALNamespace namespace onde o SCHEMA será criado
	 * @return boolean
	 */
	private function _canCreateSCHEMA($SCHEMA, $SCHEMATable, $idGLOBALNamespace){

		// verifica se o SCHEMA já existe no namespace
		// REGRAS PARA CRIAÇÃO DE UM NOVO SCHEMA:
		// 1) não pode ser criado um SCHEMA no namespace GLOBAL igual a um SCHEMA em qualquer outro namespace;
		// 2) não pode ser criado um SCHEMA em um determinado namespace igual a um SCHEMA no namespace GLOBAL;
		// 3) não podem haver dois SCHEMAs iguais dentro de um mesmo namespace;
		// 4) podem haver dois SCHEMAs iguais dentro de namespaces diferentes, desde que nenhum deles seja GLOBAL;

		$query  = '
		SELECT id FROM '.$SCHEMATable.'
		WHERE sKey = '.prepareSQL($SCHEMA).' ';
		$query .= (!is_null($idGLOBALNamespace)) ? 'AND (idGLOBALNamespace = '.prepareSQL($idGLOBALNamespace).' OR idGLOBALNamespace IS NULL); ' : '; ';


		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;
		if ($result->num_rows !== 0) return false;
		$result->free();

		return true;
	}



	/**
	 * Verifica se um determinado SCHEMAProperty pode ser criado no namespace
	 * @param string $SCHEMAProperty      sKey (nome, token) do SCHEMAProperty a ser criado
	 * @param int $idSCHEMAObject         id do SCHEMAObject onde o SCHEMAProperty será criado
	 * @param int|NULL $idGLOBALNamespace namespace onde o SCHEMAProperty será criado
	 * @return boolean
	 */
	private function _canCreateSCHEMAProperty($SCHEMAProperty, $idSCHEMAObject, $idGLOBALNamespace){

		// REGRAS PARA CRIAÇÃO DE UM NOVO SCHEMAProperty:
		// 1) SCHEMAProperty e SCHEMAObject devem pertencer ao mesmo namespace,
		//    EXCETO APENAS se SCHEMAObject pertencer ao namespace GLOBAL;

		// verifica se o namespace do SCHEMAProperty é compatível com o namespace do SCHEMAObject:
		$query  = '
		SELECT id FROM tbSCHEMAObject
		WHERE id = '.prepareSQL($idSCHEMAObject).' ';

		if (is_null($idGLOBALNamespace)) {
			$query .= 'AND idGLOBALNamespace IS NULL; ';
		} else {
			$query .= 'AND (idGLOBALNamespace = '.prepareSQL($idGLOBALNamespace).' OR idGLOBALNamespace IS NULL); ';
		}


		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;
		if ($result->num_rows == 0) return false;
		$result->free();


		// 2) não pode ser criado um SCHEMAProperty no namespace GLOBAL igual a um SCHEMAProperty em qualquer outro namespace;
		// 3) não pode ser criado um SCHEMAProperty em um determinado namespace igual a um SCHEMAProperty no namespace GLOBAL;
		// 4) não podem haver dois SCHEMAProperty iguais dentro de um mesmo namespace;
		// 5) podem haver dois SCHEMAProperty iguais dentro de namespaces diferentes, desde que nenhum deles seja GLOBAL;
		// IMPORTANTE: todas as regras acima assumem que os SCHEMAProperty em questão pertencem a um mesmo SCHEMAObject;

		// verifica se o SCHEMAProperty já existe:
		$query  = '
		SELECT id FROM tbSCHEMAProperty
		WHERE sKey = '.prepareSQL($SCHEMAProperty).'
		AND idSCHEMAObject = '.prepareSQL($idSCHEMAObject).' ';
		$query .= (!is_null($idGLOBALNamespace)) ? ('AND (idGLOBALNamespace = '.prepareSQL($idGLOBALNamespace).' OR idGLOBALNamespace IS NULL); ') : ('; ') ;


		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;
		if ($result->num_rows !== 0) return false;
		$result->free();

		return true;
	}



	/**
	 * Função genérica que cria novos SCHEMA (SCHEMAObject, SCHEMABond ou SCHEMATie) para o MultiBond
	 * @return int (id do SCHEMA criado)
	 */
	private function createSCHEMA($SCHEMATable=NULL, $SCHEMA=NULL, $sComment=NULL, $sNamespace=NULL) {

		$SCHEMA   = filter_var($SCHEMA, FILTER_SANITIZE_MAGIC_QUOTES);
		$sComment = filter_var($sComment, FILTER_SANITIZE_MAGIC_QUOTES);

		if (is_null($SCHEMATable)) return false;
		if (is_null($SCHEMA) || strlen($SCHEMA)===0) return false;


		// valida os nomes aceitos de SCHEMATable
		if ($SCHEMATable !== 'tbSCHEMAObject' && $SCHEMATable !== 'tbSCHEMABond' && $SCHEMATable !== 'tbSCHEMATie') return false;


		// se não for informado um namespace diferente, o namespace atual do MultiBond será usado
		if (is_null($sNamespace)) {
			$idGLOBALNamespace = $this->GLOBALNamespace;
		} else {
			$sNamespace = filter_var($sNamespace, FILTER_SANITIZE_MAGIC_QUOTES);
			$idGLOBALNamespace = $this->getNamespaceId($sNamespace);
			if (is_null($idGLOBALNamespace)) return false;
		}

		// ATENÇÃO!
		// acima deste ponto, o valor de $idGLOBALNamespace NÃO PODERIA SER NULL
		// o que significa que um namespace não foi atribuído para esta operação
		//
		// a partir deste ponto, o valor de $idGLOBALNamespace PODE SER NULL
		// o que significa que o namespace "GLOBAL" foi atribuído para esta operação,
		// e seu valor interno aos objetos PHP "0" (inteiro zero) foi substituído pelo valor usado no banco de dados "NULL" (nulo)

		$idGLOBALNamespace = ($idGLOBALNamespace == 0) ? NULL : $idGLOBALNamespace;



		// verifica se o SCHEMA já existe
		if (!$this->_canCreateSCHEMA($SCHEMA, $SCHEMATable, $idGLOBALNamespace)) return false;



		// dados da transação
		$tr = $this->transaction();
		if (!$tr) return false;

		$iTransaction = $tr['iTransaction'];
		$tsNow = $tr['tsNow'];



		$query  = '
		INSERT INTO '.$SCHEMATable.'
		(
			id,
			idGLOBALNamespace,
			sKey,
			sComment,
			uniqueUserId,
			tsCreation,
			tsLastUpdate,
			iTransaction,
			sAction

		) VALUES (

			NULL,
			'.prepareSQL($idGLOBALNamespace).',
			'.prepareSQL($SCHEMA).',
			'.prepareSQL($sComment).',
			'.prepareSQL($_SESSION['uniqueUserId']).',
			'.prepareSQL($tsNow).',
			'.prepareSQL($tsNow).',
			'.prepareSQL($iTransaction).',
			"I"
		); ';


		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;
		$idSCHEMA = $this->db->insert_id;

		$success = $this->log($iTransaction, $tsNow);

		return $idSCHEMA;
	}




	/**
	 * Função genérica que atualiza SCHEMA (SCHEMAObject, SCHEMABond ou SCHEMATie) para o MultiBond
	 * @return boolean
	 */
	private function updateSCHEMA($id=NULL, $SCHEMATable=NULL, $SCHEMA=NULL, $sComment=NULL, $sNamespace=NULL) {

		$id      	= filter_var($id, FILTER_SANITIZE_NUMBER_INT);
		$SCHEMA     = filter_var($SCHEMA, FILTER_SANITIZE_MAGIC_QUOTES);
		$sComment   = filter_var($sComment, FILTER_SANITIZE_MAGIC_QUOTES);


		if (is_null($id))          return false;
		if (is_null($SCHEMATable)) return false;
		if (is_null($SCHEMA))      return false;


		// valida os nomes aceitos de SCHEMATable
		if ($SCHEMATable !== 'tbSCHEMAObject' && $SCHEMATable !== 'tbSCHEMABond' && $SCHEMATable !== 'tbSCHEMATie') return false;


		// se não for informado um namespace diferente, o namespace atual do MultiBond será usado
		if (is_null($sNamespace)) {
			$idGLOBALNamespace = $this->GLOBALNamespace;
		} else {
			$sNamespace = filter_var($sNamespace, FILTER_SANITIZE_MAGIC_QUOTES);
			$idGLOBALNamespace = $this->getNamespaceId($sNamespace);
			if (is_null($idGLOBALNamespace)) return false;
		}




		// ATENÇÃO!
		// acima deste ponto, o valor de $idGLOBALNamespace não podia ser NULL
		// o que significa que um namespace não foi atribuído para esta operação
		//
		// a partir deste ponto, o valor de $idGLOBALNamespace pode ser NULL
		// o que significa que o namespace GLOBAL foi atribuído para esta operação,
		// e seu valor interno aos objetos PHP 0 (inteiro zero) foi substituído pelo valor usado no banco de dados NULL

		$idGLOBALNamespace = ($idGLOBALNamespace == 0) ? NULL : $idGLOBALNamespace;



		// verifica se o SCHEMA já existe
		// if (!$this->_canCreateSCHEMA($SCHEMA, $SCHEMATable, $idGLOBALNamespace)) return false;

		// verifica se o SCHEMA existe no namespace, com um id diferente,
		// o que significaria que ao fazer update, estamos trocando o nome do SCHEMA e
		// este novo nome está em conflito com um SCHEMA existente
		if (!$this->_canCreateSCHEMA($SCHEMA, $SCHEMATable, $idGLOBALNamespace)) {

			     if ($SCHEMATable === 'tbSCHEMAObject') { $existing_id = $this->getSCHEMAObjectId($SCHEMA);     }
			else if ($SCHEMATable === 'tbSCHEMABond')   { $existing_id = $this->getSCHEMABondId($SCHEMA);       }
			else if ($SCHEMATable === 'tbSCHEMATie')    { $existing_id = $this->getSCHEMATieId($SCHEMA); }
			else { return false; }

			if ($existing_id && $existing_id[0] && $existing_id[0][0] != $id) return false;
		}



		// dados da transação
		$tr = $this->transaction();
		if (!$tr) return false;

		$iTransaction = $tr['iTransaction'];
		$tsNow = $tr['tsNow'];



		$query = '
		UPDATE '.$SCHEMATable.' SET
			sKey = '.prepareSQL($SCHEMA).',
			sComment = '.prepareSQL($sComment).',
			uniqueUserId = '.prepareSQL($_SESSION['uniqueUserId']).',
			tsCreation = '.prepareSQL($tsNow).',
			tsLastUpdate = '.prepareSQL($tsNow).',
			iTransaction = '.prepareSQL($iTransaction).',
			sAction = "U"
		WHERE id = '.prepareSQL($id).' ';

		$query .= (is_null($idGLOBALNamespace)) ? ('AND idGLOBALNamespace IS NULL ') : ('AND idGLOBALNamespace = '.prepareSQL($idGLOBALNamespace).' ') ;

		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;

		$success = $this->log($iTransaction, $tsNow);

		return $success;
	}




	/**
	 * Função genérica que exclui um SCHEMA (SCHEMAObject, SCHEMABond ou SCHEMATie) do MultiBond
	 * @return boolean
	 */
	private function excludeSCHEMA($id=NULL, $SCHEMATable=NULL) {

		$id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);

		if (is_null($id))          return false;
		if (is_null($SCHEMATable)) return false;


		// valida os nomes aceitos de SCHEMATable
		if ($SCHEMATable !== 'tbSCHEMAObject' && $SCHEMATable !== 'tbSCHEMABond' && $SCHEMATable !== 'tbSCHEMATie') return false;


		// dados da transação
		$tr = $this->transaction();
		if (!$tr) return false;

		$iTransaction = $tr['iTransaction'];
		$tsNow = $tr['tsNow'];


		$query = '
		UPDATE '.$SCHEMATable.'
		SET
			uniqueUserId = '.prepareSQL($_SESSION['uniqueUserId']).',
			tsCreation = '.prepareSQL($tsNow).',
			tsLastUpdate = '.prepareSQL($tsNow).',
			iTransaction = '.prepareSQL($iTransaction).',
			sAction = "D"
		WHERE id = '.prepareSQL($id).'; ';

		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;

		$this->log($iTransaction, $tsNow);

		return true;
	}






	/**
	 * Função genérica que cria novas SCHEMAProperty para o MultiBond
	 * @return int (id da SCHEMAProperty criada)
	 */
	public function createSCHEMAProperty($sSCHEMAObject=NULL, $sKey=NULL, $sDataType=NULL, $fMultiple=0, $sComment=NULL, $sNamespace=NULL) {

		$sSCHEMAObject = filter_var($sSCHEMAObject, FILTER_SANITIZE_MAGIC_QUOTES);
		$sKey          = filter_var($sKey,          FILTER_SANITIZE_MAGIC_QUOTES);
		$sDataType     = filter_var($sDataType,     FILTER_SANITIZE_MAGIC_QUOTES);
		$fMultiple     = filter_var($fMultiple,     FILTER_SANITIZE_NUMBER_INT);
		$sComment      = filter_var($sComment,      FILTER_SANITIZE_MAGIC_QUOTES);


		if (is_null($sSCHEMAObject)) return false;
		if (is_null($sKey))          return false;


		// validação do tipo de objeto ao qual se aplica esta propriedade
		if ($sSCHEMAObject !== '') {
			$sSCHEMAObject = $this->getSCHEMAObjectId($sSCHEMAObject);
			$temp_SCHEMAObject = array();
			foreach($sSCHEMAObject as $types) {
				foreach($types as $t) {
					$temp_SCHEMAObject[] = $t;
				}
			}
			$sSCHEMAObject = $temp_SCHEMAObject;
			$sSCHEMAObject = count($sSCHEMAObject)===1 ? $sSCHEMAObject[0] : NULL;
		}
		if (is_null($sSCHEMAObject)) return false;



		// se não for informado um namespace diferente, o namespace atual do MultiBond será usado
		if (is_null($sNamespace)) {
			$idGLOBALNamespace = $this->GLOBALNamespace;
		} else {
			$sNamespace = filter_var($sNamespace, FILTER_SANITIZE_MAGIC_QUOTES);
			$idGLOBALNamespace = $this->getNamespaceId($sNamespace);
			if (is_null($idGLOBALNamespace)) return false;
		}

		// ATENÇÃO!
		// acima deste ponto, o valor de $idGLOBALNamespace não podia ser NULL
		// o que significa que um namespace não foi atribuído para esta operação
		//
		// a partir deste ponto, o valor de $idGLOBALNamespace pode ser NULL
		// o que significa que o namespace GLOBAL foi atribuído para esta operação,
		// e seu valor interno aos objetos PHP 0 (inteiro zero) foi substituído pelo valor usado no banco de dados NULL

		$idGLOBALNamespace = ($idGLOBALNamespace == 0) ? NULL : $idGLOBALNamespace;


		// apenas são aceitos os seguintes formatos de dados:
		$sDataType = strtolower($sDataType);
		if (
			   $sDataType !== 'smallint'
			&& $sDataType !== 'bigint'
			&& $sDataType !== 'money'
			&& $sDataType !== 'float'
			&& $sDataType !== 'datetime'
			&& $sDataType !== 'string'
			&& $sDataType !== 'text'
			&& $sDataType !== 'blob'
			&& $sDataType !== 'hid'
			&& $sDataType !== 'object'
		) {
			return false;
		}


		// verifica se o SCHEMAProperty já existe no namespace
		if (!$this->_canCreateSCHEMAProperty($sKey, $sSCHEMAObject, $idGLOBALNamespace)) return false;

		// REGRAS PARA CRIAÇÃO DE UM NOVO SCHEMAProperty:
		// IMPORTANTE: todas as regras abaixo assumem que os SCHEMAProperty em questão pertencem a um mesmo sSCHEMAObject;
		//
		// 1) não pode ser criado um sSCHEMAObject::SCHEMAProperty no namespace GLOBAL igual a um sSCHEMAObject::SCHEMAProperty em qualquer outro namespace;
		// 2) não pode ser criado um sSCHEMAObject::SCHEMAProperty em um determinado namespace igual a um sSCHEMAObject::SCHEMAProperty no namespace GLOBAL;
		// 3) não podem haver dois sSCHEMAObject::SCHEMAProperty iguais dentro de um mesmo namespace;
		// 4) podem haver dois sSCHEMAObject::SCHEMAProperty iguais dentro de namespaces diferentes, desde que nenhum deles seja GLOBAL;




		// dados da transação
		$tr = $this->transaction();
		if (!$tr) return false;

		$iTransaction = $tr['iTransaction'];
		$tsNow = $tr['tsNow'];



		$query  = '
		INSERT INTO tbSCHEMAProperty
		(
			id,
			idGLOBALNamespace,
			idSCHEMAObject,
			sKey,
			sDataType,
			fMultiple,
			sComment,
			uniqueUserId,
			tsCreation,
			tsLastUpdate,
			iTransaction,
			sAction

		) VALUES (

			NULL,
			'.prepareSQL($idGLOBALNamespace).',
			'.prepareSQL($sSCHEMAObject).',
			'.prepareSQL($sKey).',
			'.prepareSQL($sDataType).',
			'.prepareSQL($fMultiple).',
			'.prepareSQL($sComment).',
			'.prepareSQL($_SESSION['uniqueUserId']).',
			'.prepareSQL($tsNow).',
			'.prepareSQL($tsNow).',
			'.prepareSQL($iTransaction).',
			"I"
		); ';


		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;
		$idSCHEMAProperty = $this->db->insert_id;

		$this->log($iTransaction, $tsNow);

		return $idSCHEMAProperty;
	}

	public function updateSCHEMAProperty($id=NULL, $sSCHEMAObject=NULL, $sKey=NULL, $sDataType=NULL, $fMultiple=0, $sComment=NULL, $sNamespace=NULL) {

		$id      	   = filter_var($id,            FILTER_SANITIZE_NUMBER_INT);
		$sSCHEMAObject = filter_var($sSCHEMAObject, FILTER_SANITIZE_MAGIC_QUOTES);
		$sKey          = filter_var($sKey,          FILTER_SANITIZE_MAGIC_QUOTES);
		$sDataType     = filter_var($sDataType,     FILTER_SANITIZE_MAGIC_QUOTES);
		$fMultiple     = filter_var($fMultiple,     FILTER_SANITIZE_NUMBER_INT);
		$sComment      = filter_var($sComment,      FILTER_SANITIZE_MAGIC_QUOTES);


		if (is_null($id))            return false;
		if (is_null($sSCHEMAObject)) return false;
		if (is_null($sKey))          return false;


		// valor original recebido contendo o nome do ObjectType como string
		$sOriginalSCHEMAObject = $sSCHEMAObject;


		// validação do tipo de objeto ao qual se aplica esta propriedade
		if ($sSCHEMAObject !== '') {
			$sSCHEMAObject = $this->getSCHEMAObjectId($sSCHEMAObject);
			$temp_SCHEMAObject = array();
			foreach($sSCHEMAObject as $types) {
				foreach($types as $t) {
					$temp_SCHEMAObject[] = $t;
				}
			}
			$sSCHEMAObject = $temp_SCHEMAObject;
			$sSCHEMAObject = count($sSCHEMAObject)===1 ? $sSCHEMAObject[0] : NULL;
		}
		if (is_null($sSCHEMAObject)) return false;



		// se não for informado um namespace diferente, o namespace atual do MultiBond será usado
		if (is_null($sNamespace)) {
			$idGLOBALNamespace = $this->GLOBALNamespace;
		} else {
			$sNamespace = filter_var($sNamespace, FILTER_SANITIZE_MAGIC_QUOTES);
			$idGLOBALNamespace = $this->getNamespaceId($sNamespace);
			if (is_null($idGLOBALNamespace)) return false;
		}

		// ATENÇÃO!
		// acima deste ponto, o valor de $idGLOBALNamespace não podia ser NULL
		// o que significa que um namespace não foi atribuído para esta operação
		//
		// a partir deste ponto, o valor de $idGLOBALNamespace pode ser NULL
		// o que significa que o namespace GLOBAL foi atribuído para esta operação,
		// e seu valor interno aos objetos PHP 0 (inteiro zero) foi substituído pelo valor usado no banco de dados NULL

		$idGLOBALNamespace = ($idGLOBALNamespace == 0) ? NULL : $idGLOBALNamespace;


		// apenas são aceitos os seguintes formatos de dados:
		$sDataType = strtolower($sDataType);
		if (
			   $sDataType !== 'smallint'
			&& $sDataType !== 'bigint'
			&& $sDataType !== 'money'
			&& $sDataType !== 'float'
			&& $sDataType !== 'datetime'
			&& $sDataType !== 'string'
			&& $sDataType !== 'text'
			&& $sDataType !== 'blob'
			&& $sDataType !== 'hid'
			&& $sDataType !== 'object'
		) {
			return false;
		}


		// verifica se o SCHEMAProperty existe no namespace, com um id diferente,
		// o que significaria que ao fazer update, estamos trocando o nome do SCHEMAProperty,
		// e este novo nome está em conflito com um SCHEMAProperty existente
		if (!$this->_canCreateSCHEMAProperty($sKey, $sSCHEMAObject, $idGLOBALNamespace)) {
			$existing_id = $this->getSCHEMAPropertyId($sKey, $sOriginalSCHEMAObject);
			if ($existing_id && $existing_id[0] && $existing_id[0][0] != $id) return false;
		}


		// dados da transação
		$tr = $this->transaction();
		if (!$tr) return false;

		$iTransaction = $tr['iTransaction'];
		$tsNow = $tr['tsNow'];



		$query = '
		UPDATE tbSCHEMAProperty
		SET
			idGLOBALNamespace = '.prepareSQL($idGLOBALNamespace).',
			idSCHEMAObject = '.prepareSQL($sSCHEMAObject).',
			sKey = '.prepareSQL($sKey).',
			sDataType = '.prepareSQL($sDataType).',
			fMultiple = '.prepareSQL($fMultiple).',
			sComment = '.prepareSQL($sComment).',
			uniqueUserId = '.prepareSQL($_SESSION['uniqueUserId']).',
			tsLastUpdate = '.prepareSQL($tsNow).',
			iTransaction = '.prepareSQL($iTransaction).',
			sAction = "U"
		WHERE id = '.prepareSQL($id).'; ';

		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;

		$success = $this->log($iTransaction, $tsNow);

		return $success;
	}

	public function excludeSCHEMAProperty($id=NULL) {

		$id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);

		if (is_null($id)) return false;


		// dados da transação
		$tr = $this->transaction();
		if (!$tr) return false;

		$iTransaction = $tr['iTransaction'];
		$tsNow = $tr['tsNow'];


		$query = '
		UPDATE tbSCHEMAProperty
		SET
			uniqueUserId = '.prepareSQL($_SESSION['uniqueUserId']).',
			tsLastUpdate = '.prepareSQL($tsNow).',
			iTransaction = '.prepareSQL($iTransaction).',
			sAction = "D"
		WHERE id = '.prepareSQL($id).'; ';

		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;

		$success = $this->log($iTransaction, $tsNow, 'D');

		return $success;
	}







	/**
	 * Cria um novo Object no SCHEMA para o MultiBond
	 * @return boolean
	 */
	public function createSCHEMAObject($SCHEMAObject=NULL, $sComment=NULL, $sNamespace=NULL) {

		return $this->createSCHEMA('tbSCHEMAObject', $SCHEMAObject, $sComment, $sNamespace);
	}

	public function updateSCHEMAObject($id=NULL, $SCHEMAObject=NULL, $sComment=NULL, $sNamespace=NULL) {

		return $this->updateSCHEMA($id, 'tbSCHEMAObject', $SCHEMAObject, $sComment, $sNamespace);
	}

	public function excludeSCHEMAObject($id=NULL) {

		return $this->excludeSCHEMA($id, 'tbSCHEMAObject');
	}





	/**
	 * Cria um novo Bond no SCHEMA para o MultiBond
	 * @return boolean
	 */
	public function createSCHEMABond($SCHEMABond=NULL, $sComment=NULL, $sNamespace=NULL) {

		return $this->createSCHEMA('tbSCHEMABond', $SCHEMABond, $sComment, $sNamespace);
	}

	public function updateSCHEMABond($id=NULL, $SCHEMABond=NULL, $sComment=NULL, $sNamespace=NULL) {

		return $this->updateSCHEMA($id, 'tbSCHEMABond', $SCHEMABond, $sComment, $sNamespace);
	}

	public function excludeSCHEMABond($id=NULL) {

		return $this->excludeSCHEMA($id, 'tbSCHEMABond');
	}





	/**
	 * Cria um novo Tie no SCHEMA para o MultiBond
	 * @return boolean
	 */
	public function createSCHEMATie($SCHEMATie=NULL, $sComment=NULL, $sNamespace=NULL) {

		return $this->createSCHEMA('tbSCHEMATie', $SCHEMATie, $sComment, $sNamespace);
	}

	public function updateSCHEMATie($id=NULL, $SCHEMATie=NULL, $sComment=NULL, $sNamespace=NULL) {

		return $this->updateSCHEMA($id, 'tbSCHEMATie', $SCHEMATie, $sComment, $sNamespace);
	}

	public function excludeSCHEMATie($id=NULL) {

		return $this->excludeSCHEMA($id, 'tbSCHEMATie');
	}





	/**
	 * Cria as entradas dos registros nas tabelas tbLOG***
	 * Procura em todas as tabelas tbDATA*** por registros com o mesmo timestamp e id de transação
	 * e copia estes registros para a tabela de log, marcando o tipo de atividade (I,U,D)
	 * @return Boolean
	 */
	public function log($iTransaction=NULL, $tsNow=NULL) {

		if (is_null($this->db)) 	return false;
		if (is_null($iTransaction)) return false;
		if (is_null($tsNow)) 		return false;

		$iTransaction = filter_var($iTransaction, FILTER_SANITIZE_NUMBER_INT);
		$tsNow        = filter_var($tsNow, FILTER_SANITIZE_STRING);


		if (is_null($iTransaction)) return false;
		if (is_null($tsNow)) 		return false;


		// log da tabela tbDATAObject
		$query = '
		INSERT INTO tbDATA_LOGObject
		(
			logId,
			sAction,
			id,
			idSCHEMAObject,
			idGLOBALNamespace,
			uniqueUserId,
			tsCreation,
			tsLastUpdate,
			iTransaction,
			fArchived

		) SELECT

			NULL,
			sAction,
			id,
			idSCHEMAObject,
			idGLOBALNamespace,
			uniqueUserId,
			tsCreation,
			tsLastUpdate,
			iTransaction,
			fArchived

		FROM  tbDATAObject
		WHERE tsLastUpdate = '.prepareSQL($tsNow).'
		AND   iTransaction = '.prepareSQL($iTransaction).'; ';


//	echo "<pre>";
//	print_r($query);
//	echo "<hr>";
//	echo "</pre>";


		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;



		// log da tabela tbDATABond
		$query = '
		INSERT INTO tbDATA_LOGBond
		(
			logId,
			sAction,
			id,
			idGLOBALNamespace,
			idSCHEMABond,
			uniqueUserId,
			tsCreation,
			tsLastUpdate,
			iTransaction

		) SELECT

			NULL,
			sAction,
			id,
			idGLOBALNamespace,
			idSCHEMABond,
			uniqueUserId,
			tsCreation,
			tsLastUpdate,
			iTransaction

		FROM  tbDATABond
		WHERE tsLastUpdate = '.prepareSQL($tsNow).'
		AND   iTransaction = '.prepareSQL($iTransaction).'; ';


//	echo "<pre>";
//	print_r($query);
//	echo "<hr>";
//	echo "</pre>";


		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;



		// log da tabela tbDATATie
		$query = '
		INSERT INTO tbDATA_LOGTie
		(
			logId,
			sAction,
			id,
			idObject,
			idBond,
			idSCHEMATie,
			idGLOBALNamespace,
			uniqueUserId,
			tsCreation,
			tsLastUpdate,
			iTransaction

		) SELECT

			NULL,
			sAction,
			id,
			idObject,
			idBond,
			idSCHEMATie,
			idGLOBALNamespace,
			uniqueUserId,
			tsCreation,
			tsLastUpdate,
			iTransaction

		FROM  tbDATATie
		WHERE tsLastUpdate = '.prepareSQL($tsNow).'
		AND   iTransaction = '.prepareSQL($iTransaction).'; ';


//	echo "<pre>";
//	print_r($query);
//	echo "<hr>";
//	echo "</pre>";


		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;



		// log da tabela tbDATAProperty
		$query = '
		INSERT INTO tbDATA_LOGProperty
		(
			logId,
			sAction,
			id,
			idObject,
			idSCHEMAProperty,
			idGLOBALNamespace,
			uniqueUserId,
			smallintValue,
			bigintValue,
			moneyValue,
			floatValue,
			datetimeValue,
			stringValue,
			textValue,
			blobValue,
			hidValue,
			objectValue,
			iIndex,
			tsCreation,
			tsLastUpdate,
			iTransaction

		) SELECT

			NULL,
			sAction,
			id,
			idObject,
			idSCHEMAProperty,
			idGLOBALNamespace,
			uniqueUserId,
			smallintValue,
			bigintValue,
			moneyValue,
			floatValue,
			datetimeValue,
			stringValue,
			textValue,
			blobValue,
			hidValue,
			objectValue,
			iIndex,
			tsCreation,
			tsLastUpdate,
			iTransaction

		FROM  tbDATAProperty
		WHERE tsLastUpdate = '.prepareSQL($tsNow).'
		AND   iTransaction = '.prepareSQL($iTransaction).'; ';


//	echo "<pre>";
//	print_r($query);
//	echo "<hr>";
//	echo "</pre>";


		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;



		// log da tabela tbSCHEMA_LOGObject
		$query = '
		INSERT INTO tbSCHEMA_LOGObject
		(
			logId,
			sAction,
			id,
			idGLOBALNamespace,
			sKey,
			sComment,
			uniqueUserId,
			tsCreation,
			tsLastUpdate,
			iTransaction

		) SELECT

			NULL,
			sAction,
			id,
			idGLOBALNamespace,
			sKey,
			sComment,
			uniqueUserId,
			tsCreation,
			tsLastUpdate,
			iTransaction

		FROM  tbSCHEMAObject
		WHERE tsLastUpdate = '.prepareSQL($tsNow).'
		AND   iTransaction = '.prepareSQL($iTransaction).'; ';


//	echo "<pre>";
//	print_r($query);
//	echo "<hr>";
//	echo "</pre>";


		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;



		// log da tabela de tbSCHEMABond
		$query = '
		INSERT INTO tbSCHEMA_LOGBond
		(
			logId,
			sAction,
			id,
			idGLOBALNamespace,
			sKey,
			sComment,
			uniqueUserId,
			tsCreation,
			tsLastUpdate,
			iTransaction

		) SELECT

			NULL,
			sAction,
			id,
			idGLOBALNamespace,
			sKey,
			sComment,
			uniqueUserId,
			tsCreation,
			tsLastUpdate,
			iTransaction

		FROM  tbSCHEMABond
		WHERE tsLastUpdate = '.prepareSQL($tsNow).'
		AND   iTransaction = '.prepareSQL($iTransaction).'; ';


//	echo "<pre>";
//	print_r($query);
//	echo "<hr>";
//	echo "</pre>";


		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;



		// log da tabela tbSCHEMATie
		$query = '
		INSERT INTO tbSCHEMA_LOGTie
		(
			logId,
			sAction,
			id,
			idGLOBALNamespace,
			sKey,
			sComment,
			uniqueUserId,
			tsCreation,
			tsLastUpdate,
			iTransaction

		) SELECT

			NULL,
			sAction,
			id,
			idGLOBALNamespace,
			sKey,
			sComment,
			uniqueUserId,
			tsCreation,
			tsLastUpdate,
			iTransaction

		FROM  tbSCHEMATie
		WHERE tsLastUpdate = '.prepareSQL($tsNow).'
		AND   iTransaction = '.prepareSQL($iTransaction).'; ';


//	echo "<pre>";
//	print_r($query);
//	echo "<hr>";
//	echo "</pre>";


		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;



		// log da tabela tbSCHEMAProperty
		$query = '
		INSERT INTO tbSCHEMA_LOGProperty
		(
			logId,
			sAction,
			id,
			idGLOBALNamespace,
			idSCHEMAObject,
			sKey,
			sDataType,
			fMultiple,
			sComment,
			uniqueUserId,
			tsCreation,
			tsLastUpdate,
			iTransaction

		) SELECT

			NULL,
			sAction,
			id,
			idGLOBALNamespace,
			idSCHEMAObject,
			sKey,
			sDataType,
			fMultiple,
			sComment,
			uniqueUserId,
			tsCreation,
			tsLastUpdate,
			iTransaction

		FROM  tbSCHEMAProperty
		WHERE tsLastUpdate = '.prepareSQL($tsNow).'
		AND   iTransaction = '.prepareSQL($iTransaction).'; ';


//	echo "<pre>";
//	print_r($query);
//	echo "<hr>";
//	echo "</pre>";


		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;


		// E a partir de agora, faz a exclusão dos registros que foram marcados com sAction='D'
		// Nas tabelas tbDATA* e tbSCHEMA* ( Não nas tabelas tb*_LOG* )

		$query = 'DELETE FROM tbDATAObject WHERE sAction="D"; ';
		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;

		$query = 'DELETE FROM tbDATAProperty WHERE sAction="D"; ';
		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;

		$query = 'DELETE FROM tbDATABond WHERE sAction="D"; ';
		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;

		$query = 'DELETE FROM tbDATATie WHERE sAction="D"; ';
		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;

		$query = 'DELETE FROM tbSCHEMAObject WHERE sAction="D"; ';
		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;

		$query = 'DELETE FROM tbSCHEMAProperty WHERE sAction="D"; ';
		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;

		$query = 'DELETE FROM tbSCHEMABond WHERE sAction="D"; ';
		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;

		$query = 'DELETE FROM tbSCHEMATie WHERE sAction="D"; ';
		$result = $this->db->query(utf8_decode($query));
		if (!$result) return false;

		return true;

	}





	/**
	 * Chama uma stored procedure do banco de dados para recuperar o próximo número de transação disponível.
	 * Todas as operações executadas dentro desta transação terão o mesmo número e timestamp,
	 * possibilitando ao log reconstruir a linha do tempo de todas as alterações dos dados
	 * @return Array|Boolean Array com os índices 'iTransaction' e 'tsNow', ou false em caso de falha
	 */
	public function transaction() {

		$iTransaction = NULL;
		$tsNow = NULL;

		$query = 'CALL spNewTransaction();';

		$success_query = $this->db->multi_query(utf8_decode($query));
		if (!$success_query) return false;

		$result = $this->db->use_result();

		while($row = $result->fetch_object()) {
			$iTransaction = isset($row->iTransaction) ? toUTF8($row->iTransaction) : NULL;
			$tsNow        = isset($row->tsNow)        ? toUTF8($row->tsNow)        : NULL;
		}

		// para execução de stored procedures, é necessário limpar os resultados da seguinte maneira:
		$result->free();
		while ($this->db->next_result()) {
			$result = $this->db->use_result();
			if ($result instanceof mysqli_result) $result->free();
		}

		return array('iTransaction'=>$iTransaction, 'tsNow'=>$tsNow);
	}



}

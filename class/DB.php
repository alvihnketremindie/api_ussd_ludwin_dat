<?php

class DB {

    public $dbParams;
    private $connexion;
    private $query_id = 0;
    private $affected_rows = 0;

    /**
     * Constructor
     */
    function __construct($dbParams) {
        $this->dbParams = $dbParams;
        $this->connexion = $this->getDBConnection();
    }

    function db_connexion() {
        $j = 0;
        do {
            $dbAccess = @mysqli_connect($this->dbParams['host'], $this->dbParams['user'], $this->dbParams['password'], $this->dbParams['database']);
            $j++;
        } while (mysqli_connect_errno() && $j <= 5);

        if (mysqli_connect_errno()) {
            $this->db_connect_error();
        } else {
            /* Modification du jeu de résultats en utf8 */
            $charset = @mysqli_set_charset($dbAccess, "utf8");
            if (!$charset) {
                throw new Exception($this->db_error("Erreur lors du chargement du jeu de caractères utf8 :"));
            }
        }
        return $dbAccess;
    }

    private function getDBConnection() {
        $dbAccess = $this->db_connexion();
        return $dbAccess;
    }

    public function test_connexion() {
        return mysqli_ping($this->connexion);
    }

    public function db_close() {
        $dbAccess = $this->connexion;
        if ($dbAccess) {
            mysqli_close($dbAccess);
        }
    }

    public function db_reconnect() {
        $this->db_close();
        $this->connexion = $this->getDBConnection();
        $syntaxe = mysqli_connect_error();
        $code = mysqli_connect_errno();
        $requete = "Tentative de Reconnexion a la BDD avec les parametres : " . $this->dbParams['host'] . ", " . $this->dbParams['user'] . ", " . $this->dbParams['password'] . ", " . $this->dbParams['database'];
        $this->db_log("bdd_reoonnect", $code, $syntaxe, $requete);
    }

    public function db_get_affected_rows() {
        return $this->affected_rows;
    }

    private function db_escape_string($string) {
        $dbAccess = $this->connexion;
        if (get_magic_quotes_runtime()) {
            $string = stripslashes($string);
        }
        if (!preg_match("/^adddate\\(/i", $string)) {
            return @mysqli_real_escape_string($dbAccess, $string);
        }
        return $string;
    }

    protected function db_connect_error() {
        $dbAccess['errorMessage'] = mysqli_connect_error();
        $dbAccess['errorCode'] = mysqli_connect_errno();
        $dbAccess['errorPush'] = "Tentative de connexion a la BDD avec les parametres : " . $this->dbParams['host'] . ", " . $this->dbParams['user'] . ", " . $this->dbParams['password'] . ", " . $this->dbParams['database'];
        $this->db_log("connexion_bdd", $dbAccess['errorCode'], $dbAccess['errorMessage'], $dbAccess['errorPush']);
    }

    protected function db_error($sqlQuery) {
        $dbAccess = $this->connexion;
        $code = mysqli_errno($dbAccess);
        $syntaxe = mysqli_error($dbAccess);
        $requete = $sqlQuery;
        $this->db_log("bdd_requete", $code, $syntaxe, $requete);
    }

    protected function db_log($nom_fichier_log, $code, $syntaxe, $requete) {
        $action = "erreur";
        $info['name'] = $nom_fichier_log;
        $info['code'] = $code;
        $info['syntaxe'] = $syntaxe;
        $info['requete'] = $requete . ";";
        logger($action, $info);
    }

    /** reqLog Log la requte SQL transmise
     * @param string $rows le nombre de ligne affectées par la requete
     * @param string $requete contenu de la requête
     */
    protected function reqLog($rows, $requete) {
        $action = "requetesql";
        $info['affected_rows'] = $rows;
        $info['requete'] = $requete;
        logger($action, $info);
    }

    public function db_on_duplicate_key($table, $data) {
        $i = 0;
        $complete = "";
        foreach ($data as $field => $value) {
            $complete .= ($i > 0 ? ', ' : '') . $field . '=' . "'" . $this->db_escape_string($value) . "'";
            $i++;
        }
        $sqlQuery = "INSERT INTO $table SET " . $complete . " ON DUPLICATE KEY UPDATE " . $complete;
        $result = $this->db_query($sqlQuery);
        if ($result) {
            return @mysqli_insert_id($this->connexion);
        } else {
            return -1;
        }
    }

    public function db_insert($inserTable, $insertData) {
        $sqlQuery = "INSERT INTO $inserTable SET ";
        $sqlQuery .= $this->buildAttributes($insertData);
        $result = $this->db_query($sqlQuery);
        if ($result) {
            return @mysqli_insert_id($this->connexion);
        } else {
            return -1;
        }
    }

    public function db_insert_ignore($inserTable, $insertData) {
        $sqlQuery = "INSERT IGNORE INTO $inserTable SET ";
        $sqlQuery .= $this->buildAttributes($insertData);
        $result = $this->db_query($sqlQuery);
        if ($result) {
            return mysqli_insert_id($this->connexion);
        } else {
            return -1;
        }
    }

    public function db_update($updateTable, $updateData, $clauseWhere = '1') {
        $sqlQuery = "UPDATE `" . $updateTable . "` SET ";
        foreach ($updateData as $key => $value) {
            if (strtolower($value) == 'null') {
                $sqlQuery.= "`$key` = NULL";
            } elseif (strtolower($value) == 'now()') {
                $sqlQuery.= "`$key` = NOW(), ";
            } elseif (preg_match("/^increment\((\-?\d+)\)$/i", $value, $m)) {
                $sqlQuery.= "`$key` = `$key` + $m[1], ";
            } elseif (preg_match("/^decrement\((\-?\d+)\)$/i", $value, $m)) {
                $sqlQuery.= "`$key` = `$key` - $m[1], ";
            } elseif (preg_match("/^adddate\\(/i", $value)) {
                $sqlQuery.= "`$key` = " . $value . ", ";
            } else {
                $sqlQuery.= "`$key`='" . $this->db_escape_string($value) . "', ";
            }
        }
        $sqlQuery = rtrim($sqlQuery, ', ') . ' WHERE ' . $clauseWhere . ';';
        return $this->db_query($sqlQuery);
    }

    protected function buildAttributes($attributes) {
        $keys = array_keys($attributes);
        $sqlQuery = '';
        $nb_keys = count($keys);
        for ($index = 0; $index < $nb_keys; $index++) {
            $key = $keys[$index];
            $value = $this->db_escape_string($attributes[$key]);
            if (strtolower($value) == 'null') {
                $value = "NULL";
            } elseif (strtolower($value) == 'now()') {
                $value = @date("Y-m-d H:i:s");
            }
            if (preg_match("/^adddate\\(/i", $value)) {
                $sqlQuery.= "`$key` = $value";
            } else {
                $sqlQuery .= "$key='$value'";
            }
            //We need to add a comma if not our last param
            if ($index !== count($keys) - 1) {
                $sqlQuery .= ', ';
            }
        }
        return($sqlQuery);
    }

    public function db_query($sqlQuery) {
        if (!$this->test_connexion()) {
            $this->db_reconnect();
        }
        $dbAccess = $this->connexion;
        $this->query_id = mysqli_query($dbAccess, $sqlQuery);
        if (!$this->query_id) {
            $this->db_error($sqlQuery);
            return -1;
        } else {
            $this->affected_rows = @mysqli_affected_rows($dbAccess);
            $this->reqLog($this->affected_rows, $sqlQuery . ";");
            return $this->query_id;
        }
    }

    private function db_free_result($query_id = -1) {
        if ($query_id !== -1) {
            $this->query_id = $query_id;
        }
        if ($this->query_id !== 0 && !mysqli_free_result($this->query_id)) {
            $this->db_error("Probleme dans la Liberation de resultat");
        }
    }

    public function db_data_seek($query_id, $newid) {
        if ($query_id !== -1) {
            $this->query_id = $query_id;
        }
        if (isset($this->query_id) and isset($newid)) {
            $record = mysqli_data_seek($this->query_id, $newid);
        } else {
            $this->db_error("MYSQL DATA SEEK ");
            $record = null;
        }

        return $record;
    }

    public function db_fetch_array($query_id = -1) {
        if ($query_id !== -1) {
            $this->query_id = $query_id;
        }
        if (isset($this->query_id)) {
            $record = mysqli_fetch_array($this->query_id);
        } else {
            $this->db_error("MYSQL FETCH ARRAY ");
            $record = null;
        }

        return $record;
    }

    public function db_fetch_assoc($query_id = -1) {
        if ($query_id !== -1) {
            $this->query_id = $query_id;
        }
        if (isset($this->query_id)) {
            $record = mysqli_fetch_assoc($this->query_id);
        } else {
            $this->db_error("MYSQL FETCH ASSOC ");
            $record = null;
        }
        return $record;
    }

    public function db_fetch_all($sql) {
        $query_id = $this->db_query($sql);
        $out = array();
        while ($row = $this->db_fetch_array($query_id)) {
            $out[] = $row;
        }
        return $out;
    }

    public function db_fetch_all_assoc($sql) {
        $query_id = $this->db_query($sql);
        $out = array();
        while ($row = $this->db_fetch_assoc($query_id)) {
            $out[] = $row;
        }
        return $out;
    }

    public function db_num_rows($query_id = -1) {
        if ($query_id !== -1) {
            $this->query_id = $query_id;
        }
        if (isset($this->query_id)) {
            $row = @mysqli_num_rows($this->query_id);
        } else {
            $this->db_error("Probleme dans le compte des numeros de lignes");
            $row = 0;
        }
        return $row;
    }

    protected function parse_params($params) {
        $return = '';
        if (array_key_exists('where', $params)) {
            $return.= ' WHERE ' . $params['where'];
        }
        if (array_key_exists('order', $params)) {
            $return .= ' ORDER BY ' . $params['order'];
        }
        if (array_key_exists('orderdesc', $params)) {
            $return .= ' ORDER BY ' . $params['orderdesc'] . ' DESC';
        }
        if (array_key_exists('group', $params)) {
            $return .= ' GROUP BY ' . $params['group'];
        }
        if (array_key_exists('limit', $params)) {
            $return .= ' LIMIT ' . $params['limit'];
        }
        return $return;
    }

    public function db_find_record($find, $findTable, $findParams = array(), $all = false, $read = NULL) {
        if ($findTable == null) {
            $sqlQuery = "SELECT $find";
        } else {
            $sqlQuery = "SELECT $find FROM $findTable";
            $sqlQuery.= $this->parse_params($findParams);
        }
        $result = $this->db_query($sqlQuery);
        if (isset($read) && $read == 1) {
            return $result;
        } else {
            $out = null;
            if ($all) {
                while ($row = $this->db_fetch_array($result)) {
                    $out[] = $row;
                }
                return $out;
            } else {
                return $this->db_fetch_array($result);
            }
        }
    }

    public function db_find_record_assoc($find, $findTable, $findParams = array(), $all = false, $read = NULL) {
        if ($findTable == null) {
            $sqlQuery = "SELECT $find";
        } else {
            $sqlQuery = "SELECT $find FROM $findTable";
            $sqlQuery.= $this->parse_params($findParams);
        }
        $result = $this->db_query($sqlQuery);
        if (isset($read) && $read == 1) {
            return $result;
        } else {
            $out = null;
            if ($all) {
                while ($row = $this->db_fetch_assoc($result)) {
                    $out[] = $row;
                }
                return $out;
            } else {
                return $this->db_fetch_assoc($result);
            }
        }
    }

}

?>

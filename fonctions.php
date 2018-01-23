<?php

function __autoload($classes) {
    $explodeClasses = explode('|', $classes);
    for ($i = 0, $explodeClassesMax = count($explodeClasses); $i < $explodeClassesMax; $i++) {
        include_once(CLASSES . $explodeClasses[$i] . '.php');
    }
}

function db_connect() {
    $db_params = array('host' => DB_HOST, 'user' => DB_USER, 'password' => DB_PASS, 'database' => DB_NAME);
    $db = new DB($db_params);
    return $db;
}

function debug($input) {
    // $affiche = true;
    $affiche = false;
    if ($affiche) {
        print_r($input) . PHP_EOL;
    }
}

function logger($type, $message, $debug = false) {
    $contents = "date_log=" . @date("Y-m-d H:i:s") . " __[$type]__ | " . parse_reponse($message) . PHP_EOL;
    $debug = true;
    #if ($debug)    print $contents;
    file_put_contents(LOG_PATH.@date('Ymd').'.log', $contents, FILE_APPEND);
    file_put_contents(LOG_PATH.@date('Ymd')."_".$type.'.log', $contents, FILE_APPEND);
}

function parse_reponse($array) {
    $toReturn = '';
    if (is_array($array)) {
        $count = count($array);
        $i = 1;
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $toReturn .= ' [ ' . $key . ' => ' . parse_reponse($value) . ' ] ';
            } else {
                $toReturn .= $key . '=' . nettoyerChaine($value);
                if ($i < $count) {
                    $toReturn .= ' | ';
                }
            }
            $i++;
        }
    } else {
        $toReturn .= nettoyerChaine($array);
    }
    return $toReturn;
}

function nettoyerChaine($string) {
    $dict = array("\r" => '', "\t" => ' ', '{CR}' => "\n", "\n\n" => "\n");
    $string = str_ireplace(array_keys($dict), array_values($dict), $string);
    $string = str_ireplace("\n", " ", $string);
    return $string;
}

function getdescription($xmlelement, $lang = "FR") {
    $description = null;
    foreach ($xmlelement->Language as $Language) {
        if ($Language->LanguageCode == $lang) {
            $description = (string) $Language->Description;
        }
    }
    if (empty($description)) {
        $description = getdescriptionFR($xmlelement);
    }
    return $description;
}

function getdescriptionEN($xmlelement) {
    foreach ($xmlelement->Language as $Language) {
        if ($Language->LanguageCode == "EN") {
            return (string) $Language->Description;
        }
    }
}

function getdescriptionFR($xmlelement) {
    $description = null;
    foreach ($xmlelement->Language as $Language) {
        if ($Language->LanguageCode == "FR") {
            $description = (string) $Language->Description;
        }
    }
    if (empty($description)) {
        $description = getdescriptionEN($xmlelement);
    }
    return $description;
}

function insertAction($action, $params) {
    $db = db_connect();
    if ($db->test_connexion()) {
        $id = $db->db_insert("actions", array("action" => $action, "params" => $params, "date" => "NOW()"));
        $db->db_close();
        return $id;
    }
    return 0;
}

function getTransactionId($prefix = "") {
    return hexdec(md5(uniqid($prefix, TRUE)));
}

function json_clean_decode($json, $assoc = false, $depth = 512, $options = 0) {
    // search and remove comments like /* */ and // 
    $json = preg_replace("#(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|([\s\t](//).*)#", '', $json);
    if (version_compare(phpversion(), '5.4.0', '>=')) {
        $json = json_decode($json, $assoc, $depth, $options);
    } elseif (version_compare(phpversion(), '5.3.0', '>=')) {
        $json = json_decode($json, $assoc, $depth);
    } else {
        $json = json_decode($json, $assoc);
    }
    return $json;
}

function verifusefull($table, $infos, $keyverif) {
    $db = db_connect();
    if ($db->test_connexion()) {
        $keys = explode("|", $keyverif);
        $where = '';
        foreach ($keys as $key) {
            $where .= $key . " = " . $infos[$key] . " AND ";
        }
        $findParams = array("where" => $where . "active = 'YES'", "limit" => "1");
        $record = $db->db_find_record_assoc("*", $table, $findParams, true);
        $db->db_close();
        if (isset($record) and ! empty($record)) {
            return true;
        } else {
            return false;
        }
    }
}

function SaveAndMoveFile($contents, $nom, $courant, $archive) {
    if ($contents) {
        $nomfichier = $courant . $nom;
        if (file_exists($nomfichier)) {
            $commande_copy = "cp " . $nomfichier . " $archive" . @date("YmdHis") . $nom;
            logger(__FUNCTION__, $commande_copy);
            exec($commande_copy);
        }
        file_put_contents($nomfichier, $contents);
    }
}

function checkTicket($url, $idticket) {
    $service = new Service($url);
    $ticketelements = array(
        "ticket" => $idticket,
        "gameid" => "0",
        "len" => "FR"
    );
    $response = $service->executeUrl($ticketelements);
    $xmlinfo = new SimpleXMLElement($response);
    $record['statut_ticket'] = (string) $xmlinfo->stato_biglietto;
    $record['winning_ticket'] = (string) $xmlinfo->importo_vincita_biglietto;
    $record['CodConc'] = (string) $xmlinfo->id_cn;
    $record['CodDiritto'] = (string) $xmlinfo->id_pvend;
    $record['IdTerminal'] = (string) $xmlinfo->nr_terminale;
    $record['corps_ticket'] = $response;
    return $record;
}

function getAliasInfos($alias) {
    global $iniRepartition;
    $data = @$iniRepartition[$alias];
    if (isset($data) and ! empty($data) and strtoupper($data["statut"]) == "YES") {
        return $data;
    } else {
        return null;
    }
}

function get_Values($idmatch, $idpari, $type, $system_code) {
    switch ($type) {
        case "equipe1":
            $nomChoix = "1";
            break;
        case "equipe2":
            $nomChoix = "2";
            break;
        case "nul":
            $nomChoix = "N";
            break;
        case "autre":
            $nomChoix = "AUTRES";
            break;
        default :
            $nomChoix = $type;
            break;
    }
    $AllPariInfos = getAllPariInfos($idmatch, $idpari, $nomChoix, $system_code);
#	print_r($AllPariInfo);
    $values["CodPal"] = $AllPariInfos["idPal"];
    $values["CodEvent"] = $AllPariInfos["idEvenement"];
    $values["CodBet"] = $AllPariInfos["idPari"];
    $values["CodDraw"] = $AllPariInfos["idChoix"];
    $values["Odd"] = $AllPariInfos["cote"];

    $params["idEvenement"] = $idmatch;
    $params["idPari"] = $idpari;
    $params["nomChoix"] = $nomChoix;
    $params["system_code"] = $system_code;

    logger(__FUNCTION__, array("params" => $params, "values" => $values));

    return $values;
}

function getAllPariInfos($idEvenement, $idPari, $nomChoix, $system_code,$live = 'false') {
    $record = array();
    $db = db_connect();
    if ($db->test_connexion()) {
        $findParams = array("where" => "idBetradar = '$idEvenement' AND idPari = '$idPari' AND nomChoix = '$nomChoix' AND system_code = '$system_code' AND livePari = '$live'", "limit" => "1");
        $record = $db->db_find_record_assoc("*", "allpari", $findParams);
        $db->db_close();
    }
    return $record;
}

function getMatches($filtre = "") {
    $record = array();
    $db = db_connect();
    if ($db->test_connexion()) {
        $findParams = array("where" => "statutEvenement = 'open' AND statutPari = 'open' AND statutChoix = 'open' AND nomEvenement != '' $filtre", "group" => "idBetradar");
        $record = $db->db_find_record_assoc("idBetradar,idEvenement,nomEvenement,dateEvenement,idSport,nomSport,idCompetition,nomCompetition", "allpari", $findParams, true);
        $db->db_close();
    }
    return $record;
}

function getRepartitions($group = null) {
    $record = array();
    $db = db_connect();
    if ($db->test_connexion()) {
        if ($group) {
            $findParams = array("where" => "active = 'YES'", "group" => $group);
        } else {
            $findParams = array("where" => "active = 'YES'");
        }
        $record = $db->db_find_record_assoc("*", "repartition", $findParams, true);
        $db->db_close();
    }
    return $record;
}

function curlMultiRequest($urls) {
    $mh = curl_multi_init();
    foreach ($urls as $i => $url) {
        $ch[$i] = curl_init($url);
        curl_setopt($ch[$i], CURLOPT_RETURNTRANSFER, 1);
        curl_multi_add_handle($mh, $ch[$i]);
    }

    // Start performing the request
    do {
        $execReturnValue = curl_multi_exec($mh, $runningHandles);
    } while ($execReturnValue == CURLM_CALL_MULTI_PERFORM);
    // Loop and continue processing the request
    while ($runningHandles && $execReturnValue == CURLM_OK) {
        // Wait forever for network
        $numberReady = curl_multi_select($mh);
        if ($numberReady != -1) {
            // Pull in any new data, or at least handle timeouts
            do {
                $execReturnValue = curl_multi_exec($mh, $runningHandles);
            } while ($execReturnValue == CURLM_CALL_MULTI_PERFORM);
        }
    }
    // Check for any errors
    if ($execReturnValue != CURLM_OK) {
        trigger_error("Curl multi read error $execReturnValue\n", E_USER_WARNING);
    }
    // Extract the content
    foreach ($urls as $i => $url) {
        // Check for errors
        $curlError = curl_error($ch[$i]);
        if ($curlError == "") {
            $res[$i] = curl_multi_getcontent($ch[$i]);
        } else {
            print "Curl error on handle $i: $curlError\n";
        }
        // Remove and close the handle
        curl_multi_remove_handle($mh, $ch[$i]);
        curl_close($ch[$i]);
    }
    // Clean up the curl_multi handle
    curl_multi_close($mh);
    // Print the response data
    debug($res);
}

function get_service($code) {
    $parseIniFile = parse_ini_file(INI_FILE, true);
    $serviceConfig = $parseIniFile[$code];
    return $serviceConfig;
}

function format_date($date_in) {
    $date_in = trim($date_in);
    list($datei, $heure) = explode(" ", $date_in);
    // list($heure, $autre) = explode(" ", $heurei);
    $annee = substr($datei, 0, 4);
    $mois = substr($datei, 4, 2);
    $jour = substr($datei, 6, 2);
    $date = implode("-", array($annee, $mois, $jour));
    $date_out = $date . " " . $heure;
    return $date_out;
}

?>

<?php

include(dirname(dirname(__FILE__)) . '/global.php');
$msisdn = @$_REQUEST['msisdn'];
$lastName = @$_REQUEST['nom'];
$name = @$_REQUEST['prenom'];
$annee = @$_REQUEST['annee'];
$alias = @$_REQUEST['alias'];
$result = array();
if (!$msisdn) {
    $to_print = "Pas de MSISDN fourni.";
} elseif (!$name) {
    $to_print = "Pas de prenom fourni.";
} elseif (!$lastName) {
    $to_print = "Pas de nom de famille fourni.";
} elseif (!$annee) {
    $to_print = "Pas d'annee de naissance fourni.";
} elseif (!$alias) {
    $to_print = "L'alias est absent.";
} else {
    $aliasInfos = getAliasInfos($alias);
    if ($aliasInfos) {
        try {
            $secretkey = $general["infos"]["secretkey"];
            $wsdl_file = $general["urls"]["inscriptions"];
            $options = array(
                'uri' => 'http://schemas.xmlsoap.org/soap/envelope/',
                'style' => SOAP_RPC,
                'use' => SOAP_ENCODED,
                'soap_version' => SOAP_1_1,
                'cache_wsdl' => WSDL_CACHE_NONE,
                'connection_timeout' => 15,
                'trace' => true,
                'encoding' => 'UTF-8',
                'exceptions' => true,
            );
            $client = new SoapClient($wsdl_file, $options);
            $userAccount = 'USSD' . $aliasInfos["indicatif"] . substr($msisdn, -$aliasInfos["significant"]);
            $age = @date('Y') - $annee;
            $sex = "-";
            $skinCode = $aliasInfos["system_code"];
            $transactionId = $aliasInfos["indicatif"] . @date('YmdHis') . insertAction("inscription", $userAccount);
            $signedData = base64_encode($userAccount . $transactionId . $age . $name . $lastName . $sex . $skinCode . $secretkey);
            $signature = md5($signedData);
            $params = array(
                'userAccount' => $userAccount,
                'transactionId' => $transactionId,
                'dateOfBirth' => $age,
                'name' => $name,
                'lastName' => $lastName,
                'sex' => $sex,
                'skinCode' => $skinCode,
                'signedData' => $signedData,
                'signature' => $signature
            );
            $return_vale = $client->registration($params);
            $to_print = json_encode($return_vale);
            $result = json_clean_decode($to_print, true);
            $resultCode = (string) $result["resultCode"];
            $db = db_connect();
            if ($db->test_connexion() && $resultCode === "0") {
                $inscriptions = array(
                    "date" => @date("Y-m-d H:i:s"),
                    "alias" => $alias,
                    "userAccount" => $userAccount,
                    "password" => (string) $result["password"],
                    "lastName" => $lastName,
                    "name" => $name,
                    "age" => $age,
                    "signedData" => $signedData,
                    "signature" => $signature,
                    "resultCode" => $resultCode,
                    "transactionId" => $transactionId
                );
                $db->db_on_duplicate_key("inscriptions", $inscriptions);
                $db->db_close();
            }
            logger("inscription", array("RequestJSON" => json_encode($params), "ResponseJSON" => $to_print, "RequestXML" => $client->__getLastRequest(), "ResponseXML" => $client->__getLastResponse()));
        } catch (SoapFault $fault) {
            $to_print = json_encode($fault);
            logger("inscrption_soapfault", array("faultcode" => $fault->faultcode, "faultstring" => $fault->faultstring));
        }
    } else {
        $to_print = "ERROR : La configuration de l'alias " . $alias . " n'est pas encore defini";
    }
}
echo $to_print;
logger("inscription_process", array("Output" => $to_print, "input" => $_REQUEST));
?>

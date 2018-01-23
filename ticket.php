<?php
$infos = strtolower(@$argv[1]);
include(dirname(__FILE__) . '/global.php');
switch ($infos) {
    case "paie":
        $url = $general["urls"]["baseurl"] . "api/payTicket.php";
        $statut = "statut  = '" . Elements::TICKET_PAYABLE . "'";
        $table = "paiement";
        break;
    case "check":
        $url = $general["urls"]["baseurl"] . "api/checkTicket.php";
        $statut = "statut not in ('" . Elements::TICKET_PAYABLE . "','" . Elements::TICKET_PAYE . "','" . Elements::TICKET_PERDANT . "')";
        $table = "checking";
        break;
    default :
        exit("Le name $infos n'est pas dans la liste des possibilites");
        break;
}
$db = db_connect();
if ($db->test_connexion()) {
    foreach ($iniRepartition as $alias => $repartition) {
        if (strtoupper($repartition["statut"]) === "YES") {
            $array = array(
                "table" => $table,
                "alias" => $alias,
                "statut" => $statut,
                "url" => $url
            );
            $checkStatutPayable = new Elements($array);
            $checkStatutPayable->checkElements();
        }
    }
}
?>


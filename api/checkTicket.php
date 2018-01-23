<?php

include(dirname(dirname(__FILE__)) . '/global.php');
$idticket = isset($_REQUEST['idticket']) ? $_REQUEST['idticket'] : '';
if ($idticket == '') {
    die('ERROR, idticket');
}
$db = db_connect();
if (!$db->test_connexion()) {
    die('ERROR,DB_CONNECT_FAILED');
}
$url = $general["urls"]["statut_ticket"];
$params = checkTicket($url, $idticket);
list($statut_ticket, $winning_ticket, $CodConc, $CodDiritto, $IdTerminal) = $params;
$updateData = array("statut" => $statut_ticket, "gainEffectif" => $winning_ticket, "date_change" => @date("Y-m-d H:i:s"));
$db->db_update("tickets", $updateData, "idticket = '$idticket'");
echo json_encode($updateData);
?>

<?php

include(dirname(dirname(__FILE__)) . '/global.php');
$idticket = isset($_REQUEST['idticket']) ? $_REQUEST['idticket'] : '';
if ($idticket == '') {
    die('ERROR, idticket');
}
$aliasInfos = getAliasInfos($alias);
if (!$aliasInfos) {
    die("ERROR : La configuration de l'alias " . $alias . " n'est pas encore defini");
}
$db = db_connect();
if (!$db->test_connexion()) {
    die('ERROR,DB_CONNECT_FAILED');
}
$url = $general["urls"]["statut_ticket"];
$params = checkTicket($url, $idticket);
list($statut_ticket, $winning_ticket, $CodConc, $CodDiritto, $IdTerminal) = $params;
$transactionId = $aliasInfos["indicatif"] . @date('YmdHis') . insertAction("payement", $idticket);
if ($statut_ticket != Elements::TICKET_PAYABLE) {
    $db->db_close();
    die("Ticket $idticket Impayable");
} else {
    $xml_doc = new DOMDocument('1.0', 'utf-8');
    $service = $xml_doc->createElement('ServicesPSQF');
    $xml_doc->appendChild($service);
    #Balise Service
    $request = $xml_doc->createElement('PaymentRequest');
    $service->appendChild($request);
    #Balise Request
    $request->appendChild($xml_doc->createElement('CodConc', $CodConc));
    $request->appendChild($xml_doc->createElement('CodDiritto', $CodDiritto));
    $request->appendChild($xml_doc->createElement('IdTerminal', $IdTerminal));
    $request->appendChild($xml_doc->createElement('TransactionID', $transactionId));
    $request->appendChild($xml_doc->createElement("TicketSogei", $idticket));
    $postdata = $xml_doc->saveXML();
    //debug($postdata);
    if ($postdata) {
        $service = new Service($general["urls"]["paiement_ticket"]);
        $xmlstr = $service->postXmlData($postdata);
        //debug($xmlstr);
        if ($xmlstr) {
            $xmlinfo = new SimpleXMLElement($xmlstr);
            $Code = $xmlinfo->PaymentResponse->ReturnCode->Code;
            if ($Code == "0" or $Code == "1024") {
                $db->db_update("tickets", array("statut" => Elements::TICKET_PAYE, "date_paiement" => @date("Y-m-d H:i:s")), "idticket = '$idticket'");
            }
        }
    }
    $db->db_close();
}
$params2 = checkTicket($url, $idticket);
$ticketparams["idticket"] = $idticket;
$ticketparams["statut"] = $params2['statut_ticket'];
$ticketparams["gain"] = $params2['winning_ticket'];
$ticketparams["code"] = @$Code;
echo json_encode($ticketparams);
?>


<?php

include(dirname(__FILE__) . '/global.php');
$liste_choice = strtolower(@$argv[1]);
$arrayXml["Language"] = 'FR';
$transactionId = hexdec($arrayXml["Language"]) . @date('YmdHis') . insertAction("getlistes", json_encode($arrayXml) . "_" . $liste_choice);
$arrayXml["TransactionID"] = $transactionId;
switch ($liste_choice) {
    case "sport":
        $object = new Sports($arrayXml);
        $filename = "list_sports.xml";
	$url = $general["urls"]["sport"];
        break;
    case "competition":
        $object = new Tournaments($arrayXml);
        $filename = "list_tournaments.xml";
        $url = $general["urls"]["tournament"];
        break;
    case "pari":
        $object = new Bets($arrayXml);
        $filename = "list_bets.xml";
        $url = $general["urls"]["bet"];
        break;
    default :
        exit("Le name $liste_choice n'est pas dans la liste des possibilites");
        break;
}
$postdata = $object->createXML();
if ($postdata) {
    $service = new Service($url);
    $xmlstr = $service->postXmlData($postdata);
    if ($xmlstr) {
        $object->parseXML($xmlstr);
        SaveAndMoveFile($xmlstr, $filename, XML_CURRENT_FILE_DIR, XML_ARCHIVE_FILE_DIR);
    }
}
?>

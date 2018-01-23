<?php

include_once('../global.php');
$bet["alias"] = "ludwinairtelrdc";
#$bet["msisdn"] = '243009356644';
$bet["msisdn"] = 'USSD00243000114614';
$bet["mise"] = "1000";

$parielements = array(
    "idmatch" => "299",
    "id_genre" => "14",
    "cote" => "289",
    "type" => "equipe2"
);
$bet["pari"][] = $parielements;
/*//
$parielements = array(
    "idmatch" => "295",
    "id_genre" => "3",
    "cote" => "122",
    "type" => "nul"
);
$bet["pari"][] = $parielements;
//*/
/*
$parielements = array(
    "idmatch" => "299",
    "id_genre" => "3",
    "cote" => "246",
    "type" => "equipe2"
);
$bet["pari"][] = $parielements;
//  */
/*//
$parielements = array(
    "idmatch" => "308",
    "id_genre" => "7",
    "cote" => "1291",
    "type" => "1-2"
);
$bet["pari"][] = $parielements;
//*/
/*
$parielements = array(
    "idmatch" => "309",
    "id_genre" => "7",
    "cote" => "1801",
    "type" => "autre"
);
$bet["pari"][] = $parielements;
//*/
#$ticket = json_encode($bet);
#$ticket = '{"alias":"ludwinairtelrdc","msisdn":"00243999334655","mise":"5","pari":[{"id_match":"918","id_genre":"14","cote":330,"type":"equipe1"}]}';
$ticket = '{"alias":"ludwinairtelrdc","msisdn":"00243998444073","mise":"100","pari":[{"id_match":"11191031","id_genre":"14","cote":165,"type":"equipe1"}]}';
echo $ticket . PHP_EOL;
$url = $general["urls"]["baseurl"] . "api/creerTicket.php";
$service = new Service($url);
$service->setDebug(TRUE);
$response = $service->executeUrl(array("ticket" => $ticket));
echo PHP_EOL."----------------------------".PHP_EOL;
echo $response . PHP_EOL;
?>

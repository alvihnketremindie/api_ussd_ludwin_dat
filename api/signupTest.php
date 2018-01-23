<?php

include_once('../global.php');
$inscrit["alias"] = "ludwinairtelrdc";
$inscrit["msisdn"] = '00243000'.@date("His");
$inscrit["nom"] = "DIGITAL";
$inscrit["prenom"] = "AfriqueTest";
$inscrit["annee"] = "1980";

$url = $general["urls"]["baseurl"] . "api/inscription.php";
$service = new Service($url);
$service->setDebug(TRUE);
$response = $service->executeUrl($inscrit);
echo $response . PHP_EOL;
?>


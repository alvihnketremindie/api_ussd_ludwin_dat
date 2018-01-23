<?php

$name_choice = strtolower(@$argv[1]);
switch ($name_choice) {
    case "prematch_full":
        $type = "FULL";
        $isLive = "0";
        $methode_name = "db_insert_ignore";
        break;
    case "prematch_delta":
        $type = "DELTA";
        $isLive = "0";
        $methode_name = "db_on_duplicate_key";
        break;
    case "live_full":
        $type = "FULL";
        $isLive = "1";
        $methode_name = "db_insert_ignore";
        break;
    case "live_delta":
        $type = "DELTA";
        $isLive = "1";
        $methode_name = "db_on_duplicate_key";
        break;
    default :
        exit("Le name $name_choice n'est pas dans la liste des possibilites");
        break;
}
include(dirname(__FILE__) . '/global.php');
$url = $general["urls"]["event"];
$recup = array();
$service = new Service($url);
foreach ($iniRepartition as $repartition) {
    $statut = strtoupper(trim(@$repartition["statut"]));
    if ($statut === "YES" and ! in_array($repartition["system_code"], $recup)) {
        #print_r($repartition);
        $array = array("system_code" => $repartition["system_code"], "type" => $type, "isLive" => $isLive, "len" => $repartition["langue"]);
        #print_r($array);
        $xmlstr = $service->executeUrl($array);
        $prematch = new Premacht($xmlstr, $repartition["system_code"], $repartition["langue"]);
        SaveAndMoveFile($xmlstr, $name_choice . "-" . $repartition["system_code"] . "-" . $repartition["langue"] . ".xml", XML_CURRENT_FILE_DIR, XML_ARCHIVE_FILE_DIR);
        $prematch->parse_xml_str($methode_name);
        $recup[] = $repartition["system_code"];
    }
}
?>


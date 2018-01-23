<?php

$name_choice = strtolower(@$argv[1]);
switch ($name_choice) {
    case "sport":
        $methode_name = "parseSport";
        break;
    case "competition":
        $methode_name = "parseCompetition";
        break;
    case "pari":
        $methode_name = "parsePari";
        break;
    case "choix":
        $methode_name = "parseChoix";
        break;
    default :
        exit("Le name $name_choice n'est pas dans la liste des possibilites");
        break;
}
include(dirname(__FILE__) . '/global.php');
foreach (glob(XML_CURRENT_FILE_DIR . "prematch*.xml") as $xmlfile) {
    $xmlstr = @file_get_contents($xmlfile);
    $prematch = new XMLstatitc($xmlstr);
    $prematch->{$methode_name}();
	echo $methode_name." ---- ".$name_choice." ---- ".$xmlfile.PHP_EOL;
}
?>

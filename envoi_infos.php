<?php
$infos = strtolower(@$argv[1]);
$type = strtolower(@$argv[2]);
switch ($infos) {
    case "match":
        $key = "send_match";
        $methode_name = "sendMatch";
        $id = null;
        break;
    case "resultat_mi_temps":
        $key = "send_cote_1N2_45";
        $methode_name = "sendPariResultat";
        $id = 14;
        break;
    case "resultat_final":
        $key = "send_cote_1N2_90";
        $methode_name = "sendPariResultat";
        $id = 3;
        break;
    case "score_exact":
        $key = "send_cote_score_exact";
        $methode_name = "sendPariScore";
        $id = 7;
        break;
    default :
        exit("Le name $infos n'est pas dans la liste des actions");
        break;
}
switch ($type) {
    case "prematch":
        $filtre = " and livePari = 'false'";
        break;
    case "live":
        $filtre = " and livePari = 'true'";
        break;
    default :
        exit("Le name $type n'est pas dans la liste des types");
        break;
}
include(dirname(__FILE__) . '/global.php');
$maintenant = @date("Y-m-d H:i:s");
$filtre .= " and dateEvenement > '$maintenant'";
foreach ($iniRepartition as $repartition) {
    $statut = strtoupper(@$repartition["statut"]);
    if ($statut === "YES") {
        $repartition_competitions = @$repartition["competitions"];
        if (isset($repartition_competitions) and ! empty($repartition_competitions) and $repartition_competitions !== "all") {
            $competitions = explode("|", $repartition_competitions);
            $filtre .= " and idCompetition in ('" . implode("','", $competitions) . "')";
        }
        $matches = getMatches($filtre);
        foreach ($matches as $match) {
            $nom_match = @$match["nomEvenement"];
            if (isset($nom_match) and ! empty($nom_match)) {
                $matcheobject = new Matches($match, $repartition);
                if ($id) {
                    $matcheobject->{$methode_name}($repartition[$key], $id);
                } else {
                    $matcheobject->{$methode_name}($repartition[$key]);
                }
            }
        }
    }
}
?>

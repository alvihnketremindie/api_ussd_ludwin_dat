<?php

class Premacht {

    protected $xmlinfo;
    protected $lang;

    function __construct($xmlstr, $system_code, $langue) {
        $this->xmlinfo = new SimpleXMLElement($xmlstr);
        $this->infos["system_code"] = $system_code;
        $this->infos["langue"] = $langue;
    }

    public function parse_xml_str($methode_name) {
        $db = db_connect();
        if ($db->test_connexion()) {
            $infos = $this->infos;
            $lang = $infos['langue'];
#            $infos['active'] = 'YES';
            foreach ($this->xmlinfo->Mobile->GetEventsResponse->SportMobile as $Sport) {
                $infos["idSport"] = (string) $Sport->CodeSport;
                $util = verifusefull("list_sports", $infos, "idSport");
                if ($util) {
                    $infos["nomSport"] = getdescription($Sport->LanguageSport, $lang);
                    foreach ($Sport->TournamentMobile as $Competition) {
                        $infos["idCompetition"] = (string) $Competition->CodeTournament;
                        #$util = verifusefull("list_tournaments", $infos, "idSport|idCompetition");
                        $util = true;
                        if ($util) {
                            $infos["nomCompetition"] = getdescription($Competition->LanguageTournament, $lang);
                            foreach ($Competition->EventMobile as $Evenement) {
                                $infos["idPal"] = (string) $Evenement->CodePal;
                                $infos["idEvenement"] = (string) $Evenement->CodEvent;
                                $infos["idBetradar"] = (string) $Evenement->idBetradar;
                                $infos["dateEvenement"] = format_date((string) $Evenement->TimeStamp);
                                $infos["nomEvenement"] = getdescription($Evenement->LanguageEvent, $lang);
                                $infos["Op"] = (string) $Evenement->Op;
                                $infos["statutEvenement"] = (string) $Evenement->Status;
                                foreach ($Evenement->Market as $Pari) {
                                    $infos["idPari"] = (string) $Pari->CodeBet;
                                    $util = verifusefull("list_bets", $infos, "idPari");
                                    if ($util) {
                                        $infos["nomPari"] = getdescription($Pari->LanguageBet, $lang);
                                        $infos["Handicap"] = (string) $Pari->Handicap;
                                        $infos["LegAAMS"] = (string) $Pari->LegAAMS;
                                        $infos["LegMax"] = (string) $Pari->LegMax;
                                        $infos["LegMin"] = (string) $Pari->LegMin;
                                        $infos["livePari"] = (string) $Pari->Live;
                                        $infos["statutPari"] = (string) $Pari->Status;
                                        foreach ($Pari->MarketDraw as $Choix) {
                                            $infos["idChoix"] = (string) $Choix->CodeDraw;
                                            $infos["statutChoix"] = (string) $Choix->StatusDraw;
                                            $infos["nomChoix"] = getdescription($Choix->LanguageDraw, $lang);
                                            $infos["cote"] = (string) $Choix->Odd;
                                            $db->{$methode_name}("allpari", $infos);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $db->db_close();
        }
    }

}

?>

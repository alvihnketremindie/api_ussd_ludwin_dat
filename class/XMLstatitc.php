<?php

class XMLstatitc {

    function __construct($xmlstr) {
        $this->xmlinfo = new SimpleXMLElement($xmlstr);
    }

    public function insertInfos($table, $infos, $keyinterest) {
        $db = db_connect();
        if ($db->test_connexion()) {
            $keys = explode("|", $keyinterest);
            foreach ($keys as $key) {
                $insertData[$key] = $infos[$key];
            }
            $db->db_on_duplicate_key($table, $insertData);
        }
    }

    public function parseSport() {
        foreach ($this->xmlinfo->Mobile->GetEventsResponse->SportMobile as $Sport) {
            $infos["idSport"] = (string) $Sport->CodeSport;
            $infos["nomSport"] = getdescriptionFR($Sport->LanguageSport);
            $this->insertInfos("list_sports", $infos, "idSport|nomSport");
        }
    }

    public function parseCompetition() {
        foreach ($this->xmlinfo->Mobile->GetEventsResponse->SportMobile as $Sport) {
            $infos["idSport"] = (string) $Sport->CodeSport;
            $infos["nomSport"] = getdescriptionFR($Sport->LanguageSport);
            foreach ($Sport->TournamentMobile as $Competition) {
                $infos["idCompetition"] = (string) $Competition->CodeTournament;
                $infos["nomCompetition"] = getdescriptionFR($Competition->LanguageTournament);
                $this->insertInfos("list_tournaments", $infos, "idSport|nomSport|idCompetition|nomCompetition");
            }
        }
    }

    public function parsePari() {
        foreach ($this->xmlinfo->Mobile->GetEventsResponse->SportMobile as $Sport) {
            foreach ($Sport->TournamentMobile as $Competition) {
                foreach ($Competition->EventMobile as $EventMobile) {
                    foreach ($EventMobile->Market as $Pari) {
                        $infos["idPari"] = (string) $Pari->CodeBet;
                        $infos["nomPari"] = getdescriptionFR($Pari->LanguageBet);
                        $this->insertInfos("list_bets", $infos, "idPari|nomPari");
                    }
                }
            }
        }
    }

    public function parseChoix() {
        foreach ($this->xmlinfo->Mobile->GetEventsResponse->SportMobile as $Sport) {
            foreach ($Sport->TournamentMobile as $Competition) {
                foreach ($Competition->EventMobile as $EventMobile) {
                    foreach ($EventMobile->Market as $Pari) {
                        $infos["idPari"] = (string) $Pari->CodeBet;
                        $infos["nomPari"] = getdescriptionFR($Pari->LanguageBet);
                        foreach ($Pari->MarketDraw as $Choix) {
                            $infos["idChoix"] = (string) $Choix->CodeDraw;
                            $infos["nomChoix"] = getdescriptionFR($Choix->LanguageDraw);
                            $this->insertInfos("list_draws", $infos, "idPari|nomPari|idChoix|nomChoix");
                        }
                    }
                }
            }
        }
    }

}

?>

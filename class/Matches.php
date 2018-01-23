<?php

class Matches {

    protected $id_match_remote;
    protected $matchid;
    protected $datematch;
    protected $equipe1;
    protected $equipe2;
    protected $url_remote;
    protected $base_de_donnes;
    protected $sportid;
    protected $competitionid;
    protected $idpari = null;
    protected $cote1 = null;
    protected $cote2 = null;
    protected $coteN = null;
    protected $coteS = null;
    protected $score1 = null;
    protected $score2 = null;

    public function __construct($matchinfos, $repartitioninfos) {
        $this->matchid = trim($matchinfos["idBetradar"]);
	$this->event = trim($matchinfos["idEvenement"]);
        $this->datematch = trim($matchinfos['dateEvenement']);
        list($equipe1, $equipe2) = explode(" - ", $matchinfos['nomEvenement']);
        $this->equipe1 = addslashes(trim($equipe1));
        $this->equipe2 = addslashes(trim($equipe2));
        $this->sportid = trim($matchinfos["idSport"]);
        $this->competitionid = trim($matchinfos["idCompetition"]);
        $this->url_remote = $repartitioninfos["url"];
        $this->base_de_donnes = $repartitioninfos["base_de_donnes"];
        #print_r($matchinfos);
        #print_r($repartitioninfos);
    }

    public function sendInfosToRemote($requete, $action) {
        $postdata["action"] = $action;
        $postdata["base_de_donnes"] = $this->base_de_donnes;

        $search = array('{idBetradar}', '{idEvenement}', '{equipe1}', '{equipe2}', '{dateEvenement}', '{idSport}', '{idCompetition}', '{idPari}', '{cote1}', '{cote2}', '{coteN}', '{coteS}', '{score1}', '{score2}');
        $replace = array($this->matchid, $this->event, $this->equipe1, $this->equipe2, $this->datematch, $this->sportid, $this->competitionid, $this->idpari, $this->cote1, $this->cote2, $this->coteN, $this->coteS, $this->score1, $this->score2);
        $postdata["corps_req"] = str_ireplace($search, $replace, $requete);

        $service = new Service($this->url_remote);
        $service->setDebug(true);
        $service->call(null, $postdata, null);
    }

    public function sendMatch($requete) {
        $this->sendInfosToRemote($requete, "insert");
    }

    public function sendPariResultat($requete, $idpari) {
        $this->idpari = $idpari;
        $db = db_connect();
        if ($db->test_connexion()) {
            $findParams = array("where" => "idPari = '{$this->idpari}' AND idBetradar = '{$this->matchid}' AND  statutEvenement = 'open' AND statutPari = 'open' AND statutChoix = 'open'");
            $resultatQuery = $db->db_find_record_assoc("idChoix,nomChoix,cote", "allpari", $findParams, false, 1);
            while ($row = $db->db_fetch_assoc($resultatQuery)) {
                $nomChoix = trim(@$row["nomChoix"]);
                $cote = trim(@$row["cote"]);
                if ($nomChoix == "1") {
                    $this->cote1 = $cote;
                } elseif ($nomChoix == "N") {
                    $this->coteN = $cote;
                } elseif ($nomChoix == "2") {
                    $this->cote2 = $cote;
                }
            }
            $db->db_close();
            if (!empty($this->idpari) and ! empty($this->cote1) and ! empty($this->cote2) and ! empty($this->coteN)) {
                $this->sendInfosToRemote($requete, "insert");
            }
        }
    }

    public function sendPariScore($requete, $idpari) {
        $this->idpari = $idpari;
        $db = db_connect();
        if ($db->test_connexion()) {
            $findParams = array("where" => "idPari = '{$this->idpari}' AND idBetradar = '{$this->matchid}' AND  statutEvenement = 'open' AND statutPari = 'open' AND statutChoix = 'open'");
            $resultatQuery = $db->db_find_record_assoc("idChoix,nomChoix,cote", "allpari", $findParams, false, 1);
            while ($row = $db->db_fetch_assoc($resultatQuery)) {
                $this->coteS = trim(@$row["cote"]);
                $scoreArray = explode("-", trim(@$row["nomChoix"]));
                if (!isset($scoreArray[1])) {
                    $this->score1 = "autre";
                    $this->score2 = "autre";
                } else {
                    $this->score1 = trim(@$scoreArray[0]);
                    $this->score2 = trim(@$scoreArray[1]);
                }
                if (!empty($this->idpari) and ! empty($this->coteS) and ! empty($this->score1) and ! empty($this->score2)) {
                    $this->sendInfosToRemote($requete, "insert");
                }
            }
            $db->db_close();
        }
    }
}

?>

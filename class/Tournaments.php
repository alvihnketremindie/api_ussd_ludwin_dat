<?php

class Tournaments {

    public function __construct($array) {
        foreach ($array as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public function createXML() {
        $xml_doc = new DOMDocument('1.0', 'utf-8');
        $service = $xml_doc->createElement('ServicesPSQF');
        $xml_doc->appendChild($service);
        #Balise Service
        $request = $xml_doc->createElement('TournamentRequest');
        $service->appendChild($request);
        #Balise Request
        $request->appendChild($xml_doc->createElement('TransactionID', $this->TransactionID));
        if (isset($this->CodSport) and ! empty($this->CodSport) and isset($this->CodTournament) and ! empty($this->CodTournament)) {
            $request->appendChild($xml_doc->createElement('CodSport', $this->CodSport));
            $request->appendChild($xml_doc->createElement('CodTournament', $this->CodTournament));
        }
        $request->appendChild($xml_doc->createElement("Language", $this->Language));
        return $xml_doc->saveXML();
    }

    public function parseXML($xml) {
        $xmlinfo = new SimpleXMLElement($xml);
        $infos = array();
        $db = db_connect();
        if ($db->test_connexion()) {
            foreach ($xmlinfo->TournamentResponse as $Response) {
                foreach ($Response->Tournament as $Competition) {
                    $infos["idSport"] = (string) $Competition->CodSport;
                    $infos["idCompetition"] = (string) $Competition->CodTournament;
                    $infos["acronymCompetition"] = (string) $Competition->Acronym;
                    $infos["descriptionCompetition"] = (string) $Competition->Description;
                    $db->db_on_duplicate_key("list_tournaments", $infos);
                }
            }
            $db->db_close();
        }
    }

}

?>

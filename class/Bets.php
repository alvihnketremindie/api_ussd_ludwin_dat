<?php

class Bets {

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
        $request = $xml_doc->createElement('BetsRequest');
        $service->appendChild($request);
        #Balise Request
        $request->appendChild($xml_doc->createElement('TransactionID', $this->TransactionID));
        if (isset($this->CodBet) and ! empty($this->CodBet)) {
            $request->appendChild($xml_doc->createElement('CodBet', $this->CodBet));
        }
        $request->appendChild($xml_doc->createElement("Language", $this->Language));
        return $xml_doc->saveXML();
    }

    public function parseXML($xml) {
        $xmlinfo = new SimpleXMLElement($xml);
        $infos = array();
        $db = db_connect();
        if ($db->test_connexion()) {
            foreach ($xmlinfo->BetsResponse as $Response) {
                foreach ($Response->Bet as $Pari) {
                    $infos["idPari"] = (string) $Pari->CodBet;
                    $infos["descriptionPari"] = (string) $Pari->Description;
                    $infos["IsLive"] = (string) $Pari->IsLive;
                    $infos["IsHandicap"] = (string) $Pari->IsHandicap;
                    $infos["IsStatic"] = (string) $Pari->IsStatic;
                    $infos["typeHandicap"] = (string) $Pari->KindHandicap;
                    $infos["nombreDeChoix"] = (string) $Pari->NumDraw;
                    $db->db_on_duplicate_key("list_bets", $infos);
                }
            }
            $db->db_close();
        }
    }

}

?>

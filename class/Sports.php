<?php

class Sports {

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
        $request = $xml_doc->createElement('SportRequest');
        $service->appendChild($request);
        #Balise Request
        $request->appendChild($xml_doc->createElement('TransactionID', $this->TransactionID));
        if (isset($this->CodSport) and ! empty($this->CodSport)) {
            $request->appendChild($xml_doc->createElement('CodSport', $this->CodSport));
        }
        $request->appendChild($xml_doc->createElement("Language", $this->Language));
        return $xml_doc->saveXML();
    }

    public function parseXML($xml) {
        $xmlinfo = new SimpleXMLElement($xml);
        $infos = array();
        $db = db_connect();
        if ($db->test_connexion()) {
            foreach ($xmlinfo->SportResponse as $response) {
                foreach ($response->Sport as $Sport) {
                    $infos["idSport"] = (string) $Sport->CodSport;
                    $infos["acronymSport"] = (string) $Sport->Acronym;
                    $infos["descriptionSport"] = (string) $Sport->Description;
                    $db->db_on_duplicate_key("list_sports", $infos);
                }
            }
            $db->db_close();
        }
    }

}

?>

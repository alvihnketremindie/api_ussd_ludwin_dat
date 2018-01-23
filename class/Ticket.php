<?php

class Ticket {

    public function __construct($array) {
        foreach ($array as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public function createXML($array) {
        $xml_doc = new DOMDocument('1.0', 'utf-8');
        $service = $xml_doc->createElement('ServicesPSQF');
        $xml_doc->appendChild($service);
        #Balise Service
        $request = $xml_doc->createElement('SellRequest');
        $service->appendChild($request);
        #Balise Request
        $request->appendChild($xml_doc->createElement('CodConc', $this->CodConc));
        $request->appendChild($xml_doc->createElement('CodDiritto', $this->CodDiritto));
        $request->appendChild($xml_doc->createElement('IdTerminal', $this->IdTerminal));
        #Balise Account
        $account = $xml_doc->createElement('Account');
        $request->appendChild($account);
        $account->appendChild($xml_doc->createElement('AccountId', $this->AccountId));
        $account->appendChild($xml_doc->createElement('CodConc', $this->CodConc));
        $account->appendChild($xml_doc->createElement('CodDiritto', $this->CodDiritto));
        $account->appendChild($xml_doc->createElement('Type', $this->Type));
        #Balise Request
        $request->appendChild($xml_doc->createElement('TransactionID', $this->TransactionID));
        $request->appendChild($xml_doc->createElement("AmountCoupon", $this->AmountCoupon));
        $request->appendChild($xml_doc->createElement("AmountWin", $this->AmountWin));
        #Balise BetCoupon
        foreach ($array as $arrayvalues) {
            $BetCoupon = $xml_doc->createElement('BetCoupon');
            $request->appendChild($BetCoupon);
            foreach ($arrayvalues as $key => $value) {
                $BetCoupon->appendChild($xml_doc->createElement($key, $value));
            }
        }
        return $xml_doc->saveXML();
    }

    public function parseXML($xml) {
        $xmlinfo = new SimpleXMLElement($xml);
        $Code = (string) $xmlinfo->SellResponse->ReturnCode->Code;
        $Description = (string) $xmlinfo->SellResponse->ReturnCode->Description;
        $infos = array("code" => $Code, "description" => $Description);
        if ($Code == "0" or $Code == "1024") {
            $infos["idticket"] = (string) $xmlinfo->SellResponse->TicketSogei;
            $infos["alias"] = (string) $this->alias;
            $infos["userAccount"] = (string) $this->AccountId;
            $infos["mise"] = (string) $this->AmountCoupon;
            $infos["codconc"] = (string) $this->CodConc;
            $infos["coddiritto"] = (string) $this->CodDiritto;
            $infos["idterminal"] = (string) $this->IdTerminal;
            $infos["gainEnJeu"] = (string) $xmlinfo->SellResponse->AmountWin;
            $infos["date_soumission"] = @date("Y-m-d H:i:s");
            $infos["transactionId"] = (string) $this->TransactionID;
            $infos["statut"] = Elements::TICKET_SOUMIS;
            
            $db = db_connect();
            if ($db->test_connexion()) {
                $db->db_insert_ignore("tickets", $infos);
                $db->db_close();
            }
        }
        return $infos;
    }

}

?>


<?php

include(dirname(dirname(__FILE__)) . '/global.php');
if (isset($_REQUEST['ticket']) and ! empty($_REQUEST['ticket'])) {
    $bet = json_clean_decode($_REQUEST['ticket']);
	#print_r($bet);
    $alias = (string) $bet->alias;
    $msisdn = (string) $bet->msisdn;
    $mise = (string) $bet->mise;
    $aliasInfos = getAliasInfos($alias);
    if ($aliasInfos) {
        $userAccount = 'USSD' . $aliasInfos["indicatif"] . substr($msisdn, -$aliasInfos["significant"]);
        $IdTerminals = explode("|", $aliasInfos["IdTerminals"]);
        $transactionId = $aliasInfos["indicatif"] . @date('YmdHis') . insertAction("creerTicket", $userAccount);
        $system_code = $aliasInfos["system_code"];
        
        $arrayXml["alias"] = $alias;
        $arrayXml["IdTerminal"] = $IdTerminals[array_rand($IdTerminals)];
        $arrayXml["CodConc"] = $aliasInfos["CodConc"];
        $arrayXml["CodDiritto"] = $aliasInfos["CodDiritto"];
        $arrayXml["AmountCoupon"] = $mise;
        $arrayXml["AmountWin"] = $mise;
        $arrayXml["AccountId"] = $userAccount;
        $arrayXml["TransactionID"] = $transactionId;
        $arrayXml["Type"] = $general["infos"]["type_ticket"];
        foreach ($bet->pari as $pari) {
            #print_r($pari);
            $values = get_Values($pari->id_match, $pari->id_genre, $pari->type, $system_code);
            $arrayXml["AmountWin"] = $arrayXml["AmountWin"] * ($values["Odd"] / 100);
            $arrayValues[] = $values;
            #echo $arrayXml["AmountWin"].PHP_EOL;
        }
	#echo $arrayXml["AmountWin"].PHP_EOL;
        $arrayXml["AmountWin"] = floor($arrayXml["AmountWin"]);
        #echo $arrayXml["AmountWin"].PHP_EOL;
	#print_r($arrayXml);
	#print_r($arrayValues);
        $ticket = new Ticket($arrayXml);
        $xmlinput = $ticket->createXML($arrayValues);
        if ($xmlinput) {
            $service = new Service($general["urls"]["creer_ticket"]);
            $xmloutput = $service->postXmlData($xmlinput);
            #debug($xmloutput);
            $ticketInfos = $ticket->parseXML($xmloutput);
            logger("creerTicket", array("jsoninput" => $_REQUEST['ticket'], "ResponseJSON" => json_encode($ticketInfos), "xmlinput" => $xmlinput, "xmloutput" => $xmloutput));
            echo json_encode($ticketInfos);
        } else {
            echo "Bad request";
        }
    } else {
        echo "ERROR : La configuration de l'alias " . $alias . " n'est pas encore defini";
    }
} else {
    echo "No ticekt";
}
?>

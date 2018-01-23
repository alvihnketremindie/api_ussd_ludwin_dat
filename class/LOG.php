<?php

class LOG {

    var $chemin;

    function __construct($chemin) {
        $this->chemin = $chemin;
    }

    function getLog($params) {
        $log = '';
        foreach ($params as $key => $value) {
            $log .= "|" . $key . "=" . $value;
        }
        return $log;
    }

    function cdr($dossier, $to_log) {
        $log_date = @date("Y-m-d H:i:s");
        $log_jour = @date("Ymd");
        $log_text = $this->getLog($to_log);
        $aLogger = "date=$log_date" . $log_text . "\r\n";
        $log_chemin = $this->chemin . "/" . $log_jour;
        $log_chemin_fichier = $log_chemin . "-" . $dossier . ".log";
        @file_put_contents($log_chemin_fichier, $aLogger, FILE_APPEND);
        @file_put_contents($log_chemin, $aLogger, FILE_APPEND);
        print($aLogger.PHP_EOL);
    }

}

<?php
include_once('fonctions.php');
error_reporting(E_ALL);
// #header("Content-Type: text/plain; Charset=UTF-8");
date_default_timezone_set('UTC');
define ('ROOT', dirname(__FILE__));
define ('LOG_DIR', '/var/log/portailsport/');
define ('LOG_PATH', '/var/log/portailsport/');
define ('CLASSES', ROOT.'/class/');
define ('INI_FILE', ROOT.'/ini/services.ini');
define ('INI_REPARTITION', ROOT.'/ini/repartition.ini');
define ('XML_FILE_DIR', '/home/portailsport/');
define ('XML_CURRENT_FILE_DIR', XML_FILE_DIR.'/courant/');
define ('XML_ARCHIVE_FILE_DIR', XML_FILE_DIR.'/archive/');
define ('SERVICE_INDISPONIBLE', 'Desole le service est indisponible pour le moment, veuillez reessayer plus tard');
define ('DB_HOST', 'localhost');
define ('DB_USER', 'root');
define ('DB_PASS', 'Pass4Root@zxvf.ovh_Dedicated');
// define ('DB_PASS', 'digital');
define ('DB_NAME', 'portailsport');
// autoload('LOG|DB');
$general = parse_ini_file(INI_FILE,TRUE);
$iniRepartition = parse_ini_file(INI_REPARTITION,TRUE);
#URL
$log = new LOG(LOG_DIR);
?>

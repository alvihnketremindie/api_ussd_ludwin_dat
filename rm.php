<?php
include_once (dirname(__FILE__).'/global.php');
$dbi = db_connect();
if ($dbi->test_connexion()) {
        $requete = "ALTER TABLE `filesession` DROP `id`";
        $exec = $dbi->db_query($requete);
        $requete = "ALTER TABLE `filesession` ADD `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST";
        $exec = $dbi->db_query($requete);
        $dbi->db_close();
}
?>
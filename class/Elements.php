<?php

class Elements {

    const STATE_ONGOING = "ON-GOING";
    const STATE_PENDING = "PENDING";
    const STATE_STANDBY = "STANDBY";
    const STATE_FINISHED = "FINISHED";
    const STATE_LIVE = "LIVE";
    const STATE_EXPIRED = "EXPIRED";
    const STATE_FAILED = "FAILED";
    const STATE_DELETED = "DELETED";
    const STATE_PAUSED = "PAUSED";
    const STATE_ACTIVE = "ACTIVE";
    const STATE_TERMINER = "FINISHED";
    const LISTE_CONTINUE = "CONTINUE";
    const LISTE_FIN = "FIN";
    const NOMBRE_TICKETS = "1000";
#    const TICKET_SOUMIS = "SOUMIS";
    const TICKET_SOUMIS = "EN COURS";
    const TICKET_PAYABLE = "PAYABLE";
    const TICKET_PAYE = "PAYE";
    const TICKET_PERDANT = "PERDANT";

    private $db;
    private $table;
    private $alias = null;
    private $statut_autre = '';
    private $url;

    function __construct($array) {
        $this->db = db_connect();
        foreach ($array as $key => $value) {
            $this->{$key} = $value;
        }
        /*
          $this->table = $table;
          $this->alias = $alias;
          $this->url = $url;
         */
    }

    public function insertVague() {
        return $req = $this->db->db_insert($this->table, array("date_operation" => @date("Y-m-d H:i:s"), "alias" => $this->alias));
    }

    public function updateVagueParams($id_element, $champ_id, $table, $updateData) {
        $updateReq = $this->db->db_update($table, $updateData, "$champ_id=$id_element");
        if ($updateReq and $this->db->db_get_affected_rows() > 0) {
            return true;
        }
        return false;
    }

    public function retourneParamsVague($ressource) {
        $db = $this->db;
        $ligneDebut = $db->db_fetch_array($ressource);
        $id_debut = ($ligneDebut ? intval($ligneDebut[0]) : 0);
        $id_fin = 0;
        $sens = Elements::LISTE_FIN;
        $nb_ligne = $db->db_num_rows($ressource);
        if ($nb_ligne > 0) {
            $db->db_data_seek($ressource, $nb_ligne - 1);
            $ligneFin = $db->db_fetch_array($ressource);
            $id_fin = intval($ligneFin[0]);
        }
        $db->db_data_seek($ressource, 0);
        if ($id_fin > 0) {
            $findParams = array("where" => "alias = '{$this->alias}' and $this->statut and id > $id_fin group by idticket", "limit" => "1");
            $infoBD = $db->db_find_record_assoc("idticket", "tickets", $findParams);
            if ($infoBD) {
                // On a encore des tickets en attente
                $sens = Elements::LISTE_CONTINUE;
            }
        }
        return array("id_debut" => $id_debut, "id_fin" => $id_fin, "sens" => $sens);
    }

    public function finVague($id_element, $champ_id, $ligne) {
        $dernier_id = isset($ligne['id']) ? $ligne['id'] : 0;
        $this->updateVagueParams($id_element, $champ_id, $this->table, array('dernier_id' => $dernier_id));
        //on teste si on a ete interrompu ou si on a fini totalement;
        if (!$ligne) {
            $this->updateVagueParams($id_element, $champ_id, $this->table, array('statut' => Elements::STATE_TERMINER));
        } else {
            $this->updateVagueParams($id_element, $champ_id, $this->table, array('statut' => Elements::STATE_PENDING));
        }
    }

    public function checkElements() {
        $db = $this->db;
        $findParams = array("order" => "id desc", "limit" => "1", "where" => "alias = '{$this->alias}'");
        $find = "id_fin,sens";
        /* /Supression des checks de plus de 3 jours.
          $db->db_query("DELETE FROM reabonnement WHERE statut = '".Elements::STATE_TERMINER."' AND TIMESTAMPDIFF(DAY,date_operation,'".@date("Y-m-d H:i:s")."')>=3" );
          // */
        $tableau = $db->db_find_record_assoc($find, $this->table, $findParams);
        $id_fin = @$tableau['id_fin'];
        $sens = @$tableau['sens'];
        if (isset($sens) and $sens == Elements::LISTE_CONTINUE) {
            $findParams2 = array("limit" => Elements::NOMBRE_TICKETS, "order" => "id", "where" => "alias = '{$this->alias}' and $this->statut and id > $id_fin group by idticket");
        } else {
            $findParams2 = array("limit" => Elements::NOMBRE_TICKETS, "order" => "id", "where" => "alias = '{$this->alias}' and $this->statut group by idticket");
        }
        $ticketRessource = $db->db_find_record_assoc("*", "tickets", $findParams2, false, 1);
        $paramsVague = $this->retourneParamsVague($ticketRessource);
        $id = $this->insertVague();
        if ($id != null && $id > 0) {
            $this->updateVagueParams($id, "id", $this->table, $paramsVague);
            if ($this->updateVagueParams($id, "id", $this->table, array('statut' => Elements::STATE_ACTIVE)) and $ticketRessource) {
                while ($ligne = $db->db_fetch_assoc($ticketRessource)) {
                    $service = new Service($this->url);
                    $ticketelements = array("idticket" => $ligne['idticket']);
                    $service->executeUrl($ticketelements);
                }
                $this->finVague($id, "id", $ligne);
            }
        }
        $db->db_close();
    }

}

?>

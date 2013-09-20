<?php
// +-------------------------------------------------+
// | 2002-2011 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: printer_data.class.php,v 1.1 2012-09-17 09:39:52 ngantier Exp $

if (stristr($_SERVER['REQUEST_URI'], ".class.php")) die("no access");

require_once('./circ/print_pret/func.inc.php');

class printer_data {
	public $data;	// info biblo, empr, expl utile à l'impression
	
	public function __construct(){
		
		$this->fetch_data();
		
	}
	
	protected function fetch_data(){
		$this->get_data_mybiblio();
	}
	
	function get_data_mybiblio(){
		global $biblio_name, $biblio_logo, $biblio_adr1, $biblio_adr2, $biblio_cp, $biblio_town, $biblio_state, $biblio_country, $biblio_phone, $biblio_email, $biblio_website ;
		$this->data["biblio"]["name"]=$biblio_name;
		$this->data["biblio"]["email"]=$biblio_email;
		$this->data["biblio"]["adr1"]=$biblio_adr1;
		$this->data["biblio"]["town"]=$biblio_town;
		$this->data["biblio"]["phone"]=$biblio_phone;
		$this->data["biblio"]["email"]=$biblio_email;		
	}
	
	function get_data_empr($id_empr){		
		$empr=get_info_empr($id_empr);		
		$i=count($this->data["empr_list"]);
		$this->data["empr_list"][$i]["name"]=$empr->nom;
		$this->data["empr_list"][$i]["fistname"]=$empr->prenom;	
		return $this->data["empr_list"][$i];
	}
	
	function get_data_expl($cb_doc){		
		$expl=get_info_expl($cb_doc);		
		$i=count($this->data["expl_list"]);
		$this->data["expl_list"][$i]["tit"]=$expl->tit;
		$this->data["expl_list"][$i]["cb"]=$expl->expl_cb;
		$this->data["expl_list"][$i]["location"]=$expl->location_libelle;
		$this->data["expl_list"][$i]["section"]=$expl->section_libelle;
		$this->data["expl_list"][$i]["cote"]=$expl->expl_cote;
		$this->data["expl_list"][$i]["date_pret"]=$expl->aff_pret_date;
		$this->data["expl_list"][$i]["date_retour"]=$expl->aff_pret_retour;	
		return $this->data["expl_list"][$i];
	}	
}
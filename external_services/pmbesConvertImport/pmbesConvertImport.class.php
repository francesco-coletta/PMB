<?php
// +-------------------------------------------------+
// | 2002-2007 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: pmbesConvertImport.class.php,v 1.5.2.1 2013-08-09 09:38:23 dgoron Exp $

if (stristr($_SERVER['REQUEST_URI'], ".class.php")) die("no access");

require_once($class_path."/external_services.class.php");
require_once($include_path."/parser.inc.php");
require_once($base_path."/admin/convert/convert.class.php");
require_once($class_path."/z3950_notice.class.php");

class pmbesConvertImport extends external_services_api_class {

	var $error=false;		//Y-a-t-il eu une erreur
	var $error_message="";	//Message correspondant à l'erreur
	var $es;				//Classe mère qui implémente celle-ci !
	var $msg;

	var $catalog;
	var $converted_notice;
	
	function restore_general_config() {
	}
	
	function form_general_config() {
		return false;
	}
	
	function save_general_config() {
	}
	
	function get_catalog() {
		
		if (!count($this->catalog)) {
			//Lecture des différents formats de conversion possibles
			global $base_path;
			if (file_exists("$base_path/admin/convert/imports/catalog_subst.xml")) {
				$fic_catal = "$base_path/admin/convert/imports/catalog_subst.xml";
			} else {
				$fic_catal = "$base_path/admin/convert/imports/catalog.xml";
			}
			$this->catalog=_parser_text_no_function_(file_get_contents($fic_catal),"CATALOG");
		}
		return $this->catalog;
	}

	
	/*
	 * returne la liste des conversions possibles
	 */
	function get_convert_types() {
		
		$this->get_catalog();
		//Création et filtrage de la liste des types d'import
		for ($i=0; $i<count($this->catalog['ITEM']); $i++) {
			if ($this->catalog['ITEM'][$i]['VISIBLE']!='no') {
			   $convert_types[$i]=utf8_encode($this->catalog['ITEM'][$i]['NAME']);
			}
		}
		return $convert_types;
	}
	
	
	/*
	 * @param notice = 1 notice sans entête
	 * @param convert_type_id = identifiant de la conversion à réaliser
	 * @param import = true >> exécuter l'import après conversion
	 */
	function convert($notice, $convert_type_id, $import=0, $source_id=0) {
		
		$this->get_catalog();
		$this->source_id=$source_id;
		$convert_type=$this->catalog['ITEM'][$convert_type_id];
		$importable=$this->catalog['ITEM'][$convert_type_id]['IMPORT'];

		if (count($convert_type)) {
			$export= new convert(utf8_decode($notice),$convert_type_id);
			$this->converted_notice=$export->output_notice;
					
			if($import && ($importable=='yes') && $this->converted_notice) {
				$this->import();
			}
		}
				
		return array('notice'=>$notice);
	}
	
	
	function import($unimarc_notice='') {
		
		global $deflt_integration_notice_statut;
		global $gestion_acces_active, $gestion_acces_user_notice, $gestion_acces_empr_notice;
		
		if ($unimarc_notice) {
			$this->converted_notice=$unimarc_notice;
		}
		if ($this->converted_notice) {			
			$z = new z3950_notice('unimarc',$this->converted_notice);
			$z->source_id = $this->source_id;
			$z->statut = $deflt_integration_notice_statut;
			$z->var_to_post();
			$retour = $z->insert_in_database();
			if($retour[0]){
				//parce que les droits sur une nouvelle ressource se calculent forcément sur le formulare que n'existe pas dans ce cas...
				if ($gestion_acces_active==1) {
					$ac= new acces();
					//traitement des droits acces user_notice
					if ($gestion_acces_user_notice==1) {
						$dom_1= $ac->setDomain(1);
						$dom_1->applyRessourceRights($retour[1]);
					}
					//traitement des droits acces empr_notice
					if ($gestion_acces_empr_notice==1) {
						$dom_2= $ac->setDomain(2);
						$dom_2->applyRessourceRights($retour[1]);
					}
				}
			}
		}
	}
	
}

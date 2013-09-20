<?php
// +-------------------------------------------------+
// | 2002-2011 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: notice_onglet.class.php,v 1.3 2013-03-01 13:31:32 dbellamy Exp $

if (stristr($_SERVER['REQUEST_URI'], ".class.php")) die("no access");

require_once("./classes/notice_tpl_gen.class.php"); 

class notice_onglet {
	
	public function __construct($id_tpl){
		$this->id_tpl=$id_tpl+0;
		$this->fetch_data();
	}
	
	protected function fetch_data(){
		if(!$this->id_tpl)return false;
		$this->noti_tpl=new notice_tpl_gen($this->id_tpl);
		
			
	}

	public function get_onglet_header(){
		//print ($this->noti_tpl->name);
		return($this->noti_tpl->name);
	}	
	
	public function get_onglet_content($id_notice){
		$tpl= $this->noti_tpl->build_notice($id_notice);		
		
		return $tpl;
	}
} // class end

class notice_onglets {
	
	public function __construct($ids=''){
		global $opac_notices_format_onglets;

		if(!$ids)$ids=$opac_notices_format_onglets;
		$this->ids=$ids;
		
	}
	
	public function insert_onglets($id_notice,$retour_aff){			
		$id_notice+=0;
		$onglets_title="";
		$onglets_content="";
		if($this->ids){
			$onglets=explode(",", $this->ids);
			foreach($onglets as $id_tpl){
				$notice_onglet=new notice_onglet($id_tpl);
				
				$title=$notice_onglet->get_onglet_header();
				$onglet_title="
				<li id='onglet_tpl_".$id_tpl."_".$id_notice."'  class='isbd_public_inactive'>
					<a href='#' title=\"".$title."\" onclick=\"show_what('tpl_".$id_tpl."_', '$id_notice'); return false;\">".$title."</a>
				</li>";
	
				$content=$notice_onglet->get_onglet_content($id_notice);
				$onglet_content="
				<div id='div_tpl_".$id_tpl."_".$id_notice."' class='onglet_tpl' style='display:none;'>
					".$content."
				</div>";
				// Si pas de titre ou de contenu rien ne s'affiche.
				if($title && $onglet_content){
					$onglets_title.=$onglet_title;
					$onglets_content.=$onglet_content;
				}
			}
		}	
		$retour_aff=str_replace('<!-- onglets_perso_list -->', $onglets_title, $retour_aff);
		$retour_aff=str_replace('<!-- onglets_perso_content -->', $onglets_content, $retour_aff);
		return $retour_aff;
	}

}	 // class end


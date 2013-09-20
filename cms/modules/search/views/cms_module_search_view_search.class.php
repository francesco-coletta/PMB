<?php
// +-------------------------------------------------+
// © 2002-2012 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: cms_module_search_view_search.class.php,v 1.7.2.1 2013-07-25 10:21:02 apetithomme Exp $

if (stristr($_SERVER['REQUEST_URI'], ".class.php")) die("no access");
require_once($include_path."/h2o/h2o.php");

class cms_module_search_view_search extends cms_module_common_view{
	protected $cadre_parent;
	
	public function __construct($id=0){
		parent::__construct($id+0);
	}
	
	public function get_form(){
		$form ="
		<div class='row'>
			<div class='row'>
				<div class='colonne3'>
					<label for='cms_module_search_view_help'>".$this->format_text($this->msg['cms_module_search_view_help'])."</label>
				</div>
				<div class='colonne-suite'>
					<input type='radio' name='cms_module_search_view_help' value='1' ".($this->parameters['help'] ? "checked='checked'" : "")."/>&nbsp;".$this->format_text($this->msg['cms_module_search_view_help_yes'])."
					&nbsp;<input type='radio' name='cms_module_search_view_help' value='0' ".(!$this->parameters['help'] ? "checked='checked'" : "")."/>&nbsp;".$this->format_text($this->msg['cms_module_search_view_help_no'])."
				</div>
			</div>
		</div>
		".parent::get_form();
		return $form;
	}
	
	public function save_form(){
		global $cms_module_search_view_help;
		
		$this->parameters['help'] = $cms_module_search_view_help+0;
		
		return parent::save_form();
	}
	
	public function render($datas){
		global $base_path,$opac_autolevel2;
		global $opac_modules_search_title,$opac_modules_search_author,$opac_modules_search_publisher,$opac_modules_search_titre_uniforme;
		global $opac_modules_search_collection,$opac_modules_search_subcollection,$opac_modules_search_category,$opac_modules_search_indexint;
		global $opac_modules_search_keywords,$opac_modules_search_abstract,$opac_modules_search_docnum;
		global $dest,$user_query,$charset;
		//juste une searchbox...
		if(count($datas) == 1){
			if($datas[0]['page']>0){
				$action = $base_path."/index.php?lvl=cmspage&pageid=".$datas[0]['page'];
			}else{
				if ($opac_autolevel2==2) {
					$action = $base_path."/index.php?lvl=more_results&autolevel1=1";
				} else {
					$action = $base_path."/index.php?lvl=search_result&search_type_asked=simple_search";
				}
			}
			$onsubmit = "if (".$this->get_module_dom_id()."_searchbox.user_query.value.length == 0) { ".$this->get_module_dom_id()."_searchbox.user_query.value='*';}".$this->get_module_dom_id()."_change_dest();";
		}else{
			if ($opac_autolevel2==2) {
				$action = $base_path."/index.php?lvl=more_results&autolevel1=1";
			} else {
				$action = $base_path."/index.php?lvl=search_result&search_type_asked=simple_search";
			}
			$onsubmit = $this->get_module_dom_id()."_change_dest();";
		}
		if ($opac_modules_search_title==2) $look["look_TITLE"]=1;
		if ($opac_modules_search_author==2) $look["look_AUTHOR"]=1 ;
		if ($opac_modules_search_publisher==2) $look["look_PUBLISHER"] = 1 ; 
		if ($opac_modules_search_titre_uniforme==2) $look["look_TITRE_UNIFORME"] = 1 ; 
		if ($opac_modules_search_collection==2) $look["look_COLLECTION"] = 1 ;	
		if ($opac_modules_search_subcollection==2) $look["look_SUBCOLLECTION"] = 1 ;
		if ($opac_modules_search_category==2) $look["look_CATEGORY"] = 1 ;
		if ($opac_modules_search_indexint==2) $look["look_INDEXINT"] = 1 ;
		if ($opac_modules_search_keywords==2) $look["look_KEYWORDS"] = 1 ;
		if ($opac_modules_search_abstract==2) $look["look_ABSTRACT"] = 1 ;
		$look["look_ALL"] = 1 ;
		if ($opac_modules_search_docnum==2) $look["look_DOCNUM"] = 1;
		$html = "
			<form method='post' class='searchbox' action='".$action."' name='".$this->get_module_dom_id()."_searchbox' ".($onsubmit!= "" ? "onsubmit=\"".$onsubmit."\"" : "").">
				";
		foreach($look as $looktype=>$lookflag) { $html.="
				<input type='hidden' value='1' name='$looktype'>"; 
		}
		$html.="
				<input type='text' name='user_query' value='".htmlentities($user_query,ENT_QUOTES,$charset)."'/>
				<input class='bouton' type='submit' value='".$this->format_text($this->msg['cms_module_search_button_label'])."' />";
		if ($this->parameters['help']) {
			$html.="
				<input class='bouton' type='button' onclick='window.open(\"./help.php?whatis=simple_search\", \"search_help\", \"scrollbars=yes, toolbar=no, dependent=yes, width=400, height=400, resizable=yes\"); return false' value='".$this->format_text($this->msg['cms_module_search_help'])."'>";
		}
		if(count($datas) >1){
			$html.= "<br/>";
			for($i=0 ; $i<count($datas) ; $i++){
				$checked ="";
				if($dest){
					if($datas[$i]['page'] == $dest){
						$checked= " checked='checked'";
					}
				}else if($i == 0){
					$checked= " checked='checked'";
				}
				$html.="
					<span class='search_radio_button' id='search_radio_button_".$i."'><input type='radio' name='dest' value='".$datas[$i]['page']."'".$checked."/>".$this->format_text($datas[$i]['name'])."</span>";
			}
		}
		$html.= "		
			</form>";
		return $html;
	}
	
	public function get_headers(){
		global $base_path;
		$headers = array();
		
		$headers[] = "
		<script type='text/javascript'>
			function ".$this->get_module_dom_id()."_change_dest(){
				var page = 0;
				var dests = document.forms['".$this->get_module_dom_id()."_searchbox'].dest;
				for(key in dests){
					if(dests[key].checked){
						page = dests[key].value;
						break;
					}
				}
				
				if(page>0){
					document.forms['".$this->get_module_dom_id()."_searchbox'].action = '".$base_path."/index.php?lvl=cmspage&pageid='+page;
				}
				return true;
			}
		</script>";
		return $headers;	
	}
}
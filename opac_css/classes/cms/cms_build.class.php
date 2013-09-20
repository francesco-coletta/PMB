<?php
// +-------------------------------------------------+
// | 2002-2007 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: cms_build.class.php,v 1.44.2.2 2013-09-17 12:47:00 arenou Exp $

if (stristr($_SERVER['REQUEST_URI'], ".class.php")) die("no access");

if (substr(phpversion(), 0, 1) == "5") @ini_set("zend.ze1_compatibility_mode", "0");

require_once($class_path."/autoloader.class.php");
$autoloader = new autoloader();
$autoloader->add_register("cms_modules",true);		
require_once($class_path."/cms/cms_modules_parser.class.php");
class cms_build{	
	var $dom;
	var $headers = array();
	var $id_version; // version du portail
	var $fixed_cadres = array();
	//Constructeur	 
	function cms_build(){

	}	
	
	function transform_html($html){
		global $pageid;
		global $charset;

		if($charset=='utf-8') $html = preg_replace('/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]'.
		'|[\x00-\x7F][\x80-\xBF]+'.
		'|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*'.
		'|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})'.
		'|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/S',
		'?', $html );

		$pageid+=0;
		@ini_set("zend.ze1_compatibility_mode", "0");
		$this->cadre_portail_list=array();
		$this->dom = new DomDocument();
		if(!@$this->dom->loadHTML($html)) return $html;
		$this->dom->encoding = $charset;
		//bon, l'histoire se répète, c'est quand on pense que c'est simple que c'est vraiment complexe...
		// on commence par récupérer les zones...
		$this->id_version=$this->get_version_public();
		if(!$this->id_version) return $html;
		//On vide ce qui est trop vieux dans la table de cache des cadres
		$this->manage_cache_cadres("clean");
		$cache_cadre_object=array();//Tableau qui sert à stocker les objets générés pour les cadres.
		$query_zones = "select distinct build_parent from cms_build where build_type='cadre' and build_version_num= '".$this->id_version."'";
		$result_zones = mysql_query($query_zones);
		if(mysql_num_rows($result_zones)){
			while($row_zones = mysql_fetch_object($result_zones)){
				
				//pour chaque zone, on récupère les cadres fixes...
				$query_cadres = "select * from cms_build where build_parent = '".$row_zones->build_parent."' 
					and build_fixed = 1 and build_type='cadre' and build_version_num= '".$this->id_version."' ";

				$result_cadres = mysql_query($query_cadres);
				if(mysql_num_rows($result_cadres)){
					$cadres = array();
					//on place les cadres dans un tableau
					while($row_cadres = mysql_fetch_object($result_cadres)){
							$cadres[]=$row_cadres;
					}
					$ordered_cadres = $this->order_cadres($cadres,$cache_cadre_object);
					$this->fixed_cadres[$row_zones->build_parent] = $ordered_cadres;
					foreach($ordered_cadres as $cadre){
						$this->apply_change($cadre,$cache_cadre_object);
						if($cadre->build_div){
							$this->add_div($cadre->build_obj);
						}
					}
				}	
				//on passe au cadre dynamiques
				$query_dynamics = "select * from cms_build where build_parent = '".$row_zones->build_parent."' 
				and build_fixed = 0 and build_type='cadre' and  build_version_num= '".$this->id_version."' 
				order by id_build ";
				$result_dynamics = mysql_query($query_dynamics);
				if(mysql_num_rows($result_dynamics)){
					$cadres = array();
					while($row_dynamics = mysql_fetch_object($result_dynamics)){
						$cadres[]=$row_dynamics;
					}
					$ordered_cadres = $this->order_cadres($cadres,$cache_cadre_object);
					foreach($ordered_cadres as $cadre){
						$this->apply_change($cadre,$cache_cadre_object);
						if($cadre->build_div){
							$this->add_div($cadre->build_obj);
						}
					}
				}
			}
		}
		//on traite la css des Zones. A voir plus tard pour la gestion du placement
		$query_css_zones = "select * from cms_build where build_type='zone' and  build_version_num= '".$this->id_version."' ";
		$res = mysql_query($query_css_zones);
		if(mysql_num_rows($res)){
			while($r = mysql_fetch_object($res)){
				$node = $this->dom->getElementById($r->build_obj);
				if($node){
					if( $r->build_css){
						$this->add_css($node,$r->build_css);
					}	
					if($r->build_div){
						$this->add_div($r->build_obj);
					}
				}	
			}
		}
		//gestion du placement des zones du contener
		$query_zones = "select * from cms_build where build_type='zone' and  build_version_num= '".$this->id_version."' and build_parent='container' ";
		$res = mysql_query($query_zones);
		$contener = $this->dom->getElementById("container");
		$zones=array();
		if(mysql_num_rows($res)){
			while($r = mysql_fetch_object($res)){
				$zones[]=$r;
			}
			$ordered_zones = $this->order_cadres($zones,$cache_cadre_object);
			foreach($ordered_zones as $zone){
				$this->apply_change($zone,$cache_cadre_object);
				if($cadre->build_div){
				//	$this->add_div($cadre->build_obj);
				}
			}
		}				
		//on insère les entêtes des modules dans le head
		$this->insert_headers();
		$html = $this->dom->saveHTML();
		@ini_set("zend.ze1_compatibility_mode", "1");

		return $html;
	}
	
	function add_div($id){	
		
		$node = $this->dom->getElementById($id);
		if(!$node) return;
		
		$obj_div= $this->dom->createElement('div');
		$obj_div->setAttribute('id',"add_div_".$id);
		$obj_div->setAttribute('class',"row");
		$node->parentNode->insertBefore($obj_div,$node);
	}
		
	function clear_session_version(){
		$_SESSION["build_id_version"]="";
	}
	
	function get_version_public(){
		global $dbh,$opac_cms;
		global $build_id_version; // passer en get si constrution de l'opac en cours
		if($build_id_version){
			$_SESSION["build_id_version"]=$build_id_version;
		} else{
			$build_id_version=$_SESSION["build_id_version"];
		}
		if($build_id_version ) {			
			// mode opac en constuction
			$requete = "select * from cms_version where 
			id_version='$build_id_version'
			order by version_date desc 
			";	
		} elseif($opac_cms){
			// mode opac, on prend la dernière version
			$requete = "select * from cms_version where 
			version_cms_num=$opac_cms
			order by version_date desc 
			";		
		}else{
			return"";
		}	
		$res = mysql_query($requete, $dbh);				
		if($row = mysql_fetch_object($res)){	
			return $row->id_version;
		} else {
			$_SESSION["build_id_version"]="";	
		}	
	}

	function apply_change($cadre,&$cache_cadre_object){
		global $charset;
		if(substr($cadre->build_obj,0,strlen("cms_module_"))=="cms_module_"){
			$id_cadre= substr($cadre->build_obj,strrpos($cadre->build_obj,"_")+1);
			if($cache_cadre_object[$cadre->build_obj]){
				$obj=$cache_cadre_object[$cadre->build_obj];
			}else{
				$obj=cms_modules_parser::get_module_class_by_id($id_cadre);
				$cache_cadre_object[$cadre->build_obj]=$obj;
			}
			if($obj && $obj->check_conditions()){
				//on va chercher ses entetes...
				$this->headers = array_merge($this->headers,$obj->get_headers());
				$this->headers = array_unique($this->headers);
				
				//on s'occupe du cadre en lui-même
				//on récupère le contenu du cadre
				$res = $this->manage_cache_cadres("select",$cadre->build_obj,"html");
				if($res["select"]){
					$html = $res["value"];
				}else{
					$html = $obj->show_cadre();
					//on regarde si une condition n'empeche pas la mise en cache !
					if($obj->check_for_cache()){
					$this->manage_cache_cadres("insert",$cadre->build_obj,"html",$html);
					}
				}
				//ca a peut-être l'air complexe, mais c'est logique...
				$tmp_dom = new domDocument();
				if($charset == "utf-8"){
					@$tmp_dom->loadHTML("<?xml version='1.0' encoding='$charset'>".$html);
				}else{
					@$tmp_dom->loadHTML($html);
				}
				if (!$tmp_dom->getElementById($obj->get_dom_id())) $this->setAllId($tmp_dom);
				if($this->dom->getElementById($cadre->build_parent) ){
					$this->dom->getElementById($cadre->build_parent)->appendChild($this->dom->importNode($tmp_dom->getElementById($obj->get_dom_id()),true));
				}	
				$dom_id =$obj->get_dom_id();
				//on rappelle le tout histoire de récupérer les CSS and co...
				$this->apply_dom_change($obj->get_dom_id(),$cadre);					
			}
		}else{
			$this->apply_dom_change($cadre->build_obj,$cadre);
		}
	}
	
	
	function order_cadres($cadres,&$cache_cadre_object){
		//on retente de mettre de l'ordre dans tout ca...
		//init
		$ordered_cadres = array();
		$cadres_dom = array();
		$zone = "";
		//on élimine ce qui n'est pas dans le dom (ou ne va pas l'être)
		for($i=0 ; $i<count($cadres) ; $i++){
			if(!$zone) $zone = $cadres[$i]->build_parent;
			if(substr($cadres[$i]->build_obj,0,strlen("cms_module_"))=="cms_module_"){
				$id_cadre= substr($cadres[$i]->build_obj,strrpos($cadres[$i]->build_obj,"_")+1);
				$res = $this->manage_cache_cadres("select",$cadres[$i]->build_obj,"object");
				if($res["select"] == true){
					if($res["value"]){
						$cadres_dom[] = $res["value"];
					}
				}else{
					if($cache_cadre_object[$cadres[$i]->build_obj]){
						$obj=$cache_cadre_object[$cadres[$i]->build_obj];
					}else{
						$obj=cms_modules_parser::get_module_class_by_id($id_cadre);
						$cache_cadre_object[$cadres[$i]->build_obj]=$obj;
					}
					if($obj && $obj->check_conditions()){
						$cadres_dom[] = $cadres[$i];
						if($obj->check_for_cache()){
							$this->manage_cache_cadres("insert",$cadres[$i]->build_obj,"object",$cadres[$i]);
						}
					}else if($obj->check_for_cache()){
						$this->manage_cache_cadres("insert",$cadres[$i]->build_obj,"object","");
					}
				}
			}else if($cadres[$i]->build_fixed || $this->dom->getElementById($cadres[$i]->build_obj)){
				$cadres_dom[] = $cadres[$i];
			}
		}
		$cadres = $cadres_dom;
		//après ce petit tour de passe passe, il nous reste ques les éléments présent sur la page...
		$ordered_cadres[] =$this->get_next_cadre($cadres,$zone);
		$i=0;
		$nb =count($cadres);
		while(count($cadres)){
			$ordered_cadres[] =$this->get_next_cadre($cadres,$zone,$ordered_cadres[count($ordered_cadres)-1]->build_obj);
			if($i==$nb) break;
			$i++;
		}
		
		//le reste, c'est que l'on à jamais pu placer (perte de chainage via supression de cadres)...
		foreach($cadres as $cadre){
			$ordered_cadres[] = $cadre;
		}
		return $ordered_cadres;		
	}
	
	/*
	 * Permets la gestion du cache pour les cadres du portail dans l'opac
	 */
	protected function manage_cache_cadres($todo,$build_object_name="",$content_type="",$content=""){
		$res=array($todo=>false,"value"=>"");
		if($_SESSION["cms_build_activate"]){
			return $res;
		}
		$my_hash_cadre=md5($build_object_name.serialize($_GET).serialize($_POST));
		switch ($todo) {
			case "select":
				$requete="SELECT cache_cadre_hash,cache_cadre_content  FROM cms_cache_cadres WHERE cache_cadre_hash='".addslashes($my_hash_cadre)."' AND cache_cadre_type_content='".addslashes($content_type)."'";
				$res=mysql_query($requete);
				if($res && mysql_num_rows($res)){
					$html = mysql_result($res,0,1);
					if($html){
						if($content_type == "object"){
							$value = unserialize($html);
						}else{
							$value = $html;
						}
					}else{
						$value = "";
					}
					$res=array($todo=>true,"value"=>$value);
				}
				break;
			case "insert":
				$cache_cadre_content="";
				if($content_type == "object"){
					if($content){
						$cache_cadre_content=serialize($content);
					}
				}else{
					$cache_cadre_content=$content;
				}
				$requete="INSERT INTO cms_cache_cadres(cache_cadre_hash,cache_cadre_type_content,cache_cadre_content) VALUES('".addslashes($my_hash_cadre)."','".addslashes($content_type)."','".addslashes($cache_cadre_content)."')";
				$res2=mysql_query($requete);
				if($res2){
					$res=array($todo=>true,"value"=>"");
				}
				break;
			case "clean":
				global $opac_search_cache_duration;//Variable en seconde
				$requete="DELETE FROM cms_cache_cadres WHERE NOW() > DATE_ADD(`cache_cadre_create_date`, INTERVAL ".($opac_search_cache_duration*1)." SECOND)";
				mysql_query($requete);
				$res=array($todo=>true,"value"=>"");
			default:
				break;
		}
		
		return $res;
	}
	
	function get_next_cadre(&$cadres,$zone,$before=""){
		$next = false;
		//on commence par aller par rapport au dynamiques
		
		foreach($cadres as $key => $cadre){
			if($cadre->build_child_before == $before){
				$next = $cadre;
				unset($cadres[$key]);
				return $next;
			}
		}
		// on perd le fil, on reprend les valeurs sures, les éléments fixe
		for($i=0 ; $i<count($this->fixed_cadres[$zone]) ; $i++){
			foreach($cadres as $key => $cadre){
				if($cadre->build_child_before != "" && $cadre->build_child_before == $this->fixed_cadres[$zone][$i]->build_obj){
					$next = $cadre;
					unset($cadres[$key]);
					return $next;
				}
			}
		}
		return $next;
	}
	
	function setAllId($DOMNode){
  		if($DOMNode->hasChildNodes()){
  			for ($i=0; $i<$DOMNode->childNodes->length;$i++) {
  				$this->setAllId($DOMNode->childNodes->item($i));
  			}
  		}
		if($DOMNode->hasAttributes()){
        	$id=$DOMNode->getAttribute("id");
        	if($id){
          		$DOMNode->setIdAttribute("id",true);
        	}
      	}
	}
	
	function apply_dom_change($id,$infos){	
		//on s'assure que la zone existe !
		$parent = $this->dom->getElementById($infos->build_parent);
		if($parent){
			$node = $this->dom->getElementById($id);
			if($node){
				
				//on ajoute l'attribut fixed si on est sur un élément fixé!
				if($infos->build_fixed){
					$node->setAttribute("fixed","yes");
				}
				//on lui ajoute les éléments de la CSS
				$node = $this->add_css($node,$infos->build_css);
				//on le place dans la bonne zone
				$this->place($node,$parent,$infos);
			}
		}
	}

	function add_css($node,$css){
		if($css){
			$node->setAttribute("style",$css);
		}
		return $node;
	}

	function get_first_child($zone) {
		$childs=$zone->childNodes;
		$first_child=null;
		for ($i=0; $i<$childs->length;$i++) {
			$child=$childs->item($i);
			if (($child->nodeType==XML_ELEMENT_NODE)&&($child->getAttribute("id"))) {
				$first_child=$child;
				break;	
			}
		}
		return $first_child;
	}
	
	function get_nextSibling($zone,$field) {
		$childs=$zone->childNodes;
		$next=null;
		$found=0;
		for ($i=0; $i<$childs->length;$i++) {
			$child=$childs->item($i);
			if (($child->nodeType==XML_ELEMENT_NODE)&&($child->getAttribute("id"))){				
				if($child->getAttribute("id")==$field->getAttribute("id")) {					
					$found=1;
				}elseif($found){// coup suivant, c'est le bon
					$next=$child;
					break;						
				}
			}	
		}
		return $next;
	}
	
	function get_previousSibling($zone,$field) {
		$childs=$zone->childNodes;
		$previous=null;
		for ($i=0; $i<$childs->length;$i++) {
			$child=$childs->item($i);
			if (($child->nodeType==XML_ELEMENT_NODE)&&($child->getAttribute("id"))){				
				if(($child->getAttribute("id")==$field->getAttribute("id")) ) {	
					return $previous;	
					break;	
				}else{					
					$previous=$child;						
				}
			}	
		}
		return null;
	}	

	function place($node,$parent,$infos){
		$previous_brother = $this->get_previous_node_id($infos);
		if($previous_brother!== false){
			if($previous_brother!= ""){
				//un précédent connu, on insère le noeud juste avant le précédent, puis on remet le précédent au dessus...
				$node_next= $this->get_nextSibling($parent,$this->dom->getElementById($previous_brother));
				if($node_next && ($node->getAttribute("id") !=$node_next->getAttribute("id"))){
					$parent->insertBefore($node,$node_next);
				} elseif(!$node_next) {
					$parent->appendChild($node);
				} else {
				}
			}else{
				//pas de parent, c'est le premier...
				$node_child=$this->get_first_child($parent);
				if($node_child && ($node->getAttribute("id")!=$node_child->getAttribute("id"))){
					$parent->insertBefore($node,$node_child);
				} elseif(!$node_child) {
					$parent->appendChild($node);
				} else {
				}
			}
		}else{
			$next_brother = $this->get_next_node_id($infos);
			if($next_brother){				
				$node_previous=$this->get_previousSibling($parent,$this->dom->getElementById($next_brother));				
				if($node_previous && ($node->getAttribute("id")!=$node_previous->getAttribute("id"))){
					$node = $parent->insertBefore($node,$node_previous);
				}elseif(!$node_previous){
					$parent->appendChild($node);					
				}else {
				}
			}else{
				$parent->appendChild($node);
			}
		}
	}
	
	function get_previous_node_id($infos){
		return $this->_get_previous_node_id($infos->build_obj);
	}

	function _get_previous_node_id($node_id){
		if($node_id === ""){
			return $node_id;
		}else{
			if($this->dom->getElementById($node_id)){
				$query = "select build_child_before from cms_build where build_obj = '".$node_id."' and  build_version_num= '".$this->id_version."' ";
				$result = mysql_query($query);
				if(mysql_num_rows($result)){
					$previous = mysql_result($result,0,0);
					if($this->dom->getElementById($previous)){
						return $previous;
					}else{
						return $this->_get_previous_node_id($previous);
					}
				}
			}else{
				return false;
			}
		}

	}

	function get_next_node_id($infos){
		return $this->_get_next_node_id($infos->build_obj);
	}
	
	function _get_next_node_id($node_id){
		if($node_id === ""){
			return $node_id;
		}else{
			if($this->dom->getElementById($node_id)){
				$query = "select build_child_after from cms_build where build_obj = '".$node_id."' and  build_version_num= '".$this->id_version."'";
				$result = mysql_query($query);
				if(mysql_num_rows($result)){
					$next = mysql_result($result,0,0);
					if($this->dom->getElementById($next)){
						return $next;
					}else{
						return $this->_get_next_node_id($next);
					}
				}	
			}else{
				return false;
			}
		}
	}
	
	function insert_headers(){
		if(count($this->headers)){
			$headers = implode("\n",$this->headers);
			$tmp_dom = new domDocument();
			@$tmp_dom->loadHTML($headers);
			for ($i=0 ; $i<$tmp_dom->getElementsByTagName("head")->item(0)->childNodes->length ; $i++){
				$this->dom->getElementsByTagName("head")->item(0)->appendChild($this->dom->importNode($tmp_dom->getElementsByTagName("head")->item(0)->childNodes->item($i),true));
			}
		}
	}
// class end
}

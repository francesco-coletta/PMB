<?php
// +-------------------------------------------------+
// © 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: bookreaderBNF.class.php,v 1.3 2013-04-23 13:06:18 apetithomme Exp $

if (stristr($_SERVER['REQUEST_URI'], ".class.php")) die("no access");

class bookreaderBNF {
	var $doc;		//le document BNF à traiter
	var $bnfClass;
	
	function bookreaderBNF($doc){
		$this->doc = $doc;
		$this->getBnfClass();
	}
	
	function getBnfClass(){
		global $visionneuse_path;
		$class_name = $this->doc->driver->getBnfClass($this->doc->mimetype);
		$this->bnfClass = new $class_name($visionneuse_path."/temp/".$this->doc->id);
	}
	
	function getPage($page){
		$content = $this->bnfClass->get_page_content($page);
		print $content;
	}
	
	function getWidth($page){
		print $this->bnfClass->getWidth($page);
	}
	
	function getHeight($page){
		print $this->bnfClass->getHeight($page);
	}
	
	function search($user_query){
		return $this->bnfClass->search($user_query);
	}
	
	function getBookmarks(){
		return $this->bnfClass->getBookmarks();
	}
	
	function getPDF($pdfParams){
		$this->bnfClass->generatePDF($pdfParams);
	}
	
	function getPageCount(){
		return $this->bnfClass->getNbPages();
	}
	
	function getPagesSizes(){
 		$this->pagesSizes= $this->bnfClass->pagesSizes;
	}
}
?>
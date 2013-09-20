<?php
// +-------------------------------------------------+
// © 2002-2012 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: RootNode.php,v 1.2 2013-04-25 16:02:17 mbertin Exp $
namespace Sabre\PMB;

class RootNode extends Collection {
	public $config;
	
	function __construct($config){
		parent::__construct($config);
		$this->type = "rootNode";
	}
	
	function getName() {
		return "";	
	}
}
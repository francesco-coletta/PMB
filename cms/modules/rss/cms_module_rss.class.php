<?php
// +-------------------------------------------------+
// | 2002-2011 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: cms_module_rss.class.php,v 1.1 2012-03-06 17:25:51 arenou Exp $

if (stristr($_SERVER['REQUEST_URI'], ".class.php")) die("no access");

class cms_module_rss extends cms_module_common_module {
	
	public function __construct($id=0){
		$this->module_path = str_replace(basename(__FILE__),"",__FILE__);
		parent::__construct($id);
	}
}
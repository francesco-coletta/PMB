<?php
// +-------------------------------------------------+
// © 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: facette.inc.php,v 1.1 2013-01-09 14:09:59 ngantier Exp $
if (stristr($_SERVER['REQUEST_URI'], ".inc.php")) die("no access");

require_once($class_path.'/facette_search.class.php');

$facette = new facettes();
switch($sub){
	case 'see_more':		
		
		//ajax_http_send_response($facette->create_table_facettes());
		ajax_http_send_response($facette->see_more($id));
	
	break;
}

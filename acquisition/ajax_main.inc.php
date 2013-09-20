<?php
// +-------------------------------------------------+
// © 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: ajax_main.inc.php,v 1.3 2011-06-06 08:04:28 dbellamy Exp $

if (stristr($_SERVER['REQUEST_URI'], ".inc.php")) die("no access");

//En fonction de $categ, il inclut les fichiers correspondants

switch($categ) {
	case 'ach':
		include("./acquisition/achats/ajax_main.inc.php");
		break;
	case 'sugg':
		include("./acquisition/suggestions/ajax/ajax_sugg.inc.php");
		break;
	default:
		break;		
}

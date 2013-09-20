<?php
// +-------------------------------------------------+
// © 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: ajax_main.inc.php,v 1.11 2012-07-20 13:22:47 arenou Exp $

if (stristr($_SERVER['REQUEST_URI'], ".inc.php")) die("no access");

//En fonction de $categ, il inclut les fichiers correspondants

switch($categ) {
	case 'collections_state':
		include('./catalog/serials/ajax/collections_state.inc.php');
	break;
	case 'caddie_add':
		include('./catalog/caddie/caddie_ajax.inc.php');
	break;
	case 'caddie':
		include('./catalog/caddie/caddie_ajax.inc.php');
	break;
	case 'expand':
		include('./catalog/notices/search/expand_ajax.inc.php');
	break;
	case 'expand_block':
		include('./catalog/notices/search/expand_block_ajax.inc.php');
	break;	
	case 'avis':
		include('./catalog/notices/avis_ajax.inc.php');
	break;
	case 'explnum':
		include('./catalog/explnum/explnum_ajax.inc.php');
	break;
	case 'serialcirc_diff':
		include('./catalog/serialcirc_diff/serialcirc_diff_ajax.inc.php');
	break;
	case 'search_params';
		include('./catalog/notices/search/search_ajax.inc.php');
		break;
	case 'force_integer';
		include('./catalog/notices/search/external/ajax_integer.inc.php');
		break;	
	default:
	//tbd
	break;		
}
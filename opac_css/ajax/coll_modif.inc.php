<?php
// +-------------------------------------------------+
// © 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: coll_modif.inc.php,v 1.1.2.2 2013-09-11 08:10:11 ngantier Exp $

if (stristr($_SERVER['REQUEST_URI'], ".inc.php")) die("no access");


switch($quoifaire){	
	case 'coll_save':
		coll_save($id,$sql_field,$texte);	
	break;
}

/*
 * Enregistrement 
 */
function coll_save($id,$sql_field,$texte){
	
	global $dbh;
	
	mysql_query("UPDATE collections_state SET $sql_field='$texte' WHERE collstate_id=".$id, $dbh);
	
	ajax_http_send_response($texte);
	
}

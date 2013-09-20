// +-------------------------------------------------+
// © 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: coll_modif.js,v 1.1.2.2 2013-09-11 08:10:11 ngantier Exp $


function coll_modif_update(id,sql_field,texte){
	// récupération du form d'édition de la collection
	var action = new http_request();
	var url = "./ajax.php?module=ajax&categ=coll_modif&id="+id+"&quoifaire=coll_save&texte="+texte+"&sql_field="+sql_field;	
	action.request(url);
}


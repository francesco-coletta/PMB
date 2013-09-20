<?php
// +-------------------------------------------------+
//  2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: addon.inc.php,v 1.2.2.9 2013-09-06 08:00:11 apetithomme Exp $

if (stristr($_SERVER['REQUEST_URI'], ".inc.php")) die("no access");

function traite_rqt($requete="", $message="") {

	global $dbh;
	$retour="";
	$res = mysql_query($requete, $dbh) ; 
	$erreur_no = mysql_errno();
	if (!$erreur_no) {
		$retour = "Successful";
	} else {
		switch ($erreur_no) {
			case "1060":
				$retour = "Field already exists, no problem.";
				break;
			case "1061":
				$retour = "Key already exists, no problem.";
				break;
			case "1091":
				$retour = "Object already deleted, no problem.";
				break;
			default:
				$retour = "<font color=\"#FF0000\">Error may be fatal : <i>".mysql_error()."<i></font>";
				break;
			}
	}		
	return "<tr><td><font size='1'>".$message."</font></td><td><font size='1'>".$retour."</font></td></tr>";
}
echo "<table>";

/******************** AJOUTER ICI LES MODIFICATIONS *******************************/
// MB - Indexer la colonne num_renvoi_voir de la table noeuds
$rqt = "ALTER TABLE noeuds DROP INDEX i_num_renvoi_voir";
echo traite_rqt($rqt,"ALTER TABLE noeuds DROP INDEX i_num_renvoi_voir");
$rqt = "ALTER TABLE noeuds ADD INDEX i_num_renvoi_voir (num_renvoi_voir)";
echo traite_rqt($rqt,"ALTER TABLE noeuds ADD INDEX i_num_renvoi_voir (num_renvoi_voir)");
// FT - Ajout des paramètres pour forcer les tags meta pour les moteurs de recherche
if (mysql_num_rows(mysql_query("select 1 from parametres where type_param= 'opac' and sstype_param='meta_description' "))==0){
	$rqt="insert into parametres(type_param,sstype_param,valeur_param,comment_param,section_param,gestion) values('opac','meta_description','','Contenu du meta tag description pour les moteurs de recherche','b_aff_general',0)";
	echo traite_rqt($rqt,"INSERT INTO parametres opac_meta_description");
}
if (mysql_num_rows(mysql_query("select 1 from parametres where type_param= 'opac' and sstype_param='meta_keywords' "))==0){
	$rqt="insert into parametres(type_param,sstype_param,valeur_param,comment_param,section_param,gestion) values('opac','meta_keywords','','Contenu du meta tag keywords pour les moteurs de recherche','b_aff_general',0)";
	echo traite_rqt($rqt,"INSERT INTO parametres opac_meta_keywords");
}	
if (mysql_num_rows(mysql_query("select 1 from parametres where type_param= 'opac' and sstype_param='meta_author' "))==0){
	$rqt="insert into parametres(type_param,sstype_param,valeur_param,comment_param,section_param,gestion) values('opac','meta_author','','Contenu du meta tag author pour les moteurs de recherche','b_aff_general',0)";
	echo traite_rqt($rqt,"INSERT INTO parametres opac_meta_author");
}

//DG - autoriser le code HTML dans les cotes exemplaires
if (mysql_num_rows(mysql_query("select 1 from parametres where type_param= 'pmb' and sstype_param='html_allow_expl_cote' "))==0){
	$rqt = "INSERT INTO parametres (id_param, type_param, sstype_param, valeur_param, comment_param, section_param, gestion)
			VALUES (0, 'pmb', 'html_allow_expl_cote', '0', 'Autoriser le code HTML dans les cotes exemplaires ? \n 0 : non \n 1', '',0) ";
	echo traite_rqt($rqt, "insert pmb_html_allow_expl_cote=0 into parametres");
}
//maj valeurs possibles pour empr_sort_rows
$rqt = "update parametres set comment_param='Colonnes qui seront disponibles pour le tri des emprunteurs. Les colonnes possibles sont : \n n: nom+prénom \n b: code-barres \n c: catégories \n g: groupes \n l: localisation \n s: statut \n cp: code postal \n v: ville \n y: année de naissance \n ab: type d\'abonnement \n #n : id des champs personnalisés' where type_param= 'empr' and sstype_param='sort_rows' ";
echo traite_rqt($rqt,"update empr_sort_rows into parametres");


// MB - Création d'une table de cache pour les cadres du portail pour accélérer l'affichage
// MB - Un TEXT pour cache_cadre_content ne suffisait pas ;-)
$rqt = "DROP TABLE IF EXISTS cms_cache_cadres";
echo traite_rqt($rqt,"DROP TABLE IF EXISTS cms_cache_cadres");
$rqt = "CREATE TABLE  cms_cache_cadres (
		cache_cadre_hash VARCHAR( 32 ) NOT NULL,
		cache_cadre_type_content VARCHAR(30) NOT NULL,
		cache_cadre_create_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
		cache_cadre_content MEDIUMTEXT NOT NULL,
		PRIMARY KEY (  cache_cadre_hash, cache_cadre_type_content )
	);";
echo traite_rqt($rqt,"CREATE TABLE cms_cache_cadres");

// AP - Ajout de la gestion de l'ordre dans le contenu éditorial
$rqt = "ALTER TABLE cms_sections ADD section_order INT UNSIGNED default 0";
echo traite_rqt($rqt,"alter table cms_sections add section_order");

$rqt = "ALTER TABLE cms_articles ADD article_order INT UNSIGNED default 0";
echo traite_rqt($rqt,"alter table cms_articles add article_order");



/******************** JUSQU'ICI **************************************************/
/* PENSER à  faire +1 au paramètre $pmb_subversion_database_as_it_shouldbe dans includes/config.inc.php */
/* COMMITER les deux fichiers addon.inc.php ET config.inc.php en même temps */

echo traite_rqt("update parametres set valeur_param='".$pmb_subversion_database_as_it_shouldbe."' where type_param='pmb' and sstype_param='bdd_subversion'","Update to $pmb_subversion_database_as_it_shouldbe database subversion.");
echo "<table>";
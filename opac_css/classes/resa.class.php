<?php
// +-------------------------------------------------+
// � 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: resa.class.php,v 1.14 2012-08-20 08:06:32 ngantier Exp $

// classe emprunteur
// classe remani�e le 7.12.2003. : prise en compte de r�sas sur le bulletinage

if ( ! defined( 'RESA_CLASS' ) ) {
  define( 'RESA_CLASS', 1 );

class reservation {

//---------------------------------------------------------
//			Propri�t�s
//---------------------------------------------------------
var $id			= 0		;	// id MySQL de la r�servation
var $id_empr		= 0		;	// id MySQL emprunteur
var $id_notice		= 0		;	// id notice
var $id_bulletin	= 0		;	// id bulletin si applicable
var $tstamp		= ''		;	// time stamp de la r�servation
var $message		= ''		;	// message d'erreur �ventuel
var $statut		= 0		;	// statuts possibles
var $notice		= ''		;	// notice abr�g�e (pour affichage)

/* note les statuts possibles :
0	->	aucun probl�me pour r�server
1	->	aucun exemplaire ne peut �tre reserv�
2	->	un ou des exemplaires peuvent �tre reserv�s et un au moins des exemplaires est disponible
*/

//---------------------------------------------------------
//			M�thodes
//---------------------------------------------------------

// <----------------- constructeur ------------------>
function reservation($id_empr=0, $id_notice=0, $bulletinage=0) {
	$this->id_empr = $id_empr;
	$this->id_notice = $id_notice;
	if($bulletinage) {
		$this->id_bulletin = $bulletinage;
		$this->id_notice = 0;
	}
}

function check_expl_reservable($id_expl) {
	global $dbh;
	$query = "select e.expl_cb as cb, e.expl_id as id, s.statut_allow_resa as reservable, e.expl_notice as notice, e.expl_bulletin as bulletin, e.expl_note as note, expl_comment, s.statut_libelle as statut";
	$query .= " from exemplaires e, docs_statut s";
	$query .= " where e.expl_id=$id_expl";
	$query .= " and s.idstatut=e.expl_statut";
	$query .= " limit 1";

	$result = mysql_query($query, $dbh);
	if (($expl = mysql_fetch_array($result))) {			
		if (!$expl['reservable']) {
			// l'exemplaire est en consultation sur place ou pas r�servable
			return 0;				
		}
	} else {
		// exemplaire inconnu
		return 0;
	}

	// on check si l'exemplaire a une r�servation
	$query = "select resa_idempr as empr, id_resa, resa_cb, concat(ifnull(concat(empr_nom,' '),''),empr_prenom) as nom_prenom, empr_cb from resa left join empr on resa_idempr=id_empr where resa_idnotice='$expl->notice' and resa_idbulletin='$expl->bulletin' order by resa_date limit 1";
	$result = mysql_query($query, $dbh);
	if (mysql_num_rows($result)) { 
		// l'exemplaire a une r�servation
		return 0;
		
	}
	// l'exemplaire est disponible pour valider une r�servation
	return 1;
}
		
// <----------------- add() : ajout d'une r�servation ------------------>
function add() {
	global $dbh;
	global $msg;
	// quelques v�rifications d'usage
	if (!$this->empr_exists() || !$this->notice_exists() || $this->resa_exists() || $this->allready_loaned())
		return FALSE;
	// check_statut inclus la possibilit� de r�server ou pas les docs dispo 
	if($this->check_statut())
		return FALSE;
	// tout est OK, �criture de la r�servation en table
	$query = "insert into resa (id_resa, resa_idempr, resa_idnotice, resa_idbulletin, resa_date) ";
	$query .= "values (";
	if($this->id_notice) $query .= "'', '".$this->id_empr."', '".$this->id_notice."',0 , NOW())";
	elseif ($this->id_bulletin)
		$query .= "'', '".$this->id_empr."',0 ,'".$this->id_bulletin."', NOW())";
	$result = mysql_query($query, $dbh);
	
	if(!$result) {
		$this->message = "$query ". $msg["resa_no_create"];
	} else {
		$this->message = $msg["resa_ajoutee"];
		$this->id = mysql_insert_id($dbh);

		// Archivage de la r�sa: info lecteur et notice et nombre d'exemplaire
		$rqt = "SELECT * FROM empr WHERE id_empr=".$this->id_empr;
		$empr = mysql_fetch_object(mysql_query($rqt));	
				
		$id_notice=$id_bulletin=0;
		if($this->id_notice) {
			$id_notice=	$this->id_notice;
			$query = "SELECT count(*) FROM exemplaires where expl_notice='$id_notice'";
		}elseif($this->id_bulletin) {
			$id_bulletin=$this->id_bulletin;
			$query = "SELECT count(*) FROM exemplaires where expl_bulletin='$id_bulletin'";
		}
		$nb_expl = mysql_result(mysql_query($query),0);
		
		$query = "INSERT INTO resa_archive SET
			resarc_id_empr = '".$this->id_empr."', 
			resarc_idnotice = '".$id_notice."', 
			resarc_idbulletin = '".$id_bulletin."',
			resarc_date = SYSDATE(), 
			resarc_loc_retrait = '0',
			resarc_from_opac= '1',
			resarc_empr_cp ='".addslashes($empr->empr_cp)."',
			resarc_empr_ville = '".addslashes($empr->empr_ville)."',
			resarc_empr_prof = '".addslashes($empr->empr_prof)."',
			resarc_empr_year = '".$empr->empr_year."',
			resarc_empr_categ = '".$empr->empr_categ."',
			resarc_empr_codestat = '".$empr->empr_codestat ."',
			resarc_empr_sexe = '".$empr->empr_sexe."',
			resarc_empr_location = '".$empr->empr_location."',
			resarc_expl_nb = '$nb_expl'		
		 ";
		mysql_query($query, $dbh);
		$stat_id = mysql_insert_id($dbh);
		// Lier achive et r�sa pour suivre l'�volution de la r�sa
		$query = "update resa SET resa_arc='$stat_id' where id_resa='".$this->id."'";
		mysql_query($query, $dbh);		
	}
}

// <----------------- delete() : suppression d'une r�servation ------------------>
function delete() {
	global $dbh;
	global $msg;
	//  suppression  de la r�servation de la table des r�servations
	$id_notice=$id_bulletin=0;
	if($this->id_notice) {
		$id_notice=$this->id_notice;
		$query = "delete from resa where resa_idempr=".$this->id_empr." and resa_idnotice=".$this->id_notice;
	} elseif($this->id_bulletin) {
		$id_bulletin=$this->id_bulletin;
		$query = "delete from resa where resa_idempr=".$this->id_empr." and resa_idbulletin=".$this->id_bulletin;
	}	
	$result = @mysql_query($query, $dbh);
	// archivage resa
	$rqt_arch = "UPDATE resa_archive SET resarc_anulee = 1 WHERE resarc_id_empr = '".$this->id_empr."' and resarc_idnotice = '".$id_notice."' and	resarc_idbulletin = '".$id_bulletin."' "; 
	mysql_query($rqt_arch, $dbh);
	
	if(!$result) {
		$this->message = $msg[resa_no_suppr];
		return FALSE;
	} else {
		// on checke l'existence d'autres r�servataires
		$query = "select e.empr_nom, e.empr_prenom, e.empr_cb from resa r, empr e";
		$query .= " where r.resa_idempr=e.id_empr";
		if($this->id_notice)
			$query .= " and r.resa_idnotice=".$this->id_notice;
		elseif($this->id_bulletin)
			$query .= " and r.resa_idbulletin=".$this->id_bulletin;
		$query .= " order by r.resa_date limit 1";
		$result = mysql_query($query, $dbh);
		if(mysql_num_rows($result)) {

			// d'autres r�servataires existent
			$next_empr = mysql_fetch_object($result);

			$this->message = $msg[resa_supprimee];

			// on regarde la disponibilit� du document
			// on compte le nombre total d'exemplaires pour la notice
			if($this->id_notice)
				$query = "select count(1) from exemplaires where expl_notice=".$this->id_notice;
			elseif($this->id_bulletin)
				$query = "select count(1) from exemplaires where expl_bulletin=".$this->id_bulletin;
			$result = mysql_query($query, $dbh);
			$total_ex = mysql_result($result, 0, 0);

			// on compte le nombre d'exemplaires sortis
			$query = "select count(1) from exemplaires e, pret p";
			if($this->id_notice)
				$query .= " where e.expl_notice=".$this->id_notice;
			elseif($this->id_bulletin)
				$query .= " where e.expl_bulletin=".$this->id_bulletin;			
			$query .= " and p.pret_idexpl=e.expl_id";
			$result = mysql_query($query, $dbh);
			$total_sortis = mysql_result($result, 0, 0);

			// on en d�duit le nombre d'exemplaires disponibles
			$total_dispo = $total_ex - $total_sortis;

			if($total_dispo) {
				$this->message .= " <strong> $msg[resa_dispo_suivant] ";
				$this->message .= $next_empr->empr_nom." ".$next_empr->empr_prenom."</strong>. (".$next_empr->empr_cb.")";
				}
			return TRUE;
		} else {
			$this->message = $msg[resa_supprimee];
			return TRUE;
		}
	}
}

// <----------------- empr_exists() : v�rification de l'existence de l'utilisateur ------------------>
function empr_exists() {
	global $dbh;
	global $msg;
	$query = "select count(1) from empr where id_empr=".$this->id_empr;
	$result = @mysql_query($query, $dbh);
	if(!@mysql_result($result, 0, 0)) {
		$this->message = "<strong>$msg[resa_no_empr]</strong>";
		return FALSE;
		}
	return TRUE;
	}

// <----------------- notice_exists() : v�rification de l'existence de la notice  ou du bulletinage ------------------>
function notice_exists() {
	global $dbh;
	global $msg;
	if($this->id_notice) $query = "select count(1) from notices where notice_id=".$this->id_notice;
		elseif ($this->id_bulletin)
			$query = "select count(1) from bulletins where bulletin_id=".$this->id_bulletin;
	$result = @mysql_query($query, $dbh);
	if(!@mysql_result($result, 0, 0)) {
		$this->message = "<strong>$msg[resa_no_doc]</strong>";
		return FALSE;
		}
	return TRUE;
	}

// <----------------- resa_exists() : v�rification de l'existence de la r�servation ------------------>
function resa_exists() {
	global $dbh;
	global $msg;
	$query = "select count(1) from resa where resa_idempr=".$this->id_empr;
	if($this->id_notice) $query .= " and resa_idnotice=".$this->id_notice;
		elseif ($this->id_bulletin)
			$query .= " and resa_idbulletin=".$this->id_bulletin;
	$result = @mysql_query($query, $dbh);
	if(@mysql_result($result, 0, 0)) {
		$this->message = "<strong>$msg[resa_deja_resa]</strong>";
		return TRUE;
		}
	return FALSE;
	}

// <----------------- allready_loaned() : on regarde si l'emprunteur n'a pas d�j� ce document ------------------>
function allready_loaned() {
	global $dbh;
	global $msg;
	$query = "select count(1) from pret p, exemplaires e";
	$query .= " where p.pret_idempr=".$this->id_empr;
	$query .= " and p.pret_idexpl=e.expl_id";
	if($this->id_notice)
		$query .= " and e.expl_notice=".$this->id_notice;
	elseif ($this->id_bulletin)
		$query .= " and e.expl_bulletin=".$this->id_bulletin;
	$result = @mysql_query($query, $dbh);
	if(@mysql_result($result, 0, 0)) {
		$this->message = "<strong>$msg[resa_deja_doc]</strong>";
		return TRUE;
	}
	return FALSE;
}

// <----------------- check_statut() : le genre de choses qu'on peut attendre en retour ------------------>
/* fonction complexe � rediscuter : cas possibles :
- doc en consultation sur place uniquement
- doc mixed : exemplaire(s) en consultation sur place et exemplaire(s) en circulation
- doc en circulation ET disponible
La solution retenue : fetcher tous les exemplaires attach�s � la notice et d�finir des flags de situation
*/
function check_statut() {
	global $dbh;
	global $pmb_resa_dispo; // les r�sa de disponibles sont-elles autoris�es ?
	global $msg;
	
	if ($this->id_notice) {
		$notice = new notice_affichage($this->id_notice,0,0,0);
		$notice->do_header();
		$this->notice = $notice->notice_header;
	} elseif ($this->id_bulletin) {
		$bulletin = new bulletinage($this->id_bulletin, 0);
		$this->notice = $bulletin->display;
	}

	// on checke s'il y a des exemplaires pr�tables
	$query = "select expl_id from exemplaires e, docs_statut s";
	if($this->id_notice) $query .= " where s.idstatut=e.expl_statut and s.pret_flag=1 and e.expl_notice=".$this->id_notice;
	elseif ($this->id_bulletin)
	$query .= " where s.idstatut=e.expl_statut and s.pret_flag=1 and e.expl_bulletin=".$this->id_bulletin;
	$result = mysql_query($query, $dbh);
	if(!@mysql_num_rows($result)) {
		// aucun exemplaire n'est disponible pour le pr�t
		$this->message .= "$msg[resa]&nbsp;:&nbsp;".$this->notice."<br /><strong>$msg[resa_no_expl]</strong>";
		return 1;
	}
	
	// on regarde si les r�sa de disponibles sont autoris�es
	if ($pmb_resa_dispo=="1") return 0;
	
	// on checke si un exemplaire est disponible
	// aka. si un des exemplaires en circulation n'est pas mentionn� dans la table des pr�ts,
	// c'est qu'il est disponible � la biblioth�que
	$list_dispo = '';

	while($pretable = mysql_fetch_object($result)) {
		$req2 = "select count(1) from pret where pret_idexpl=".$pretable->expl_id;
		$req2_result = mysql_query($req2, $dbh);
		if(!mysql_result($req2_result, 0, 0)) {
			// l'exemplaire ne figure pas dans la table pret -> dispo
			// on r�cup�re les donn�es exemplaires pour constituer le message
			$req3 = "select p.expl_cote, s.section_libelle, l.location_libelle";
			$req3 .= " from exemplaires p, docs_section s, docs_location l";
			$req3 .= " where p.expl_id=".$pretable->expl_id;
			$req3 .= " and s.idsection=p.expl_section";
			$req3 .= " and l.idlocation=p.expl_location limit 1";
			$req3_result = mysql_query($req3, $dbh);
			$req3_obj = mysql_fetch_object($req3_result);
			if($req3_obj->expl_cote) {
				$list_dispo .= '<br />'.$req3_obj->location_libelle.'.';
				$list_dispo .= $req3_obj->section_libelle.' cote&nbsp;: '.$req3_obj->expl_cote;
			}
		}
	}

	if($list_dispo) {
		$this->message = "$msg[resa_doc_dispo]<br />";
		$this->message .= $this->notice.$list_dispo;
		return 2;
	}

	// rien de sp�cial
	return  0;
}

} # fin de d�claration classe reservation

} # fin de d�finition

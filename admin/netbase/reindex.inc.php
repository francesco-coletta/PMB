<?php
// +-------------------------------------------------+
// � 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: reindex.inc.php,v 1.30 2013-03-14 15:16:39 mbertin Exp $

if (stristr($_SERVER['REQUEST_URI'], ".inc.php")) die("no access");

require_once($class_path."/notice.class.php");

// la taille d'un paquet de notices
$lot = REINDEX_PAQUET_SIZE; // defini dans ./params.inc.php

// taille de la jauge pour affichage
$jauge_size = GAUGE_SIZE;
$jauge_size .= "px";

// initialisation de la borne de d�part
if (!isset($start)) $start=0;

$v_state=urldecode($v_state);

// on commence par :
if (!isset($index_quoi)) $index_quoi='NOTICES';

switch ($index_quoi) {
	case 'NOTICES':
	
		if (!$count) {
			$notices = mysql_query("SELECT count(1) FROM notices", $dbh);
			$count = mysql_result($notices, 0, 0);
		}
		
		print "<br /><br /><h2 align='center'>".htmlentities($msg["nettoyage_reindex_notices"], ENT_QUOTES, $charset)."</h2>";
		
		$query = mysql_query("SELECT notice_id FROM notices LIMIT $start, $lot");
		if(mysql_num_rows($query)) {
		
			// d�finition de l'�tat de la jauge
			$state = floor($start / ($count / $jauge_size));
			$state .= "px";
			// mise � jour de l'affichage de la jauge
			print "<table border='0' align='center' width='$jauge_size' cellpadding='0'><tr><td class='jauge' width='100%'>";
			print "<img src='../../images/jauge.png' width='$state' height='16px'></td></tr></table>";
			
			// calcul pourcentage avancement
			$percent = floor(($start/$count)*100);
			
			// affichage du % d'avancement et de l'�tat
			print "<div align='center'>$percent%</div>";
			
			while(($row = mysql_fetch_object($query))) {
				// constitution des pseudo-indexes
				notice::majNotices($row->notice_id);
			}
			mysql_free_result($query);
			$next = $start + $lot;
			print "
				<form class='form-$current_module' name='current_state' action='./clean.php' method='post'>
				<input type='hidden' name='v_state' value=\"".urlencode($v_state)."\">
				<input type='hidden' name='spec' value=\"$spec\">
				<input type='hidden' name='start' value=\"$next\">
				<input type='hidden' name='count' value=\"$count\">
				<input type='hidden' name='index_quoi' value=\"NOTICES\">
				</form>
				<script type=\"text/javascript\"><!-- 
					setTimeout(\"document.forms['current_state'].submit()\",1000); 
					-->
				</script>";
		} else {
				// mise � jour de l'affichage de la jauge
				print "<table border='0' align='center' width='$table_size' cellpadding='0'><tr><td class='jauge'>";
				print "<img src='../../images/jauge.png' width='$jauge_size' height='16'></td></tr></table>";
				print "<div align='center'>100%</div>";
				$v_state .= "<br /><img src=../../images/d.gif hspace=3>".htmlentities($msg["nettoyage_reindex_notices"], ENT_QUOTES, $charset)." $count ".htmlentities($msg["nettoyage_res_reindex_notices"], ENT_QUOTES, $charset);
				print "
					<form class='form-$current_module' name='current_state' action='./clean.php' method='post'>
					<input type='hidden' name='v_state' value=\"".urlencode($v_state)."\">
					<input type='hidden' name='spec' value=\"$spec\">
					<input type='hidden' name='start' value='0'>
					<input type='hidden' name='count' value='0'>
					<input type='hidden' name='index_quoi' value=\"AUTEURS\">
					</form>
					<script type=\"text/javascript\"><!-- 
						setTimeout(\"document.forms['current_state'].submit()\",1000); 
						-->
					</script>";	
		}
	
		break ;
	
	case 'AUTEURS':
		if (!$count) {
			$elts = mysql_query("SELECT count(1) FROM authors", $dbh);
			$count = mysql_result($elts, 0, 0);
		}
		
		print "<br /><br /><h2 align='center'>".htmlentities($msg["nettoyage_reindex_authors"], ENT_QUOTES, $charset)."</h2>";
		
		$query = mysql_query("SELECT author_id as id,concat(author_name,' ',author_rejete,' ', author_lieu, ' ',author_ville,' ',author_pays,' ',author_numero,' ',author_subdivision) as auteur from authors LIMIT $start, $lot", $dbh);
		if (mysql_num_rows($query)) {
		
			// d�finition de l'�tat de la jauge
			$state = floor($start / ($count / $jauge_size));
			
			// mise � jour de l'affichage de la jauge
			print "<table border='0' align='center' width='$jauge_size' cellpadding='0'><tr><td class='jauge'>";
			print "<img src='../../images/jauge.png' width='$state' height='16'></td></tr></table>";
			
			// calcul pourcentage avancement
			$percent = floor(($start/$count)*100);
			
			// affichage du % d'avancement et de l'�tat
			print "<div align='center'>$percent%</div>";
			
			while(($row = mysql_fetch_object($query))) {
				// constitution des pseudo-indexes
				$ind_elt = strip_empty_chars($row->auteur); 
				$req_update = "UPDATE authors ";
				$req_update .= " SET index_author=' ${ind_elt} '";
				$req_update .= " WHERE author_id=$row->id ";
				$update = mysql_query($req_update, $dbh);
				}
			mysql_free_result($query);
			$next = $start + $lot;
			print "
				<form class='form-$current_module' name='current_state' action='./clean.php' method='post'>
				<input type='hidden' name='v_state' value=\"".urlencode($v_state)."\">
				<input type='hidden' name='spec' value=\"$spec\">
				<input type='hidden' name='start' value=\"$next\">
				<input type='hidden' name='count' value=\"$count\">
				<input type='hidden' name='index_quoi' value=\"AUTEURS\">
				</form>
				<script type=\"text/javascript\"><!-- 
					setTimeout(\"document.forms['current_state'].submit()\",1000); 
					-->
				</script>";
		} else {
			// mise � jour de l'affichage de la jauge
			print "<table border='0' align='center' width='$table_size' cellpadding='0'><tr><td class='jauge'>";
			print "<img src='../../images/jauge.png' width='$jauge_size' height='16'></td></tr></table>";
			print "<div align='center'>100%</div>";
			$v_state .= "<br /><img src=../../images/d.gif hspace=3>".htmlentities($msg["nettoyage_reindex_authors"], ENT_QUOTES, $charset)." $count ".htmlentities($msg["nettoyage_res_reindex_authors"], ENT_QUOTES, $charset);
			print "
				<form class='form-$current_module' name='current_state' action='./clean.php' method='post'>
				<input type='hidden' name='v_state' value=\"".urlencode($v_state)."\">
				<input type='hidden' name='spec' value=\"$spec\">
				<input type='hidden' name='start' value='0'>
				<input type='hidden' name='count' value='0'>
				<input type='hidden' name='index_quoi' value=\"EDITEURS\">
				</form>
				<script type=\"text/javascript\"><!-- 
					setTimeout(\"document.forms['current_state'].submit()\",1000); 
					-->
				</script>";	
		}
		break ;
	
	case 'EDITEURS':
		if (!$count) {
			$elts = mysql_query("SELECT count(1) FROM publishers", $dbh);
			$count = mysql_result($elts, 0, 0);
		}
		
		print "<br /><br /><h2 align='center'>".htmlentities($msg["nettoyage_reindex_publishers"], ENT_QUOTES, $charset)."</h2>";
		
		$query = mysql_query("SELECT ed_id as id, ed_name as publisher, ed_ville, ed_pays from publishers LIMIT $start, $lot");
		if (mysql_num_rows($query)) {
		
			// d�finition de l'�tat de la jauge
			$state = floor($start / ($count / $jauge_size));
			
			// mise � jour de l'affichage de la jauge
			print "<table border='0' align='center' width='$jauge_size' cellpadding='0'><tr><td class='jauge'>";
			print "<img src='../../images/jauge.png' width='$state' height='16'></td></tr></table>";
			
			// calcul pourcentage avancement
			$percent = floor(($start/$count)*100);
			
			// affichage du % d'avancement et de l'�tat
			print "<div align='center'>$percent%</div>";
			
			while(($row = mysql_fetch_object($query))) {
				// constitution des pseudo-indexes
				$ind_elt = strip_empty_chars($row->publisher." ".$row->ed_ville." ".$row->ed_pays); 
				$req_update = "UPDATE publishers ";
				$req_update .= " SET index_publisher=' ${ind_elt} '";
				$req_update .= " WHERE ed_id=$row->id ";
				$update = mysql_query($req_update);
				}
			mysql_free_result($query);
			$next = $start + $lot;
			print "
				<form class='form-$current_module' name='current_state' action='./clean.php' method='post'>
				<input type='hidden' name='v_state' value=\"".urlencode($v_state)."\">
				<input type='hidden' name='spec' value=\"$spec\">
				<input type='hidden' name='start' value=\"$next\">
				<input type='hidden' name='count' value=\"$count\">
				<input type='hidden' name='index_quoi' value=\"EDITEURS\">
				</form>
				<script type=\"text/javascript\"><!-- 
					setTimeout(\"document.forms['current_state'].submit()\",1000); 
					-->
				</script>";
		} else {
			// mise � jour de l'affichage de la jauge
			print "<table border='0' align='center' width='$table_size' cellpadding='0'><tr><td class='jauge'>";
			print "<img src='../../images/jauge.png' width='$jauge_size' height='16'></td></tr></table>";
			print "<div align='center'>100%</div>";
			$v_state .= "<br /><img src=../../images/d.gif hspace=3>".htmlentities($msg["nettoyage_reindex_publishers"], ENT_QUOTES, $charset)." $count ".htmlentities($msg["nettoyage_res_reindex_publishers"], ENT_QUOTES, $charset);
			print "
				<form class='form-$current_module' name='current_state' action='./clean.php' method='post'>
				<input type='hidden' name='v_state' value=\"".urlencode($v_state)."\">
				<input type='hidden' name='spec' value=\"$spec\">
				<input type='hidden' name='start' value='0'>
				<input type='hidden' name='count' value='0'>
				<input type='hidden' name='index_quoi' value=\"CATEGORIES\">
				</form>
				<script type=\"text/javascript\"><!-- 
					setTimeout(\"document.forms['current_state'].submit()\",1000); 
					-->
				</script>";	
		}
		break ;
	
	case 'CATEGORIES':
		if (!$count) {
			$elts = mysql_query("SELECT count(1) FROM categories", $dbh);
			$count = mysql_result($elts, 0, 0);
		}
		
		print "<br /><br /><h2 align='center'>".htmlentities($msg["nettoyage_reindex_categories"], ENT_QUOTES, $charset)."</h2>";
		
		$req = "select num_noeud, langue, libelle_categorie from categories limit $start, $lot ";
		$query = mysql_query($req, $dbh);
		 
		if (mysql_num_rows($query)) {
		
			// d�finition de l'�tat de la jauge
			$state = floor($start / ($count / $jauge_size));
			
			// mise � jour de l'affichage de la jauge
			print "<table border='0' align='center' width='$jauge_size' cellpadding='0'><tr><td class='jauge'>";
			print "<img src='../../images/jauge.png' width='$state' height='16'></td></tr></table>";
			
			// calcul pourcentage avancement
			$percent = floor(($start/$count)*100);
			
			// affichage du % d'avancement et de l'�tat
			print "<div align='center'>$percent%</div>";
			
			while($row = mysql_fetch_object($query)) {
				// constitution des pseudo-indexes
				$ind_elt = strip_empty_words($row->libelle_categorie, $row->langue); 
				
				$req_update = "UPDATE categories ";
				$req_update.= "SET index_categorie=' ${ind_elt} '";
				$req_update.= "WHERE num_noeud='".$row->num_noeud."' and langue='".$row->langue."' ";
				$update = mysql_query($req_update);
			}
			mysql_free_result($query);
			$next = $start + $lot;
			print "
				<form class='form-$current_module' name='current_state' action='./clean.php' method='post'>
				<input type='hidden' name='v_state' value=\"".urlencode($v_state)."\">
				<input type='hidden' name='spec' value=\"$spec\">
				<input type='hidden' name='start' value=\"$next\">
				<input type='hidden' name='count' value=\"$count\">
				<input type='hidden' name='index_quoi' value=\"CATEGORIES\">
				</form>
				<script type=\"text/javascript\"><!-- 
					setTimeout(\"document.forms['current_state'].submit()\",1000); 
					-->
				</script>";
		} else {
			// mise � jour de l'affichage de la jauge
			print "<table border='0' align='center' width='$table_size' cellpadding='0'><tr><td class='jauge'>";
			print "<img src='../../images/jauge.png' width='$jauge_size' height='16'></td></tr></table>";
			print "<div align='center'>100%</div>";
			$v_state .= "<br /><img src=../../images/d.gif hspace=3>".htmlentities($msg["nettoyage_reindex_categories"], ENT_QUOTES, $charset)." $count ".htmlentities($msg["nettoyage_res_reindex_categories"], ENT_QUOTES, $charset);
			print "
				<form class='form-$current_module' name='current_state' action='./clean.php' method='post'>
				<input type='hidden' name='v_state' value=\"".urlencode($v_state)."\">
				<input type='hidden' name='spec' value=\"$spec\">
				<input type='hidden' name='start' value='0'>
				<input type='hidden' name='count' value='0'>
				<input type='hidden' name='index_quoi' value=\"COLLECTIONS\">
				</form>
				<script type=\"text/javascript\"><!-- 
					setTimeout(\"document.forms['current_state'].submit()\",1000); 
					-->
				</script>";	
		}
		break ;
	
	case 'COLLECTIONS':
		if (!$count) {
			$elts = mysql_query("SELECT count(1) FROM collections", $dbh);
			$count = mysql_result($elts, 0, 0);
		}
		
		print "<br /><br /><h2 align='center'>".htmlentities($msg["nettoyage_reindex_collections"], ENT_QUOTES, $charset)."</h2>";
		
		$query = mysql_query("SELECT collection_id as id, collection_name as collection, collection_issn from collections LIMIT $start, $lot");
		if (mysql_num_rows($query)) {
		
			// d�finition de l'�tat de la jauge
			$state = floor($start / ($count / $jauge_size));
			
			// mise � jour de l'affichage de la jauge
			print "<table border='0' align='center' width='$jauge_size' cellpadding='0'><tr><td class='jauge'>";
			print "<img src='../../images/jauge.png' width='$state' height='16'></td></tr></table>";
			
			// calcul pourcentage avancement
			$percent = floor(($start/$count)*100);
			
			// affichage du % d'avancement et de l'�tat
			print "<div align='center'>$percent%</div>";
			
			while(($row = mysql_fetch_object($query))) {
				// constitution des pseudo-indexes
				$ind_elt = strip_empty_words($row->collection); 
				if($tmp = $row->collection_issn){
					$ind_elt .= " ".strip_empty_words($tmp); 
				}
				
				$req_update = "UPDATE collections ";
				$req_update .= " SET index_coll=' ${ind_elt} '";
				$req_update .= " WHERE collection_id=$row->id ";
				$update = mysql_query($req_update);
			}
			mysql_free_result($query);
			$next = $start + $lot;
			print "
				<form class='form-$current_module' name='current_state' action='./clean.php' method='post'>
				<input type='hidden' name='v_state' value=\"".urlencode($v_state)."\">
				<input type='hidden' name='spec' value=\"$spec\">
				<input type='hidden' name='start' value=\"$next\">
				<input type='hidden' name='count' value=\"$count\">
				<input type='hidden' name='index_quoi' value=\"COLLECTIONS\">
				</form>
				<script type=\"text/javascript\"><!-- 
					setTimeout(\"document.forms['current_state'].submit()\",1000); 
					-->
				</script>";
		} else {
			// mise � jour de l'affichage de la jauge
			print "<table border='0' align='center' width='$table_size' cellpadding='0'><tr><td class='jauge'>";
			print "<img src='../../images/jauge.png' width='$jauge_size' height='16'></td></tr></table>";
			print "<div align='center'>100%</div>";
			$v_state .= "<br /><img src=../../images/d.gif hspace=3>".htmlentities($msg["nettoyage_reindex_collections"], ENT_QUOTES, $charset)." $count ".htmlentities($msg["nettoyage_res_reindex_collections"], ENT_QUOTES, $charset);
			print "
				<form class='form-$current_module' name='current_state' action='./clean.php' method='post'>
				<input type='hidden' name='v_state' value=\"".urlencode($v_state)."\">
				<input type='hidden' name='spec' value=\"$spec\">
				<input type='hidden' name='start' value='0'>
				<input type='hidden' name='count' value='0'>
				<input type='hidden' name='index_quoi' value=\"SOUSCOLLECTIONS\">
				</form>
				<script type=\"text/javascript\"><!-- 
					setTimeout(\"document.forms['current_state'].submit()\",1000); 
					-->
				</script>";	
		}
		break ;
	
	case 'SOUSCOLLECTIONS':
		if (!$count) {
			$elts = mysql_query("SELECT count(1) FROM sub_collections", $dbh);
			$count = mysql_result($elts, 0, 0);
		}
		
		print "<br /><br /><h2 align='center'>".htmlentities($msg["nettoyage_reindex_sub_collections"], ENT_QUOTES, $charset)."</h2>";
		
		$query = mysql_query("SELECT sub_coll_id as id, sub_coll_name as sub_collection, sub_coll_issn from sub_collections LIMIT $start, $lot");
		if (mysql_num_rows($query)) {
		
			// d�finition de l'�tat de la jauge
			$state = floor($start / ($count / $jauge_size));
			
			// mise � jour de l'affichage de la jauge
			print "<table border='0' align='center' width='$jauge_size' cellpadding='0'><tr><td class='jauge'>";
			print "<img src='../../images/jauge.png' width='$state' height='16'></td></tr></table>";
			
			// calcul pourcentage avancement
			$percent = floor(($start/$count)*100);
			
			// affichage du % d'avancement et de l'�tat
			print "<div align='center'>$percent%</div>";
			
			while(($row = mysql_fetch_object($query))) {
				// constitution des pseudo-indexes
				$ind_elt = strip_empty_words($row->sub_collection); 
				if($tmp = $row->sub_coll_issn){
					$ind_elt .= " ".strip_empty_words($tmp); 
				}
				$req_update = "UPDATE sub_collections ";
				$req_update .= " SET index_sub_coll=' ${ind_elt} '";
				$req_update .= " WHERE sub_coll_id=$row->id ";
				$update = mysql_query($req_update);
			}
			mysql_free_result($query);
			$next = $start + $lot;
			print "
				<form class='form-$current_module' name='current_state' action='./clean.php' method='post'>
				<input type='hidden' name='v_state' value=\"".urlencode($v_state)."\">
				<input type='hidden' name='spec' value=\"$spec\">
				<input type='hidden' name='start' value=\"$next\">
				<input type='hidden' name='count' value=\"$count\">
				<input type='hidden' name='index_quoi' value=\"SOUSCOLLECTIONS\">
				</form>
				<script type=\"text/javascript\"><!-- 
					setTimeout(\"document.forms['current_state'].submit()\",1000); 
					-->
				</script>";
		} else {
			// mise � jour de l'affichage de la jauge
			print "<table border='0' align='center' width='$table_size' cellpadding='0'><tr><td class='jauge'>";
			print "<img src='../../images/jauge.png' width='$jauge_size' height='16'></td></tr></table>";
			print "<div align='center'>100%</div>";
			$v_state .= "<br /><img src=../../images/d.gif hspace=3>".htmlentities($msg["nettoyage_reindex_sub_collections"], ENT_QUOTES, $charset)." $count ".htmlentities($msg["nettoyage_res_reindex_sub_collections"], ENT_QUOTES, $charset);
			print "
				<form class='form-$current_module' name='current_state' action='./clean.php' method='post'>
				<input type='hidden' name='v_state' value=\"".urlencode($v_state)."\">
				<input type='hidden' name='spec' value=\"$spec\">
				<input type='hidden' name='start' value='0'>
				<input type='hidden' name='count' value='0'>
				<input type='hidden' name='index_quoi' value=\"SERIES\">
				</form>
				<script type=\"text/javascript\"><!-- 
					setTimeout(\"document.forms['current_state'].submit()\",1000); 
					-->
				</script>";	
		}
		break ;
	
	case 'SERIES':
		if (!$count) {
			$elts = mysql_query("SELECT count(1) FROM series", $dbh);
			$count = mysql_result($elts, 0, 0);
		}
		
		print "<br /><br /><h2 align='center'>".htmlentities($msg["nettoyage_reindex_series"], ENT_QUOTES, $charset)."</h2>";
		
		$query = mysql_query("SELECT serie_id as id, serie_name from series LIMIT $start, $lot");
		if (mysql_num_rows($query)) {
		
			// d�finition de l'�tat de la jauge
			$state = floor($start / ($count / $jauge_size));
			
			// mise � jour de l'affichage de la jauge
			print "<table border='0' align='center' width='$jauge_size' cellpadding='0'><tr><td class='jauge'>";
			print "<img src='../../images/jauge.png' width='$state' height='16'></td></tr></table>";
			
			// calcul pourcentage avancement
			$percent = floor(($start/$count)*100);
			
			// affichage du % d'avancement et de l'�tat
			print "<div align='center'>$percent%</div>";
			
			while(($row = mysql_fetch_object($query))) {
				// constitution des pseudo-indexes
				$ind_elt = strip_empty_words($row->serie_name); 
				
				$req_update = "UPDATE series ";
				$req_update .= " SET serie_index=' ${ind_elt} '";
				$req_update .= " WHERE serie_id=$row->id ";
				$update = mysql_query($req_update);
			}
			mysql_free_result($query);
			$next = $start + $lot;
			print "
				<form class='form-$current_module' name='current_state' action='./clean.php' method='post'>
				<input type='hidden' name='v_state' value=\"".urlencode($v_state)."\">
				<input type='hidden' name='spec' value=\"$spec\">
				<input type='hidden' name='start' value=\"$next\">
				<input type='hidden' name='count' value=\"$count\">
				<input type='hidden' name='index_quoi' value=\"SERIES\">
				</form>
				<script type=\"text/javascript\"><!-- 
					setTimeout(\"document.forms['current_state'].submit()\",1000); 
					-->
				</script>";
		} else {
			// mise � jour de l'affichage de la jauge
			print "<table border='0' align='center' width='$table_size' cellpadding='0'><tr><td class='jauge'>";
			print "<img src='../../images/jauge.png' width='$jauge_size' height='16'></td></tr></table>";
			print "<div align='center'>100%</div>";
			$v_state .= "<br /><img src=../../images/d.gif hspace=3>".htmlentities($msg["nettoyage_reindex_series"], ENT_QUOTES, $charset)." $count ".htmlentities($msg["nettoyage_res_reindex_series"], ENT_QUOTES, $charset);
			print "
				<form class='form-$current_module' name='current_state' action='./clean.php' method='post'>
				<input type='hidden' name='v_state' value=\"".urlencode($v_state)."\">
				<input type='hidden' name='spec' value=\"$spec\">
				<input type='hidden' name='start' value='0'>
				<input type='hidden' name='count' value='0'>
				<input type='hidden' name='index_quoi' value=\"DEWEY\">
				</form>
				<script type=\"text/javascript\"><!-- 
					setTimeout(\"document.forms['current_state'].submit()\",1000); 
					-->
				</script>";	
		}
		break ;
	
	case 'DEWEY':
		if (!$count) {
			$elts = mysql_query("SELECT count(1) FROM indexint", $dbh);
			$count = mysql_result($elts, 0, 0);
		}
		
		print "<br /><br /><h2 align='center'>".htmlentities($msg["nettoyage_reindex_indexint"], ENT_QUOTES, $charset)."</h2>";
		
		$query = mysql_query("SELECT indexint_id as id, concat(indexint_name,' ',indexint_comment) as index_indexint from indexint LIMIT $start, $lot");
		if (mysql_num_rows($query)) {
		
			// d�finition de l'�tat de la jauge
			$state = floor($start / ($count / $jauge_size));
			
			// mise � jour de l'affichage de la jauge
			print "<table border='0' align='center' width='$jauge_size' cellpadding='0'><tr><td class='jauge'>";
			print "<img src='../../images/jauge.png' width='$state' height='16'></td></tr></table>";
			
			// calcul pourcentage avancement
			$percent = floor(($start/$count)*100);
			
			// affichage du % d'avancement et de l'�tat
			print "<div align='center'>$percent%</div>";
			
			while(($row = mysql_fetch_object($query))) {
				// constitution des pseudo-indexes
				$ind_elt = strip_empty_words($row->index_indexint); 
				
				$req_update = "UPDATE indexint ";
				$req_update .= " SET index_indexint=' ${ind_elt} '";
				$req_update .= " WHERE indexint_id=$row->id ";
				$update = mysql_query($req_update);
			}
			mysql_free_result($query);
			$next = $start + $lot;
			print "
				<form class='form-$current_module' name='current_state' action='./clean.php' method='post'>
				<input type='hidden' name='v_state' value=\"".urlencode($v_state)."\">
				<input type='hidden' name='spec' value=\"$spec\">
				<input type='hidden' name='start' value=\"$next\">
				<input type='hidden' name='count' value=\"$count\">
				<input type='hidden' name='index_quoi' value=\"DEWEY\">
				</form>
				<script type=\"text/javascript\"><!-- 
					setTimeout(\"document.forms['current_state'].submit()\",1000); 
					-->
				</script>";
		} else {
			// mise � jour de l'affichage de la jauge
			print "<table border='0' align='center' width='$table_size' cellpadding='0'><tr><td class='jauge'>";
			print "<img src='../../images/jauge.png' width='$jauge_size' height='16'></td></tr></table>";
			print "<div align='center'>100%</div>";
			$v_state .= "<br /><img src=../../images/d.gif hspace=3>".htmlentities($msg["nettoyage_reindex_indexint"], ENT_QUOTES, $charset)." $count ".htmlentities($msg["nettoyage_res_reindex_indexint"], ENT_QUOTES, $charset);
			print "
				<form class='form-$current_module' name='current_state' action='./clean.php' method='post'>
				<input type='hidden' name='v_state' value=\"".urlencode($v_state)."\">
				<input type='hidden' name='spec' value=\"$spec\">
				<input type='hidden' name='start' value='0'>
				<input type='hidden' name='count' value='0'>
				<input type='hidden' name='index_quoi' value=\"FRAIS_ANNEXES\">
				</form>
				<script type=\"text/javascript\"><!-- 
					setTimeout(\"document.forms['current_state'].submit()\",1000); 
					-->
				</script>";	
		}
		break ;
	
	case 'FRAIS_ANNEXES':
		if (!$count) {
			$elts = mysql_query("SELECT count(1) FROM frais", $dbh);
			$count = mysql_result($elts, 0, 0);
		}
		
		print "<br /><br /><h2 align='center'>".htmlentities($msg["nettoyage_reindex_frais_annexes"], ENT_QUOTES, $charset)."</h2>";
		
		$query = mysql_query("SELECT id_frais as id, libelle from frais LIMIT $start, $lot");
		if (mysql_num_rows($query)) {
		
			// d�finition de l'�tat de la jauge
			$state = floor($start / ($count / $jauge_size));
			
			// mise � jour de l'affichage de la jauge
			print "<table border='0' align='center' width='$jauge_size' cellpadding='0'><tr><td class='jauge'>";
			print "<img src='../../images/jauge.png' width='$state' height='16'></td></tr></table>";
			
			// calcul pourcentage avancement
			$percent = floor(($start/$count)*100);
			
			// affichage du % d'avancement et de l'�tat
			print "<div align='center'>$percent%</div>";
			
			while(($row = mysql_fetch_object($query))) {
				// constitution des pseudo-indexes
				$ind_elt = strip_empty_words($row->libelle); 
				
				$req_update = "UPDATE frais ";
				$req_update .= " SET index_libelle=' ${ind_elt} '";
				$req_update .= " WHERE id_frais=$row->id ";
				$update = mysql_query($req_update);
			}
			mysql_free_result($query);
			$next = $start + $lot;
			print "
				<form class='form-$current_module' name='current_state' action='./clean.php' method='post'>
				<input type='hidden' name='v_state' value=\"".urlencode($v_state)."\">
				<input type='hidden' name='spec' value=\"$spec\">
				<input type='hidden' name='start' value=\"$next\">
				<input type='hidden' name='count' value=\"$count\">
				<input type='hidden' name='index_quoi' value=\"FRAIS_ANNEXES\">
				</form>
				<script type=\"text/javascript\"><!-- 
					setTimeout(\"document.forms['current_state'].submit()\",1000); 
					-->
				</script>";
		} else {
			// mise � jour de l'affichage de la jauge
			print "<table border='0' align='center' width='$table_size' cellpadding='0'><tr><td class='jauge'>";
			print "<img src='../../images/jauge.png' width='$jauge_size' height='16'></td></tr></table>";
			print "<div align='center'>100%</div>";
			$v_state .= "<br /><img src=../../images/d.gif hspace=3>".htmlentities($msg["nettoyage_reindex_frais_annexes"], ENT_QUOTES, $charset)." $count ".htmlentities($msg["nettoyage_res_reindex_frais_annexes"], ENT_QUOTES, $charset);
			print "
				<form class='form-$current_module' name='current_state' action='./clean.php' method='post'>
				<input type='hidden' name='v_state' value=\"".urlencode($v_state)."\">
				<input type='hidden' name='spec' value=\"$spec\">
				<input type='hidden' name='start' value='0'>
				<input type='hidden' name='count' value='0'>
				<input type='hidden' name='index_quoi' value=\"FINI\">
				</form>
				<script type=\"text/javascript\"><!-- 
					setTimeout(\"document.forms['current_state'].submit()\",1000); 
					-->
				</script>";	
		}
		break ;

	case 'FINI':
		$spec = $spec - INDEX_NOTICES;
		$v_state .= "<br /><img src=../../images/d.gif hspace=3>".htmlentities($msg["nettoyage_reindex_fini"], ENT_QUOTES, $charset);
		print "
			<form class='form-$current_module' name='process_state' action='./clean.php?spec=$spec&start=0' method='post'>
				<input type='hidden' name='v_state' value=\"".urlencode($v_state)."\">
			</form>
			<script type=\"text/javascript\"><!--
				setTimeout(\"document.forms['process_state'].submit()\",1000);
				-->
			</script>";
		break ;
}

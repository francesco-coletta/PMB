<?php
// +-------------------------------------------------+
// � 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: print_thesaurus.php,v 1.14.2.1 2013-05-29 14:30:00 mbertin Exp $

$base_path = ".";
$base_auth = "AUTORITES_AUTH";
$base_title = $msg[print_thes_title];
$base_nobody=1;
$base_noheader=1;


require($base_path."/includes/init.inc.php");
@set_time_limit(0);
require_once("$class_path/thesaurus.class.php");
require_once("$class_path/noeuds.class.php");
require_once("$class_path/categories.class.php");
// constantes
			$color[1]="black";
			$color[2]="#c9e9ff"; // bleu
			$color[3]="#c6ffc5"; // vert
			$color[4]="#ffedc5"; // saumon
			$color[5]="#fcffc5"; // jaune
			$color[6]="#d7d8ff"; // violet
		
			$fontsize[1]=" font-size:1.2em; ";
			$fontsize[2]=" font-size:1.0em; ";
			$fontsize[3]=" font-size:0.9em; "; 
			$fontsize[4]=" font-size:0.8em; "; 
			$fontsize[5]=" font-size:0.8em; "; 
			$fontsize[6]=" font-size:0.8em; "; 
			$fontsize[7]=" font-size:0.8em; "; 
			$fontsize[8]=" font-size:0.8em; "; 
			$fontsize[9]=" font-size:0.8em; "; 

			$paddingmargin[0]=" padding-bottom: 10px; ";
			$paddingmargin[1]=" padding-bottom: 10px; ";
			$paddingmargin[2]=" padding-bottom: 8px; ";
			$paddingmargin[3]=" padding-bottom: 6px; ";
			$paddingmargin[4]=" ";
			$paddingmargin[5]=" ";
			$paddingmargin[6]=" ";
			$paddingmargin[7]=" ";
			$paddingmargin[8]=" ";
			$paddingmargin[9]=" ";


if ($action!="print") {
	print $std_header;
	print "<h3>".$msg[print_thes_title]."</h3>\n";
	print "<form name='print_options' action='print_thesaurus.php?action=print' method='post'>
		<b>".$msh[print_thes_options]."</b>
		<blockquote>".$msg[print_thes_list_type]."
			<select name='typeimpression'>";
	if ($id_noeud_origine){
		print "\n<option value='arbo' selected>".$msg[print_thes_arbo]."</option>
				<option value='alph' disabled>".$msg[print_thes_alph]."</option>
				<option value='rota' disabled>".$msg[print_thes_rota]."</option>";
		$val_enable="";
	}else{
		print "\n<option value='arbo' selected>".$msg[print_thes_arbo]."</option>
				<option value='alph' >".$msg[print_thes_alph]."</option>
				<option value='rota' >".$msg[print_thes_rota]."</option>";
		$val_enable="document.print_options.typeimpression.options[1].disabled = 0;print_options.typeimpression.options[2].disabled = 0;";
	}
	
	print "\n</select>
		</blockquote>
		<blockquote>
			<input type='checkbox' name='aff_note_application' CHECKED value='1' />&nbsp;".$msg[print_thes_na]."<br />
			<input type='checkbox' name='aff_commentaire' CHECKED value='1' />&nbsp;".$msg[print_thes_comment]."<br />
			<input type='checkbox' name='aff_voir' CHECKED value='1'/>&nbsp;".$msg[print_thes_voir]."<br />
			<input type='checkbox' name='aff_voir_aussi' CHECKED value='1'/>&nbsp;".$msg[print_thes_ta]."<br />
			<input type='checkbox' name='aff_tg' CHECKED value='1'/>&nbsp;".$msg[print_thes_tg]."<br />
			<input type='checkbox' name='aff_ts' CHECKED value='1'/>&nbsp;".$msg[print_thes_ts]."
		</blockquote>
		<b>".$msg["print_output_title"]."</b>
		<blockquote>
			<input type='radio' name='output' value='printer' checked onClick='".$val_enable."'/>&nbsp;".$msg["print_output_printer"]."<br />
			<input type='radio' name='output' value='tt' onClick=''".$val_enable."''/>&nbsp;".$msg["print_output_writer"]."<br />
			<input type='radio' name='output' value='xml' onClick='document.print_options.typeimpression.selectedIndex= 0;document.print_options.typeimpression.options[1].disabled = 1;print_options.typeimpression.options[2].disabled = 1;' />&nbsp;".$msg["print_output_xml"]."
		</blockquote>
		<input type='hidden' name='aff_langue' value='fr_FR'>
		<input type='hidden' name='id_noeud_origine' value='$id_noeud_origine'>
		<input type='hidden' name='aff_num_thesaurus' value='";
	if ($aff_num_thesaurus>0) print $aff_num_thesaurus;
	else die( "> Error with # of thesaurus");
	print "'><center><input type='submit' value='".$msg["print_print"]."' class='bouton'/>&nbsp;<input type='button' value='".$msg["print_cancel"]."' class='bouton' onClick='self.close();'/></center>";
	print "</body></html>";
	}

$rqlang = "select langue_defaut from thesaurus where id_thesaurus=".$aff_num_thesaurus ;
$reslang = mysql_query($rqlang) or die("<br />Query 'langue_defaut' failed ".mysql_error()."<br />".$rqlang);
$objlang = mysql_fetch_object($reslang);
if ($objlang->langue_defaut) $aff_langue = $objlang->langue_defaut;
else $aff_langue ="fr_FR";

if ($action=="print") {
	if($output=="xml"){
		header("Content-Type: text/xml; charset=utf-8");
		header("Content-Disposition: attachement; filename=thesaurus.xml");
		
		$thes = new thesaurus($aff_num_thesaurus);
		if($thes && $thes->num_noeud_racine){
			if($id_noeud_origine){
				$id_noeud_debut=$id_noeud_origine;
			}else{
				$id_noeud_debut=$thes->num_noeud_racine;
			}
			
			//Je peux commencer le th�saurus
			$dom = new DOMDocument('1.0', 'UTF-8');
			//$dom->preserveWhiteSpace = false;
		    $dom->formatOutput = true;
		    
		    $racine=creer_noeud_xml($dom,$dom,"THESAURII");
		    
		    $noeud=creer_noeud_xml($dom,$racine,"DATE_EX",date('Y-m-d\TH:i:s'));
			$noeudthes=creer_noeud_xml($dom,$racine,"THES");
			creer_noeud_xml($dom,$noeudthes,"LIB_THES",$thes->libelle_thesaurus);
			
			$res=categories::listChilds($id_noeud_debut, $aff_langue,1, "libelle_categorie");
		    if($res && mysql_num_rows($res)){
		    	while ($categ=mysql_fetch_object($res)) {
					if(trim($categ->libelle_categorie)){
						creer_categ_xml($dom,$noeudthes,0,$categ->num_noeud,$categ->libelle_categorie,$categ->note_application,$categ->comment_public,$categ->num_parent);
					}
				}
		    }
			//$dom->save('./temp/thesaurus_rra.xml');
			echo $dom->saveXML();			
		}else{
			die("Load thesaurus failed");
		}
	}else{
		if ($output=="tt") {
			header("Content-Type: application/word");
			header("Content-Disposition: attachement; filename=thesaurus.doc");
		}
		print "<html><body style='font-family : Arial, Helvetica, Verdana, sans-serif;'>";
		print "<h2>".affiche_text($msg["print_thes_titre_".$typeimpression])."</h2>";
		switch($typeimpression) {
			case "arbo":
				$res.="<td width=10% bgcolor='".$color[$niveau-9]."'> </td>";
				
				if ($id_noeud_origine) {
					// un noeud �tait fourni pour n'imprimer que cette branche
					$id_noeud_top = $id_noeud_origine ;
				} else {
					$rqt_id_noeud_top = "select id_noeud from noeuds where autorite='TOP' and num_thesaurus=".$aff_num_thesaurus ;
					$result_rqt_id_noeud_top = mysql_query($rqt_id_noeud_top) or die("Query 'TOP' failed");
					$obj_id_noeud_top = mysql_fetch_object($result_rqt_id_noeud_top);
					$id_noeud_top = $obj_id_noeud_top->id_noeud;
				}
				
				// premier parcours pour calculer la profondeur du th�saurus : $profondeurmax
				$niveau=0;
				$resultat="";
				$profondeurmax=0;
				enfants($id_noeud_top, $niveau, $resultat, $profondeurmax, false);
				/// deuxi�me parcours, cette fois-ci on imprime
				$niveau=0;
				$resultat="";
				echo "<table width=100% cellspacing=0 cellpadding=3>";
				enfants($id_noeud_top, $niveau, $resultat, $profondeurmax, true);
				echo "</table>" ;
				break;
			case "alph":
				$rqt = "select id_noeud from noeuds n, categories c where c.num_thesaurus=$aff_num_thesaurus and n.num_thesaurus=$aff_num_thesaurus and id_noeud=num_noeud and langue='$aff_langue' and autorite!='TOP' and autorite!='ORPHELINS' and autorite!='NONCLASSES' order by libelle_categorie ";
				$result = mysql_query($rqt) or die("Query alpha failed");
				while ($obj_id_noeud = mysql_fetch_object($result)){
					echo infos_categorie($obj_id_noeud->id_noeud);
				}
				break;
			case "rota":
				$mots=array();
				if (file_exists("$include_path/marc_tables/$aff_langue/empty_words_thesaurus")) {
					$mots_vides_thesaurus=true;
					include("$include_path/marc_tables/$aff_langue/empty_words_thesaurus");
				} else $mots_vides_thesaurus=false;  
				$rqt = "select id_noeud, libelle_categorie, index_categorie from noeuds n, categories c where c.num_thesaurus=$aff_num_thesaurus and n.num_thesaurus=$aff_num_thesaurus and id_noeud=num_noeud and langue='$aff_langue' and autorite!='TOP' and autorite!='ORPHELINS' and autorite!='NONCLASSES' order by libelle_categorie ";
				$result = mysql_query($rqt) or die("Query rota failed");
				while ($obj = mysql_fetch_object($result)) {
					// r�cup�ration de l'index du libell�, nettoyage
					$icat=$obj->index_categorie ;
					// si mots vides suppl�mentaires
					if ($mots_vides_thesaurus) {
						// suppression des mots vides
						if (is_array($empty_word_thesaurus)) {
							foreach($empty_word_thesaurus as $dummykey=>$word) {
								$word = convert_diacrit($word);
								$icat = pmb_preg_replace("/^${word}$|^${word}\s|\s${word}\s|\s${word}\$/i", ' ', $icat);
							}
						}
					}
					$icat = trim($icat);
					// echo "<br />".$obj->id_noeud." - ".$icat ;
					$icat = pmb_preg_replace('/\s+/', ' ', $icat);
	
					// l'index est propre, on va pouvoir exploser sur espace.
					$mot=array();
					// index non vide (des fois que le m�nage pr�c�dent l'aie vid� compl�tement)
					if ($icat) {
						$mot = explode(' ',$icat);
						for ($imot=0;$imot<count($mot);$imot++) {
							if ($mot[$imot]) {
								$mots[$mot[$imot]][]=$obj->id_noeud ;
							}
						}
					}
				}
				// on a un super tableau de mots
				ksort($mots, SORT_STRING);
				echo "<table>";
				foreach ($mots as $mot=>$idiz) {
					// on parcourt tous les mots trouv�s
					$rqt="select libelle_categorie, num_noeud from categories where num_noeud in(".implode(",",$idiz).") and langue='".$aff_langue."' order by index_categorie";
					$ressql = mysql_query($rqt) or die ($rqt."<br /><br />".mysql_error());
					while ($data=mysql_fetch_object($ressql)) {
						// on parcourt toutes les cat�gories utilisant ce mot pour chercher la position d'utilisation du mot
						$catnette = " ".str_replace(" - ","   ",strtolower(strip_empty_chars_thesaurus($data->libelle_categorie)))." ";
						$catnette = str_replace(" -","  ",$catnette);
						$catnette = str_replace("- ","  ",$catnette);
						$posdeb=strpos($catnette," ".$mot." ");
						$posfin=$posdeb+strlen($mot);
						// echo "<br /><br />deb $posdeb - fin: $posfin mot: $mot LIB: ".$data->libelle_categorie ;
						echo "
							<tr>
								<td align=right valign=top>".affiche_text(substr($data->libelle_categorie,0,$posdeb))."</td>
								<td align=left valign=top><b>".affiche_text(substr($data->libelle_categorie,$posdeb,$posfin-$posdeb))."</b>".affiche_text(substr($data->libelle_categorie,$posfin));
						echo infos_categorie($data->num_noeud, false, true)."</td></tr>";
					}
				}
				// print_r($mots);
				echo "</table>";
				break;
		}
		// pied de page
		print "</body></html>";
	}
	
}

mysql_close($dbh);

function infos_noeud($idnoeud, $niveau, $profondeurmax) {

	global $dbh, $aff_langue;
	global $aff_note_application, $aff_commentaire, $aff_voir, $aff_voir_aussi, $aff_tg, $aff_ts;
	global $color, $fontsize, $paddingmargin ;
	global $id_noeud_origine;
	
	// r�cup�ration info du noeud
	$rqt = "select num_noeud, libelle_categorie, num_parent, note_application, comment_public, case when langue='$aff_langue' then '' else langue end as trad, langue from categories,noeuds where num_noeud = id_noeud and num_noeud='$idnoeud' order by trad ";
	$ressql = mysql_query($rqt) or die ($rqt."<br /><br />".mysql_error());
	while ($data=mysql_fetch_object($ressql)) {
		$res.= "\n<tr>";
		$niv=$niveau-1;
		switch($niv) {
			case 10:
				$res.="<td width=10% bgcolor='".$color[$niveau-9]."'> </td>";
			case 9:
				$res.="<td width=10% bgcolor='".$color[$niveau-8]."'> </td>";
			case 8:
				$res.="<td width=10% bgcolor='".$color[$niveau-7]."'> </td>";
			case 7:
				$res.="<td width=10% bgcolor='".$color[$niveau-6]."'> </td>";
			case 6:
				$res.="<td width=10% bgcolor='".$color[$niveau-5]."'> </td>";
			case 5:
				$res.="<td width=10% bgcolor='".$color[$niveau-4]."'> </td>";
			case 4:
				$res.="<td width=10% bgcolor='".$color[$niveau-3]."'> </td>";
			case 3:
				$res.="<td width=10% bgcolor='".$color[$niveau-2]."'> </td>";
			case 2:
				$res.="<td width=10% bgcolor='".$color[$niveau-1]."'> </td>";
			case 1:
				$res.="<td width=10% bgcolor='".$color[$niveau]."'> </td>";
		}

		$printingBranche = false;
		// afin d'avoir les bons colspan sur la branche en cas d'impression d'une branche
		if ($id_noeud_origine==$idnoeud){
			$niveau=$niveau+1 ;
			$printingBranche = true;
		} 

		if (($data->note_application || $data->comment_public) && ($aff_note_application || $aff_commentaire)) {
			$style="style='border-top: 1px dotted gray;border-bottom: 1px dotted gray; ";
			$largeur="40%";
		} else {
			$style="style='";
			$largeur="70%";
		}
		$style.=" ".$fontsize[$niveau]." ".$paddingmargin[$niveau]." '";
		if ($data->trad) $res.="<td colspan='".($profondeurmax-($niveau-1))."' width=$largeur valign=top $style><font color='blue'>".affiche_text($data->trad)."</font> ".affiche_text($data->libelle_categorie)."";
		else $res.="<td colspan='".($profondeurmax-($niveau-1))."' width=$largeur valign=top $style>".affiche_text($data->libelle_categorie);

		//TERME G�N�RAL DANS LE CAS DE L'IMPRESSION D'UNE BRANCHE
		if ($printingBranche){
			$rqttg = "select libelle_categorie from categories where num_noeud = '".$data->num_parent."'";
			$restg = mysql_query($rqttg) or die ($rqttg."<br /><br />".mysql_error());
			if (mysql_num_rows($restg)) {
				$datatg=mysql_fetch_object($restg);
				$res.= "<br /><font color='blue'>TG ".affiche_text($datatg->libelle_categorie)."</font>";
			}		
		} 
			
		if ($aff_voir_aussi) {
			$rqtva = "select libelle_categorie from categories, voir_aussi where num_noeud_orig=$idnoeud and num_noeud=num_noeud_dest and categories.langue='".$data->langue."' and voir_aussi.langue='".$data->langue."' order by libelle_categorie " ;
			$resva = mysql_query($rqtva) or die ($rqtva."<br /><br />".mysql_error());
			if (mysql_num_rows($resva)) {
				$res.= "\n<font color='green'>";
				while ($datava=mysql_fetch_object($resva)) $res.= "<br />TA ".affiche_text($datava->libelle_categorie);
				$res.= "</font>";
			}
			
		}
		if ($aff_voir) {
			$rqtva = "select libelle_categorie from categories, noeuds where num_renvoi_voir=$idnoeud and num_noeud=id_noeud and categories.langue='".$data->langue."' order by libelle_categorie " ;
			$resva = mysql_query($rqtva) or die ($rqtva."<br /><br />".mysql_error());
			if (mysql_num_rows($resva)) {
				$res.= "\n<font size=-1>";
				while ($datava=mysql_fetch_object($resva)) $res.= "<br />EP <i>".affiche_text($datava->libelle_categorie)."</i>";
				$res.= "</font>";
			}
		}
		$res.="</td>";
		if ($aff_note_application && $data->note_application) $res.="<td width=30% valign=top $style><font color=#ff706d>".affiche_text($data->note_application)."</font></td>";
		if ($aff_commentaire && $data->comment_public) $res.="<td width=30% valign=top $style><font color=black>".affiche_text($data->comment_public)."</font></td>";
		$res.="\n</tr>";
	}
	return $res ;
}

function infos_categorie($idnoeud, $printcategnoeud=true, $forcer_em=false) {

	global $dbh, $aff_langue;
	global $aff_note_application, $aff_commentaire, $aff_voir, $aff_voir_aussi, $aff_tg, $aff_ts;
	
	// r�cup�ration info du noeud
	$rqt = "select num_noeud, num_parent, libelle_categorie, note_application, comment_public, case when langue='$aff_langue' then '' else langue end as trad, langue from categories join noeuds on num_noeud=id_noeud where num_noeud='$idnoeud' order by trad ";
	$ressql = mysql_query($rqt) or die ($rqt."<br /><br />".mysql_error());
	while ($data=mysql_fetch_object($ressql)) {

		if ($data->trad) $res.="<br /><font color=blue>".affiche_text($data->trad)."</font> ".affiche_text($data->libelle_categorie)."";
		elseif ($printcategnoeud) $res.="<br /><br /><b>".affiche_text($data->libelle_categorie)."</b>";

		// EP et EM
		if ($aff_voir) {
			$rqtva = "select libelle_categorie from categories, noeuds where num_renvoi_voir=$idnoeud and num_noeud=id_noeud and categories.langue='".$data->langue."' order by libelle_categorie " ;
			$resva = mysql_query($rqtva) or die ($rqtva."<br /><br />".mysql_error());
			if (mysql_num_rows($resva)) {
				$res.= "\n<font size=-1>";
				while ($datava=mysql_fetch_object($resva)) $res.= "<br />EP <i>".affiche_text($datava->libelle_categorie)."</i>";
				$res.= "</font>";
			}
		}
		if ($aff_voir || $forcer_em) {
			$rqtva = "select libelle_categorie from categories, noeuds where id_noeud=$idnoeud and num_noeud=num_renvoi_voir and categories.langue='".$data->langue."' order by libelle_categorie " ;
			$resva = mysql_query($rqtva) or die ($rqtva."<br /><br />".mysql_error());
			if (mysql_num_rows($resva)) {
				$res.= "\n<font size=-1>";
				while ($datava=mysql_fetch_object($resva)) $res.= "<br />EM <i>".affiche_text($datava->libelle_categorie)."</i>";
				$res.= "</font>";
			}
		}

		// TG
		if ($aff_tg) {
			$rqttg = "select libelle_categorie from categories join noeuds on num_noeud=id_noeud where num_noeud='$data->num_parent' and libelle_categorie not like '~%' and categories.langue='".$data->langue."' " ;
			$restg = mysql_query($rqttg) or die ($rqttg."<br /><br />".mysql_error());
			if (mysql_num_rows($restg)) {
					$res.= "\n<font color=black>";
					while ($datatg=mysql_fetch_object($restg)) $res.= "<br />TG ".affiche_text($datatg->libelle_categorie);
					$res.= "</font>";
				}
		}
		
		// TS
		if ($aff_ts) {
			$rqtts = "select libelle_categorie from categories join noeuds on num_noeud=id_noeud where num_parent='$data->num_noeud' and libelle_categorie not like '~%' and categories.langue='".$data->langue."' " ;
			$rests = mysql_query($rqtts) or die ($rqttg."<br /><br />".mysql_error());
			if (mysql_num_rows($rests)) {
					$res.= "\n<font color=black>";
					while ($datats=mysql_fetch_object($rests)) $res.= "<br />TS ".affiche_text($datats->libelle_categorie);
					$res.= "</font>";
				}
		}		
		// TA
		if ($aff_voir_aussi) {
			$rqtva = "select libelle_categorie from categories, voir_aussi where num_noeud_orig=$idnoeud and num_noeud=num_noeud_dest and categories.langue='".$data->langue."' and voir_aussi.langue='".$data->langue."' order by libelle_categorie " ;
			$resva = mysql_query($rqtva) or die ($rqtva."<br /><br />".mysql_error());
			if (mysql_num_rows($resva)) {
				$res.= "\n<font color=green>";
				while ($datava=mysql_fetch_object($resva)) $res.= "<br />TA ".affiche_text($datava->libelle_categorie);
				$res.= "</font>";
			}
			
		}
		
		if ($aff_note_application && $data->note_application) $res.="<br /><font color=#ff706d>NA ".affiche_text($data->note_application)."</font>";
		if ($aff_commentaire && $data->comment_public) $res.="<br /><font color=black>PU ".affiche_text($data->comment_public)."</font>";
	}
	return $res ;
}

function enfants($id, $niveau, &$resultat, &$profondeurmax, $imprimer=false) {

	global $dbh, $aff_langue;

	if ($imprimer) {
		$resultat=infos_noeud($id, $niveau, $profondeurmax) ;
		echo $resultat;
		flush();
	} elseif ($niveau>$profondeurmax) $profondeurmax=$niveau; 
	
	// chercher les enfants
	$rqt = "select id_noeud from noeuds, categories where num_parent='$id' and id_noeud=num_noeud and langue='$aff_langue' and autorite!='TOP' and autorite!='ORPHELINS' and autorite!='NONCLASSES' order by libelle_categorie ";
	$res = mysql_query($rqt) ;
	if (mysql_num_rows($res)) {
		$niveau++;
		while ($data=mysql_fetch_object($res)) {
			enfants($data->id_noeud, $niveau, $resultat, $profondeurmax, $imprimer);
		}
	}
}

function strip_empty_chars_thesaurus($string) {
	// traitement des diacritiques
	$string = convert_diacrit($string);

	// Mis en commentaire : qu'en est-il des caract�res non latins ???
	// SUPPRIME DU COMMENTAIRE : ER : 12/05/2004 : �a fait tout merder...
	// RECH_14 : Attention : ici suppression des �ventuels "
	//          les " ne sont plus supprim�s 
	$string = stripslashes($string) ;
	$string = pmb_alphabetic('^a-z0-9\s', ' ',pmb_strtolower($string));
	
	// espaces en d�but et fin
	$string = pmb_preg_replace('/^\s+|\s+$/', '', $string);
	
	return $string;
}


function affiche_text($string){
	global $charset;
	return htmlentities($string,ENT_QUOTES,$charset);
}


//Fonctions utilis�es pour l'exprot du th�saurus en xml

function creer_categ_xml($dom,$parent,$niveau, $num_noeud,$libelle_categorie,$note_application,$comment_public,$num_parent){
	global $dbh, $aff_langue;
	global $aff_note_application, $aff_commentaire, $aff_voir, $aff_voir_aussi, $aff_tg, $aff_ts;
	
	
	$noeud_categ=creer_noeud_xml($dom,$parent,"DE");

	//ID
	creer_noeud_xml($dom,$noeud_categ,"ID",$num_noeud);

    //Libell�
    creer_noeud_xml($dom,$noeud_categ,"LIB_DE",$libelle_categorie);

    //Note application
    if($note_application && $aff_note_application){
    	creer_noeud_xml($dom,$noeud_categ,"NA",$note_application);
    }
    
     //Commentaire public
    if($comment_public && $aff_commentaire){
    	creer_noeud_xml($dom,$noeud_categ,"NOTE",$comment_public);
    }
    
    //Voir aussi
    if($aff_voir_aussi){
    	$requete="SELECT libelle_categorie,num_noeud_dest FROM voir_aussi JOIN categories ON num_noeud_dest=num_noeud AND categories.langue='".$aff_langue."' WHERE num_noeud_orig='".$num_noeud."' AND voir_aussi.langue='".$aff_langue."' ORDER BY libelle_categorie";
	    $res=mysql_query($requete);
	    if($res && mysql_num_rows($res)){
	    	while ( $va=mysql_fetch_object($res) ) {
				if(trim($va->libelle_categorie)){
					creer_noeud_xml($dom,$noeud_categ,"TA",$va->libelle_categorie);
				}
			}
	    }
    }
   
    //Employ� pour
    if($aff_voir){
    	$requete="SELECT libelle_categorie,id_noeud FROM noeuds JOIN categories ON id_noeud=num_noeud AND categories.langue='".$aff_langue."' WHERE num_renvoi_voir='".$num_noeud."' ORDER BY libelle_categorie";
	    $res=mysql_query($requete);
	    if($res && mysql_num_rows($res)){
	    	while ( $ep=mysql_fetch_object($res) ) {
				if(trim($ep->libelle_categorie)){
					creer_noeud_xml($dom,$noeud_categ,"EP",$ep->libelle_categorie);
				}
			}
	    }
    }
    
    //Terme g�n�rique
    if($aff_tg && $num_parent){
    	$requete="SELECT libelle_categorie FROM categories WHERE langue='".$aff_langue."' AND num_noeud='".$num_parent."'";
	    $res=mysql_query($requete);
	    if($res && mysql_num_rows($res)){
	    	while ( $tg=mysql_fetch_object($res) ) {
				if(trim($tg->libelle_categorie)){
					creer_noeud_xml($dom,$noeud_categ,"TG",$tg->libelle_categorie);
				}
			}
	    }
    }
    
    //TS
    if($aff_ts){
    	$res=categories::listChilds($num_noeud, $aff_langue,0, "libelle_categorie");
	    if($res && mysql_num_rows($res)){
	    	$noeud_ts=creer_noeud_xml($dom,$noeud_categ,"TS".$niveau);
	    	while ($categ=mysql_fetch_object($res)) {
				if(trim($categ->libelle_categorie)){
					creer_categ_xml($dom,$noeud_ts,($niveau+1),$categ->num_noeud,$categ->libelle_categorie,$categ->note_application,$categ->comment_public,$categ->num_parent);
				}
			}
	    }
    } 
    
}

function encode_libelle_xml($val){
	global $charset;
	
	if($charset == "utf-8"){
		return htmlspecialchars($val,ENT_QUOTES,$charset);
	}else{
		return htmlspecialchars(utf8_encode($val),ENT_QUOTES,$charset);
	}
}

function creer_noeud_xml(&$dom,&$noeud_parent,$name,$val="",$att=array()){
	if($val){
		$noeud=$dom->createElement($name,encode_libelle_xml($val));
	}else{
		$noeud=$dom->createElement($name);
	}
	$noeud=$noeud_parent->appendChild($noeud);
	
	if(count($att)){
		do_att_xml($dom,$noeud,$att);
	}
	
	return $noeud;
}

function do_att_xml(&$dom,&$noeud_parent,$att=array()){
	foreach ( $att as $name => $value ) {
		$element_att = $dom->createAttribute($name);
		$element_att->value = $value;
		$noeud_parent->appendChild($element_att);
	}
}
//Fin des fonctions utilis�es pour l'exprot du th�saurus en xml
?>
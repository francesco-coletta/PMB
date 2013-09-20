<?php
// +-------------------------------------------------+
// © 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: show_group.inc.php,v 1.15.2.1 2013-06-03 12:23:50 dgoron Exp $

if (stristr($_SERVER['REQUEST_URI'], ".inc.php")) die("no access");

// affichage de la liste des membres d'un groupe
// récupération des infos du groupe

$myGroup = new group($groupID);

if(SESSrights & CATALOGAGE_AUTH){
	// propriétés pour le selecteur de panier 
	$selector_prop = "toolbar=no, dependent=yes, width=500, height=400, resizable=yes, scrollbars=yes";
	$cart_click = "onClick=\"openPopUp('".$base_path."/cart.php?object_type=GROUP&item=$groupID', 'cart', 600, 700, -2, -2, '$selector_prop')\"";
	$caddie="<img src='".$base_path."/images/basket_small_20x20.gif' align='middle' alt='basket' title=\"${msg[400]}\" $cart_click>";	
}else{
	$caddie="";	
}
		
print pmb_bidi("
<div class='row'>
	<a href=\"./circ.php?categ=groups\">${msg[929]}</a>&nbsp;
	</div>
<div class='row'>
	<h3>$caddie $msg[919]&nbsp;: ".$myGroup->libelle."&nbsp;
		<input type='submit' class='bouton' value='$msg[62]' onClick=\"document.location='./circ.php?categ=groups&action=modify&groupID=$groupID'\" />
		&nbsp;<input type='button' name='imprimerlistedocs' class='bouton' value='$msg[imprimer_liste_pret]' onClick=\"openPopUp('./pdf.php?pdfdoc=liste_pret_groupe&id_groupe=$groupID', 'print_PDF', 600, 500, -2, -2, '$PDF_win_prop');\" />	
	</h3>
");

if($myGroup->libelle_resp && $myGroup->id_resp)
	print pmb_bidi("
		<br />$msg[913]&nbsp;: 
			<a href='./circ.php?categ=pret&form_cb=".rawurlencode($myGroup->cb_resp)."&groupID=$groupID'>".$myGroup->libelle_resp."</a>
			");
	

print "
	</div>
<div class='row'>";

if($myGroup->nb_members) {
	print "<table >
	<tr>
		<th align='left'>".$msg["nom_prenom_empr"]."</th>
		<th align='left'>".$msg["code_barre_empr"]."</th>
		<th align='left'>".$msg["empr_nb_pret"]."</th>
		<th align='left'>".$msg["empr_nb_resa"]."</th>
		<th></th>
	</tr>";
	$parity=1;
	while(list($cle, $membre) = each($myGroup->members)) {
		if ($parity % 2) {
			$pair_impair = "even";
			} else {
				$pair_impair = "odd";
				}
		$parity += 1;
			$nb_pret=get_nombre_pret($membre['id']);
			$nb_resa=get_nombre_resa($membre['id']);
	        $tr_javascript=" onmouseover=\"this.className='surbrillance'\" onmouseout=\"this.className='$pair_impair'\" onmousedown=\"document.location='./circ.php?categ=pret&form_cb=".rawurlencode($membre['cb'])."&groupID=$groupID';\" ";
		print pmb_bidi("<tr class='$pair_impair' $tr_javascript style='cursor: pointer'>
			<td><a href=\"./circ.php?categ=pret&form_cb=".rawurlencode($membre['cb'])."&groupID=$groupID\">".$membre['nom']);
		if($membre['prenom'])print pmb_bidi(", ${membre['prenom']}");
		print pmb_bidi("
			</a></td>
			<td>${membre['cb']}</td>
			<td>".$nb_pret."</td>
			<td>".$nb_resa."</td>
			<td><a href=\"./circ.php?categ=groups&action=delmember&groupID=$groupID&memberID=${membre['id']}\">
				<img src=\"./images/trash.gif\" title=\"${msg[928]}\" border=\"0\" /></a>
				</td>
			</tr>");
		}
	print '</table><br />';
	} else {
		print "<p>$msg[922]</p>";
		}
// pour que le formulaire soit OK juste après la création du groupe 
$group_form_add_membre = str_replace("!!groupID!!", $groupID, $group_form_add_membre);
print $group_form_add_membre ;

function get_nombre_pret($id_empr) {
	$requete = "SELECT count( pret_idempr ) as nb_pret FROM pret where pret_idempr = $id_empr";
	$res_pret = mysql_query($requete);
	if (mysql_num_rows($res_pret)) {
		$rpret=mysql_fetch_object($res_pret);
		$nb_pret=$rpret->nb_pret;	
	}	
	return $nb_pret;
}

function get_nombre_resa($id_empr) {
	$requete = "SELECT count( resa_idempr ) as nb_resa FROM resa where resa_idempr = $id_empr";
	$res_resa = mysql_query($requete);
	if (mysql_num_rows($res_resa)) {
		$rresa=mysql_fetch_object($res_resa);
		$nb_resa=$rresa->nb_resa;	
	}	
	return $nb_resa;
}
<?php
// +-------------------------------------------------+
// � 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: alert.inc.php,v 1.4 2013-02-14 14:32:12 dgoron Exp $
if (stristr($_SERVER['REQUEST_URI'], ".inc.php")) die("no access");

// d�finition du minimum n�c�ssaire                         
$base_auth = "CIRCULATION_AUTH|CATALOGAGE_AUTH|AUTORITES_AUTH|ADMINISTRATION_AUTH|EDIT_AUTH";  
$base_title = "\$msg[5]";
require_once ("$base_path/includes/init.inc.php");  

require_once("$base_path/alert/message.inc.php");
if ($current_alert=="circ") {
	require_once("$base_path/alert/resa.inc.php");
	require_once("$base_path/alert/expl_todo.inc.php");			
	require_once("$base_path/alert/empr.inc.php");
	//pour les alertes de transferts
	if ($pmb_transferts_actif && (SESSrights & TRANSFERTS_AUTH))
		require_once ("$base_path/alert/transferts.inc.php");
}
if ($current_alert=="catalog") {
	require_once("$base_path/alert/tag.inc.php");
	require_once("$base_path/alert/sugg.inc.php");
}

if ($current_alert=="acquisition") {
	require_once("$base_path/alert/sugg.inc.php");
}
if ($current_alert=="demandes") {
	require_once("$base_path/alert/demandes.inc.php");
}
// le '1' permet de savoir que la session est toujours active, pour �viter les transactions ajax ult�rieures
if($aff_alerte)ajax_http_send_response("1<hr class='alert_separator'> $aff_alerte");
else ajax_http_send_response("1");
?>
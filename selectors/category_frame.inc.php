<?php
// +-------------------------------------------------+
// � 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: category_frame.inc.php,v 1.14 2013-02-20 16:29:45 ngantier Exp $
//
// Frames pour les cat�gories : il faut faire deux frames pour pouvoir naviger par terme

if (stristr($_SERVER['REQUEST_URI'], ".inc.php")) die("no access");

$base_query = "caller=$caller&p1=$p1&p2=$p2&no_display=$no_display&bt_ajouter=$bt_ajouter&dyn=$dyn&keep_tilde=$keep_tilde&parent=$parent&id2=$id2&deb_rech=".rawurlencode(stripslashes($deb_rech))."&callback=".$callback."&infield=".$infield
	."&max_field=".$max_field."&field_id=".$field_id."&field_name_id=".$field_name_id."&add_field=".$add_field."&id_thes_unique=".$id_thes_unique;

?>
<script>self.focus();</script>
<frameset rows="135,*" border=0>
	<frame name="category_search" src="./selectors/category.php?<?php echo $base_query;?>">
	<frame name="category_browse" src="">
</frameset>
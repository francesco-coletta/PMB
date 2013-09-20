<?php
// +-------------------------------------------------+
// © 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: cms_vign.php,v 1.3 2012-09-06 08:06:52 ngantier Exp $

// définition du minimum nécéssaire 
$base_path     = ".";                            
$base_auth     = ""; //"CIRCULATION_AUTH";  
$base_title    = "";    
$base_noheader = 1;
$base_nocheck  = 1;
$base_nobody   = 1;


require_once ("$base_path/includes/init.inc.php");  
require_once($class_path."/cms/cms_logo.class.php");

$logo = new cms_logo($id,$type);

$logo->show_picture($mode);
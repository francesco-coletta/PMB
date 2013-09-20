<?php
// +-------------------------------------------------+
// © 2002-2011 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: ajax_main.inc.php,v 1.2 2012-05-24 13:15:23 ngantier Exp $

if (stristr($_SERVER['REQUEST_URI'], ".inc.php")) die("no access");

require_once($class_path."/autoloader.class.php");
$autoloader = new autoloader();
$autoloader->add_register("cms_modules",true);

switch($categ){
	case "module" :
		switch($action){
			case "ajax" :
				$element = new $elem($id);
				$response = $element->execute_ajax();
				ajax_http_send_response($response['content'],$response['content-type']);
				break;
		}
		break;	
	case "build" :
		switch($action){
			case "set_version" :
				$_SESSION["build_id_version"]=$value;
				ajax_http_send_response("ok ".$_SESSION["build_id_version"]);
			break;
		}
	break;
	
}
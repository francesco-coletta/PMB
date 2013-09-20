<?php
// +-------------------------------------------------+
// © 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: func_agroparistech.inc.php,v 1.5 2013-03-25 09:39:03 mbertin Exp $

/*
 *  ATTENTION CE FICHIER EST EN UTF-8
 */

if (stristr($_SERVER['REQUEST_URI'], ".inc.php")) die("no access");

function recup_noticeunimarc_suite($notice) {
	global $infos_4XX;
	global $bl,$hl;
	global $tit_200a,$serie_200;
	global $info_003;
	
	$zones = array(
		412,
		413,
		421,
		422,
		423,
		430,
		431,
		432,
		433,
		434,
		435,
		436,
		437,
		440,
		441,
		442,
		443,
		444,
		445,
		446,
		447,
		451,
		451,
		452,
		452,
		453,
		454,
		455,
		456,
		520
	);
	
	$infos_4XX = array();
	$info_003 = array();
	
	$record = new iso2709_record($notice, AUTO_UPDATE);
	$bl=$record->inner_guide['bl'];
	$hl=$record->inner_guide['hl'];	
	$info_003=$record->get_subfield("003");
	foreach($zones as $zone){
		$infos_4XX[$zone] = $record->get_subfield($zone,"0","t","x");
	}
	
	//pour les monographies, le 200$a et 200$i s'inverse...
	if($bl == "m"){
		if(clean_string($serie_200[0]['i']) != ""){
			$tmp_buffer = $serie_200[0]['i'];
			$serie_200[0]['i'] = $tit_200a[0];
			$tit_200a[0] = $tmp_buffer;
		}
	}
} 
	
function import_new_notice_suite() {
	global $dbh ;
	global $notice_id ;
	global $bl,$hl;
	global $infos_4XX;
	global $hierarchic_level;
	global $bibliographic_level	;
	global $doc_type;
	global $origine_notice;
	global $notices_crees;
	global $issn_011;
	global $tit_200a;
	global $isbn;
	global $statutnot;
	global $info_003;
	
	if(isset($bibliographic_level) && isset($hierarchic_level)){
		$niveau_biblio = $bibliographic_level.$hierarchic_level;
	}else{
		$niveau_biblio =$bl.$hl;
	}
	
	//num_notice = fille
	//linked_notice = mere
	
	$sens = array(
		'mother' => array(
			"linked_notice",
			"num_notice"
		),
		'child' => array(
			"num_notice",
			"linked_notice"
		)
	);
	
	
	$link_type = array(
		'412' => array(
			'code' => "v",
			'sens_link' => "child"
		),
		'413' => array(
			'code' => "v",
			'sens_link' => "mother"
		),
		'421' => array(
			'code' => "e",
			'sens_link' => "mother"
		),
		'422' => array(
			'code' => "e",
			'sens_link' => "child"
		),
		'423' => array(
			'code' => "k",
			'sens_link' => "child"
		),
		'430' => array(
			'code' => "l",
			'sens_link' => "child"
		),
		'431' => array(
			'code' => "o",
			'sens_link' => "mother"
		),
		'432' => array(
			'code' => "t",
			'sens_link' => "child"
		),
		'433' => array(
			'code' => "o",
			'sens_link' => "mother"
		),
		'434' => array(
			'code' => "m",
			'sens_link' => "child"
		),
		'435' => array(
			'code' => "s",
			'sens_link' => "child"
		),
		'436' => array(
			'code' => "n",
			'sens_link' => "mother"
		),
		'437' => array(
			'code' => "o",
			'sens_link' => "mother"
		),
		'440' => array(
			'code' => "l",
			'sens_link' => "mother"
		),
		'441' => array(
			'code' => "o",
			'sens_link' => "child"
		),
		'442' => array(
			'code' => "t",
			'sens_link' => "mother"
		),
		'443' => array(
			'code' => "o",
			'sens_link' => "child"
		),
		'444' => array(
			'code' => "m",
			'sens_link' => "mother"
		),
		'445' => array(
			'code' => "s",
			'sens_link' => "mother"
		),
		'446' => array(
			'code' => "o",
			'sens_link' => "child"
		),
		'447' => array(
			'code' => "n",
			'sens_link' => "child"
		),
		'451' => array(
			'code' => "u",
			'sens_link' => "child"
		),
		'452' => array(
			'code' => "p",
			'sens_link' => "child"
		),
		'453' => array(
			'code' => "h",
			'sens_link' => "mother"
		),
		'454' => array(
			'code' => "h",
			'sens_link' => "child"
		),
		'455' => array(
			'code' => "q",
			'sens_link' => "mother"
		),
		'456' => array(
			'code' => "q",
			'sens_link' => "child"
		),
		'520' => array(
			'code' => "f",
			'sens_link' => "child"
		)
	);

	//dédoublonnage !
	if($isbn){
		$query = "select notice_id from notices where code like '".$isbn."' and notice_id != ".$notice_id;
		$result = mysql_query($query);
		if(mysql_num_rows($result)){
			$row = mysql_fetch_object($result);
			notice::del_notice($notice_id);
			$notice_id = $row->notice_id;
			mysql_query("insert into error_log (error_origin, error_text) values ('import_expl_".addslashes(SESSid).".inc', '".addslashes("La notice (".$tit_200a[0].", ".$isbn.") n'a pas été reprise car elle existe déjà en base (notice id: ".$notice_id.")")."') ") ;
		}
	}
	if($issn_011[0]){
		$query = "select notice_id from notices where code like '".$issn_011[0]."' and notice_id != ".$notice_id;
		$result = mysql_query($query);
		if(mysql_num_rows($result)){
			while($row = mysql_fetch_object($result)){
				if(in_array($row->notice_id,$notices_crees)){
					$old = new notice($row->notice_id);
					$old->replace($notice_id);
					$tab = array_flip($notices_crees);
					unset($tab[$row->notice_id]);
					$notices_crees = array_flip($tab);
				}else{
					notice::del_notice($notice_id);
					$notice_id = $row->notice_id;
					mysql_query("insert into error_log (error_origin, error_text) values ('import_expl_".addslashes(SESSid).".inc', '".addslashes("La notice (".$tit_200a[0].", ".$isbn.") n'a pas été reprise car elle existe déjà en base (notice id: ".$notice_id.")")."') ") ;
				}
			}
		}		
	}

	switch($niveau_biblio){
		case "s1" :
			foreach($infos_4XX as $key => $children){
				foreach($children as $child){
					$issn = "";
					//on commence par chercher si la notice existe
					$issn = traite_code_ISSN($child['x']);
					if($issn){
						$query = "select notice_id from notices where code ='".$issn."' and niveau_biblio = 's' and niveau_hierar = '1'";
						$result = mysql_query($query);
						if(!mysql_num_rows($result)){
							//la notice n'existe pas, il faut la créer...

							/* Origine de la notice */
							$origine_not['nom']=clean_string($origine_notice[0]['b']);
							$origine_not['pays']=clean_string($origine_notice[0]['a']);
							$orinot_id = origine_notice::import($origine_not);
							if ($orinot_id==0) $orinot_id=1 ;
							
							$query = "insert into notices set 
								typdoc = '".$doc_type."',
								tit1 = '".addslashes(clean_string($child['t']))."',
								code = '".$issn."',
								niveau_biblio = 's',
								niveau_hierar = '1',
								statut = ".$statutnot.",
								origine_catalogage = '".$orinot_id."',
								create_date = sysdate(),
								update_date = sysdate()
							";
							mysql_query($query);
							$child_id = mysql_insert_id();
							$notices_crees[$child[0]]=$child_id;
							notice::majNotices($child_id);
							notice::majNoticesGlobalIndex($child_id);
							notice::majNoticesMotsGlobalIndex($child_id);
						}else{
							$child_id = mysql_result($result,0,0);
						}
						if($child_id){
							// on regarde si une relation similaire existe déjà...
							$query = "select relation_type from notices_relations where relation_type = '".$link_type[$key]['code']."' and ((num_notice = ".$notice_id." and linked_notice = ".$child_id.") or (num_notice = ".$child_id." and linked_notice = ".$notice_id."))";
							$result = mysql_query($query);
							
							if(!mysql_num_rows($result)){
								$rank = 0;
								$query = "select count(rank) from notices_relations where relation_type = '".$link_type[$key]['code']."' and ";
								if($link_type[$key]['sens_link'] == "mother"){
									$query.= "num_notice = ".$child_id;
								}else{
									$query.= "num_notice = ".$notice_id;
								}
								$result = mysql_query($query);
								if(mysql_num_rows($result)) $rank = mysql_result($result,0,0);
								
								$query = "insert into notices_relations set 
									".$sens[$link_type[$key]['sens_link']][0]." = ".$notice_id.",
									".$sens[$link_type[$key]['sens_link']][1]." = ".$child_id.",
									relation_type = '".$link_type[$key]['code']."',
									rank = ".($rank+1)."
								";
								mysql_query($query);
							}
						}
					}
				}
			}
			break;
	}
	
	if($tmp=trim($info_003[0])){
		$requete="SELECT notices_custom_origine FROM notices_custom_values WHERE notices_custom_champ=22 AND notices_custom_origine='".$notice_id."' AND notices_custom_small_text='".addslashes($tmp)."' ";
		$res=mysql_query($requete);
		if($res && mysql_num_rows($res)){
			
		}else{
			$requete="INSERT INTO notices_custom_values(notices_custom_champ, notices_custom_origine, notices_custom_small_text) VALUES('22','".$notice_id."','".addslashes($tmp)."')";
			mysql_query($requete);
		}
	}
}	

function traite_exemplaires () {

}

// fonction spécifique d'export de la zone 995
function export_traite_exemplaires ($ex=array()) {
	
	$subfields["a"] = $ex -> lender_libelle;
	$subfields["c"] = $ex -> lender_libelle;
	$subfields["f"] = $ex -> expl_cb;
	$subfields["k"] = $ex -> expl_cote;
	$subfields["u"] = $ex -> expl_note;

	if ($ex->statusdoc_codage_import) $subfields["o"] = $ex -> statusdoc_codage_import;
	if ($ex -> tdoc_codage_import) $subfields["r"] = $ex -> tdoc_codage_import;
		else $subfields["r"] = "uu";
	if ($ex -> sdoc_codage_import) $subfields["q"] = $ex -> sdoc_codage_import;
		else $subfields["q"] = "u";
	
	global $export996 ;
	$export996['f'] = $ex -> expl_cb ;
	$export996['k'] = $ex -> expl_cote ;
	$export996['u'] = $ex -> expl_note ;

	$export996['m'] = substr($ex -> expl_date_depot, 0, 4).substr($ex -> expl_date_depot, 5, 2).substr($ex -> expl_date_depot, 8, 2) ;
	$export996['n'] = substr($ex -> expl_date_retour, 0, 4).substr($ex -> expl_date_retour, 5, 2).substr($ex -> expl_date_retour, 8, 2) ;

	$export996['a'] = $ex -> lender_libelle;
	$export996['b'] = $ex -> expl_owner;

	$export996['v'] = $ex -> location_libelle;
	$export996['w'] = $ex -> ldoc_codage_import;

	$export996['x'] = $ex -> section_libelle;
	$export996['y'] = $ex -> sdoc_codage_import;

	$export996['e'] = $ex -> tdoc_libelle;
	$export996['r'] = $ex -> tdoc_codage_import;

	$export996['1'] = $ex -> statut_libelle;
	$export996['2'] = $ex -> statusdoc_codage_import;
	$export996['3'] = $ex -> pret_flag;
	
	global $export_traitement_exemplaires ;
	$export996['0'] = $export_traitement_exemplaires ;
	
	return 	$subfields ;

	}	
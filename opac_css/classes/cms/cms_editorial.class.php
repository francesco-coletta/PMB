<?php
// +-------------------------------------------------+
// | 2002-2011 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: cms_editorial.class.php,v 1.5.2.4 2013-09-09 09:23:17 apetithomme Exp $

if (stristr($_SERVER['REQUEST_URI'], ".class.php")) die("no access");

require_once($class_path."/cms/cms_root.class.php");
require_once($class_path."/cms/cms_logo.class.php");
require_once($class_path."/cms/cms_editorial_publications_states.class.php");

require_once($class_path."/categories.class.php");
require_once($include_path."/templates/cms/cms_editorial.tpl.php");

class cms_editorial extends cms_root {
	public $id;						// identifiant du contenu
	public $num_parent;				// id du parent
	public $title;					// le titre du contenu
	public $resume;					// résumé du contenu
	public $logo;					// objet gérant le logo
	public $publication_state;		// statut de publication	
	public $start_date;				// date de début de publication
	public $end_date;				// date de fin de publication
	public $descriptors = array();	// descripteurs
	protected $type;				// le type de l'objet
	public $num_type;				// id du type de contenu 
	public $type_content = "";		// libellé du type de contenu
	public $fields_type;
	protected $opt_elements;			// les éléments optionnels constituants l'objet
	public $create_date;
	
	public function __construct($id=2,$type="section",$num_parent=0){
		$this->type = $type;
		if($id){
			$this->id = $id;
			$this->fetch_data();
			$this->logo = new cms_logo($this->id,$this->type);
		}else{
			$this->id = 0;
			$this->title = "";
			$this->resume = "";
			$this->logo = new cms_logo(0,$this->type);
			$this->publication_state = "";
			$this->start_date = "";
			$this->end_date = "";
			$this->num_parent = $num_parent;
			$this->descriptors = array();
			$this->num_type;
			$this->create_date = "";
		}
	}
	
	protected function get_descriptors(){
		// les descripteurs...
		$rqt = "select num_noeud from cms_".$this->type."s_descriptors where num_".$this->type." = '".$this->id."' order by ".$this->type."_descriptor_order";
		$res = mysql_query($rqt);
		if(mysql_num_rows($res)){
			while($row = mysql_fetch_object($res)){
				$categ = new categories($row->num_noeud, $lang);
				$this->descriptors[] = $categ->num_noeud;
			}
		}
	}
	
	protected function get_fields_type(){
		$this->fields_type = array();
		$query = "select id_editorial_type from cms_editorial_types where editorial_type_element = '".$this->type."_generic'";
		$result = mysql_query($query);
		if(mysql_num_rows($result)){
			$fields_type = new cms_editorial_parametres_perso(mysql_result($result,0,0));
			$this->fields_type = $fields_type->get_out_values($this->id);
		}
		if($this->num_type){
			$query = "select editorial_type_label from cms_editorial_types where id_editorial_type = ".$this->num_type;
			$result = mysql_query($query);
			if(mysql_num_rows($result)){
				$this->type_content = mysql_result($result,0,0);
				$fields_type = new cms_editorial_parametres_perso($this->num_type);
				$this->fields_type = array_merge($this->fields_type,$fields_type->get_out_values($this->id));
			}
		}
	}
	
	public function delete(){
		$result = $this->is_deletable();
		if($result === true){
			//l'elément
			$del = "delete from cms_".$this->type."s where id_".$this->type."='".$this->id."'";
			mysql_query($del);
			$del_desc = "delete from cms_".$this->type."_descriptors where num_".$this->type." = '".$this->id."'";
			mysql_query($del_desc);
			//ses champs persos
			$fields_type = new cms_editorial_parametres_perso($this->num_type);
			$fields_type->delete_values($this->id);
			return true;
		}else{
			return $result;
		}
	}
		
	public function get_form($name="cms_form_editorial",$id="cms_form_editorial",$attr="",$close=true){
		//on récupère le template
		global $cms_editorial_form_tpl;
		global $cms_editorial_form_del_button_tpl;
		global $msg;
		global $lang;
		global $base_path;
		
		$fields_form="";
		$fields_form.=$this->get_type_field();
		$fields_form.=$this->get_parent_field();
		$fields_form.=$this->get_title_field();
		$fields_form.=$this->get_resume_field();
		$fields_form.=$this->get_contenu_field();
		$fields_form.=$this->get_logo_field();
		$fields_form.=$this->get_desc_field();
		$fields_form.=$this->get_publication_state_field();
		$fields_form.=$this->get_dates_field();
		
		
		$form = str_replace("!!fields!!",$fields_form,$cms_editorial_form_tpl);
		
		if($this->id){
			$del_button = $cms_editorial_form_del_button_tpl;
			$type_href=$base_path."/ajax.php?module=cms&categ=get_type_form&elem=".$this->type."&type_id=".$this->num_type."&id=".$this->id;
		}else{
			$del_button = "";
			$type_href=$base_path."/ajax.php?module=cms&categ=get_type_form&elem=".$this->type."&type_id=&id=".$this->id;;
		}
		$form = str_replace("!!cms_editorial_form_suppr!!",$del_button,$form);
		$form = str_replace("!!type_href!!",$type_href,$form);
		
		$form = str_replace("!!type!!",$this->type,$form);
		$form = str_replace("!!cms_editorial_form_name!!",$name,$form);
		$form = str_replace("!!cms_editorial_form_id!!",$id,$form);
		$form = str_replace("!!cms_editorial_form_obj_id!!",$this->id,$form);
		
		if(!$this->id){
			$attr = "enctype='multipart/form-data' ".$attr;
		}
		$form = str_replace("!!cms_editorial_form_attr!!",$attr,$form);

		$form = str_replace("!!form_title!!",$msg['cms_'.($this->id ? "" : "new_").$this->type."_form_title"],$form);

		if($close){
			$form = str_replace("!!cms_editorial_suite!!","",$form);
		}		
		return $form;
	}
	
	public function get_ajax_form($name="cms_form_editable",$id="cms_form_editable"){
		global $msg;
		
		$form = $this->get_form($name,$id,"onsubmit='cms_ajax_submit();return false;'",false);
		$suite ="
		<script>
			function cms_ajax_submit(){
				var values = '';
			
				if(typeof(check_form) == 'function' && !check_form()){
					return false;
				}
				if(document.forms['$name'].cms_editorial_form_delete.value == 1){
					if(confirm(\"".$msg['cms_editorial_form_'.$this->type.'_delete_confirm']."\")){
						cms_".$this->type."_delete();
					}
				}else{
					for(var i=0 ; i<document.forms['$name'].elements.length ; i++){
						var element = document.forms['$name'].elements[i];
						if(element.name){
							if(element.type == 'select-multiple'){
								for(var j=0; j< element.options.length; j++){
									if(element.options[j].selected == true){
										values+='&'+element.name+'='+encodeURIComponent(element.options[j].value); 
									}
								}
							}else if(element.type == 'checkbox'){
								if(element.checked == true){
									values+='&'+element.name+'='+encodeURIComponent(element.value); 
								}
							}else if(element.type == 'radio'){
								if(element.checked == true){
									values+='&'+element.name+'='+encodeURIComponent(element.value);
								}
							}else{
								values+='&'+element.name+'='+encodeURIComponent(element.value);
							}
						}
					}
					var post = new http_request();
					post.request('./ajax.php?module=cms&categ=save_".$this->type."',true,values,true,cms_".$this->type."_saved);
				}
			}
			
			function cms_".$this->type."_delete(){
				var post = new http_request();
				post.request('./ajax.php?module=cms&categ=delete_".$this->type."',true,'&id='+document.forms['$name'].cms_editorial_form_obj_id.value,true,cms_".$this->type."_deleted);
			}

			function cms_".$this->type."_deleted(response){
				var result = eval('('+response+')');
				if(result.status == 'ok'){
					dijit.byId('editorial_tree_container').refresh();
					dijit.byId('content_infos').destroyDescendants();			
				}else{
					alert(result.error_message);
				}
			}

			function cms_".$this->type."_saved(response){
				dijit.byId('editorial_tree_container').refresh();
				dijit.byId('content_infos').refresh();
			}
		</script>";
		$form = str_replace("!!cms_editorial_suite!!",$suite,$form);
		return $form;		
	}
	
	public function get_parent_selector(){
		//à surcharger...
	}
	
	protected function get_parent_field(){
		global $msg;
		global $cms_editorial_parent_field;
		return str_replace("!!cms_editorial_form_parent_options!!",$this->get_parent_selector(),$cms_editorial_parent_field);
	}
	
	protected function get_title_field(){
		global $cms_editorial_title_field;
		return str_replace("!!cms_editorial_form_title!!",$this->title,$cms_editorial_title_field);
	}
	
	protected function get_resume_field(){
		global $cms_editorial_resume_field;
		return str_replace("!!cms_editorial_form_resume!!",$this->resume,$cms_editorial_resume_field);
	}
	
	protected function get_contenu_field(){
		global $cms_editorial_contenu_field;
		if($this->opt_elements['contenu']==true){
			return str_replace("!!cms_editorial_form_contenu!!",$this->contenu,$cms_editorial_contenu_field);	
		}else{
			return "";		
		}
	}
	
	protected function get_logo_field(){
		return $this->logo->get_form();
	}
	
	protected function get_desc_field(){
		global $lang;
		global $cms_editorial_desc_field;
		global $cms_editorial_first_desc,$cms_editorial_other_desc;
		
		$categs = "";
		if(count($this->descriptors)){
			for ($i=0 ; $i<count($this->descriptors) ; $i++){
				if($i==0) $categ=$cms_editorial_first_desc;
				else $categ = $cms_editorial_other_desc;
				//on y va
				$categ = str_replace('!!icateg!!', $i, $categ);
				$categ = str_replace('!!categ_id!!', $this->descriptors[$i], $categ);
				$categorie = new categories($this->descriptors[$i],$lang);
				$categ = str_replace('!!categ_libelle!!', $categorie->libelle_categorie, $categ);			
				$categs.=$categ;
			}
			$categs = str_replace("!!max_categ!!",count($this->descriptors),$categs);
		}else{
			$categs=$cms_editorial_first_desc;
			$categs = str_replace('!!icateg!!', 0, $categs) ;
			$categs = str_replace('!!categ_id!!', "", $categs);
			$categs = str_replace('!!categ_libelle!!', "", $categs);
			$categs = str_replace('!!max_categ!!', 1, $categs);
		}		
		return str_replace("!!cms_categs!!",$categs,$cms_editorial_desc_field);
	}
	
	protected function get_publication_state_field(){
		global $cms_editorial_publication_state_field;
		$publications_states = new cms_editorial_publications_states();
		return str_replace("!!cms_editorial_form_publications_states_options!!",$publications_states->get_selector_options($this->publication_state),$cms_editorial_publication_state_field);
	}
	
	protected function get_dates_field(){
		global $cms_editorial_dates_field;
		global $msg;
		$day = date("Ymd");
		$form = str_replace("!!day!!",$day,$cms_editorial_dates_field);
		
		$start_date = formatDate($this->start_date);
		if(!$start_date) $start_date = $msg['no_date'];
		$form = str_replace("!!cms_editorial_form_start_date_value!!",$this->start_date,$form);
		$form = str_replace("!!cms_editorial_form_start_date!!",$start_date,$form);
		
		$end_date = formatDate($this->end_date);
		if(!$end_date) $end_date = $msg['no_date'];
		$form = str_replace("!!cms_editorial_form_end_date_value!!",$this->end_date,$form);
		$form = str_replace("!!cms_editorial_form_end_date!!",$end_date,$form);
		return $form;
	}
	
	protected function get_type_field(){
		global $cms_editorial_type_field;
		$types = new cms_editorial_types($this->type);
		$types->get_types();
		if(count($types->types)){
			return str_replace("!!cms_editorial_form_type_options!!",$types->get_selector_options($this->num_type),$cms_editorial_type_field);
		}else{
			return "";
		}
	}
	
	public function get_from_form(){
		global $cms_editorial_form_obj_id;
		global $cms_editorial_form_type;
		global $cms_editorial_form_parent;
		global $cms_editorial_form_title;
		global $cms_editorial_form_resume;
		global $cms_editorial_form_contenu;
		global $max_categ;
		global $cms_editorial_form_publication_state;
		global $cms_editorial_form_start_date_value;
		global $cms_editorial_form_end_date_value;

		for ($i=0 ; $i<$max_categ ; $i++){
			$categ_id = 'f_categ_id'.$i;
			global $$categ_id;
			if($$categ_id > 0){
				$this->descriptors[] = $$categ_id;
			}
		}
		$this->id = $cms_editorial_form_obj_id;
		$this->num_type = $cms_editorial_form_type;
		$this->num_parent = stripslashes($cms_editorial_form_parent);
		$this->title = stripslashes($cms_editorial_form_title);
		$this->resume = stripslashes($cms_editorial_form_resume);
		if($this->resume == '<br _moz_editor_bogus_node="TRUE" />'){
			$this->resume = "";
		}
		$this->start_date = stripslashes($cms_editorial_form_start_date_value);
		$this->end_date = stripslashes($cms_editorial_form_end_date_value);
		$this->publication_state = stripslashes($cms_editorial_form_publication_state);
		if($this->opt_elements['contenu']) {
			$this->contenu = stripslashes($cms_editorial_form_contenu);
			if($this->contenu == '<br _moz_editor_bogus_node="TRUE" />'){
				$this->contenu = "";
			}
		}
		$this->logo->id = $this->id;
	}

	protected function save_logo(){
		//on agit que si un fichier a été soumis...
		if(count($_FILES)){
			$this->logo->id = $this->id;
			$this->logo->save();	
		}
	}
	
	public static function get_format_data_structure($type){
		global $msg;
		$main_fields = array();
		$main_fields[] = array(
			'var' => "id",
			'desc'=> $msg['cms_module_common_datasource_desc_id_'.$type]
		);
		if($type == "section"){
			$main_fields[] = array(
				'var' => "num_parent",
				'desc'=> $msg['cms_module_common_datasource_desc_num_parent']
			);		
		}else{
			$main_fields[] = array(
				'var' => "parent",
				'desc'=> $msg['cms_module_common_datasource_desc_parent'],
				'children' => self::prefix_var_tree(cms_section::get_format_data_structure(false,false),"parent")
			);
		}
		$main_fields[] = array(
			'var' => "title",
			'desc' => $msg['cms_module_common_datasource_desc_title']
		);
		$main_fields[] = array(
			'var' => "resume",
			'desc' => $msg['cms_module_common_datasource_desc_resume']
		);
		if($type == "article"){
			$main_fields[] = array(
				'var' => "content",
				'desc' => $msg['cms_module_common_datasource_desc_content']
			);
		}		
		$main_fields[] = array(
			'var' => "logo",
			'children' => array(
				array(
					'var' => "logo.small_vign",
					'desc' => $msg['cms_module_common_datasource_desc_small_vign']
				),
				array(
					'var' => "logo.vign",
					'desc' => $msg['cms_module_common_datasource_desc_vign']
				),
				array(
					'var' => "logo.large",
					'desc' => $msg['cms_module_common_datasource_desc_large']
				),			
				array(
					'var' => "logo.exists",
					'desc' => $msg['cms_module_common_datasource_desc_logo_exists']
				),
			),			
			'desc' => $msg['cms_module_common_datasource_desc_logo']
		);
		$main_fields[] = array(
			'var' => "publication_state",
			'desc' => $msg['cms_module_common_datasource_desc_publication_state']
		);
		$main_fields[] = array(
			'var' => "start_date",
			'desc' => $msg['cms_module_common_datasource_desc_start_date']
		);
		$main_fields[] = array(
			'var' => "end_date",
			'desc' => $msg['cms_module_common_datasource_desc_end_date']
		);
		$main_fields[] = array(
			'var' => "descriptors",
			'desc' => $msg['cms_module_common_datasource_desc_descriptors']
		);
		$main_fields[] = array(
			'var' => "type",
			'desc' => $msg['cms_module_common_datasource_desc_type_'.$type]
		);
		$main_fields[] = array(
			'var' => "fields_type",
			'desc' => $msg['cms_module_common_datasource_desc_fields_type_'.$type]
		);
		$main_fields[] = array(
			'var' => "create_date",
			'desc' => $msg['cms_module_common_datasource_desc_create_date']
		);	
		
		//pour les types de contenu
		$fields_type=array();
		$types = new cms_editorial_types($type);
		$types->get_types();
		foreach($types->types as $type){
			$infos= array(
				'var' => $type['label'],
				'desc'=> $type['comment']
			);
			foreach($type['fields'] as $field){
				$infos['children'][] = array(
					'var' => "fields_type.".$field['NAME'],
					'desc' => $field['TITRE'],
					'children' => array(
						array(
							'var' => "fields_type.".$field['NAME'].".id",
							'desc'=> $msg['cms_module_common_datasource_desc_fields_type_id'],
						),
						array(
							'var' => "fields_type.".$field['NAME'].".label",
							'desc'=> $msg['cms_module_common_datasource_desc_fields_type_label'],
						),
						array(
							'var' => "fields_type.".$field['NAME'].".values",
							'desc'=> $msg['cms_module_common_datasource_desc_fields_type_values'],
							'children' => array(
								array(
									'var'=> "fields_type.".$field['NAME'].".values[i].format_value",
									'desc' => $msg['cms_module_common_datasource_desc_fields_type_values_format_value'],
								),
								array(
									'var'=> "fields_type.".$field['NAME'].".values[i].value",
									'desc' => $msg['cms_module_common_datasource_desc_fields_type_values_value'],
								)
							)
						)
					)
				);
			}
			$fields_type[]=$infos;		
		}
		return array(
			array(
				'var' => $msg['cms_module_common_datasource_main_fields'],
				"children" => $main_fields
			),
			array(
				'var' =>  $msg['cms_module_common_datasource_types'],
				'desc' => $msg['cms_module_common_datasource_desc_types'],
				"children" => $fields_type			
			)
		);	
		
	}
	
	protected function prefix_var_tree($tree,$prefix){
		for($i=0 ; $i<count($tree) ; $i++){
			$tree[$i]['var'] = $prefix.".".$tree[$i]['var'];
			if($tree[$i]['children']){
				$tree[$i]['children'] = self::prefix_var_tree($tree[$i]['children'],$prefix);
			}
		}
		return $tree;
	}
}
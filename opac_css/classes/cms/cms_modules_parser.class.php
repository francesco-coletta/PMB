<?php
// +-------------------------------------------------+
// | 2002-2011 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: cms_modules_parser.class.php,v 1.3 2012-03-23 14:59:27 arenou Exp $

if (stristr($_SERVER['REQUEST_URI'], ".class.php")) die("no access");

class cms_modules_parser {
	private $path;
	private $modules_list = array();
	private $folders_list = array();
	private $cadres_list = array();

	public function __construct($path=""){
		global $base_path;
		if($path == "") $path = $base_path."/cms/modules/";			
		$this->path = $path;	
	}

	protected function get_folders_list(){
		if(count($this->folders_list) == 0){
			if(is_dir($this->path)){
				$dh = opendir($this->path);
				//on parcours tout le répertoire
				while(($dir = readdir($dh)) !== false){
					//le répertoire parent et common ne sont pas des modules
					if($dir != "common"  & substr($dir,0,1) != "."){
						$this->folders_list[] = $dir;
					}
				}
				closedir($dh);
			}
		}
		return $this->folders_list;
	}

	public function get_modules_list(){
		if(count($this->modules_list) == 0){
			$this->get_folders_list();
			foreach ($this->folders_list as $module_name){
				$module_class_name = "cms_module_".$module_name;
				if(class_exists($module_class_name)){
					$module = new $module_class_name();
					$this->modules_list[$module_name] = $module->informations;
				}
			}
		}
		return $this->modules_list;
	}

	public function get_module_class($class){
		$this->get_folders_list();
		if((in_array($class,$this->folders_list))){
			$module_class_name = "cms_module_".$class;
			if(class_exists($module_class_name)){
				return new $module_class_name();
			}
		}
		return false;
	}

	public function get_cadres_list(){
		if(count($this->cadres_list) == 0){
			$query = "select id_cadre, cadre_object, cadre_name from cms_cadres";
			$result = mysql_query($query);
			if(mysql_num_rows($result)){
				while($row = mysql_fetch_object($result)){
					$this->cadres_list[] = $row;
				}
			}
		}
		return $this->cadres_list;
	}

	public function get_module_class_by_id($id){
		$id+=0;
		$query = "select id_cadre, cadre_object, cadre_name from cms_cadres where id_cadre = ".$id;
		$result = mysql_query($query);
		if(mysql_num_rows($result)){
			$row = mysql_fetch_object($result);
			return new $row->cadre_object($row->id_cadre);
		}
	}
}
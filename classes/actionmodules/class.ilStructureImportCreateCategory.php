<?php

include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/actiontypebase/class.ilStructureImportCreate.php';
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportPlugin.php';

class ilStructureImportCreateCategory extends ilStructureImportCreate
{
	protected static $module_name = 'create_category';
	protected static $action_type = 'create';
	protected static $action_lang_name = 'action_create_category';
	protected static $required_parameters = 'action;name;path';
	protected static $optional_parameters = 'description';
	protected static $create_type = 'cat';
	
	public function __construct($log)
	{
	    parent::__construct($log);
	    
	    $this->type = 'cat';
	}
	
	protected function createObject($row, $container_ref)
	{
	    global $lng, $ilUser;
	    
	    /* Create object */
	    require_once("Modules/Category/classes/class.ilObjCategory.php");
	    $category_obj = new ilObjCategory();
	    $status = $this->initObjectBase($category_obj, $row, $container_ref);
	    if($status != 0)
	    {
	        return $status;
	    }
	    
	    /* Set translation */
	    $title = $row[$this->plugin->txt(ilImportExcel::EXCELCOL_NAME)];
	    $description = $row[$this->plugin->txt(ilImportExcel::EXCELCOL_DESCRIPTION)];
	    $category_obj->addTranslation($title, $description, $lng->getLangKey(), $lng->getLangKey());
	    
	    /* Apply changes */
	    $category_obj->update();
		
		return $status;
	}
}

?>
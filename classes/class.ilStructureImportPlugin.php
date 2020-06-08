<?php

include_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportDBManager.php');
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportConstants.php';
include_once("./Services/UIComponent/classes/class.ilUserInterfaceHookPlugin.php");

/**
 * HSLUUIDefaults plugin
 *
 * @author Raphael Heer <raphael.heer@hslu.ch>
 * @version $Id$
 *
 */
class ilStructureImportPlugin extends ilUserInterfaceHookPlugin
{	
	const PATH_TO_ACTION_MODULES = './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/actionmodules/';
	
	protected static $instance;
	
	private $log_activated;
	
	function getPluginName()
	{
			return "StructureImport";
	}

	public function txt(string $a_var) : string
    {
        global $DIC;
        return $DIC->language()->txt('ui_uihk_structureimport_'.$a_var);
    }

    /**
	 * @return ilStructureImportDBManager
	 */
	public static function getInstance() {
		if (!isset(self::$instance)) 
		{
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	public function updateModuleDB()
	{
		$db_manager = ilStructureImportDBManager::getInstance();
		
		// The name is $dir_content and not $files cause it can contain folders and files
		$dir_content = scandir(ilStructureImportConstants::PATH_TO_ACTION_MODULES);
		
		if($dir_content != false)
		{
			$module_list = array();
			
			foreach($dir_content as $content)
			{
				if(is_file(ilStructureImportConstants::PATH_TO_ACTION_MODULES . $content))
				{
					$tmp = str_replace('class.', '', $content);
					$class_name = str_replace('.php', '', $tmp);
			
					include_once(ilStructureImportConstants::PATH_TO_ACTION_MODULES . $content);
					if(is_subclass_of($class_name, 'ilStructureImportActionModuleBase'))
					{
						$module_list[] = array(
								ilStructureImportConstants::COL_MODULE_NAME => call_user_func(array($class_name, 'getModuleName')),
								ilStructureImportConstants::COL_ACTION_TYPE => call_user_func(array($class_name, 'getActionType')),
								ilStructureImportConstants::COL_ACTION_LANG_NAME => call_user_func(array($class_name, 'getActionLangName')),
								ilStructureImportConstants::COL_REQUIRED_PARAMETERS => call_user_func(array($class_name, 'getRequiredParameters')),
								ilStructureImportConstants::COL_OPTIONAL_PARAMETERS => call_user_func(array($class_name, 'getOptionalParameters')),
								ilStructureImportConstants::COL_FILENAME => $content
						);
					}
				}
			}
			$db_manager->_updateModuleList($module_list);
			
			return $module_list;
		}
	}
	
	public function getCreateTypesAsArray()
	{
	    $db_manager = ilStructureImportDBManager::getInstance();
	    $create_types = array();
	    
	    foreach($db_manager->_lookupAllModules() as $action_module_record)
	    {
	        if($action_module_record[ilStructureImportConstants::COL_ACTION_TYPE] == 'create')
	        {
	            try 
	            {
        			$filename = $action_module_record[ilStructureImportConstants::COL_FILENAME];
        			$tmp = str_replace('class.', '', $filename);
        			$class_name = str_replace('.php', '', $tmp);
        			
        			$action_module_dir = ilStructureImportConstants::PATH_TO_ACTION_MODULES . $filename;
        			if(is_file($action_module_dir))
        			{
        				include_once($action_module_dir);
        				$create_types[] = call_user_func(array($class_name, 'getCreateType'));
        			}
	            }
	            catch (Exception $e)
	            {
	                return array();
	            }
	        }
	    }
	    return $create_types;
	}
	
	public function getRoleName()
	{
	    return 'Structure Importer';
	}
	
	public function getRoleDescription()
	{
	    return 'This role grants the permission to execute the Structure Import';
	}
	
	public function getImporterRoleId()
	{
	    $search_for_importer_role = ilObject::_getIdsForTitle($this->getRoleName(), 'role', false);
	    if(count($search_for_importer_role) > 0)
	    {
	        $importer_role_id = $search_for_importer_role[0];
	    }
	    else 
	    {
	        $importer_role_id = null;
	    }
	    return $importer_role_id;
	}
}
 
?>
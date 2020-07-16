<?php
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportConstants.php';
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/actiontypebase/class.ilStructureImportActionModuleBase.php';
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportPlugin.php';
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilImportExcel.php';

class ilStructureImportAssignRole extends ilStructureImportActionModuleBase
{
	protected static $module_name = 'assign_role';
	protected static $action_type = 'assign';
	protected static $action_lang_name = 'action_assign_role';
	protected static $required_parameters = 'action;path;login;role';
	
	/**
	 * Executes command from row.
	 * 
	 * @param array		$row
	 * @param integer 	$currentRef
	 */
	public function executeAction($row, $root_ref, $current_ref)
	{
	    $path_string = $row[$this->plugin->txt(ilStructureImportConstants::EXCELCOL_PATH)];
	    $this->log->write("Start searching following path: $path_string", 1);
		$new_ref = $this->findPath($root_ref, $current_ref, $path_string);
		
		/* Execute actions if there is a path */
		if($new_ref > 0)
		{
            $this->log->write("Found path with a ref_id of '$new_ref'", 1);
            $status =  $this->assignRole($row, $new_ref);
		}
		
		return $new_ref;
	}
	
	private function assignRole($row, $object_ref)
	{
	    global $ilias;
	    
	    /* Get informations for action */
	    $status = 0;
	    
	    // excelinfos
	    $role_string = $row[$this->plugin->txt(ilStructureImportConstants::EXCELCOL_ROLE)];
		$user_string = $row[$this->plugin->txt(ilStructureImportConstants::EXCELCOL_LOGIN)];
	    
		// user and object infos
		$user_array = $this->getUserArrayFromString($user_string);
        $obj_id = ilObject::_lookupObjectId($object_ref);
        
        /* Execute action */
        $this->log->write("assign role the role '$role_string' to users '$user_string' in object id of '$obj_id'", 1);
        $status = $this->addUsersToContainer($user_array, $role_string, $obj_id);

        /* Return */
	    $this->log->write('return following status: ' . $status, 1);
	    return $status;
	}
}

?>
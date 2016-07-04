<?php

include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/actiontypebase/class.ilStructureImportActionModuleBase.php';
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportPlugin.php';
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilImportExcel.php';
include_once './Services/Membership/classes/class.ilParticipants.php';
include_once './Modules/Group/classes/class.ilGroupParticipants.php';
include_once './Modules/Course/classes/class.ilCourseParticipants.php';
include_once './Modules/Course/classes/class.ilCourseConstants.php';

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
	    $path_string = $row[$this->plugin->txt(ilImportExcel::EXCELCOL_PATH)];
	    $this->log->write("Start searching following path: $path_string", 1);
		$new_ref = $this->findPath($root_ref, $current_ref, $path_string);
		
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
	    
	    $status = 0;
	    $role = $row[$this->plugin->txt(ilImportExcel::EXCELCOL_ROLE)];
		$user_string = $row[$this->plugin->txt(ilImportExcel::EXCELCOL_LOGIN)];
	    $user_array = explode(',', $user_string);

	    $object = $ilias->obj_factory->getInstanceByRefId($object_ref);
	    if($object_ref > 1)
	    {
	        $obj_type = $object->getType();
	        $obj_id = $object->getId();
	        $this->log->write("assign role in: $obj_type", 1);
	        $this->log->write("assign role '$role' to users: $user_string", 1);
    	    switch($obj_type)
    	    {
    	        case 'crs':
    	            $status = $this->addToCourse($obj_id, $user_array, $role);
    	            break;
    	        case 'grp':
    	            $status = $this->addToGroup($obj_id, $user_array, $role);
    	            break;
    	        default:
    	            $this->log->write("Error: Its not possible to assign a role to users in an object with the type of'$obj_type'", 10);
    	            $status = -1;
    	            break;
    	    }
	    }
	    else
	    {
	        $this->log->write("Error: Container not found", 10);
	        $status = -1;
	    }

	    $this->log->write('return following status: ' . $status, 1);
	    return $status;
	}
	
	private function addToGroup($obj_id, $user_array, $role)
	{
	    $group_members_object = ilGroupParticipants::_getInstanceByObjId($obj_id);
	    
	    switch(strtolower($role))
	    {
	        case strtolower($this->plugin->txt('ROLE_ADMIN')):
	            $role_from_const = IL_GRP_ADMIN;
	            break;
	        case strtolower($this->plugin->txt('ROLE_MEMBER')):
	            $role_from_const = IL_GRP_MEMBER;
	            break;
	        case '':
	            $role_from_const = IL_GRP_MEMBER;
	            break;
	        default:
	            $role_from_const = '';
	            break;
	    }
	     
	    if($role_from_const!= '')
	    {
	        foreach($user_array as $user_name)
	        {
	            $user_id = ilObjUser::getUserIdByLogin(trim($user_name));
	            if($user_id > 0)
	            {
	               $group_members_object->add($user_id,$role_from_const);
	               $success_users .= $user_name . ',';
	            }
	            else 
	            {
	                
	                $error_users .= $user_name . ',';
	            }
	        }
	        
	        if($success_users != '')
	        {
	            $success_users = trim($success_users, ',');
	            $this->log->write("The role '$role' was succesfully assigned to following users: $success_users", 1);
	        }
	        
	        if($error_users != '')
	        {
	            //Better just log it. This is not a dangerous error
	            $error_users = trim($error_users, ',');
	            $this->log->write("Error while adding following users: $error_users", 5);
	        }
	    }
	    else
	    {
	        $this->error_message[] = $this->plugin->txt('error_role_not_found');
	        return -1;
	    }
	     
	    return 0;
	}
	
	private function addToCourse($obj_id, $user_array, $role)
	{
	    $course_members_object = ilCourseParticipants::_getInstanceByObjId($obj_id);;
	    
	    switch(strtolower($role))
	    {
	        case strtolower($this->plugin->txt('role_admin')):
	            $role_from_const = IL_CRS_ADMIN;
	            break;
	        case strtolower($this->plugin->txt('role_tutor')):
	            $role_from_const = IL_CRS_TUTOR;
	            break;
	        case strtolower($this->plugin->txt('role_member')):
	            $role_from_const = IL_CRS_MEMBER;
	            break;
	        case '':
	            $role_from_const = IL_CRS_MEMBER;
	            break;
	        default:
	            $role_from_const = '';
	            break;
	    }
	     
	    if($role_from_const!= '')
	    {
	        foreach($user_array as $user_name)
	        {
	            $user_id = ilObjUser::getUserIdByLogin(trim($user_name));
	            if($user_id > 0)
	            {
	               $course_members_object->add($user_id,$role_from_const);
	               $success_users .= $user_name . ',';
	            }
	            else 
	            {
	                $error_users .= $user_name . ',';
	            }
	        }
	        
	        if($success_users != '')
	        {
	            $success_users = trim($success_users, ',');
	            $this->log->write("The role '$role' was succesfully assigned to following users: $success_users", 1);
	        }
	        
	        if($error_users != '')
	        {
	            //Better just log it. This is not a dangerous error
	            $error_users = trim($error_users, ',');
	            $this->log->write("Error while adding following users: $error_users", 5);
	            //$this->error_messages[] = $this->plugin->txt() . $error_users;
	            //return -1;
	        }
	    }
	    else
	    {
	        $this->error_message = $this->plugin->txt('error_role_not_found');
	        return -1;
	    }
	     
	    return 0;
	}
}

?>
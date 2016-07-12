<?php

include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/actiontypebase/class.ilStructureImportCreate.php';
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportPlugin.php';
include_once './Services/Membership/classes/class.ilParticipants.php';
include_once './Modules/Group/classes/class.ilGroupParticipants.php';
include_once './Modules/Group/classes/class.ilObjGroup.php';

class ilStructureImportCreateGroup extends ilStructureImportCreate
{
    /* Module constants (get with getter-method) */
	protected static $module_name = 'create_group';
	protected static $action_type = 'create';
	protected static $action_lang_name = 'action_create_group';
	protected static $required_parameters = 'action;name;path';
	protected static $optional_parameters = 'description;members';
	protected static $create_type = 'grp';
	
	const GROUP_TYPE = 'group_type';
	
    public function __construct($log)
	{
	    parent::__construct($log);
	    
	    $this->type = 'grp';
	    
	    $this->group_type = $this->config->getValue(self::getModuleName(), self::GROUP_TYPE);
	    $groupOwnerNotification = 'No';
	}
	
	protected function createObject($row, $container_ref)
	{		
		global $ilUser;
		
		/* Init */
		$status = 0;
		$role = $row[$this->plugin->txt(ilImportExcel::EXCELCOL_ROLE)];
		$user_string = $row[$this->plugin->txt(ilImportExcel::EXCELCOL_LOGIN)];

		/* Create object */
		$group_obj = new ilObjGroup();
		$status = $this->initObjectBase($group_obj, $row, $container_ref);
	    if($status != 0)
	    {
	        return $status;
	    }
		
		/* Add members */
		$group_members_object = ilGroupParticipants::_getInstanceByObjId($group_obj->getId());
		$this->addMembers($group_members_object, $user_string, $role);
		
		/* Close group */
		$group_type = $this->config->getValue(self::getModuleName(),"group_type");
		$group_obj->initGroupStatus($this->group_type);
		
		/* Apply changes */
		$group_obj->update();
		
		return $status;
	}
	
	private function addMembers(&$group_members_object, $user_string, $role)
	{
	    $user_array = explode(',', $user_string);
	    switch(strtolower($role))
	    {
	        case strtolower($this->plugin->txt('role_admin')):
	            $role_from_const = IL_GRP_ADMIN;
	            break;
	        case strtolower($this->plugin->txt('role_member')):
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
	        $error_users;
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
	            //$this->error_messages[] = $this->plugin->txt() . $error_users;
	            //return -1;
	        }
	    }
	    else
	    {
	        $this->error_message = 'error_role_not_found';
	        return -1;
	    }
	    
	    return 'ok';
	}
	
	public static function getConfFields()
	{
	    $plugin = ilStructureImportPlugin::getInstance();
	    
	    $parent_fields = parent::getConfFields();
	    
	    $child_fields = array(
	            self::GROUP_TYPE => array(
	                    'type' => 'ilRadioGroupInputGUI',
	                    'options' => array(
	                            'group_open' => GRP_TYPE_PUBLIC,
	                            'group_closed' => GRP_TYPE_CLOSED
	                            /* There are also GRP_TYPE_OPEN and GRP_TYPE_UNKNOWN
	                             * But they are never meant to be selected */
	                    )
	            )
	    );
	    
	    if(is_array($parent_fields))
	    {
	    	 $fields = $parent_fields + $child_fields ;
	    }
	    else
	    {
        	 $fields = $child_fields;       
	    }
	    
	    return $fields;
	}
	
	public static function getDefaultConfigValues()
	{
	    $parent_values = parent::getDefaultConfigValues();
	    
	    $child_values = array(
	            self::GROUP_TYPE => GRP_TYPE_CLOSED
	           );
	    
	    $default_values = $parent_values + $child_values;
	    
	    return $default_values;
	}
}

?>
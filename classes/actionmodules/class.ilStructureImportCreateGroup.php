<?php
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/actiontypebase/class.ilStructureImportCreate.php';

class ilStructureImportCreateGroup extends ilStructureImportCreate
{
    /* Module constants (get with getter-method) */
	protected static $module_name = 'create_group';
	protected static $action_type = 'create';
	protected static $action_lang_name = 'action_create_group';
	protected static $required_parameters = 'action;name;path';
	protected static $optional_parameters = 'description;login;role';
	protected static $create_type = 'grp';
    protected $grp_dic_tpl;
	
    public function __construct($log)
	{
        global $ilDB;
        
	    parent::__construct($log);
	    
	    $this->type = 'grp';
	    
	    $this->group_type = $this->config->getValue(self::getModuleName(), ilStructureImportConstants::GROUP_TYPE);
	    $groupOwnerNotification = 'No';
	    
	    // Get didactic template for group role
	    $sql = 'SELECT * FROM didactic_tpl_settings WHERE title = "grp_closed"';
	    $res = $ilDB->query($sql);
	    $row = $ilDB->fetchAssoc($res);
	    $this->grp_dic_tpl = $row['id'];
	}
	
	protected function createObject($row, $container_ref)
	{		
		global $ilUser;
		
		/* Init */
		$status = 0;
		$role_string = strtolower($row[$this->plugin->txt(ilStructureImportConstants::EXCELCOL_ROLE)]);
		$user_string = $row[$this->plugin->txt(ilStructureImportConstants::EXCELCOL_LOGIN)];
		$user_array = $this->getUserArrayFromString($user_string);
		
		// Check if there is 1 or more users given for the admin role
		// This is only the case, if the given user role is the "Admin"-Role and there is min 1 user
		// Else the person who executes the import will be added as "Admin"
		if($role_string == $this->plugin->txt('role_admin') && count($user_array) > 0)
		{
		    $has_admin_user = true;
		}
		else 
		{
		    $has_admin_user = false;
		}

		/* Create object */
		$group_obj = new ilObjGroup();
		$status = $this->initObjectBase($group_obj, $row, $container_ref);
		$obj_id = $group_obj->getId();
	    if($status != 0)
	    {
	        return $status;
	    }
		
		/* Add members */
	    
        // Add users given from the action
	    if(count($user_array) > 0)
	       $this->addUsersToContainer($user_array, $role_string, $obj_id);
	    
	    // If there isnt already an admin -> add the person who executes the import as admin
	    if(!$has_admin_user)
	        $this->addUsersToContainer(array($this->executing_user), $this->plugin->txt('role_admin'), $obj_id);
		
		/* Close/Open group */
		//$group_type = $this->config->getValue(self::getModuleName(),"group_type");
		//$group_obj->initGroupStatus($this->group_type);
		$group_obj->applyDidacticTemplate($this->grp_dic_tpl);
		
		/* Apply changes */
		$group_obj->update();
		
		return $status;
	}
	
	/**
	 * 
	 * @return number|string[][]|string[][][]
	 */
	public static function getConfFields()
	{
	    $plugin = ilStructureImportPlugin::getInstance();
	    
	    $parent_fields = parent::getConfFields();
	    
	    $child_fields = array(
	            ilStructureImportConstants::GROUP_TYPE => array(
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
	
	/**
	 * 
	 * @return number
	 */
	public static function getDefaultConfigValues()
	{
	    $parent_values = parent::getDefaultConfigValues();
	    
	    $child_values = array(
	            ilStructureImportConstants::GROUP_TYPE => GRP_TYPE_CLOSED
	           );
	    
	    $default_values = $parent_values + $child_values;
	    
	    return $default_values;
	}
}

?>
<?php
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/actiontypebase/class.ilStructureImportCreate.php';
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
	
    public function __construct($log)
	{
	    parent::__construct($log);
	    
	    $this->type = 'grp';
	    
	    $this->group_type = $this->config->getValue(self::getModuleName(), ilStructureImportConstants::GROUP_TYPE);
	    $groupOwnerNotification = 'No';
	}
	
	protected function createObject($row, $container_ref)
	{		
		global $ilUser;
		
		/* Init */
		$status = 0;
		$role = $row[$this->plugin->txt(ilStructureImportConstants::EXCELCOL_ROLE)];
		$user_string = $row[$this->plugin->txt(ilStructureImportConstants::EXCELCOL_LOGIN)];
		$user_array = $this->getUserArrayFromString($user_string);

		/* Create object */
		$group_obj = new ilObjGroup();
		$status = $this->initObjectBase($group_obj, $row, $container_ref);
	    if($status != 0)
	    {
	        return $status;
	    }
		
		/* Add members */
	    $this->addUsersToContainer($user_array, $role_string, $obj_id);
		
		/* Close/Open group */
		$group_type = $this->config->getValue(self::getModuleName(),"group_type");
		$group_obj->initGroupStatus($this->group_type);
		
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
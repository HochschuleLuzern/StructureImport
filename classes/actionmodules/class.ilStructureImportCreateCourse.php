<?php
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportConstants.php';
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/actiontypebase/class.ilStructureImportCreate.php';
include_once './Modules/Course/classes/class.ilObjCourse.php';
include_once './Modules/Course/classes/class.ilCourseConstants.php';
include_once './Modules/Course/classes/class.ilCourseParticipants.php';

class ilStructureImportCreateCourse extends ilStructureImportCreate
{
	protected static $module_name = 'create_course';
	protected static $action_type = 'create';
	protected static $action_lang_name = 'action_create_course';
	protected static $required_parameters = 'action;name;path';
	protected static $optional_parameters = 'description;members';
	protected static $create_type = 'crs';
	
    public function __construct($log)
	{
	    parent::__construct($log);
	    
	    $this->type = 'crs';
	    
	    /* HSLU Defaults */
	    $this->courseSort = 'Title';
	    $this->courseAvailability = 'Unlimited';
	    $this->courseRegistration = 'Disabled'; // Gültig ist "Registration", dann für  Typ: "Confirmation", "Direct", "Password". Alles andere schaltet nicht sein.
	    $this->courseRegistrationTimespan = 'Unlimited';
	    $this->courseOwnerNotification = 'No';
	}
	
    protected function createObject($row, $container_ref)
	{
		global $lng, $ilUser;
		
		/* Init */
		$status = 0;
		$role_string = $row[$this->plugin->txt(ilStructureImportConstants::EXCELCOL_ROLE)];
		$user_string = $row[$this->plugin->txt(ilStructureImportConstants::EXCELCOL_LOGIN)];
		$user_array = $this->getUserArrayFromString($user_string);
		
		/* Values from the old structure import
		$courseSort = 'Title';
		$courseAvailability = 'Unlimited';
		$courseRegistration = 'Disabled'; // Gültig ist "Registration", dann für  Typ: "Confirmation", "Direct", "Password". Alles andere schaltet nicht sein.
		$courseRegistrationTimespan = 'Unlimited';
		$courseOwnerNotification = 'No';*/
				
		/* Start import */
		$course_obj = new ilObjCourse();
		$status = $this->initObjectBase($course_obj, $row, $container_ref);
		$obj_id = $course_obj->getId();
	    if($status != 0)
	    {
	        return $status;
	    }
		
		/* Availability */
		$course_obj->setOfflineStatus(false);
		$course_obj->setActivationType(IL_CRS_ACTIVATION_UNLIMITED);
		
		/* Registration */	
		$course_obj->getSubscriptionLimitationType($this->courseRegistration);
		$course_obj->setSubscriptionType($this->courseRegistration);
		
		/* Add members */
		$this->addUsersToContainer($user_array, $role_string, $obj_id);
		
		/* Apply changes */
		$course_obj->update();
		
		return $status;
	}
	
	/**
	 * 
	 * @return number
	 */
	public static function getConfFields()
	{
	    $plugin = ilStructureImportPlugin::getInstance();
	     
	    $parent_fields = parent::getConfFields();
	    
	    $child_fields = array();
	    /*$child_fields = array(
	            ilStructureImportConstants::CRS_ACTIVATION_TYPE => array(
	                    'type' => 'ilRadioGroupInputGUI',
	                    'options' => array(
	                            'crs_activation_offline' => IL_CRS_ACTIVATION_OFFLINE,
	                            'crs_activation_unlimited' => IL_CRS_ACTIVATION_UNLIMITED
	                            /*'crs_activation_limited' => array(
	                                    'subelements' => array(
	                                            'CRS_ACTIVATION_LIMITED_SELECTED' => array(
	                                                    'type' => 'ilDurationInputGUI'))
	                            )
	                            
	                            
                        
	            )
	    );*/
	     
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
}

?>
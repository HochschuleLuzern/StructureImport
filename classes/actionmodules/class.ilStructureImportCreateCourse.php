<?php

include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/actiontypebase/class.ilStructureImportCreate.php';
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportPlugin.php';
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
	
	const CRS_ACTIVATION_TYPE = 'crs_activation_type';
	
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
		$role = $row[$this->plugin->txt(ilImportExcel::EXCELCOL_ROLE)];
		$user_string = $row[$this->plugin->txt(ilImportExcel::EXCELCOL_LOGIN)];
		
		/* Values from the old structure import
		$courseSort = 'Title';
		$courseAvailability = 'Unlimited';
		$courseRegistration = 'Disabled'; // Gültig ist "Registration", dann für  Typ: "Confirmation", "Direct", "Password". Alles andere schaltet nicht sein.
		$courseRegistrationTimespan = 'Unlimited';
		$courseOwnerNotification = 'No';*/
				
		/* Start import */
		$course_obj = new ilObjCourse();
		$status = $this->initObjectBase($course_obj, $row, $container_ref);
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
		$course_members_object = ilCourseParticipants::_getInstanceByObjId($course_obj->getId());
		if($user_string != '')
		{
    		$this->addMembers($course_members_object, $user_string, $role);
		}
		
		/* Add Owner as Courseadmin */
		$course_members_object->add($this->executing_user, IL_CRS_ADMIN);
		
		/* Apply changes */
		$course_obj->update();
		
		return $status;
	}
	
	private function addMembers(&$course_members_object, $user_string, $role)
	{		
		$user_array = explode(',', $user_string);
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
		    $success_users = '';
		    $error_users = '';
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
	    
	    $child_fields = array();
	    /*$child_fields = array(
	            self::CRS_ACTIVATION_TYPE => array(
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
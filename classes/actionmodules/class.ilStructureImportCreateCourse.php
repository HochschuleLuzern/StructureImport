<?php
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportConstants.php';
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/actiontypebase/class.ilStructureImportCreate.php';

class ilStructureImportCreateCourse extends ilStructureImportCreate
{
    protected static $module_name = 'create_course';
    protected static $action_type = 'create';
    protected static $action_lang_name = 'action_create_course';
    protected static $required_parameters = 'action;name;path';
    protected static $optional_parameters = 'description;login;role';
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
        $role_string = strtolower($row[$this->plugin->txt(ilStructureImportConstants::EXCELCOL_ROLE)]);
        $user_string = $row[$this->plugin->txt(ilStructureImportConstants::EXCELCOL_LOGIN)];
        $user_array = $this->getUserArrayFromString($user_string);
        
        // Check if there is 1 or more users given for the admin role
        // This is only the case, if the given user role is the "Admin"-Role and there is min 1 user
        // Else the person who executes the import will be added as "Admin"
        if ($role_string == $this->plugin->txt('role_admin') && count($user_array) > 0) {
            $has_admin_user = true;
        } else {
            $has_admin_user = false;
        }
        
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
        if ($status != 0) {
            return $status;
        }
        
        /* Availability */
        $course_obj->setOfflineStatus(false);
        
        /* Registration */
        $course_obj->getSubscriptionLimitationType($this->courseRegistration);
        $course_obj->setSubscriptionType($this->courseRegistration);
        
        /* Add members */
        
        // Add users given from the action
        $this->addUsersToContainer($user_array, $role_string, $obj_id);
        
        // If there isnt already an admin -> add the person who executes the import as admin
        if (!$has_admin_user) {
            $this->addUsersToContainer(array($this->executing_user), $this->plugin->txt('role_admin'), $obj_id);
        }
        
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
         
        if (is_array($parent_fields)) {
            $fields = $parent_fields + $child_fields ;
        } else {
            $fields = $child_fields;
        }
         
        return $fields;
    }
}

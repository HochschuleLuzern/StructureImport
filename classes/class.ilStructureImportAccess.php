<?php
include_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportPlugin.php");
include_once './Services/Object/classes/class.ilObject.php';

class ilStructureImportAccess
{
    protected static $instance;
    
    protected function __construct()
    {
        global $rbacsystem, $rbacreview, $ilUser, $objDefinition;
        
        $this->rbacsystem = &$rbacsystem;
        $this->rbacreview = &$rbacreview;
        $this->ilUser = &$ilUser;
        $this->objDefinition = &$objDefinition;
        $this->plugin = ilStructureImportPlugin::getInstance();
    }
    
    /**
     * Make this class singleton
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * This function is an allround check if the user has access to the plugin.
     * Use this function only if you only want a true/false answer and dont care
     * about the reason.
     *
     * @param integer   $ref_id
     * @return boolean  $has_access
     */
    public function hasAccessToPlugin($ref_id)
    {
        $has_access = false;
        
        // $ilUser is not necesseraly defined (access for newsfeed etc.)
        if (!$this->ilUser) {
            return false;
        }
        
        // No Structure Import from root
        if ($ref_id > 0) {
            $user_id = $this->ilUser->getId();
            
            /* Check if is Admin or Importer*/
            if ($this->isAdminRole($user_id) || $this->isImporterRole($user_id)) {
                /* Check if the user has create access for every object that could be created
                 * with the structure import (at the moment thats cat, crs, grp and fold) */
                $has_access = $this->hasCreateAccess($user_id, $ref_id);
            }
        }
        
        return $has_access;
    }
    
    /**
     * Checks if given user is admin
     * @param interger  $user_id
     * @return boolean  $is_admin
     */
    public function isAdminRole($user_id)
    {
        // 2 is the obj_id of the adminrole (ilias standart)
        $is_admin = in_array($user_id, $this->rbacreview->assignedUsers(2));
        return $is_admin;
    }
    
    /**
     * Checks if given user is in importerrole
     * @param integer   $user_id
     * @return boolean  $is_importer
     */
    public function isImporterRole($user_id)
    {
        // The importer Role will be created at installation and has a variable obj_id for every installation
        $importer_role_id = $this->plugin->getImporterRoleId();
        
        if ($importer_role_id != null) {
            $is_importer = in_array($user_id, $this->rbacreview->assignedUsers($importer_role_id));
        } else {
            $is_importer = false;
        }
        
        return $is_importer;
    }
    
    /**
     * Checks if the user has create-access for all create-actionmodules
     * @param unknown $ref_id
     * @return boolean
     */
    public function hasCreateAccess($user_id, $ref_id)
    {
        /* Get the type of the current obj */
        $obj_id = ilObject::_lookupObjectId($ref_id);
        $target_type = ilObject::_lookupType($obj_id);
        
        /* Get array with allowed subtypes*/
        $allowed_subtypes = $this->getCreatableActionmoduletypes($target_type);
        $has_create_access = false;
        
        if (count($allowed_subtypes) > 0) {
            $has_create_access = true;
            
            /* Check if create access for every create-module is granted */
            foreach ($allowed_subtypes as $create_type) {
                $has_create_access &= $this->rbacsystem->checkAccessOfUser($user_id, 'create', $ref_id, $create_type);
            }
        }

        return $has_create_access;
    }
    
    /**
     * The function checkAccess from rbacsystem should only be used against types that are allowed as
     * subobject. Because for all not allowed subobjs the return will always be false (even for admins).
     * So I just will get all allowed subtypes, before I check if the user has create access.
     * @param string    $type
     * @return array    $types_to_check
     */
    public function getCreatableActionmoduletypes($type)
    {
        global $ilDB;
        
        // This functions gets a strange array...
        // $this->objDefinition->getCreatableSubObjects($type);
        
        /* I did not found a function to get every allowed subtype. So I just created SQL by myself
         * So this SQL saves every allowed subtype in an array and I combine it with all action modules */
        $sql = sprintf("SELECT subobj FROM il_object_subobj WHERE parent = %s", $ilDB->quote($type, "text"));
        $result = $ilDB->query($sql);
        while ($allowed_subtypes[] = ($ilDB->fetchAssoc($result)['subobj']));
        
        // Get every create-action from the action modules
        $types_from_action_modules = $this->plugin->getCreateTypesAsArray();
        
        // Intersection of create-actions and allowed subtypes
        $types_to_check = array_intersect($types_from_action_modules, $allowed_subtypes);

        return $types_to_check;
    }
}

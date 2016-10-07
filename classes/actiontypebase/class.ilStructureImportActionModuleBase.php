<?php
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportConfig.php';
include_once './Services/Object/classes/class.ilObject.php';

abstract class ilStructureImportActionModuleBase
{	
	protected static $module_name = "";
	protected static $action_type = "";
	protected static $action_lang_name = "";
	protected static $required_parameters = "";
	protected static $optional_parameters = "";
	
	protected $plugin;
	protected $log;
	protected $ctrl;
	protected $error_messages = array();
	protected $config;
	protected $executing_user;
	
	function __construct($log)
	{
	    global $ilCtrl, $ilUser;
	    
	    $this->plugin = ilStructureImportPlugin::getInstance();
	    $this->log = &$log;
	    $this->ctrl = &$ilCtrl;
	    $this->error_messages = array();
	    $this->config = ilStructureImportConfig::getInstance();
	    $this->executing_user = $ilUser->getId();
	}
	
	/**
	 * Returns name of the module
	 */
	public static function getModuleName()
	{
		/* Thisisaverylongactionmodulenamesoidshouldhaveenoughspacecauseonehundredcharactersarereallynotenough! */

		if(strlen(static::$module_name) > 100)
		{
			return substr(static::$module_name, 0, 99);
		}
		return static::$module_name;
	}
	
	/**
	 * Returns the type of the module
	 */
	public static function getActionType()
	{
		if(strlen(static::$action_type) > 100)
		{
			return substr(static::$action_type, 0, 99);
		}
		return static::$action_type;
	}
	
	/**
	 * Returns the Name of the action
	 */
	public static function getActionLangName()
	{
		if(strlen(static::$action_lang_name) > 100)
		{
			return substr(static::$action_lang_name, 0, 99);
		}
		return static::$action_lang_name;
	}
	
	/**
	 * Returns the required parameters for this action
	 */
	public static function getRequiredParameters()
	{
		return static::$required_parameters;
	}
	
	/**
	 * Returns the optional parameters for this action
	 */
	public static function getOptionalParameters()
	{
		return static::$optional_parameters;
	}
	
	public static function getConfFields()
	{
	    return array();
	}
	
	/**
	 * DEPRECATED:
	 * 
	 * Returns the last error message that happend
	 */
	public function getErrorMessage()
	{
	    $count = count($this->error_messages);
	    
	    if($count < 0)
	    {
	        return "";
	    }
	    else
	    {
	        return $this->error_messages[$count - 1];
	    }
	}
	
	/**
	 * Executes command from row.
	 * 
	 * @param array		$row
	 * @param integer 	$currentRef
	 */
	abstract public function executeAction($row, $root_ref, $current_ref);
	
	/**
	 * Returns an array with the default values for the executing module.
	 * 
	 * Array Strucure: $key => $value
	 * 
	 * @return array   $default_values;
	 */
    public static function getDefaultConfigValues()
    {
        return array();
    }
	
	/**
	 * Returns the ref_id of the object in the given path.
	 * 
	 * If return = 0 then the ref_id is the importdirectory
	 * If return = -1 there was an error while searching -> check $this->errorMessage()
	 * If return > 0 then the return value is the searched ref_id of the path
	 * 
	 * @param int 		$current_ref	start for search
	 * @param string	$path_string	path
	 * 
	 * @return int		$new_ref		ref to the new object
	 */
	protected function findPath($root_ref, $current_ref , $path_string)
	{
	    // If the first character is a slash, the search starts from the import root
		$first_char = substr($path_string, 0, 1);
		if($first_char == '/')
		{
			$new_ref = $root_ref;
		}
		else
		{
			$new_ref = $current_ref;
		}
		
		/* Search through the path */
		$path_pieces = explode('/', $path_string);
		$depth = count($path_pieces);
		
		for($i = 0; $i < $depth && $new_ref != -1; $i++)
		{
			$piece = trim($path_pieces[$i]);
			if($piece != '.' && $piece != '')
			{
				$new_ref = $this->findChildObject($new_ref, $piece);
			}
		}
		
		return $new_ref;
	}
	
	/**
	 * Finds object in a Container
	 * 
	 * @param unknown $current_ref
	 * @param unknown $object_name
	 * @return number
	 */
	protected function findChildObject($current_ref, $object_name)
	{
		global $tree;
		
		$found_object = false; // Set true if Object was found
		
		$children_ref_ids = $tree->getChildIds($current_ref);
		$number_of_children = count($children_ref_ids);
		if($number_of_children > 0)
		{
		    /* Check each child-object if name matches */
			foreach($children_ref_ids as $child_ref_id)
			{
			    /* Get the title of the child-object */
				$obj_id = ilObject::_lookupObjId($child_ref_id);
				$title = ilObject::_lookupTitle($obj_id);
				
				/* Check if name matches */
				if($object_name == $title)
				{
				    /* If object found -> check if there is already an object with the same name */
					if(!$found_object)
					{
					    /* Found the object name for the first time */
						$found_object = true;
						$current_ref = $child_ref_id;
					}
					else 
					{
					    /* There are multiple objects with the same name -> error -> abort action */
						$this->log->write("Error: The object '$title' exists multiple times in this folder");
						return -1;
					}
				}
			}
			
			/* Check if there was an object with this name. If not -> error -> abort action */
			if(!$found_object)
			{
				$plugin = ilStructureImportPlugin::getInstance();
				$this->log->write("Error: Object '$title' not found");
				$current_ref = -1;
			}
		}
		else 
		{
			$this->log->write("Error: Container with the ref_id of '$current_ref' is emtpy");
			$current_ref = -1;
		}
		return $current_ref;
	}
	
	/**
	 * Converts a comma-separated string of usernames (loginname) to an array with the user_id
	 * of every given user that exists
	 * 
	 * @param string      $user_string
	 * @return integer[]  $user_id_array
	 */
	protected function getUserArrayFromString($user_string)
	{
	    if($user_string == '')
	    {
	        return array();
	    }
	    
	    $user_name_array = explode(',', $user_string);
	    
	    $user_id_array = array();
	    
	    foreach($user_name_array as $user_name)
	    {
	        $user_id = ilObjUser::getUserIdByLogin(trim($user_name));
	        if($user_id > 0)
	            $user_id_array[] = $user_id;
	        else 
	            $this->log->write("Error: The user $user_name was not found", 5);
	    }
	    
	    return $user_id_array;
	}
	
	/**
	 * Adds an array of user_ids to the given object (obj_id) with the given role (role_string)
	 * 
	 * @param integer[] $user_array
	 * @param string    $role_string
	 * @param integer   $obj_id
	 */
	protected function addUsersToContainer($user_array, $role_string, $obj_id)
	{
	    $status = 0;
	    
	    $obj_type = ilObject::_lookupType($obj_id);
	    
	    /* Getting the needed objects */
	    include_once './Services/Membership/classes/class.ilParticipants.php';
	    
	    // Get the role id from its name
	    $role_from_const = $this->getRoleConstFromString($role_string, $obj_type);
	    
	    // Gets the object in which we can add the users with the role id
	    $participants_obj = $this->getParticipantsObject($obj_id, $obj_type);
	    
	    /* Add user with given roles to Container */
	    if($role_from_const != -1 && $participants_obj != -1)
	    {
	        foreach($user_array as $user_id)
	           $participants_obj->add($user_id, $role_from_const);
	    }
	    
	    return $status;
	}
	
	/**
	 * Gets the const role id from a string that contains the rolename
	 * 
	 * @param string $role_string
	 * @param string $obj_type
	 * @return number|string
	 */
	protected function getRoleConstFromString($role_string, $obj_type)
	{
	    $role_from_const = -1;
	    switch($obj_type)
	    {
	        case 'crs':
	            include_once './Modules/Course/classes/class.ilCourseConstants.php';
	            switch(strtolower($role_string))
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
	                    
	                default:
        	            $this->log->write("Error: The rolename '$role_string' does not exist in the objecttype '$obj_type'", 5);
	                    break;
	            }
	            break;
	            
	        case 'grp':
	            switch(strtolower($role_string))
	            {
        	        case strtolower($this->plugin->txt('ROLE_ADMIN')):
        	            $role_from_const = IL_GRP_ADMIN;
        	            break;
        	            
        	        case strtolower($this->plugin->txt('ROLE_MEMBER')):
        	            $role_from_const = IL_GRP_MEMBER;
        	            break;
        	            
        	        default:
        	            $this->log->write("Error: The rolename '$role_string' does not exist in the objecttype '$obj_type'", 5);
        	            break;
	            }
	            break;
	            
	        default:
	            $this->log->write("Error: The objecttype '$obj_type' does not support members", 5);
	            break;
	    }
	    
        return $role_from_const;
	}
	
	/**
	 * Gets the participants object for the given obj_id
	 * 
	 * @param integer $obj_id
	 * @param string $obj_type
	 * @return number|ilCourseParticipants|mixed
	 */
	protected function getParticipantsObject($obj_id, $obj_type = '')
	{
        if($obj_type == '')
            $obj_type = ilObject::_lookupType($obj_id);	    
	    
	    $participants_object = -1;
	    
	    switch ($obj_type)
	    {
	        case 'crs':
	            include_once './Modules/Course/classes/class.ilCourseParticipants.php';
	            $participants_object = ilCourseParticipants::_getInstanceByObjId($obj_id);
	            break;
	            
	        case 'grp':
	            include_once './Modules/Group/classes/class.ilGroupParticipants.php';
	            $participants_object = ilGroupParticipants::_getInstanceByObjId($obj_id);
	            break;
	            
	        default:
	            $this->log->write("Error: Did not found a participants object for the objecttype '$obj_type'");
                break;
        }
        
        return $participants_object;
    }
}

?>
<?php
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/actiontypebase/class.ilStructureImportActionModuleBase.php';
include_once './Services/Container/classes/class.ilContainer.php';

abstract class ilStructureImportCreate extends ilStructureImportActionModuleBase
{
    const CONF_ORDER_TYPE = 'order_type';
    
    protected $order_type;
    protected static $create_type = '';
    
    public function __construct($log)
    {
        parent::__construct($log);
        
        $this->order_type = $this->config->getValue(self::getModuleName(), self::CONF_ORDER_TYPE);
    }
    
    public function executeAction($row, $root_ref, $current_ref)
    {
        $path_string = $row[$this->plugin->txt(ilImportExcel::EXCELCOL_PATH)];
        
        $this->log->write("Start searching following path: $path_string", 1);
        $new_ref = $this->findPath($root_ref, $current_ref, $path_string);
        
        if($new_ref > 0)
        {
            $this->log->write("Found path with a ref_id of '$new_ref'", 1);
            $status = $this->createObject($row, $new_ref);
        }
        
        if($status < 0)
        {
            $new_ref = -1;
        }
        
        return $new_ref;
    }
    
    protected function initObjectBase(&$new_obj, $row, $container_ref)
    {
        $status = 0;
        
        /* Init  */
        $title = $row[$this->plugin->txt(ilImportExcel::EXCELCOL_NAME)];
        $description = $row[$this->plugin->txt(ilImportExcel::EXCELCOL_DESCRIPTION)];
        
        /* Check permissions */
        $status = self::checkPermissions($container_ref, $this->type);
        if($status == -1)
        {
            return $status;
        }
        
        /* Check duplicate configuration */
        $status = $this->checkForDuplicate($container_ref, $title, $this->type);
        if($status == 0)
        { 
            /* Basic values */
            $new_obj->setOwner($this->executing_user);
            $new_obj->setType($this->type);
            $new_obj->setTitle($title);
            $new_obj->setDescription($description);
            $new_obj->create();
            $this->log->write("Object with the title '$title' created", 1);
            
            try
            {  
                /* Place in tree */
                $new_obj->createReference();
                $new_obj->putInTree($container_ref);
                $new_obj->setPermissions($container_ref);
                $this->log->write("New object with ref_id '" . $new_obj->getRefId()."' added in tree ", 1);
                
                /* Other options */
                // Set sortmode by input or configuration
                $sort_type = $this->getOrderTypeIndex($row[$this->plugin->txt(ilImportExcel::EXCELCOL_SORT_TYPE)]);
                
                // Some containers need to do this with a settings object, and some containers can do this direct
                include_once('Services/Container/classes/class.ilContainerSortingSettings.php');
                $settings = new ilContainerSortingSettings($new_obj->getId());
                $settings->setSortMode($sort_type);
                $settings->setSortDirection(ilContainer::SORT_DIRECTION_ASC);
                
                $new_obj->setOrderType($sort_type);
                
                $this->log->write("Setted ordertyp to " . $sort_type, 1);
                
                $settings->update();
                $new_obj->update();
            }
            catch(Exception $e)
            {
                $new_obj->delete();
                $status = -1;
            }  
        }
        
        return $status;        
    }
    
    private function getOrderTypeIndex($order_type_name)
    {
        $ret = -1;
        
        switch($order_type_name)
        {
            case $this->plugin->txt('config_sort_by_title'):
                $ret = ilContainer::SORT_TITLE;
                break;
            case $this->plugin->txt('config_sort_by_manual'):
                $ret = ilContainer::SORT_MANUAL;
                break;
            case $this->plugin->txt('config_sort_by_activation'):
                $ret = ilContainer::SORT_ACTIVATION;
                break;
            case $this->plugin->txt('config_sort_by_inherit'):
                $ret = ilContainer::SORT_INHERIT;
                break;
            case $this->plugin->txt('config_sort_by_creation'):
                $ret = ilContainer::SORT_CREATION;
                break;
            default:
                $ret = $this->order_type;
                break;
        }
        
        return $ret;
    }

    protected function checkForDuplicate($container_ref, $title, $type)
    {
        global $tree;
        
        $status = 0;
        
        $check = $this->config->getValue(ilStructureImportConfig::CONF_MAIN_SETTINGS, 
                                         ilStructureImportConfig::CONF_AVOID_DUPLICATE_CREATION);
        
        if($check != ilStructureImportConfig::CONF_VAL_IGNORE)
        {      
            foreach($tree->getChildIds($container_ref) as $child_ref_id)
            {
                $child_object_id = ilObject::_lookupObjectId($child_ref_id);
                $child_title = ilObject::_lookupTitle($child_object_id);
                if($check == ilStructureImportConfig::CONF_VAL_CHECK_NAME_AND_TYPE)
                {
                    $child_type = ilObject::_lookupType($child_object_id);
                    if($title == $child_title && $type == $child_type)
                    {
                        $status = $child_ref_id;
                    }
                }
                else if($check == ilStructureImportConfig::CONF_VAL_CHECK_NAME && $title == $child_title)
                {
                    $status = $child_ref_id;
                }
            }
        }
        
        if($status > 0)
        {
            $this->log->write("Error: where is already an object with the title '$title'");
        }
        
        return $status;
    }
    
	
	protected function checkPermissions($container_ref, $type)
	{
		global $rbacsystem, $objDefinition,$ilUser, $lng, $ilObjDataCache, $ilDB;
		$plugin = ilStructureImportPlugin::getInstance();
		$status = 0;
		
	    /* Check obj-subobj */
	    $container_obj_id = ilObject::_lookupObjId($container_ref);
	    $container_type = ilObject::_lookupType($container_obj_id);
	    
	    if($container_type != NULL)
	    {
	        $query = sprintf("SELECT * FROM il_object_subobj WHERE parent = %s AND subobj = %s",
	                $ilDB->quote($container_type, 'text'),
	                $ilDB->quote($this->type, 'text'));
	        $db_res = $ilDB->query($query);
	        $val_res = $ilDB->fetchAssoc($db_res);
	    
	        if(!$val_res)
	        {
	            $this->log->write("Error: Object - Subobject combination of $container_type - $this->type is not allowed", 10);
	            
	            $status = -1;
	        }
	    }
	    else
	    {
	        $this->log->write("Error: Invalid Type", 20);
	        $status = -1;
	    }

		
		return $status;
	}
	
	/*protected function getOwnerID($owner = 0)
	{
	    if($owner != 0 && strlen($owner))
	    {
	        if((int)$owner)
	        {
	            if(ilObject::_exists((int)$owner) &&
	                    ilObject::_lookupType((int)$owner) == 'usr')
	            {
	                $new_obj->setOwner((int)$owner);
	            }
	        }
	        else
	        {
	            $usr_id = ilObjUser::_lookupId(trim($owner));
	            if((int)$usr_id)
	            {
	                $new_obj->setOwner((int)$usr_id);
	            }
	        }
	    }
	}*/
	
	public static function getCreateType()
	{
	    return static::$create_type;
	}
	
	public static function getConfFields()
	{
	    $plugin = ilStructureImportPlugin::getInstance();
	    
	    $fields = array(
	            self::CONF_ORDER_TYPE => array(
	                    'type' => 'ilSelectInputGUI',
	                    'options' => array(
	                            ilContainer::SORT_TITLE => $plugin->txt('config_sort_by_title'),
	                            ilContainer::SORT_MANUAL => $plugin->txt('config_sort_by_manual'),
	                            ilContainer::SORT_ACTIVATION => $plugin->txt('config_sort_by_activation'),
	                            ilContainer::SORT_INHERIT => $plugin->txt('config_sort_by_inherit'),
	                            ilContainer::SORT_CREATION => $plugin->txt('config_sort_by_creation')
	                    )
	            )
	    );
	    
	    return $fields;
	}
	
	public static function getDefaultConfigValues()
	{
	    $parent_values = parent::getDefaultConfigValues();
	    
	    $child_values = array(
	            //$key => $value
	            self::CONF_ORDER_TYPE => ilContainer::SORT_TITLE
	    );
	    
	    $default_values = $parent_values + $child_values;
	    
	    return $default_values;
	}
}
<?php
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportDBManager.php';
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportConstants.php';

/**
 * ilLiveVotingConfig
 *
 * @author  Timon Amstutz <timon.amstutz@ilub.unibe.ch>
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 *
 * @version $Id$
 */
class ilStructureImportConfig {

    /* Conf titles */
    const CONF_MAIN_SETTINGS = 'main_settings';
    const CONF_INSTRUCTION_FILES_CONTAINER = 'instruction_files_container';
    const CONF_AVOID_DUPLICATE_CREATION = 'avoid_duplicate_creation';
    const CONF_LOG_LEVEL = 'log_level';
    const CONF_LOG_PATH = 'log_path';
    const CONF_IGNORE_DUPLICATE = 'ignore_duplicate';
    const CONF_ONLY_CHECK_NAME_FOR_DUPLICATE = 'only_check_name_for_duplicate';
    const CONF_CHECK_NAME_AND_TYPE_FOR_DUPLICATE = 'check_name_and_type_for_duplicate';
    
    /* Conf values */
    const CONF_VAL_IGNORE = 'ignore';
    const CONF_VAL_CHECK_NAME = 'check_name';
    const CONF_VAL_CHECK_NAME_AND_TYPE = 'check_name_and_type';
    
	/**
	 * @var string
	 */
	protected $table_name = ilStructureImportConstants::TABLE_CONF_NAME;
	/**
	 * @var array
	 */
	protected static $cache = array();

    protected static $instance;
    
	/**
	 * @param $table_name
	 */
	function __construct() 
	{
	    $this->plugin = ilStructureImportPlugin::getInstance();
	    $this->db_manager = ilStructureImportDBManager::getInstance();
	}

	public static function getInstance()
	{
	    if (!isset(self::$instance)) 
	    {
	        self::$instance = new self();
	    }
	    return self::$instance;
	}

	/**
	 * @param string $table_name
	 */
	public function setTableName($table_name) 
	{
		$this->table_name = $table_name;
	}


	/**
	 * @return string
	 */
	public function getTableName() 
	{
		return $this->table_name;
	}

	/**
	 * @param $key
	 * @param $value
	 */
	public function setValue($module_name, $key, $value) 
	{
		global $ilDB;
		if (!is_string($this->getValue($module_name, $key))) {
			$ilDB->insert($this->getTableName(), array( 
		        "module_name" => array(
		                "text",
		                $module_name
		        ),
				"key_name" => array(
					"text",
					$key
				),
				"value" => array(
					"text",
					$value
				)
			));
		} 
		else 
		{
			$ilDB->update($this->getTableName(), array(
			    "module_name" => array(
			            "text",
			            $module_name
			    ),			    
				"key_name" => array(
					"text",
					$key
				),
				"value" => array(
					"text",
					$value
				)
			), array(
			        "module_name" => array(
			                "text",
			                $module_name
				),
			        "key_name" => array(
			            "text",
			            $key
			    )
			));
		}
	}


	/**
	 * @param $key
	 *
	 * @return bool|string
	 */
	public function getValue($module_name, $key) 
	{
		if (!isset(self::$cache[$key])) 
		{
			global $ilDB;
			
			$result = $ilDB->query("SELECT value FROM " . $this->getTableName() 
			        . " WHERE module_name = " . $ilDB->quote($module_name, "text")
			        . " && key_name=" . $ilDB->quote($key, "text"));
			if ($result->numRows() == 0) {
				return false;
			}
			$record = $ilDB->fetchAssoc($result);
			self::$cache[$module_name . '_' . $key] = (string)$record['value'];
		}
		return self::$cache[$module_name . '_' . $key];
	}
    
	public static function setDefaultConfigValues()
	{
	   $modules = $this->db->getAllModules();
	   
	   foreach($modules as  $module_name => $module_db_record)
	   {
	       
	   }
	}
}

?>

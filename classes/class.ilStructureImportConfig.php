<?php
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportDBManager.php';

/**
 * ilLiveVotingConfig
 *
 * @author  Timon Amstutz <timon.amstutz@ilub.unibe.ch>
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 *
 * @version $Id$
 */
class ilStructureImportConfig 
{
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

<?php
class ilStructureImportDBManager
{
    /* Attributes for the Module Table */
	const TABLE_MODULES_NAME = 'ui_uihk_si_actions';
	
	const COL_MODULE_ID = 'module_id';
	const COL_MODULE_NAME = 'module_name';
	const COL_ACTION_TYPE = 'action_type';
	const COL_ACTION_LANG_NAME = 'action_lang_name';
	const COL_REQUIRED_PARAMETERS = 'required_parameters';
	const COL_OPTIONAL_PARAMETERS = 'optional_parameters';
	const COL_FILENAME = 'filename';
	
	/* Attributes for the Configuration Table */
	const TABLE_CONF_NAME = 'ui_uihk_si_conf';
	
	const COL_CONF_MODULE = 'module_name';
	const COL_CONF_KEY = 'key_name';
	const COL_CONF_VALUE = 'value';
	
	/* Cache */
	private $cache_table_modules = array();
	private $cache_action_types = array();
	
	/* Other Attributes */	
	private $db;
	private $number_of_modules;
	
	public function __construct()
	{
		global $ilDB;
		
		/* SQL-Statements */
		$this->select_module_types_destinct = 'SELECT DISTINCT ' . ilStructureImportConstants::COL_ACTION_TYPE . ' FROM ' . ilStructureImportConstants::TABLE_MODULES_NAME;
		$this->select_module_name = 'SELECT * FROM ' . ilStructureImportConstants::TABLE_MODULES_NAME . ' WHERE ' . ilStructureImportConstants::COL_MODULE_NAME . ' = "%s"';
		$this->select_all_modules = 'SELECT * FROM '  . ilStructureImportConstants::TABLE_MODULES_NAME . ' WHERE 1';
		$this->select_count_modules = 'SELECT COUNT('. ilStructureImportConstants::COL_MODULE_NAME . ') as count FROM ' . ilStructureImportConstants::TABLE_MODULES_NAME;
		
		$this->db = $ilDB;
		$this->plugin = ilStructureImportPlugin::getInstance();
		$this->_lookupNumberOfModules();
		$this->cache_table_modules = $this->_lookupAllModules();
	}
	
	/**
	 * @var ilStructureImportDBManager
	 */
	protected static $instance;
	
	/**
	 * @return ilStructureImportDBManager
	 */
	public static function getInstance() {
		if (!isset(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * 
	 * @param unknown $action
	 * @param string $actionType
	 * @return string
	 */
	public function _lookupModuleName($action)
	{
		$ret = "";
		
		foreach($this->cache_table_modules as $key=>$value)
		{
			if($this->plugin->txt('action_' . $key) == $action)
			{
				$ret = $key;
			}
		}

		if($ret == "")
		{			
			$res = $this->db->query($this->select_all_modules);
			while($row = $this->db->fetchAssoc($res))
			{
				$module_name = $row[ilStructureImportConstants::COL_MODULE_NAME];
				if($this->plugin->txt($module_name) == $action)
				{
					$ret = $module_name;
				}
			}
		}
		
		return $ret;
	}
	
	public function _lookupNumberOfModules()
	{
		$res = $this->db->query($this->select_count_modules);
		$row = $this->db->fetchAssoc($res);
		$this->number_of_modules = $row['count'];
		
		return $this->number_of_modules;
	}
	
	public function _lookupAllModules()
	{
		$cache_table_modules = array();
		$res = $this->db->query($this->select_all_modules);
		while($row = $this->db->fetchAssoc($res))
		{
			$arr_space = $row[ilStructureImportConstants::COL_MODULE_NAME];
			$cache_table_modules[$arr_space] = $row;
		}
		
		return $cache_table_modules;
	}
	
	/**
	 * 
	 * @param unknown $moduleName
	 */
	public function _lookupActionType($moduleName)
	{
		$ret = "";

		if($this->cache_table_module[$moduleName] != NULL)
		{
			$ret = $this->cache_table_modules[$moduleName][ilStructureImportConstants::COL_ACTION_TYPE];
		}
		else
		{
			$query = sprintf($this->select_module_name, $moduleName);
			$res = $this->db->query($query);
			$row = $this->db->fetchAssoc($res);
			$ret = $row[ilStructureImportConstants::COL_ACTION_TYPE];
		}
	
		return $ret;
	}
	
	/**
	 * 
	 * @param unknown $moduleName
	 */
	public function _lookupRequiredParameters($moduleName)
	{
		$ret = "";

		if($this->cache_table_module[$moduleName] != NULL)
		{
			$ret = $this->cache_table_modules[$moduleName][ilStructureImportConstants::COL_REQUIRED_PARAMETERS];
		}
		else
		{
			$query = sprintf($this->select_module_name, $moduleName);
			$res = $this->db->query($query);
			$row = $this->db->fetchAssoc($res);
			$this->cache_action_types[$moduleName] = $row;
			$ret = $row[ilStructureImportConstants::COL_REQUIRED_PARAMETERS];
		}
		
		return $ret;
	}
	
	/**
	 * Gets optional parameters as a semikolon separeted string for the given module.
	 * 
	 * @param string $moduleName
	 */
	public function _lookupOptionalParameters($module_name)
	{
		$ret = "";

		if($this->cache_table_module[$moduleName] != NULL)
		{
			$ret = $this->cache_table_modules[$moduleName][ilStructureImportConstants::COL_OPTIONAL_PARAMETERS];
		}
		else
		{
			$query = sprintf($this->select_module_name, $moduleName);
			$res = $this->db->query($query);
			$row = $this->db->fetchAssoc($res);
			$this->cache_action_types[$moduleName] = $row;
			$ret = $row[ilStructureImportConstants::COL_OPTIONAL_PARAMETERS];
		}
		
		return $ret;
	}
	
	public function _lookupFilename($module_name)
	{
		$ret = "";
		if($this->cache_table_modules[$moduleName] != NULL)
		{
			$ret = $this->cache_table_modules[$module_name][ilStructureImportConstants::COL_FILENAME];
		}
		else 
		{
			$query = sprintf($this->select_module_name, $module_name);
			$res = $this->db->query($query);
			if($row = $this->db->fetchAssoc($res))
			{
    			$this->cache_action_types[$module_name] = $row;
    			$ret = $row[ilStructureImportConstants::COL_FILENAME];
			}
		}
		
		return $ret;
	}
	
	public function _lookupActionTypes()
	{
		if(count($this->cache_action_types) <= 0)
		{
			$query = $this->select_module_types_destinct;
			$res = $this->db->query($query);
			
			$i = 0;
			while($row = $this->db->fetchAssoc($res))
			{
				$this->cache_action_types[$i] = $row[ilStructureImportConstants::COL_ACTION_TYPE]; 
				$i++;
			}
		}
		return $this->cache_action_types;
	}
	
	public function _lookupRequiredParametersForActionType($action_type)
	{
		$this->_lookupActionTypes();
		
		$all_required_parameters = array();
		
		foreach($this->_lookupAllModules() as $module)
		{
			if($module[ilStructureImportConstants::COL_REQUIRED_PARAMETERS] != null)
			{
				$module_required_parameters = explode(';', $module[ilStructureImportConstants::COL_REQUIRED_PARAMETERS]);
				
				foreach($module_required_parameters as $parameter)
				{
					if(!in_array($parameter, $all_required_parameters))
					{
						$all_required_parameters[] = $parameter;
					}
				}
			}
		}
		
		return $all_required_parameters;
	}
	
	public function _updateModuleList($module_list)
	{
		$this->_lookupAllModules();

		foreach($module_list as $module_from_file)
		{
			$module_id = -1;
			foreach($this->cache_table_modules as $module_in_db)
			{
				if($module_in_db[ilStructureImportConstants::COL_MODULE_NAME] == $module_from_file[ilStructureImportConstants::COL_MODULE_NAME])
				{
					$module_id = $module_in_db[ilStructureImportConstants::COL_MODULE_ID];
				}
			}
			
			
			$fields = array(
					ilStructureImportConstants::COL_MODULE_NAME => array("text", $module_from_file[ilStructureImportConstants::COL_MODULE_NAME]),
					ilStructureImportConstants::COL_ACTION_TYPE => array("text", $module_from_file[ilStructureImportConstants::COL_ACTION_TYPE]),
					ilStructureImportConstants::COL_ACTION_LANG_NAME => array("text", $module_from_file[ilStructureImportConstants::COL_ACTION_LANG_NAME]),
					ilStructureImportConstants::COL_REQUIRED_PARAMETERS => array("text", $module_from_file[ilStructureImportConstants::COL_REQUIRED_PARAMETERS]),
					ilStructureImportConstants::COL_OPTIONAL_PARAMETERS => array("text", $module_from_file[ilStructureImportConstants::COL_OPTIONAL_PARAMETERS]),
					ilStructureImportConstants::COL_FILENAME => array("text", $module_from_file[ilStructureImportConstants::COL_FILENAME])
			);
			if($module_id > 0)
			{
				// Update
				$this->db->update(
							ilStructureImportConstants::TABLE_MODULES_NAME,
							$fields,
							array(ilStructureImportConstants::COL_MODULE_ID => array('integer', $module_id))
						);
			}
			else
			{
				$this->number_of_modules++;
				$fields[ilStructureImportConstants::COL_MODULE_ID] = array("integer", $this->number_of_modules);
				
				// Insert				
				$this->db->insert(
							ilStructureImportConstants::TABLE_MODULES_NAME, 
							$fields
						);
			}
		}
		/*$fields = array("module_name" => array("integer", $module['module_name']),
					"action_type" => array("integer", $module['actionType']),
					"action_langName" => array("integer", $module['actionLangName']),
					"required_parameters" => array("integer", $module['requiredParameters']),
					"optional_parameters" => array("integer", $module['optionalParameters'])
				);
		
		$ilDB->insert(ilStructureImportConstants::TABLE_MODULES_NAME, $fields);*/
	}
}

?>
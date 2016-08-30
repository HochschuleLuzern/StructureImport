<?php
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportValidator.php';
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportDBManager.php';
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilImportExcel.php';
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportReporterTableGUI.php';
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportConstants.php';


class ilStructureImportReporter
{
	const ERRORMSG = "msg_error";
	const NAME = ilStructureImportConstants::EXCELCOL_NAME;
	const PATH = ilStructureImportConstants::EXCELCOL_PATH;
	const ACTION = ilStructureImportConstants::EXCELCOL_ACTION;
	const ACTIONOLD = ilStructureImportConstants::EXCELCOL_ACTION_OLD;
	const TYPEOLD = ilStructureImportConstants::EXCELCOL_TYPE_OLD;
	
	private $structure_iImport_array;
	private $header_row = array();
	private $number_of_elements = 0;
	private $valid_actions = 0;
	private $is_new_file_version;
	private $aviable_action_modules;
	private $error_array = array();
	private $executable_array = array();
	
	/*private $arr_create_header = array(0 => self::ACTION, 1 => self::PATH, 2 => self::NAME);
	private $arr_assign_header = array(0 => ACTION, 1 => PATH, 2 => NAME);*/
	private $arr_error_header = array(0 => self::ERRORMSG, 1 => self::ACTION, 2 => self::PATH, 3 => self::NAME);
	
	public $error_message;
	
	function __construct()
	{
	    include_once './Services/Form/classes/class.ilPropertyFormGUI.php';
	    
	    global $ilCtrl;
	    
	    $this->ctrl = &$ilCtrl;
		$this->plugin = ilStructureImportPlugin::getInstance();
		$this->db_manager = ilStructureImportDBManager::getInstance();
		
		$arr_create_header = array(0 => ACTION, 1 => PATH, 2 => NAME);
		$arr_assign_header = array(0 => ACTION, 1 => PATH, 2 => NAME);
		$arr_error_header = array(0 => ERRORMSG, 1 => ACTION, 2 => PATH, 3 => NAME);
	}
	
	function checkStructureImportArray($structure_iImport_array)
	{
		$this->structure_iImport_array = $structure_iImport_array;
		$this->number_of_elements = count($this->structure_iImport_array) - 1; // -1 cause [0] = headerrow
		
		/* Get headerrow (headerrow is always the first arrayelement) */
		if($this->number_of_elements > 0)
		{
			$header_row = $this->structure_iImport_array[0];
		}
		else
		{
		    $this->error_message = $this->plugin->txt('error_array_empty');
			$status = -1;
		}

		$this->checkIfIsNewVersion($header_row);
		$this->initArrayHeaders();
				
		$status = $this->executeValidation();
		
		return $status;
	}
	
	private function initArrayHeaders()
	{
	    $this->arr_error_header = array(0 => 'msg_error', 
                        	            1 => 'action', 
                        	            2 => 'path', 
                        	            3 => 'name');
		$this->error_array[0] = $this->arr_error_header;
		
		$action_types = $this->db_manager->_lookupActionTypes();

		foreach ($action_types as $action_type)
		{
			$header_row = $this->db_manager->_lookupRequiredParametersForActionType($action_type);
			$this->executable_array[$action_type][0] = $header_row;
		}		
	}
	
	private function executeValidation()
	{
		for($i = 1; $i <= $this->number_of_elements; $i++)
		{
			$error_text = "";
			
			/* Get action of new version */
			if($this->is_new_file_version)
			{
				$action = $this->structure_iImport_array[$i][$this->plugin->txt(ilStructureImportConstants::EXCELCOL_ACTION)];
			}
			/* Get action of old version */
			else
			{
				$actionold = $this->structure_iImport_array[$i][$this->plugin->txt(ilStructureImportConstants::EXCELCOL_ACTION_OLD)];
				$typeold = $this->structure_iImport_array[$i][$this->plugin->txt(ilStructureImportConstants::EXCELCOL_TYPE_OLD)];
				$action = $actionold . ': ' . $typeold;	
			}
			
			/* Get module name */
			$module_name = $this->db_manager->_lookupModuleName($action);
			
			/* Module found */
			if($module_name != "")
			{
				/* Check if all required parameters are not empty */
				$required_parameters_string = $this->db_manager->_lookupRequiredParameters($module_name);
				$required_parameters = explode(';', $required_parameters_string);
				foreach($required_parameters as $parameter)
				{
					$value = $this->structure_iImport_array[$i][$this->plugin->txt('excelcol_' . $parameter)];
					if($value == NULL)
					{
						$error_text .= $this->plugin->txt('error_required_parameter_is_empty') . $this->plugin->txt('excelcol_' . $parameter);
					}
				}
				
				/* Check name */
				if(in_array(NAME, $required_parameters))
				{
					
					$name =	$this->structure_iImport_array[$i][$this->plugin->txt(ilStructureImportConstants::EXCELCOL_NAME)];
					$error_text .= ilStructureImportValidator::checkNameSyntax($name);
				}
				
				/* Check path */
				if(in_array(PATH, $required_parameters))
				{
					
					$path = $this->structure_iImport_array[$i][$this->plugin->txt(ilStructureImportConstants::EXCELCOL_PATH)];
					$error_text .= ilStructureImportValidator::checkPathSyntax($path);
				}
								
				/* Get action type */
				$action_type = $this->db_manager->_lookupActionType($module_name);
			}
			/* Module not found */
			else
			{
				$error_text .= $this->plugin->txt('error_action_unknown');
			}
			
			/* check if is executable and put in the right array*/
			if($error_text == "")
			{
				$tmp_array = array();
				$type_required_parameters = $this->db_manager->_lookupRequiredParametersForActionType($action_type);
				foreach($type_required_parameters as $parameter)
				{
					if(!$this->is_new_file_version && $parameter == 'action')
					{
						$tmp_array[$parameter] = $action;
					}	
					else
					{
						$tmp_array[$parameter] = $this->structure_iImport_array[$i][$this->plugin->txt('excelcol_' . $parameter)];
					}
						
				}
				
				$array_index = count($this->executable_array[$action_type]);

				$this->executable_array[$action_type][$array_index] = $tmp_array;
				$this->valid_actions++;
			}
			else 
			{			
				$array_index = count($this->error_array);
				$tmp_array = array();
				$tmp_array['msg_error'] = $error_text;
				$tmp_array['action'] = $action;
				$tmp_array['path'] = $this->structure_iImport_array[$i][$this->plugin->txt(ilStructureImportConstants::EXCELCOL_PATH)];
				$tmp_array['name'] = $this->structure_iImport_array[$i][$this->plugin->txt(ilStructureImportConstants::EXCELCOL_NAME)];
				$this->error_array[$array_index] = $tmp_array;
			}
		}
	}
	
	/**
	 * This function is only needed for HSLU
	 */
	private function checkIfIsNewVersion($header_row)
	{
		$actionold = $this->plugin->txt(ilStructureImportConstants::EXCELCOL_ACTION_OLD);
		$typeold = $this->plugin->txt(ilStructureImportConstants::EXCELCOL_TYPE_OLD);
		if(in_array($actionold, $header_row) && in_array($typeold, $header_row))
		{
			$this->is_new_file_version = false;
		}
		else
		{
			$this->is_new_file_version = true;
		}
	}
	
	function getHTML($object_gui, $filename)
	{
	    $tpl = new ilTemplate("tpl.reporter_body.html", true, true, "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport");
	    $tpl->setCurrentBlock("structureimport_reporter");
	    
	    /* Add infotext */
	    $reporter_info_message = $this->plugin->txt('msg_reporter_info');
	    $reporter_info_message = sprintf($reporter_info_message, $this->number_of_elements, $this->valid_actions);
	    $tpl->setVariable("MSG_REP_INFOTEXT", $reporter_info_message);
	    
	    /* Add "Execute import!"-button */
	    $link = $this->ctrl->getLinkTargetByClass(array(
	            ilStructureImportConstants::STANDARD_BASE_CLASS,
	            ilStructureImportConstants::STANDARD_CMD_CLASS,
	    ),'executeImport');
	    $tpl->setVariable("LINK_TO_EXECUTE", $link);
	    $tpl->setVariable("MSG_EXECUTE_IMPORT", $this->plugin->txt('button_execute_import'));
	    
	    /* Add report tables */
		if(count($this->error_array)>1)
		{
			$table_html .= $this->getIliasTable($object_gui, $this->error_array, $this->plugin->txt('invalid_entries'));
		}
		
		foreach($this->executable_array as $key=>$table_array)
		{
			$table_html .= $this->getIliasTable($object_gui, $table_array, $this->plugin->txt($key));
		}
		$tpl->setVariable("ALL_TABLES", $table_html);
		
		$tpl->parseCurrentBlock();
		$html = $tpl->get();
		
		return $html;
	}
	
	function getIliasTable($object_gui, $table_array, $title)
	{
		global $iltpl, $ilCtrl;
		$tbl_mod = new ilStructureImportReporterTableGUI($object_gui, $title, $table_array[0], count($table_array)-1);
		unset($table_array[0]);
		$tbl_mod->setData($table_array);				
		$html =  $tbl_mod->getHTML();
		return $html;
	}
	
	function getHTMLTableFromArray($array = '')
	{
		$html = '<table border="2px" class="table table-striped">';
		$html .= '<thead><tr>';
		
		/* Print header */
		foreach ($array[0] as $element)
		{
			$html .= ('<th>'. $this->plugin->txt($element).'</th>');
		}
		$html .= '</tr></thead><tbody>';
		
		/* Print tablebody */
		for($i=1; $i < count($array);$i++)
		{
			if($i%2 == 0)
			{
				$html .= '<tr class="tblrow1_mo">';
			}
			else
			{
				$html .= '<tr class="tblrow2_mo">';
			}
		
			foreach($array[$i] as $element)
			{
				$html .= ('<td>'.$element.'</td>');
			}
			$html .= '</tr>';
		
		}
		
		$html .= '</tbody></table>';
		
		return $html;
	}
}

?>
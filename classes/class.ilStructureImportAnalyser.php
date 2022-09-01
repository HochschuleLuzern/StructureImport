<?php
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportValidator.php';
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportDBManager.php';
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportActionReport.php';
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilImportExcel.php';


class ilStructureImportAnalyser
{
    /**
     * Just the constructor that needs the path to the excelfile...
     *
     * @param string $excel_file_path
     */
    public function __construct($excel_file_path)
    {
        $this->plugin = ilStructureImportPlugin::getInstance();
        $this->db_manager = ilStructureImportDBManager::getInstance();
        $this->excel_file_path = $excel_file_path;
    }
    
    /**
     * Starts an analysis from a given excelfile with all actions
     * @return number|ilStructureImportActionReport
     */
    public function analyseActionsFromFile()
    {
        $action_array = $this->getActionsArrayFromFile();
        
        if (!is_array($action_array)) {
            return -1;
        } elseif (count($action_array) < 1) {
            $this->error_message = $this->plugin->txt('error_array_empty');
            return -1;
        }
        
        $action_report = $this->createReportFromActionArray($action_array);
        
        return $action_report;
    }
    
    /**
     * In this function, the object to read the excel file is created and returns the
     * 2 dimensional array of all action. If there was an error, -1 will be returned
     *
     * @return string[][]|integer
     */
    private function getActionsArrayFromFile()
    {
        if (is_file($this->excel_file_path)) {
            $excelImporter = new ilImportExcel();
            $structure_import_array = $excelImporter->openExcelFile($this->excel_file_path);
             
            return $structure_import_array;
        }
        
        return -1;
    }
    
    /**
     * Creates an action report out of a given array with all actions and the first
     * first row (index = [0]) is the header row. All action parameters are in an
     * associated array.
     *
     * @param string[action_row][action_parameter] $actions_array
     * @return ilStructureImportActionReport
     */
    public function createReportFromActionArray($actions_array)
    {
        $action_report = new ilStructureImportActionReport($this->createTitleForAnalysis());
        
        unset($actions_array[0]);
        
        foreach ($actions_array as $action_row) {
            $error_message = "";
            /* Check Action */
            $action_from_row = $action_row[$this->plugin->txt(ilStructureImportConstants::EXCELCOL_ACTION)];
            
            $action_module_name = $this->db_manager->_lookupModuleName($action_from_row);
            
            if ($action_module_name != "") {
                /* Check if all required parameters are not empty */
                $required_parameters_string = $this->db_manager->_lookupRequiredParameters($action_module_name);
                $required_parameters_array = explode(';', $required_parameters_string);
                foreach ($required_parameters_array as $required_parameter) {
                    if ($required_parameter != '' && $required_parameter != null) {
                        $value = $action_row[$this->plugin->txt('excelcol_' . trim($required_parameter))];
                        if ($value == null) {
                            $error_message .= $this->plugin->txt('error_required_parameter_is_empty') . ' ' . $this->plugin->txt('excelcol_' . trim($required_parameter)) . '<br>';
                        }
                    }
                }
                
                /* Check name */
                if (in_array(ilStructureImportConstants::EXCELCOL_NAME, $required_parameters_array)) {
                    $name = $action_row[$this->plugin->txt(ilStructureImportConstants::EXCELCOL_NAME)];
                    $error_message .= ilStructureImportValidator::checkNameSyntax($name) . '<br>';
                }
                
                /* Check path */
                if (in_array(ilStructureImportConstants::EXCELCOL_PATH, $required_parameters_array)) {
                    $path = $action_row[$this->plugin->txt(ilStructureImportConstants::EXCELCOL_PATH)];
                    $error_message .= ilStructureImportValidator::checkPathSyntax($path) . '<br>';
                }
                
                /* Get action type */
                $action_type = $this->db_manager->_lookupActionType($action_module_name);
                
                if ($action_type == -1 || $action_type == '' || $action_type == null) {
                    $error_message = "Error: The actiontype for this action was not found...<br>";
                }
            } else {
                // There is no actionmodule for this action...
                $error_message .= $this->plugin->txt('error_action_unknown') . '<br>';
            }
            
            /* If there is any error message, the action ist classified as invalid action*/
            if ($error_message == "") {
                // Add with action type to valid actions
                $action_report->addValidAction($action_row, $action_type);
            } else {
                // Add with error message to invalid actions
                $action_report->addInvalidAction($action_row, $error_message);
            }
        }
        
        return $action_report;
    }
    
    /**
     * Creates the title for this analyse. Right now the title is a short titletext
     * in the given language and the filename
     *
     * @return string
     */
    private function createTitleForAnalysis()
    {
        // Get only the filename of the path
        $filename = basename($this->excel_file_path);
        
        $title = $this->plugin->txt('reporter_analysis_title') . ' ' . $filename;
        
        return $title;
    }
}

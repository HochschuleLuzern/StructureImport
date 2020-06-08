<?php
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportReporterTableGUI.php';
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportAnalyser.php';
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportActionReport.php';

/**
 * GUI-Class ilStructureImportAnalyserGUI
 * 
 * @author          faheer
 * @version         $Id:
 * 
 * TODO: If ilStructureImportTabContentGUI is renamed -> rename it here too!
 * @ilCtrl_isCalledBy ilStructureImportAnalyserGUI: ilStructureImportTabContentGUI
 */
class ilStructureImportAnalyserGUI
{
    /**
     * Just the constructor...
     * 
     * @param string $excel_file_path
     */
    function __construct($excel_file_path)
    {
        global $DIC;
         
        $this->ctrl = $DIC->ctrl();
        $this->plugin = ilStructureImportPlugin::getInstance();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->analyser = new ilStructureImportAnalyser($excel_file_path);
    }
    
    /**
     * Used for the controlflow
     */
    public function executeCommand()
    {
        $cmd = $this->ctrl->getCmd();
         
        switch($cmd)
        {
            case 'showReport':
                {
                    $this->showReport();
                }
        }
    }
    
    /**
     * Shows the report page with all actions listed in tables
     */
    private function showReport()
    {
        $action_report = $this->analyser->analyseActionsFromFile();
        
        if($action_report != -1)
        {
            $reporter_html = $this->createReportHTML($action_report);
        }

        $this->tpl->setContent($reporter_html);
    }
    
    /**
     * Create the HTML-Code for the page out of a given action report
     * 
     * @param   ilStructureImportActionReport $action_report
     * @return  string  html
     */
    private function createReportHTML(ilStructureImportActionReport $action_report)
    {
        $reporter_template = new ilTemplate("tpl.reporter_body.html", true, true, "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport");
        $reporter_template->setCurrentBlock("structureimport_reporter");
         
        /* Adds javascript to global template */
        $this->tpl->addJavaScript('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/templates/default/remove_btn_show_wait.js');
        
        /* Add title */
        $reporter_template->setVariable("TITLE_REPORTER", $action_report->getTitle());
        
        /* Add infotext */
        $reporter_info_message = $this->plugin->txt('msg_reporter_info');
        $reporter_info_message = sprintf($reporter_info_message, $this->number_of_elements, $this->valid_actions);
        $reporter_template->setVariable("MSG_REP_INFOTEXT", $reporter_info_message);
         
        /* Add "Execute import!"-button */
        $link = $this->ctrl->getLinkTargetByClass(array(
                        ilStructureImportConstants::STANDARD_BASE_CLASS,
                        ilStructureImportConstants::STANDARD_CMD_CLASS,
        ),'executeImport');
        $reporter_template->setVariable("LINK_TO_EXECUTE", $link);
        $reporter_template->setVariable("MSG_EXECUTE_IMPORT", $this->plugin->txt('button_execute_import'));
         
        /* Add text to the waiting div*/
        $reporter_template->setVariable("MSG_WAIT_TEXT", $this->plugin->txt('msg_wait_text'));
         
        /* Add report tables */
        
        // If there are any actions that are analysed as invalid -> add a "Error table"
        $table_html = "";
        if($action_report->getNumberOfInvalidActions()>0) {
            // Get an array with all invalid actions (array[0] is the headerrow)
            $invalid_actions = $action_report->getInvalidActions();
            
            // Get the headerrow and remove it from the array with all invalid actions
            $header_row = $invalid_actions[0];
            unset($invalid_actions[0]);

            // Create a table for those 'Error-Actions' and get the HTML-code of it
            $table_html .= $this->createIliasTableFromActionArray($header_row, $invalid_actions, $this->plugin->txt('invalid_entries'));
        }
        
        // Go through all action-types and create for each type a table (the return from the table is as HTML)
        foreach($action_report->getAllValidActionArrays() as $action_type=>$actions_array)
        {
            // Get the headerrow and remove it from the array with all invalid actions
            $header_row = $actions_array[0];
            unset($actions_array[0]);
            
            // Create a table for the in this action given action-type and get the HTML-code of it
            $table_html .= $this->createIliasTableFromActionArray($header_row, $actions_array, $this->plugin->txt($action_type));
        }
        
        // Add all tables to the template
        $reporter_template->setVariable("ALL_TABLES", $table_html);
        
        // Parse template and return the HTML-Code
        $reporter_template->parseCurrentBlock();
        
        return $reporter_template->get();
    }
    
    /**
     * Creates an IliasTable out of a given Action Array and its headerrow and returns the HTML-code of it
     * 
     * @param String[]     $header_row
     * @param String[][]   $actions_array
     * @param String       $table_name
     */
    private function createIliasTableFromActionArray($header_row, $actions_array, $table_name)
    {
        // Creating and initaliasing the table with a title and names for the columns (count)
        $tbl_mod = new ilStructureImportReporterTableGUI($this, $table_name, $header_row, count($actions_array));
        
        // Set the data for the table
        $tbl_mod->setData($actions_array);
        
        // Return the HTLM-code of the table        
        return $tbl_mod->getHTML();
    }
}
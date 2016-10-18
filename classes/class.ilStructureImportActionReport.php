<?php


class ilStructureImportActionReport
{
    private $valid_action_arrays = array();
    
    private $invalid_actions = array();
    
    private $number_of_actions = 0;
    
    private $report_title = '';
    
    private $error_col_name = '';
    
    public function __construct($report_title)
    {
        $this->report_title = $report_title;
        $this->db_manager = ilStructureImportDBManager::getInstance();
        $this->plugin = ilStructureImportPlugin::getInstance();
        $this->error_col_name = $this->plugin->txt(ilStructureImportConstants::ERRORMSG);
        
        // This is the header column for the error table
        $this->invalid_actions = array( 0 =>
            array(
                $this->error_col_name,
                $this->plugin->txt(ilStructureImportConstants::EXCELCOL_ACTION),
                $this->plugin->txt(ilStructureImportConstants::EXCELCOL_PATH),
                $this->plugin->txt(ilStructureImportConstants::EXCELCOL_NAME)
            )
        );
    }
    
    /**
     * Add a valid action row to the list
     * 
     * @param string[] $action_row
     * @param string $action_type
     */
    public function addValidAction($action_row, $action_type)
    {

        if(!isset($this->valid_action_arrays[$action_type]))
        {
            $this->createHeaderRowForActionType($action_type);
        }
        
        $tmp_array = array();
        foreach($this->valid_action_arrays[$action_type][0] as $column_name)
        {
            if(isset($action_row[$column_name]))
                $tmp_array[$column_name] = $action_row[$column_name];
            else 
                $tmp_array[$column_name] = '';
        }

        $this->valid_action_arrays[$action_type][] = $tmp_array;
        $this->number_of_actions++;
    }
    
    /**
     * Creates the header row for a given action type out of all required parameters
     * 
     * @param unknown $action_type
     */
    private function createHeaderRowForActionType($action_type)
    {
        // Get all required parameters for the given action type
        $required_parameters_for_type = $this->db_manager->_lookupRequiredParametersForActionType($action_type);
        
        // Fill a temp-array with the column names for the header row
        $header_row = array();
        foreach($required_parameters_for_type as $parameter)
            $header_row[] = $this->plugin->txt('excelcol_'.$parameter);
        
        // Add the headerrow to the first array place for this action type (Index = [0])
        $this->valid_action_arrays[$action_type][0] = $header_row;
    }
    
    /**
     * Adds an invalid action row with its error message to the list of invalid actions
     * 
     * @param string[] $action_row
     * @param string $error_message
     */
    public function addInvalidAction($action_row, $error_message)
    {
        $tmp_array = array();
        
        foreach($this->invalid_actions[0] as $column_name)
        {
            if(isset($action_row[$column_name]))
                $tmp_array[$column_name] = $action_row[$column_name];
            else if($column_name == $this->error_col_name)
                $tmp_array[$column_name] = $error_message;
            else 
                $tmp_array[$column_name] = '';
        }
        
        $this->invalid_actions[] = $tmp_array;
        $this->number_of_actions++;
    }
    
    /**
     * Get the title
     * 
     * @return string
     */
    public function getTitle()
    {
        return $this->report_title;
    }
    
    /**
     * Set the title
     * 
     * @param string $report_title
     */
    public function setTitle($report_title)
    {
        $this->report_title = $report_title;
    }
    
    /**
     * Returns an array with all valid actions categoriesed in their action types
     * The structure of the array looks like this:
     * 
     * valid_actions_array[action_type][action_row][action_parameter]
     * 
     * 
     * The first Index (index = [0]) of the action_row is the headerrow
     * 
     * valid_actions_array[action_type][0] = header row
     * 
     * You need this row, cause the action_parameters are in a associated array
     * 
     * 
     * E.g to get the name(action_parameter) of a create(action_type) action 
     * in the 3. (action row) row you use following call:
     * 
     * valid_actions_array['create'][3]['name']
     * 
     * But better dont use something like this. In most of the time, you will just
     * iterate over the array and get you stuff...
     * 
     * @return  string[][][] valid_action_arrays
     */
    public function getAllValidActionArrays()
    {
        return $this->valid_action_arrays;
    }
    
    /**
     * This array is a bit easier to use than the array of all valid action. It has the
     * same structure like if you get an action type of the "all valid action"-array.
     * Because of this, its only 2 dimensional. The structure looks like this:
     * 
     * invalid_actions[action_row][action_parameter]
     * 
     * Like before, the first place (index = [0]) of the action row is the header row
     * 
     * @return string[][]
     */
    public function getInvalidActions()
    {
        return $this->invalid_actions;
    }
    
    /**
     * Gets the number of all actions
     * @return integer
     */
    public function getNumberOfActions()
    {
        return $this->number_of_actions;
    }
    
    /**
     * Gets the number of all valid actions
     * @return integer
     */
    public function getNumberOfValidActions()
    {
        return $this->number_of_actions - (count($this->invalid_actions) - 1);
    }
    
    /**
     * Gets the number of all invalid actions
     * @return number
     */
    public function getNumberOfInvalidActions()
    {
        return count($this->invalid_actions)-1;
    }
}
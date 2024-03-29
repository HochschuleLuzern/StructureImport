<?php
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/actiontypebase/class.ilStructureImportCreate.php';
include_once("Modules/Folder/classes/class.ilObjFolder.php");

class ilStructureImportCreateFolder extends ilStructureImportCreate
{
    protected static $module_name = 'create_folder';
    protected static $action_type = 'create';
    protected static $action_lang_name = 'action_create_folder';
    protected static $required_parameters = 'action;name;path';
    protected static $optional_parameters = 'description;hslutype';
    protected static $create_type = 'fold';
    protected $didactic_templates = array();
    
    public function __construct($log)
    {
        parent::__construct($log);
         
        $this->type = 'fold';
        $this->readStandardDicTpls();
    }
    
    protected function readStandardDicTpls()
    {
        global $ilDB;
        $standard_tpls = array('Dateiaustausch', 'Gruppenordner');
        foreach ($standard_tpls as $tpl_name) {
            $sql = "SELECT * FROM didactic_tpl_settings WHERE title = " . $ilDB->quote($tpl_name, "text");
            $res = $ilDB->query($sql);
            if ($row = $ilDB->fetchAssoc($res)) {
                $this->didactic_templates[$tpl_name] = $row['id'];
            }
        }
    }
    
    protected function createObject($row, $container_ref)
    {
        global $lng, $ilUser;
        
        /* Init */
        $status = 0;
        $folder_permission = $row[$this->plugin->txt(ilStructureImportConstants::EXCELCOL_PERMISSION_TYPE)];
        
        /* Create object */
        $folder_obj = new ilObjFolder();
        $status = $this->initObjectBase($folder_obj, $row, $container_ref);
        if ($status != 0) {
            return $status;
        }
        
        /* Set special permissions */
        $ref_id = $folder_obj->getRefId();
        
        /* Apply changes */
        $folder_obj->update();
        
        if ($this->didactic_templates[$folder_permission] != null) {
            $folder_obj->applyDidacticTemplate($this->didactic_templates[$folder_permission]);
        }
        
        /*try {
         * include_once ('./Services/AccessControl/classes/class.ilPostboxHelper.php');
            switch($folder_permission)
            {
                case $this->plugin->txt('folder_permission_postbox'):
                    ilPostboxHelper::_makePostbox($ref_id);
                    $this->log->write("Set folder permissiontype to postbox", 1);
                    break;
                case $this->plugin->txt('folder_permission_exchange'):
                    ilPostboxHelper::_makeExchangeFolder($ref_id);
                    $this->log->write("Set folder permissiontype to exchange", 1);
                    break;
                case $this->plugin->txt('folder_permission_group'):
                    ilPostboxHelper::_makeGroupFolder($ref_id);
                    $this->log->write("Set folder permissiontype to group", 1);
                    break;
                case $this->plugin->txt('folder_permission_normal'):
                case '':
                default:
                    ilPostboxHelper::_makeNormalFolder($ref_id);
                    $this->log->write("Set folder permissiontype to a normal folder", 1);
                    break;
            }
        }
        catch(Exception $e)
        {
            ilPostboxHelper::_makeNormalFolder($ref_id);
            $this->log->write('Error while settings special folder permissiontype. The permissiontype was set to normal', 10);
        }*/

        return $status;
    }
}

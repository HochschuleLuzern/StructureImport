<?php

class ilStructureImportConstants
{
    /* Plugin constants */
    
    // Path Constants
    const PATH_TO_ACTION_MODULES = ILIAS_ABSOLUTE_PATH . '/Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/actionmodules/';
    const IMPORT_FILENAME = 'importFilename';
    const IMPORT_FILEDIR = CLIENT_DATA_DIR . '/StructureImport/import/';
    const DEFAULT_LOGFILE_DIR = CLIENT_DATA_DIR . '/StructureImport/log/';
    
    // Tab name Constants
    const TAB_INSTRUCTION_ID = 'ilstructureimportinstruction';
    const TAB_IMPORT_ID = 'ilstructureimportmain';
    const TAB_UPDATE_DB = 'ilstructureimportupdatedb';
    
    // Standard classes as constats
    const STANDARD_BASE_CLASS = 'ilUIPluginRouterGUI';
    const STANDARD_CMD_CLASS = 'ilStructureImportTabContentGUI';
    const TAB_CONTENT_CLASS = 'ilstructureimporttabcontentgui';
    
    /* DB constants */
    
    // Attributes for the Module Table
    const TABLE_MODULES_NAME = 'ui_uihk_si_actions';
    
    const COL_MODULE_ID = 'module_id';
    const COL_MODULE_NAME = 'module_name';
    const COL_ACTION_TYPE = 'action_type';
    const COL_ACTION_LANG_NAME = 'action_lang_name';
    const COL_REQUIRED_PARAMETERS = 'required_parameters';
    const COL_OPTIONAL_PARAMETERS = 'optional_parameters';
    const COL_FILENAME = 'filename';
    
    // Attributes for the Configuration Table
    const TABLE_CONF_NAME = 'ui_uihk_si_conf';
    
    const COL_CONF_MODULE = 'module_name';
    const COL_CONF_KEY = 'key_name';
    const COL_CONF_VALUE = 'value';
    
    
    /* Excel constants */
    
    // Name of excelcolumns in lang-file 
    const EXCELCOL_COMMENT = 'excelcol_com';
    const EXCELCOL_NAME = 'excelcol_name';
    const EXCELCOL_PATH = 'excelcol_path';
    const EXCELCOL_ACTION = 'excelcol_action';
    const EXCELCOL_DESCRIPTION = 'excelcol_description';
    const EXCELCOL_ACTION_OLD = 'excelcol_action_old';
    const EXCELCOL_TYPE_OLD = 'excelcol_type_old';
    const EXCELCOL_LOGIN = 'excelcol_login';
    const EXCELCOL_ROLE = 'excelcol_role';
    const EXCELCOL_PERMISSION_TYPE = 'excelcol_permission_type';
    const EXCELCOL_SORT_TYPE = 'excelcol_sort_type';
    
    // Other excel constants
    const ERROR_ROW_NUMBER = "row";
    const ERROR_TEXT = "errortext";
    
    /* Structure Import Reporter constants */
    
    // Regex constants
    const REGEXPATH = "*";
    const REGEXNAME = "/^[A-Za-z0-9\ ÄäÖöÜü]{3,255}$/";
    const REGEXLOGIN = "*";
    
    // Other constants
    const ERRORMSG = "msg_error";
    
    
    /*  */
    
    // Module constants
    const CONF_ORDER_TYPE = 'order_type';
    const GROUP_TYPE = 'group_type';
    const CRS_ACTIVATION_TYPE = 'crs_activation_type';
    
    /* Config constants */
    
    // Conf titles
    const CONF_MAIN_SETTINGS = 'main_settings';
    const CONF_INSTRUCTION_FILES_CONTAINER = 'instruction_files_container';
    const CONF_AVOID_DUPLICATE_CREATION = 'avoid_duplicate_creation';
    const CONF_LOG_LEVEL = 'log_level';
    const CONF_LOG_PATH = 'log_path';
    const CONF_IGNORE_DUPLICATE = 'ignore_duplicate';
    const CONF_ONLY_CHECK_NAME_FOR_DUPLICATE = 'only_check_name_for_duplicate';
    const CONF_CHECK_NAME_AND_TYPE_FOR_DUPLICATE = 'check_name_and_type_for_duplicate';
    
    // Conf values
    const CONF_VAL_IGNORE = 'ignore';
    const CONF_VAL_CHECK_NAME = 'check_name';
    const CONF_VAL_CHECK_NAME_AND_TYPE = 'check_name_and_type';
}

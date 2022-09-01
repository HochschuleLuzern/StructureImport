<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/UIComponent/classes/class.ilUIHookPluginGUI.php");
include_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportAccess.php");
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportConstants.php';

/**
* ilBacklinkUIHookGUI class
* This class is just needed to add a link to the structure import plugin.
*
* @author Raphael Heer <raphael.heer@hslu.ch>
* @version $Id$
* @ingroup ServicesUIComponent
*/
class ilStructureImportUIHookGUI extends ilUIHookPluginGUI
{
    const TAB_CONTENT_CLASS = 'ilstructureimporttabcontentgui';
    
    public function modifyGUI($a_comp, $a_part, $a_par = array())
    {
        global $ilCtrl, $ilTabs;
        
        /* The Structureimporttab should only be showed in tabs (logic) */
        if ($a_part == "tabs") {
            /* Get ref_id from current dir */
            $ref_id = (int) $_GET['ref_id'];
            
            /* Gets the type of the current object
             * Structure Import is only needed in Container Objects */
            $obj_id = ilObject::_lookupObjectId($ref_id);
            $obj_type = ilObject::_lookupType($obj_id);
            $obj_types_with_tab = array('cat','fold','crs','grp');
            
            if (in_array($obj_type, $obj_types_with_tab) &&
                $_GET['baseClass'] != 'iluipluginroutergui' &&
                $_GET['cmdClass'] != 'ilobjectactivationgui') {
                $plugin = ilStructureImportPlugin::getInstance();
                $access_checker = ilStructureImportAccess::getInstance();
                
                /* Check for role and create permission */
                if ($ref_id > 0 && $access_checker->hasAccessToPlugin($ref_id)) {
                    /* Check if cmd class is already from */
                    $cmd_class = $ilCtrl->getCmdClass();
                    $is_already_in_content_class = $cmd_class == ilStructureImportConstants::TAB_CONTENT_CLASS ? true: false;
                        
                    if (!$is_already_in_content_class) {
                        /* Finally adds the link to the structure import plugin */
                        self::getStructureImportTab($ilTabs, $ref_id);
                    }
                }
            }
        }
    }
    
    public static function getStructureImportTab(&$Tabs_GUI, $ref_id)
    {
        global $ilCtrl;
        
        $ilCtrl->setParameterByClass(ilStructureImportConstants::STANDARD_CMD_CLASS, 'ref_id', $ref_id);
        $link = $ilCtrl->getLinkTargetByClass(array(
                ilStructureImportConstants::STANDARD_BASE_CLASS,
                ilStructureImportConstants::STANDARD_CMD_CLASS	,
        ), 'upload');
        $plugin = ilStructureImportPlugin::getInstance();
        $Tabs_GUI->addTab(ilStructureImportConstants::TAB_IMPORT_ID, $plugin->txt('tab_title'), $link);
    }
}

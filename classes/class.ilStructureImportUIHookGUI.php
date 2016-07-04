<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/UIComponent/classes/class.ilUIHookPluginGUI.php");
include_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportTabContentGUI.php");

/**
* ilBacklinkUIHookGUI class
* This class is just needed to add a link to the structure import plugin.
*
* @author Raphael Heer <raphael.heer@hslu.ch>
* @version $Id$
* @ingroup ServicesUIComponent
*/
class ilStructureImportUIHookGUI extends ilUIHookPluginGUI {
    
    const TAB_CONTENT_CLASS = 'ilstructureimporttabcontentgui';
    
	function modifyGUI($a_comp, $a_part, $a_par = array())
	{
		global $ilCtrl, $tree, $ilTabs, $rbacreview, $ilias, $ilUser;
		
		$plugin = ilStructureImportPlugin::getInstance();
		
		// $ilUser is not necesseraly defined (access for newsfeed etc.)
		if(!$ilUser)
		{
		    return array();
		}
		
		/* Check if user is admin */
		$is_admin = in_array($ilUser->getId(), $rbacreview->assignedUsers(2));
	    $importer_role_id = $plugin->getImporterRoleId();
	    if($importer_role_id != null)
	    {
	        $is_importer = in_array($ilUser->getId(), $rbacreview->assignedUsers($importer_role_id));
	    }
	    else
	    {
	        $is_importer = false;
	    }
		
		/* Check ref_id */
		$ref_id = (int)$_GET['ref_id'];
		
		if($ref_id > 0 && ($is_admin || $is_importer))
		{
		    /* Gets the type of the current object
		     * Structure Import is only needed in Container Objects */
    		$object = $ilias->obj_factory->getInstanceByRefId($ref_id);
    		$obj_type=$object->getType();
    		$obj_types_with_tab = array('cat','fold','crs','grp');
    		    		
    		if ($a_part == "tabs" && in_array($obj_type, $obj_types_with_tab))
    		{
    		    /* Check if cmd class is already from */
    		    $cmd_class = $ilCtrl->getCmdClass();
    		    $is_already_in_content_class = $cmd_class == self::TAB_CONTENT_CLASS ? true: false;
    		    
    		    if(!$is_already_in_content_class)
    		    {
    		        /* Finally adds the link to the structure import plugin */
		            self::getStructureImportTab($ilTabs, $ref_id);
    		    }
    		}
		}
	}	
	
	static function getStructureImportTab(&$Tabs_GUI, $ref_id)
	{
	    global $ilCtrl;
	    
	    $ilCtrl->setParameterByClass(ilStructureImportTabContentGUI::STANDARD_CMD_CLASS, 'ref_id', $ref_id);
	    $link = $ilCtrl->getLinkTargetByClass(array(
	            ilStructureImportTabContentGUI::STANDARD_BASE_CLASS,
	            ilStructureImportTabContentGUI::STANDARD_CMD_CLASS	,
	    ),'upload');
	    $plugin = ilStructureImportPlugin::getInstance();
	    $Tabs_GUI->addTab(ilStructureImportTabContentGUI::TAB_IMPORT_ID, $plugin->txt('tab_title'), $link);
	}
}
?>
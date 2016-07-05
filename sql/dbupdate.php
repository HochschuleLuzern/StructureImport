<#1>
<?php 
include_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportDBManager.php');

if(!$ilDB->tableExists(ilStructureImportDBManager::TABLE_MODULES_NAME))
{
	$fields = array(
		ilStructureImportDBManager::COL_MODULE_ID => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		
		ilStructureImportDBManager::COL_MODULE_NAME => array(
			'type' => 'text',
			'length' => 100,
			'notnull' => true
		),
		ilStructureImportDBManager::COL_ACTION_TYPE => array(
			'type' => 'text',
			'length' => 100,
			'notnull' => true
		),
		
		ilStructureImportDBManager::COL_ACTION_LANG_NAME => array(
			'type' => 'text',
			'length' => 100,
			'notnull' => true
		),
		
		ilStructureImportDBManager::COL_REQUIRED_PARAMETERS => array(
			'type' => 'text',
			'length' => 255,
			'notnull' => true
		),
		
		ilStructureImportDBManager::COL_OPTIONAL_PARAMETERS => array(
			'type' => 'text',
			'length' => 255,
			'notnull' => false
		),
		
		ilStructureImportDBManager::COL_FILENAME => array(
			'type' => 'text',
			'length' => 255,
			'notnull' => true
		)
	);
	
	$ilDB->createTable(ilStructureImportDBManager::TABLE_MODULES_NAME, $fields);
	$ilDB->addPrimaryKey(ilStructureImportDBManager::TABLE_MODULES_NAME, array(ilStructureImportDBManager::COL_MODULE_ID));
	$ilDB->addUniqueConstraint(ilStructureImportDBManager::TABLE_MODULES_NAME, array(ilStructureImportDBManager::COL_MODULE_NAME));
}
?>
<#2>
<?php 

if(!$ilDB->tableExists(ilStructureImportDBManager::TABLE_CONF_NAME))
{
    $fields = array(
            ilStructureImportDBManager::COL_CONF_MODULE => array(
                    'type' => 'text',
                    'length' => 100,
                    'notnull' => true
            ),

            ilStructureImportDBManager::COL_CONF_KEY => array(
                    'type' => 'text',
                    'length' => 100,
                    'notnull' => true
            ),
            ilStructureImportDBManager::COL_CONF_VALUE => array(
                    'type' => 'text',
                    'length' => 100,
                    'notnull' => false
            )
    );

    $ilDB->createTable(ilStructureImportDBManager::TABLE_CONF_NAME, $fields);
    $ilDB->addPrimaryKey(ilStructureImportDBManager::TABLE_CONF_NAME, array(ilStructureImportDBManager::COL_CONF_MODULE, ilStructureImportDBManager::COL_CONF_KEY));
    $ilDB->addUniqueConstraint(ilStructureImportDBManager::TABLE_CONF_NAME, array(ilStructureImportDBManager::COL_CONF_MODULE, ilStructureImportDBManager::COL_CONF_KEY));
}
?>
<#3>
<?php

include_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportPlugin.php');
$plugin = ilStructureImportPlugin::getInstance();
$plugin->updateModuleDB();

?>
<#4>
<?php

include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportPlugin.php';
include_once './Services/AccessControl/classes/class.ilObjRole.php';
include_once './Services/Object/classes/class.ilObject.php';
global $rbacadmin;

$plugin = ilStructureImportPlugin::getInstance();
if(count(ilObject::_getIdsForTitle($plugin->getRoleName(), 'role', false)) < 1)
{
	$role = new ilObjRole();
	$role->setTitle($plugin->getRoleName());
	$role->setDescription($plugin->getRoleDescription());
	$role->setDiskQuota(0);
	$role->setPersonalWorkspaceDiskQuota(0);
	
	$role->create();
	
	$rbacadmin->assignRoleToFolder($role->getId(), ROLE_FOLDER_ID,'y');
	$rbacadmin->setProtected(
		ROLE_FOLDER_ID,
		$role->getId(),
		'n'
	);
	
}

?>

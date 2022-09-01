<?php
/* Copyright (c) 1998-2014 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once 'Services/Component/classes/class.ilPluginConfigGUI.php';
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportConfig.php';


/**
 * ContainerFilter configuration user interface class
 *
 * @author  Fabian Schmid <fabian.schmid@ilub.unibe.ch>
 * @version $Id$
 *
 */
class ilStructureImportConfigGUI extends ilPluginConfigGUI
{
    /**
     * @var ilPropertyFormGUI
     */
    protected $form;

    
    public function __construct()
    {
        $this->plugin = ilStructureImportPlugin::getInstance();
        $this->db_manager = ilStructureImportDBManager::getInstance();
        $this->config_object = new ilStructureImportConfig();
    }

    /**
     * Handles all commands, default is "configure"
     */
    public function performCommand($cmd)
    {
        switch ($cmd) {
            case 'configure':
            case 'save':
                $this->$cmd();
                break;
        }
    }


    /**
     * Configure screen
     */
    public function configure()
    {
        global $tpl;
        $this->initConfigurationForm();
        $this->getValues();
        $tpl->setContent($this->form->getHTML());
    }


    public function getValues()
    {
        foreach ($this->getAllFields() as $module_name => $fields) {
            foreach ($fields as $key => $item) {
                $values[$module_name . '_' . $key] = $this->config_object->getValue($module_name, $key);
                if (is_array($item['subelements'])) {
                    foreach ($item['subelements'] as $subkey => $subitem) {
                        $values[$module_name . '_' . $key . '_' . $subkey] = $this->config_object->getValue($module_name, $key . '_' . $subkey);
                    }
                }
            }
            $this->form->setValuesByArray($values);
        }
    }


    /**
     * Init configuration form.
     *
     * @return object form object
     */
    public function initConfigurationForm()
    {
        global $lng, $ilCtrl;
        include_once('Services/Form/classes/class.ilPropertyFormGUI.php');
        $this->form = new ilPropertyFormGUI();
        
        $module_fields = $this->getAllFields();

        foreach ($module_fields as $module_name => $fields) {
            $section_header = new ilFormSectionHeaderGUI();
            $section_title = /*$this->plugin->txt('settings_from') . ' ' . */$this->plugin->txt($module_name);
            $section_header->setTitle($section_title);
            $this->form->addItem($section_header);
            
            foreach ($fields as $key => $item) {
                $field = new $item['type']($this->plugin->txt('config_' . $key), $module_name . '_' . $key);
                
                /* Add Infotext for Formitem */
                if ($item['info']) {
                    $field->setInfo($this->plugin->txt('config_' . $item['info']));
                }
                
                /* Only for Selectbox, Radiobuttons */
                if (is_array($item['options'])) {
                    switch ($item['type']) {
                        case 'ilSelectInputGUI':
                            $field->setOptions($item['options']);
                            break;
                        case 'ilRadioGroupInputGUI':
                            foreach ($item['options'] as $radio_name => $radio_id) {
                                $field->addOption(new ilRadioOption($this->plugin->txt('config_' . $radio_name), $radio_id));
                            }
                            break;
                    }
                }
                
                /* Add Subelements */
                if (is_array($item['subelements'])) {
                    foreach ($item['subelements'] as $subkey => $subitem) {
                        $subfield = new $subitem['type']($this->plugin->txt('config_' . $subkey), $module_name . '_' . $key . '_' . $subkey);
                        if ($subitem['info']) {
                            $subfield->setInfo($this->plugin->txt('config_' . $subitem['info']));
                        }
                        $field->addSubItem($subfield);
                    }
                }
                $this->form->addItem($field);
            }
        }
        
        $this->form->addCommandButton('save', $lng->txt('save'));
        $this->form->setTitle($this->plugin->txt('configuration_title'));
        $this->form->setFormAction($ilCtrl->getFormAction($this));

        return $this->form;
    }
       
    private function getAllFields()
    {
        $module_fields = array();
        
        $module_fields[ilStructureImportConstants::CONF_MAIN_SETTINGS] = $this->getMainFields();
        
        $module_list = $this->db_manager->_lookupAllModules();
        foreach ($module_list as $module_name => $module_data) {
            $filename = $module_data['filename'];
            $path = ilStructureImportConstants::PATH_TO_ACTION_MODULES . $filename;
            if (is_file($path)) {
                include_once $path;
                $tmp = str_replace('class.', '', $filename);
                $class_name = str_replace('.php', '', $tmp);
                $conf_fields = call_user_func(array($class_name, 'getConfFields'));
                
                if (count($conf_fields) > 0) {
                    $module_fields[$module_name] = $conf_fields;
                }
            }
        }
        
        return $module_fields;
    }
    
    private function getMainFields()
    {
        $fields = array(
              /*Example for entries:
                'conf_name' => array(
                   'type' => 'ilCheckboxInputGUI'
                   'info' => 'Some information
                   'subelements' => array(
                       'subtype' => array(
                           'type' => ilTextInputGUI')))*/
                ilStructureImportConstants::CONF_LOG_PATH => array(
                        'type' => 'ilTextInputGUI',
                        'info' => 'log_path_recomment'
                ),
                ilStructureImportConstants::CONF_LOG_LEVEL => array(
                        'type' => 'ilNumberInputGUI',
                        'info' => 'log_level_info'
                ),
                ilStructureImportConstants::CONF_AVOID_DUPLICATE_CREATION => array(
                        'type' => 'ilRadioGroupInputGUI',
                        'options' => array(
                                ilStructureImportConstants::CONF_IGNORE_DUPLICATE
                                   => ilStructureImportConstants::CONF_VAL_IGNORE,
                                ilStructureImportConstants::CONF_ONLY_CHECK_NAME_FOR_DUPLICATE
                                   => ilStructureImportConstants::CONF_VAL_CHECK_NAME,
                                ilStructureImportConstants::CONF_CHECK_NAME_AND_TYPE_FOR_DUPLICATE
                                   => ilStructureImportConstants::CONF_VAL_CHECK_NAME_AND_TYPE
                                ),
                        'info' => 'avoid_duplicate_creation_info'
                ),
                ilStructureImportConstants::CONF_INSTRUCTION_FILES_CONTAINER => array(
                        'type' => 'ilTextInputGUI',
                        'info' => 'instruction_conf_info'
                )
        );
        
        return $fields;
    }

    /**
     * Save form input
     */
    private function save()
    {
        global $tpl, $ilCtrl;
        $this->initConfigurationForm();
        if ($this->form->checkInput()) {
            $module_fields = $this->getAllFields();
            foreach ($module_fields as $module_name => $fields) {
                foreach ($fields as $key => $item) {
                    $this->config_object->setValue($module_name, $key, $this->form->getInput($module_name . '_' . $key));
                    if (is_array($item['subelements'])) {
                        foreach ($item['subelements'] as $subkey => $subitem) {
                            $this->config_object->setValue($module_name, $key . '_' . $subkey, $this->form->getInput($module_name . '_' . $key . '_' . $subkey));
                        }
                    }
                }
            }
            ilUtil::sendSuccess($this->plugin->txt('config_saved'), true);
            $ilCtrl->redirect($this, 'configure');
        } else {
            $this->form->setValuesByPost();
            $tpl->setContent($this->form->getHtml());
        }
    }

    /**
     * @param string $key
     *
     * @return bool|string
     */
    public static function _getValue($key)
    {
        /*if (! isset(self::$cache[$key])) {
            /** @var ilDB $ilDB
            global $ilDB;
            $result = $ilDB->query('SELECT config_value FROM ' . ilStructureImportConstants::TABLE_NAME . ' WHERE config_key = '
                . $ilDB->quote($key, 'text'));
            if ($result->numRows() == 0) {
                return false;
            }
            $record = $ilDB->fetchAssoc($result);
            self::$cache[$key] = $record['config_value'];
        }

        return self::$cache[$key];*/
    }
}

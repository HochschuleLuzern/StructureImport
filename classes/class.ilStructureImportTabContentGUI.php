<?php
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilImportExcel.php';
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportReporter.php';
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportPlugin.php';
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportDBManager.php';
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportConfig.php';

include_once './Services/Object/classes/class.ilObjectListGUIFactory.php';
include_once './Services/Logging/classes/class.ilLog.php';

/**
 * GUI-Class ilStructureImportTabContentGUI
 *
 * @author            Raphael Heer <raphael.heer@hslu.ch>
 * @version           $Id:
 *
 * @ilCtrl_isCalledBy ilStructureImportTabContentGUI: ilRouterGUI, ilUIPluginRouterGUI
 */
class ilStructureImportTabContentGUI
{
	const PATH_TO_ACTION_MODULES = './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/actionmodules/';
	const IMPORT_FILENAME = 'importFilename';
	const IMPORT_FILEDIR = './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/import/';
	const DEFAULT_LOGFILE_DIR = './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/log/';
	
	const TAB_INSTRUCTION_ID = 'ilstructureimportinstruction';
	const TAB_IMPORT_ID = 'ilstructureimportmain';
	const TAB_UPDATE_DB = 'ilstructureimportupdatedb';
	
	const STANDARD_BASE_CLASS = 'ilUIPluginRouterGUI';
	const STANDARD_CMD_CLASS = 'ilStructureImportTabContentGUI';
	
	private $logfile_dir;
	private $error_message;
	
	private $is_admin;
	private $is_importer;
	
	function __construct() 
	{		
		global $tpl, $ilCtrl, $ilTabs, $objDefinition, $ilUser;
		
		$this->tabs = &$ilTabs;
		$this->tpl = &$tpl;
		$this->ctrl = &$ilCtrl;
		$this->obj_def = &$objDefinition;
		$this->plugin = ilStructureImportPlugin::getInstance();
		$this->ref_id = &$_GET['ref_id'];
		$this->file = $_GET['name'];		
		$this->user = &$ilUser;
		$this->obj = ilObjectFactory::getInstanceByRefId($this->ref_id);		
		$this->config = ilStructureImportConfig::getInstance();
	}
	
	function executeCommand()
	{
	    if($this->checkAccess())
	    {
    		/* Fill header */
    		$this->initHeader();
    				
    		/* Fill content */
    		$cmd = $this->ctrl->getCmd();
    		
    		switch ($cmd) 
    		{
    			case 'upload':
    				$this->upload();
    				break;
    			case 'showReport':
    				$this->showReport();
    				break;
    			case 'executeImport':
    				$this->executeImport();
    				break;
    			case 'showInstruction':
    				$this->showInstruction();
    				break;
    			case 'updateDB':
    			    $this->updateDB();
    			    break;
    			default:
    			    ilUtil::sendFailure($this->plugin->txt('error_unknown_cmd') . " $cmd", true);
    			    ilUtil::redirect('index.php');
    				break;
    		}

    		/* Show createt content */
    		$this->tpl->addCss('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/templates/default/structureimport.css');
    		$this->tpl->getStandardTemplate();
    		$this->tpl->show();
        }
	}

	
	private function initHeader()
	{
		global $ilLocator;
		
		/* Add breadcrumbs */
		$ilLocator->addRepositoryItems($this->ref_id);
		$this->tpl->setLocator($ilLocator->getHTML());
		
		/* Add title, description and icon of the current repositoryobject */
		$this->tpl->setTitle($this->obj->getTitle());
		$this->tpl->setDescription($this->obj->getDescription());
		$this->tpl->setTitleIcon(ilUtil::getTypeIconPath($this->obj->getType(), $this->obj->getId(), 'big'));
		
		/* Create and add backlink */
		$back_link = $this->ctrl->getLinkTargetByClass(array(
				'ilRepositoryGUI',
				'ilObj' . $this->obj_def->getClassName($this->obj->getType()) . 'GUI'
				));		
		$class_name = $this->obj_def->getClassName($this->obj->getType());
		$this->ctrl->setParameterByClass('ilObj' . $class_name . 'GUI', 'ref_id', $this->ref_id);
		$this->tabs->setBackTarget($this->plugin->txt('tab_back_link'), $back_link);		
		
		/* Create and add tab with link to addon description */
		$this->ctrl->setParameterByClass(self::STANDARD_CMD_CLASS, 'ref_id', $this->ref_id);
		$instrunction_link = $this->ctrl->getLinkTargetByClass(array(
					self::STANDARD_BASE_CLASS,
					self::STANDARD_CMD_CLASS,
			),'showInstruction');
		$class_name = $this->obj_def->getClassName($this->obj->getType());
		$this->tabs->addTab(self::TAB_INSTRUCTION_ID, $this->plugin->txt('tab_instruction'), $instrunction_link);
		
		/* Add Tab for Upload */
		ilStructureImportUIHookGUI::getStructureImportTab($this->tabs, $this->ref_id);
		
		/* Add UpdateDB Button*/
		if($this->is_admin)
		{
    		$this->ctrl->setParameterByClass(self::STANDARD_CMD_CLASS, 'ref_id', $this->ref_id);
    		$instrunction_link = $this->ctrl->getLinkTargetByClass(array(
    		        self::STANDARD_BASE_CLASS,
    		        self::STANDARD_CMD_CLASS,
    		),'updateDB');
    		$class_name = $this->obj_def->getClassName($this->obj->getType());
    		$this->tabs->addTab(self::TAB_UPDATE_DB, $this->plugin->txt('tab_update_db'), $instrunction_link);
		}
		/*$this->ctrl->setParameterByClass(get_class($this), 'ref_id', $this->ref_id);
		$main_link = $this->ctrl->getLinkTargetByClass(array(
		        'ilUIPluginRouterGUI',
		         get_class($this),
		),'upload');
		$tabtitle = $this->plugin->txt('tab_title');
		$this->tabs->addTab(self::TAB_IMPORT_ID, $tabtitle, $main_link);*/
		
		/*$force_active = ($_GET["cmd"] == "showInstruction")
		? true
		: false;
		$this->tabs->addTarget($this->plugin->txt('tab_instruction'),
		        $instrunction_link, "self::TAB_INSTRUCTION_ID", get_class($this)
		        , "", $force_active);*/
	}
	
	private function showReport()
	{		
	    $this->tabs->activateTab(self::TAB_IMPORT_ID);
		$filedir = $this->checkFileUpload();
		
		if($filedir != -1)
		{
		    try 
		    {
		        /* Import data from excel */
		        $excelImporter = new ilImportExcel();
		        $array = $excelImporter->openExcelFile($filedir);
		        	
		        if($array != -1)
		        {
		            if(count($array) != 1)
		            {
		                try 
		                {
    		                /* Analyse the data */
    		                $reporter = new ilStructureImportReporter();
    		                $status = $reporter->checkStructureImportArray($array);
    		                 
    		                if($status != -1)
    		                {
        		                /* Get html for the page */
        		                $html .= $reporter->getHTML($this, $this->filename);
    		                }
    		                else
    		                {
    		                    $html = $reporter->error_message;
    		                }
		                }
		                catch(Exception $e)
		                {
		                    $html = $this->plugin->txt('error_unknown_exception_html_out') . $e->getMessage();
		                }
		            }
		            else
		            {
		                $html = $this->plugin->txt("msg_empty_import_excelsheet");
		            }
		        }
		        else
		        {
		            $html = $this->error_message;
		        }
		    }
		    catch (Exception $e)
		    {
		        $html = $this->plugin->txt('error_unknown_exception_html_out') . $e->getMessage();
		    }
		}
		else
		{			
			$html = $this->error_message;
		}

		/* Set html for the page */
		$this->tpl->setContent($html);
	}
	
	private function initExecuteImportForm()
	{
		
	}
	
	private function checkFileUpload()
	{
		if(isset($_FILES['file_']))
		{
			$file = $_FILES['file_'];
			if($file['error'] == 0)
			{
			    try 
			    {
			        $this->filename = ilUtil::stripSlashes($file['name']);
			        $size = ilUtil::stripSlashes($file["size"]);
			        $temp_name = $file["tmp_name"];
			        $filedir = self::IMPORT_FILEDIR . $this->filename;
			        ilUtil::moveUploadedFile($temp_name, $this->filename, $filedir);
			        $this->ctrl->setParameter($this, self::IMPORT_FILENAME, $this->filename);
			        $status = $filedir;
			    }
			    catch (Exception $e)
			    {
			        $this->error_message = $this->plugin->txt('error_unknown_exception_html_out') . $e->getMessage();
			        $status = -1;
			    }
				
			}
			else
			{
				$this->error_message = $this->plugin->txt('error_invalid_excelsheet');
				$status = -1;
			}
		}
		else
		{
			$this->error_message = $this->plugin->txt('error_fileupload');
			$status = -1;
		}
		return $status;
	}
	
	private function checkAccess()
	{
	    global $rbacreview, $ilUser;
	    
	    $has_access = false;
	    
	    /* Check if plugin is active */
	    if (!$this->plugin->isActive())
	    {
	        ilUtil::sendFailure($this->plugin->txt('error_plugin_is_deactivated'), true);
	        ilUtil::redirect('index.php');
	    }
	    
	    /* Check if this is a user (webfeeds for example are no users) */
	    if(!$ilUser)
	    {
	        ilUtil::sendFailure('error_iluser_is_not_set', true);
	        ilUtil::redirect('index.php');
	    }
	    
	    /* Check for admins */
	    $this->is_admin = in_array($ilUser->getId(), $rbacreview->assignedUsers(2));
	    
	    /* Check for importer role */
	    $this->importer_role_id = $this->plugin->getImporterRoleId();
	    if($this->importer_role_id != null)
	    {
	        $this->is_importer = in_array($ilUser->getId(), $rbacreview->assignedUsers($this->importer_role_id));
	    }
	    
	    /* No admin and No importer role = no access to this plugin */
	    if($this->is_admin || $this->is_importer)
	    {
	        $has_access = true;
	    }
	    else 
	    {
	        ilUtil::sendFailure($this->plugin->txt('error_plugin_permission_denied'), true);
	        ilUtil::redirect('index.php');
	    }

	    return $has_access;
	}
	
	private function updateDB()
	{
	    $this->tabs->activateTab(self::TAB_UPDATE_DB);
		$updated_modules = $this->plugin->updateModuleDB();
		$this->tpl->setContent(print_r($updated_modules, true));
		
		$html = '<h1>'.$this->plugin->txt('msg_modules_in_db').'</h1>';
		$html .= '<ul>';
		
		foreach($updated_modules as $module)
		{
		    $module_name = $module[ilStructureImportDBManager::COL_MODULE_NAME];
		    $html .= '<li>' . $module_name . ' (' . $this->plugin->txt($module_name) . ')</li><br>';
		}
		$html .= '</ul>';
		$this->tpl->setContent($html);
	}
	
	private function executeImport()
	{
	    $this->tabs->activateTab(self::TAB_IMPORT_ID);
		$filename = $_GET[self::IMPORT_FILENAME];
		$filedir = self::IMPORT_FILEDIR . $filename;
		
		if(is_file($filedir))
		{
		    /* Create logfile */
		    $logfilename = date('Ymdhi') . '_' . $this->user->getId() . '.log';
		    $this->logfile_dir = $this->config->getValue(ilStructureImportConfig::CONF_MAIN_SETTINGS, 
	                                                     ilStructureImportConfig::CONF_LOG_PATH);
		    if(is_dir($this->logfile_dir)==null)
		    {
		        $this->logfile_dir = self::DEFAULT_LOGFILE_DIR;
		    }
		    $log_level = $this->config->getValue(ilStructureImportConfig::CONF_MAIN_SETTINGS,
		                                         ilStructureImportConfig::CONF_LOG_LEVEL);
		    $this->log = new ilLog($this->logfile_dir , $logfilename, 'Structure Import', true, $log_level);
		    $this->log->write('Start logging', 1);
		    
    		/* Get Excel Data */
    		$excelImporter = new ilImportExcel();
    		$import_array = $excelImporter->openExcelFile($filedir);
    		
    		if(import_array == -1)
    		{
    		    $this->log->write('Error while importing excel', 20);
    		    unset($this->log);
    		    return;
    		}
    		$this->log->write('Imported Excelfile', 1);
    		
    		/* Get header row */
    		$number_of_actions = count($import_array);
    		$header_row = $import_array[0];
    		
    		$db_manager = new ilStructureImportDBManager();
    		$action_modules = array();
    		
    		$root_ref = $this->ref_id;
    		$current_ref = $root_ref;
    		
    		for($i = 1; $i < $number_of_actions; $i++)
    		{
    			$row = $import_array[$i];
    				
    			/* Get module name */
    			$action = $row[$this->plugin->txt(ilImportExcel::EXCELCOL_ACTION)];
    			$module_name = $db_manager->_lookupModuleName($action);
    			if($module_name != '')
    			{
    			    /* Create instance of Module if it does not exist */
    			    if($action_modules[$module_name] == null)
    			    {
        				$filename = $db_manager->_lookupFilename($module_name);
        				
        				$tmp = str_replace('class.', '', $filename);
        				$class_name = str_replace('.php', '', $tmp);
        				if(is_file(self::PATH_TO_ACTION_MODULES . $filename))
        				{
        					include_once(self::PATH_TO_ACTION_MODULES . $filename);
        					$action_modules[$module_name] = new $class_name($this->log);
        				}
        				else
        				{
        					$this->log->write($this->plugin->txt('error_modulefile_not_found', 20));
        					continue;
        				}
    			    }
    			    
    			    /* Execute import */
    			    $this->log->write("Executing the Module '" . $module_name ."'", 5);
    			    $new_ref = $action_modules[$module_name]->executeAction($row, $root_ref, $current_ref);
    			    
    			    if($new_ref == -1)
    			    {
    			        $this->log->write($action_modules[$module_name]->getErrorMessage(), 10);
    			    }
    			    else
    			    {
    			        $this->log->write("Action successfully executed!", 5);
    			        $current_ref = $new_ref;
    			        $this->log->write("The workdir has now the ref_id ' $current_ref'", 5);
    			    }
    			}
    			else
    			{
    				$this->log->write($this->plugin->txt('error_module_not_found_in_db'), 10);
    			}
    		}
		}
		else 
		{
		    ilUtil::sendFailure($this->plugin->txt('error_file_not_found') . $filename, true);
		    ilUtil::redirect('index.php');
		}
		
		/* Finished Import :) */
		$this->log->write('Import beendet');
		unset($this->log);
		
		/* Delete file after import */
		//ilUtil::delDir($filedir, true);
		unlink($filedir);
		
		/* Set template content */
		$content = '<h1>'.$this->plugin->txt('msg_import_finished').'</h1>';
		$this->tpl->setContent($content);
	}
	
	private function upload()
	{
		include_once('Services/Form/classes/class.ilPropertyFormGUI.php');
		
		$this->tabs->activateTab(self::TAB_IMPORT_ID);
		
		$this->form_gui = new ilPropertyFormGUI();
		$this->form_gui->setFormAction($this->ctrl->getFormAction($this, "showReport"));
		$this->form_gui->setTitle($this->plugin->txt('title_upload'));
		$this->form_gui->addCommandButton('showReport',$this->plugin->txt('button_upload'));
		$file = new ilFileInputGUI($this->plugin->txt("msg_import_file"), "file_".$purpose);		
    	$this->form_gui->addItem($file);
    	
		$this->tpl->setContent($this->form_gui->getHTML());
	}
	
	private function showInstruction()
	{
	    $this->tabs->activateTab(self::TAB_INSTRUCTION_ID);
	    
	    $tpl = new ilTemplate("tpl.instruction_main.html", true, true, "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport");
	    
	    $tpl->setCurrentBlock("intro");
	    $tpl->setVariable("INSTRUCTION_TITLE", $this->plugin->txt("instruction_main_title"));
	    
	    $tpl->setVariable("VERSION_INFOTEXT", $this->plugin->txt("instruction_infobox"));
	    
	    $tpl->setVariable("TITLE_PARAGRAPH_1", $this->plugin->txt("instruction_title_1"));
	    $tpl->setVariable("TEXT_PARAGRAPH_1", $this->plugin->txt("instruction_paragraph_1"));
	    $tpl->setVariable("IMG_SRC_EXCEL_SHEET", ilUtil::getImagePath("img_excel_sheet.jpg", "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport"));
	    
	    $tpl->setVariable("TITLE_PARAGRAPH_2", $this->plugin->txt("instruction_title_2"));
	    $tpl->setVariable("TEXT_PARAGRAPH_2", $this->plugin->txt("instruction_paragraph_2"));
	    $link = $this->config->getValue(ilStructureImportConfig::CONF_MAIN_SETTINGS, ilStructureImportConfig::CONF_INSTRUCTION_FILES_CONTAINER);
	    if($link != null)
	    {
	        $html_link = "<a href='$link'>Zur Seite mit den Dokumenten</a>";
	        $tpl->setVariable("LINK_TO_INSTRUCTION_DIRECTORY", $html_link);
	    }
	    else
	    {
	        $tpl->setVariable("LINK_TO_INSTRUCTION_DIRECTORY", $this->plugin->txt('error_link_missing'));
	    }
	    $tpl->setVariable("IMG_SRC_OPEN_IMPORT", ilUtil::getImagePath("img_open_import.jpg", "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport"));
	     
	    
	    $tpl->setVariable("TITLE_PARAGRAPH_3", $this->plugin->txt("instruction_title_3"));
	    $tpl->setVariable("TEXT_PARAGRAPH_3", $this->plugin->txt("instruction_paragraph_3"));
	    $tpl->setVariable("IMG_SRC_UPLOAD_FILE", ilUtil::getImagePath("img_upload_file.jpg", "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport"));
	     
	    $tpl->setVariable("TITLE_PARAGRAPH_4", $this->plugin->txt("instruction_title_4"));
	    $tpl->setVariable("TEXT_PARAGRAPH_4", $this->plugin->txt("instruction_paragraph_4"));
	    $tpl->setVariable("IMG_SRC_EXECUTE_IMPORT", ilUtil::getImagePath("img_execute_import.jpg", "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport"));
	     
        $tpl->parseCurrentBlock();
	    
	    $this->tpl->setContent($tpl->get());
	}	
}

?>
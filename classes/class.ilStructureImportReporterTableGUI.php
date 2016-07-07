<?php
include_once ('./Services/Table/classes/class.ilTable2GUI.php');

class ilStructureImportReporterTableGUI extends ilTable2GUI
{
	function __construct($object_gui, $title, $headerRow, $number_of_actions = null)
	{
		parent::__construct($object_gui);
		$this->plugin = ilStructureImportPlugin::getInstance();
		$this->headerRow = $headerRow;
		$this->cols = count($headerRow);
		$this->setTitle($number_of_actions==null?$title:"$title($number_of_actions)");
		$this->setEnableHeader(true);
		$this->disable('sort');
		$this->disable('numinfo');
		$this->setRowTemplate("tpl.reporter_table_row.html", "Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport");
		$this->setNoEntriesText($this->plugin->txt('msg_no_entries'));
		$this->setLimit(1000);
		
		foreach($headerRow as $colTitle)
		{
			$this->addColumn($this->plugin->txt('excelcol_' . $colTitle));
		}
	}
	
	/**
	 * 
	 * @param unknown $row
	 */
	function fillRow($row)
	{
		foreach($this->headerRow as $colTitle)
		{
			$this->tpl->setCurrentBlock('standard_td');
			$this->tpl->setVariable('VALUE', $row[$colTitle]);
			$this->tpl->parseCurrentBlock();
		}
	}	
}
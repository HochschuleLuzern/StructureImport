<?php
// TODO: If there is no support for ilias without phpexcel -> delete this if-else thing 
if(is_dir('./libs/composer/vendor/phpoffice/phpexcel/Classes/PHPExcel.php'))
{
	include_once './libs/composer/vendor/phpoffice/phpexcel/Classes/PHPExcel.php';
}
else
{
	include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/lib/PHPExcel/PHPExcel.php';
}

class ilImportExcel
{
    /* Name of excelcolumns in lang-file */
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
    
    
    const ERROR_ROW_NUMBER = "row";
    const ERROR_TEXT = "errortext";
    
	private $excel_errors = array();
	private $excel_header_row = array();
	private $excel_sheet;
	private $excel_header_row_nr;
	private $excel_rows = array();
	public $error_message;
	
	function __construct()
	{
		$this->plugin = ilStructureImportPlugin::getInstance();
		
		$this->comment_row_enabled = false;
	}
	
	function openExcelFile($import_file)
	{
		$obj_reader = PHPExcel_IOFactory::createReader('Excel2007');
		$obj_reader->setReadDataOnly(true);
		if(is_file($import_file))
		{
		  $obj_PHP_excel = $obj_reader->load($import_file);
		}
		else 
		{
		    return -1;
		}
		
		$this->excel_sheet = $obj_PHP_excel->getsheet(0);
		
		$array = $this->readExcelFile();
		
		if($array == -1)
		{
			return -1;
		}

		return $array;
	}
		
	private function readExcelFile()
	{
		$highestRow = $this->excel_sheet->getHighestRow();
		
		/* Get header*/
		$excel_header_row_nr = $this->getHeaderRowNr($highestRow);
		if($excel_header_row_nr >= 0 && $excel_header_row_nr != null)
		{
			$this->excel_header_row = $this->getHeaderRowContent($excel_header_row_nr);
		}
		else
		{
			$this->error_message = "Incorrect Array";
			return -1;
		}
		
		$this->excel_rows[0] = $this->excel_header_row;
		$this->checkHeaderOptions();
		$array_entry = 1;
		for ($row_nr = $excel_header_row_nr + 1; $row_nr < $highestRow; $row_nr++)
		{
			if(!$this->isRowEmpty($row_nr))
			{
				$row = $this->getRow($row_nr);
				if(!$this->isCommentRow($row))
				{
					$this->excel_rows[$array_entry] = $row;
					$array_entry++;
				}
			}
		}
		
		if($this->is_old_fileversion)
		{
			for($i = 0; $i < count($this->excel_header_row);$i++)
			{
				if($this->excel_header_row[$i] == 'Typ')
				{
					unset($this->excel_rows[0][$i]);
					unset($this->excel_header_row[$i]);
				}
			}
		}
		
		return $this->excel_rows;
	}
	
	private function checkHeaderOptions()
	{
		foreach($this->excel_header_row as $option)
		{
			switch($option)
			{
				case $this->plugin->txt(self::EXCELCOL_COMMENT):
					$this->comment_row_enabled = true;
					break;
			}
		}
	}
	
	/**
	 * Checks if a row is empty
	 * 
	 * @param unknown $row
	 */
	private function isRowEmpty($row) 
	{
		$result = true;
		for ($i=0; $i<5 && $result; $i++) 
		{
			$value = $this->excel_sheet->getCellByColumnAndRow($i, $row)->getCalculatedValue();
			if ($value) 
			{
				$result = false;
			}
		}
		return $result;
	}
	
	/**
	 * Gets the rownumber of the header
	 * 
	 * @return number of the headerrow
	 */
	private function getHeaderRowNr($highest_row)
	{
		$row_nr_return = -1;
		$looks_like_header = false; 
		for($row = 0; !$looks_like_header && $row < $highest_row; $row++)
		{
			$has_empty_cells = false;
			for($col = 0; !$hasEmptyClles && $col < 4; $col++)
			{
				$value = $this->excel_sheet->getCellByColumnAndRow($col, $row)->getCalculatedValue();
				if($value == '')
				{
					$has_empty_cells = true;
				}
			}

			if(!$has_empty_cells)
			{
				$looks_like_header = true;
				$row_nr_return = $row;
			}
		}
		
		return $row_nr_return;
	}
	
	/**
	 * Returns an array that contains the headerrow. The array is numeric and contains the
	 * titles in order of appearance.
	 * 
	 * @param integer 		$excel_header_row_nr
	 */
	private function getHeaderRowContent($excel_header_row_nr)
	{
		$this->excel_header_row = array();
		
		$cell_is_empty = false;
		for($col = 0; !$cell_is_empty; $col++)
		{
			$value = $this->excel_sheet->getCellByColumnAndRow($col, $excel_header_row_nr)->getCalculatedValue();
			if($value != '')
			{
				$excel_header_row[$col] = $value;
			}
			else
			{
				$cell_is_empty = true;
			}
		}
		
		$this->is_old_fileversion = $this->isOldFileversion($excel_header_row);
		
		return $excel_header_row;
	}
	
	/**
	 * Reads and returns a row as associative array
	 * 
	 * @param integer $row_nr
	 * @return array $row
	 */
	private function getRow($row_nr)
	{		
		$i = 0;
		foreach($this->excel_header_row as $col_nr=>$row_title)
		{
			$value = $this->excel_sheet->getCellByColumnAndRow($col_nr, $row_nr)->getCalculatedValue();
			if(is_null($value))
			{
				$value = "";
			}
			$row[$row_title] = $value;
		}
		
		/* If is old fileversion -> convert to new */
		if($this->is_old_fileversion)
		{
			$action = $row[$this->plugin->txt(self::EXCELCOL_ACTION)]  . ': ' . $row[$this->plugin->txt(self::EXCELCOL_TYPE_OLD)];
			$row[$this->plugin->txt(self::EXCELCOL_ACTION)] = $action;
			unset($row[$this->plugin->txt(self::EXCELCOL_TYPE_OLD)]);
		}
		
		return $row;
	}
	
	private function isOldFileversion($header)
	{
		$ret = false;
		
		if(in_array($this->plugin->txt(self::EXCELCOL_TYPE_OLD), $header))
		{
			$ret = true;
		}
				
		return $ret;
	}
	
	private function isCommentRow(&$row)
	{
		$ret = false;
		
		if($this->comment_row_enabled && strlen($row[$this->plugin->txt(self::EXCELCOL_COMMENT)]) != 0)
		{
			$ret = true;
		}
		else
		{
			$ret = false; 
		}
		
		return $ret;
	}
}

?>
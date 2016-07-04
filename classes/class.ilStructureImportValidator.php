<?php

class ilStructureImportValidator
{
	const REGEXPATH = "*";
	const REGEXNAME = "/^[A-Za-z0-9\ ÄäÖöÜü]{3,255}$/";
	const REGEXLOGIN = "*";
	/*const ACTIONCREATECATEGORY = "";
	const ACTIONCREATECOURSE = "";
	const ACTIONCREATEFOLDER = "";
	const ACTIONCREATEGROUP = "";
	const ACTIONASSIGNROLE = "";
	const ACTIONDELETE = "";*/
	
	/**
	 * Checks the syntax of a name
	 * 
	 * @param string $name
	 */
	static function checkNameSyntax($name)
	{
		// TODO: implement with lang
		$ret = "";
		if($name === "")
		{
			$ret = "Name is empty?!";
		}
		else if(!preg_match(self::REGEXNAME, $name))
		{
			$ret = "Name does not follow syntax guideline";
		}
		
		return $ret;
	}
	
	/**
	 * Checks pathsyntax
	 * 
	 * @param string $path
	 */
	static function checkPathSyntax($path)
	{
		// TODO: implement with lang
		$ret = "";
		if($path === "")
		{
			$ret = "Path is empty?!";
		}
		else if(substr($path, 0, 1) == '/' && strlen($path) == 1)
		{
			
		}
		else if(substr($path, 0, 2) == './')
		{
			
		}
		else if(substr($path, 0, 1) == '.' && strlen($path) == 1)
		{
			
		}
		/*else if(!preg_match(REGEXPATH, $path))
		{
			$ret = "Path does not follow syntax guideline";
		}*/
		
		return $ret;
	}
		
	/**
	 * Checks if the action is available
	 * 
	 * @param unknown $action
	 */
	public static function checkAction($action, $actionType = '')
	{
		$ret = "";
		if($actionType == '')
		{
			// TODO: Add function to read from db...
			$plugin = ilStructureImportPlugin::getInstance();
			$availableActions = array();
			$actionFound;
			$ret = false;
			
			$count = count($availableActions) - 1;
			for($i = 0; $i < $count; $i++)
			{
				// TODO Get lang function
				echo 'Get a lang function!';
				die;
				if($action == $plugin->txt($availableActions[$i]))
				{
					$actionFound = true;
				}
			}
			if(!$actionFound)
			{
				$ret = "Action not found!";
			}
		}
		else
		{
			$ret = self::checkOldAction($action, $actionType);
		}
		return $ret;
	}
	
	/**
	 * Checks actions from the old styled excelfile. This version was just
	 * a thing for the old structureimport form hslu
	 * 
	 * @param string $actionName Name of the action
	 * @param string $actionType Type of the action
	 */
	public static function checkOldAction($actionName, $actionType)
	{
		$ret = "";
		
		switch($actionName)
		{
			case 'Erstellen':
				switch($actionType)
				{
					case 'Kategorie':
					case 'Kurs':
					case 'Ordner':
					case 'Gruppe':
						$actionFound = true;
						break;						
				}
				break;
			case 'Zuordnen':
				if($actionType == 'Rolle')
				{
					$actionFound = true;
				}
				break;
			case 'Löschen':
				switch($actionType)
				{
					case 'Kategorie':
					case 'Kurs':
					case 'Ordner':
					case 'Gruppe':
						$actionFound = true;
						break;
				}
				break;
		}
		
		if(!$actionFound)
		{
			$ret = "Action not found!";
		}
		
		return $ret;
	}
}

?>
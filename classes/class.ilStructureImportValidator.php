<?php
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportConstants.php';

class ilStructureImportValidator
{
    /**
     * Checks the syntax of a name
     *
     * @param string $name
     */
    public static function checkNameSyntax($name)
    {
        // TODO: implement with lang
        $ret = "";
        if ($name === "") {
            $ret = "Name is empty?!";
        } elseif (!preg_match(ilStructureImportConstants::REGEXNAME, $name)) {
            $ret = "Name does not follow syntax guideline";
        }
        
        return $ret;
    }
    
    /**
     * Checks pathsyntax
     *
     * @param string $path
     */
    public static function checkPathSyntax($path)
    {
        // TODO: implement with lang
        $ret = "";
        if ($path === "") {
            $ret = "Path is empty?!";
        } elseif (substr($path, 0, 1) == '/' && strlen($path) == 1) {
        } elseif (substr($path, 0, 2) == './') {
        } elseif (substr($path, 0, 1) == '.' && strlen($path) == 1) {
        }
        /*else if(!preg_match(ilStructureImportConstants::REGEXPATH, $path))
        {
            $ret = "Path does not follow syntax guideline";
        }*/
        
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
        
        switch ($actionName) {
            case 'Erstellen':
                switch ($actionType) {
                    case 'Kategorie':
                    case 'Kurs':
                    case 'Ordner':
                    case 'Gruppe':
                        $actionFound = true;
                        break;
                }
                break;
            case 'Zuordnen':
                if ($actionType == 'Rolle') {
                    $actionFound = true;
                }
                break;
            case 'L�schen':
                switch ($actionType) {
                    case 'Kategorie':
                    case 'Kurs':
                    case 'Ordner':
                    case 'Gruppe':
                        $actionFound = true;
                        break;
                }
                break;
        }
        
        if (!$actionFound) {
            $ret = "Action not found!";
        }
        
        return $ret;
    }
}

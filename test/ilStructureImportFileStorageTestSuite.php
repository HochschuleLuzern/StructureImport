<?php

use\PHPUnit\Framework\TestCase;
use\Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
include_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/StructureImport/classes/class.ilStructureImportFileStorage.php';

class ilStructureImportFileStorageTestSuite extends TestCase {
    
    public function setUp() {
        parent::setUp();
    }
    
    public function testGeneratedPath()
    {
        global $DIC;
        
        $file_store = $DIC->filesystem()->storage();
        
        $user_id = 55;
        $storage = new ilStructureImportFileStorage($file_store);
        $should_path = $storage->getPathPostfix() . $user_id;
        
        echo $storage->generateDirPath();
    }
}
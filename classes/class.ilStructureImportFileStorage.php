<?php
use ILIAS\FileUpload\Location;

include_once('Services/FileSystem/classes/class.ilFileSystemStorage.php');
class ilStructureImportFileStorage
{
    private static $instances = array();
    
    /**
     * @var $storage ILIAS\Filesystem\Filesystem
     */
    protected $storage;
    
    public function __construct($storage)
    {
        $this->storage = $storage;
        $this->base_path = $this->getPathPrefix() . '/';
    }
    
    public function getPathPrefix()
    {
        return 'StructureImport';
    }
    
    public function getImportDirPath()
    {
        return $this->base_path . 'import_files/';
    }
    
    public function getLogDirPath()
    {
        return $this->base_path . 'import_logs/';
    }
    
    public function getFileContent($filename)
    {
        return $this->storage->read($this->base_path . $filename);
    }
    
    public function saveImportFileFromUpload(ILIAS\FileUpload\FileUpload $upload, $file_upload_info, $dest_filename)
    {
        if(!$upload->hasBeenProcessed())
            $upload->process();
        $tmp_filename = $file_upload_info['tmp_name'];
        $upload_result = $upload->getResults()[$tmp_filename];
        $upload->moveOneFileTo($upload_result, $this->getImportDirPath(), Location::STORAGE, $dest_filename);
    }
    
    public function getImportFileStream($filename)
    {
        return $this->storage->readStream($this->getImportDirPath() . $filename);
    }
    
    public function getImportFilePath($filename)
    {
        $file_stream = $this->storage->readStream($this->getImportDirPath() . $filename);
        return $file_stream->getMetadata('uri');
    }
}
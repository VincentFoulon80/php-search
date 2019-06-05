<?php

namespace VFou\Search\Services\FAL;

class Directory
{
    /**
     * @var string $workingDir
     */
    private $path;

    /**
     * @var File[] $files
     */
    private $files;

    private $keepOpen;

    /**
     * Directory constructor.
     * @param string $path
     * @throws \Exception
     */
    public function __construct($path = "", $keepFilesOpenned = true)
    {
        if($path !== ""){
            if(substr($path, -1) != DIRECTORY_SEPARATOR){
                $path .= DIRECTORY_SEPARATOR;
            }
            $this->path = $path;
            $this->createDirectoryIfNotExist();
        }
        $this->keepOpen = $keepFilesOpenned;
    }

    /**
     * @param $directory
     * @throws \Exception
     */
    private function createDirectoryIfNotExist(){
        if(!file_exists($this->path))
        {
            mkdir($this->path, 0775, true);
        } elseif(!is_dir($this->path)){
            throw new \Exception("The file at path $this->path is not a directory !");
        }
    }

    /**
     * @param string $filename
     * @param boolean $createIfNotExist
     * @return File
     */
    public function open($filename, $createIfNotExist = true){
        if(!isset($this->files[$filename])){
            if(file_exists($this->path.$filename)){
                $this->files[$filename] = new File($this->path, $filename, $this->keepOpen);
            } elseif($createIfNotExist){
                $this->files[$filename] = new File($this->path, $filename, $this->keepOpen);
            } else {
                return null;
            }
        }
        return $this->files[$filename] ?? null;
    }

    /**
     * @param $file
     */
    public function delete($file){
        $this->open($file)->delete();
    }

    /**
     * @return File[]
     */
    public function openAll(){
        $all = scandir($this->path);
        foreach($all as $file){
            if(is_file($this->path.$file)){
                $this->open($file, false);
            }
        }
        return $this->files;
    }

    public function free(){
        $this->files = [];
    }

    /**
     * @param bool $softDelete
     * @throws \Exception
     */
    public function deleteAll($softDelete = true){
        if($softDelete){
            $all = $this->openAll();
            if(count($all) > 0){
                foreach($all as $file){
                    $file->delete();
                }
            }
        } else {
            foreach($this->files ?? [] as $file){
                $file->delete();
            }
            $this->hardDelete(substr($this->path,0,-1));
            $this->createDirectoryIfNotExist();
        }
        $this->files = [];
    }
    private function hardDelete($dir) {
        if (!is_dir($dir) || is_link($dir)) return unlink($dir);
        foreach (scandir($dir) as $file) {
            if ($file == '.' || $file == '..') continue;
            if (!$this->hardDelete($dir . DIRECTORY_SEPARATOR . $file)) {
                chmod($dir . DIRECTORY_SEPARATOR . $file, 0777);
                if (!$this->hardDelete($dir . DIRECTORY_SEPARATOR . $file)) return false;
            };
        }
        return rmdir($dir);
    }
}

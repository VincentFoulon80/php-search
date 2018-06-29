<?php

namespace VFou\Search\Services\FAL;

class File
{
    /**
     * @var string $directory
     */
    private $directory;
    /**
     * @var string $name
     */
    private $name;

    /**
     * @var array|string $content
     */
    private $content;

    /**
     * @var bool
     */
    private $deleted;

    /**
     * @var bool
     */
    private $modified;

    /**
     * File constructor.
     * @param $directory
     * @param $name
     */
    public function __construct($directory, $name)
    {
        $this->directory = $directory;
        $this->name = $name;
        $this->deleted = false;
        $this->loaded = false;
    }

    public function load(){
        $path = $this->directory.$this->name;
        if(file_exists($path) && is_file($path))
        {
            $this->content = unserialize(file_get_contents($path));
        } else {
            $this->content = [];
        }
    }

    /**
     * @throws \Exception
     */
    public function unload(){
        $path = $this->directory.$this->name;
        if(!$this->deleted){
            if($this->modified)
            {
                if(file_exists($path) && !is_file($path))
                {
                    throw new \Exception("Unable to write the file $path : It's not a file !");
                }
                file_put_contents($path, serialize($this->content));
            }
        } else {
            unlink($path);
        }
        $this->content = [];
        $this->loaded = false;
    }

    /**
     * @throws \Exception
     */
    public function __destruct(){
        $this->unload();
    }

    /**
     * @return string
     */
    public function getName(){
        return $this->name;
    }

    /**
     * @return string
     */
    public function getContent(){
        if(!$this->loaded){
            $this->load();
            $this->loaded = true;
        }
        return $this->content;
    }

    /**
     * @param $content
     */
    public function setContent($content){
        $this->modified = true;
        $this->content = $content;
    }

    /**
     * @param bool $clean
     */
    public function delete($clean = true){
        if($clean) $this->content = "";
        $this->deleted = true;
    }

    /**
     *
     */
    public function restore(){
        $this->deleted = false;
    }
}

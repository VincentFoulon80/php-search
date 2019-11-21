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
     * @var bool
     */
    private $loaded;

    /**
     * @var bool
     */
    private $keepOpen;

    /**
     * File constructor.
     * @param $directory
     * @param $name
     * @param bool $keepOpen
     */
    public function __construct($directory, $name, $keepOpen = true)
    {
        $this->directory = $directory;
        $this->name = str_replace(DIRECTORY_SEPARATOR,"_",$name);
        $this->deleted = false;
        $this->loaded = false;
        $this->keepOpen = $keepOpen;
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
			if(file_exists($path))
				unlink($path);
        }
        $this->content = [];
        $this->loaded = false;
    }

    /**
     * @throws \Exception
     */
    public function __destruct(){
		if($this->loaded || $this->modified){
			$this->unload();
		}
    }

    /**
     * @return string
     */
    public function getName(){
        return $this->name;
    }

    /**
     * @return string|array
     * @throws \Exception
     */
    public function getContent(){
        if(!$this->loaded){
            $this->load();
            $this->loaded = true;
        }
        $content = $this->content;
        if(!$this->keepOpen){
            $this->unload();
        }
        return $content;
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

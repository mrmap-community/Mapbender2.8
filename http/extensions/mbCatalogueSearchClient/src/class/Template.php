<?php

namespace Geoportal\Suche;

class Template
{
    /**
     * @var string
     */
    private $path;
    private $fullPath;
    private $baseViewDir;
    private $parsedView;

    /**
     * Template constructor.
     * @param string $filepath
     */
    public function __construct($filepath = "")
    {
        $this->baseViewDir = '../src/views/';
        $this->path = $filepath;
        $this->fullPath = $this->getRealFilePath($filepath);
    }

    /**
     * @param $filepath
     * @param array $params
     * @return string
     */
    public function parse($filepath, $params = array())
    {
        ob_start();
        extract($params);
        include($filepath);
        $ret = ob_get_contents();
        ob_end_clean();
        return $ret;
    }

    /**
     * @param string $filename
     * @param array $params
     * @return string
     */
    public function parsePartial($filename = "", $params = array())
    {
        return $this->parse($filename, $params);
    }

    /**
     * @param array $params
     * @return $this
     */
    public function renderView($params = array())
    {
        $this->parsedView = $this->parse($this->fullPath, $params);
        //return $this->parse($this->fullPath, $params);
        return $this;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function render()
    {
        //echo $this->parse($this->fullPath);
        echo $this->parsedView;
    }

    /**
     * @param string $filename
     * @param array $params
     */
    public function parseView($filename = "", $params = array())
    {
        echo $this->parse($filename, $params);
    }

    /**
     * @param string $baseViewDir
     */
    public function setBaseViewDir($baseViewDir = "")
    {
        $this->baseViewDir = $baseViewDir;
    }

    /**
     * @param string $filepath
     * @return string
     */
    private function getRealFilePath($filepath = "")
    {
        return $this->baseViewDir . $filepath;
    }

}

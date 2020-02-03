<?php

namespace Geoportal\Suche;

class Configuration
{
    private $configuration;
    private $parser;

    public function __construct(&$configuration, $arrayParser)
    {
        $this->configuration = &$configuration;
        $this->parser = $arrayParser;
    }

    public function get($path)
    {
        return $this->parser->get($path, $this->configuration);
    }

    public function set($path, $value)
    {
        $this->parser->set($path, $value, $this->configuration);
        return $this;
    }
}

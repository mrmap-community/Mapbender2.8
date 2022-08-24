<?php

declare(strict_types=1);

namespace Mapbender\Core\Template;

use Mapbender\Core\Request\RequestInterface;


class Template implements TemplateInterface
{
    protected $folder;

    function __construct($folder = null)
    {
        if ($folder) {
            $this->set_folder($folder);
        }
    }

    public function set_folder($folder)
    {
        // normalize the internal folder value by removing any final slashes
        $this->folder = rtrim($folder, '/');
    }

    public function render(RequestInterface $request, string $template_name, array $context = null)
    {
        if (is_null($context)) $context = array();

        $template = $this->find_template($template_name);

        /**
         * TODO
         * If template was not found throw an exception instead of rendering a white
         * page.
         */
        if (!$template) return;

        echo $this->render_template($request, $template, $context);
    }

    protected function find_template(string $template_name)
    {
        $file_without_ext = "{$this->folder}/{$template_name}";

        /**
         * TODO
         * Make extentions a configuration.
         */
        $extensions = ["php", "html", "htm", "json", "xml", "geojson", "gml"];

        foreach ($extensions as $ext) {
            $file = $file_without_ext . "." . $ext;
            if (file_exists($file)) return $file;
        }

        /**
         * TODO
         * If template was not found throw an exception instead of rendering a white
         * page.
         */
        return false;
    }

    protected function render_template(RequestInterface $request, string $template, array $context)
    {
        ob_start();
        
        foreach ($context as $key => $value) {
            ${$key} = $value;
        }
        $request = $request;
        include $template;

        return ob_get_clean();
    }
}

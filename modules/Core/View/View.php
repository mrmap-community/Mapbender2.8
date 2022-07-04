<?php

declare(strict_types=1);

namespace Mapbender\Core\View;

use Mapbender\Core\Request\Request;
use Mapbender\Core\Template\TemplateInterface;
use Mapbender\Core\Template\Template;

class View implements ViewInterface
{
    // Should be set in the view
    public $context = [];
    public $template_path = null;
    public $template_name = null;

    public function __construct()
    {
        $this->request = new Request();
    }

    public function render()
    {
        $this->render_template();
    }

    public function get_context(): array
    {
        return $this->context;
    }

    public function render_template()
    {
        $template = $this->get_template();
        $template->render($this->request, $this->template_name, $this->context);
    }

    protected function get_template(): TemplateInterface
    {
        return new Template($this->template_path);
    }
}

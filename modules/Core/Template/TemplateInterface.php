<?php

declare(strict_types=1);

namespace Mapbender\Core\Template;

use Mapbender\Core\Request\RequestInterface;


interface TemplateInterface
{
    public function render(RequestInterface $request, string $template_name, array $context);
}

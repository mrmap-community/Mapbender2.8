<?php

declare(strict_types=1);

namespace Mapbender\Core\Template;

use Mapbender\Core\Request\RequestInterface;


interface TemplateInterface
{
    public function render(string $template_name, array $context, RequestInterface $request);
}

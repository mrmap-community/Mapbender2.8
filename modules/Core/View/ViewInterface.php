<?php

declare(strict_types=1);

namespace Mapbender\Core\View;


interface ViewInterface
{
    public function get_context(): array;
    
    public function render_template();

    public function render();
}

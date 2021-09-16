<?php

declare(strict_types=1);

namespace Mapbender\Core\Request;


interface RequestInterface
{
    public function query_params(): array;
    
    public function cookies(): array;
}

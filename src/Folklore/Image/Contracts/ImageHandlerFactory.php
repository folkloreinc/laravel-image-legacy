<?php

namespace Folklore\Image\Contracts;

use Closure;

interface ImageHandlerFactory
{
    public function source($name = null);

    public function extend($driver, Closure $callback);
}

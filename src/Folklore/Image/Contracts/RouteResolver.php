<?php

namespace Folklore\Image\Contracts;

use Illuminate\Routing\Route;

interface RouteResolver
{
    public function resolveToImage(Route $route);

    public function resolveToResponse(Route $route);

    public function getPathFromRoute(Route $route);

    public function getConfigFromRoute(Route $route);
}

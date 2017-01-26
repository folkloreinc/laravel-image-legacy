<?php namespace Folklore\Image;

use Folklore\Image\Exception\ParseException;

class Router
{
    protected $router;

    protected $container;

    protected $routes = [];

    protected $registered = false;

    public function __construct($router, $container, $routes = [])
    {
        $this->router = $router;
        $this->container = $container;
        $this->routes = $routes;
    }

    public function addRoutes($routes)
    {
        foreach ($routes as $name => $route) {
            $this->addRoute($route, is_numeric($name) ? null:$name);
        }
    }

    public function addRoute($route, $name = null)
    {
        if ($name === null) {
            $name = sizeof($this->routes);
        }

        $this->routes[$name] = $route;

        if ($this->registered) {
            $this->registerRouteOnRouter($route, $name);
        }
    }

    public function getRoute($name)
    {
        return $this->routes[$name];
    }

    public function getRouteName($name)
    {
        $route = $this->getRoute($name);
        return array_get($route, 'as', 'image.'.$name);
    }

    public function registerRoutesOnRouter()
    {
        foreach ($this->routes as $name => $route) {
            $this->registerRouteOnRouter($route, $name);
        }

        $this->registered = true;
    }

    protected function registerRouteOnRouter($route, $name)
    {
        $router = $this->getRouter();

        $as = $this->getRouteName($name);
        $routePath = array_get($route, 'route', '{pattern}');
        $domain = array_get($route, 'domain', null);
        $cache = array_get($route, 'cache', false);
        $middleware = array_get($route, 'middleware', []);

        if ($cache) {
            $middleware[] = 'image.middleware.cache';
        }

        // Here we check if the route contains any url config
        $patternOptions = array_get($route, 'url', []);
        if (sizeof($patternOptions)) {
            $patternName = 'image_pattern_'.preg_replace('/[^a-z0-9]+/i', '_', preg_replace('/^image\./', '', $as));
            $routePattern = $this->container['image.url']->pattern($patternOptions);
            $router->pattern($patternName, $routePattern);
        } else {
            $patternName = 'image_pattern';
        }

        $path = preg_replace('/\{\s*pattern\s*\}/i', '{'.$patternName.'}', $routePath);

        $router->get($path, array(
            'as' => $as,
            'domain' => $domain,
            'middleware' => $middleware,
            'image' => $route,
            'uses' => '\Folklore\Image\Http\ImageController@serve'
        ));
    }

    public function getRouter()
    {
        return $this->router;
    }
}

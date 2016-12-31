<?php namespace Folklore\Image;

use Folklore\Image\Exception\ParseException;
use Folklore\Image\Contracts\UrlGenerator as UrlGeneratorContract;

class Router
{
    protected $routes = [];
    
    protected $app;
    
    protected $router;
    
    public function __construct($app, $router)
    {
        $this->app = $app;
        $this->router = $router;
        $this->routes = $app['config']->get('image.routes', []);
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
            $this->routes[] = $route;
        } else {
            $this->routes[$name] = $route;
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
    
    public function addRoutesToRouter()
    {
        foreach ($this->routes as $name => $route) {
            $this->addRouteToRouter($route, $name);
        }
    }
    
    public function addRouteToRouter($route, $name)
    {
        $router = $this->getRouter();
        
        $patternOptions = array_only($route, ['pattern', 'parameters_format']);
        $source = array_get($route, 'source');
        $as = array_get($route, 'as', 'image.'.$name);
        $routePath = array_get($route, 'route', '{pattern}');
        $domain = array_get($route, 'domain', null);
        $cache = array_get($route, 'cache', false);
        $middleware = array_get($route, 'middleware', []);
        
        if ($cache) {
            $middleware[] = 'image.middleware.cache';
        }
        
        if (sizeof($patternOptions)) {
            $patternName = 'image_pattern_'.str_replace('.', '_', preg_replace('/^image\./', '', $as));
            $routePattern = $this->app['image']->source($source)
                ->pattern($patternOptions);
            $router->pattern($patternName, $routePattern);
        } else {
            $patternName = 'image_pattern';
        }
        
        $path = preg_replace('/\{\s*pattern\s*\}/', '{'.$patternName.'}', $routePath);
        
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

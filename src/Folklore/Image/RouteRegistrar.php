<?php
namespace Folklore\Image;

use Illuminate\Routing\Router;
use Illuminate\Container\Container;
use Folklore\Image\Contracts\UrlGenerator as UrlGeneratorContract;
use Illuminate\Support\Arr;

class RouteRegistrar
{
    protected $router;

    protected $urlGenerator;

    /**
     * The attributes that can be set through this class.
     *
     * @var array
     */
    protected $allowedAttributes = [
        'as', 'domain', 'middleware', 'name', 'namespace', 'prefix',
    ];

    protected $patternName = 'image_pattern';

    protected $cacheMiddleware = 'image.middleware.cache';

    protected $controller = '\Folklore\Image\Http\ImageController@serve';

    /**
     * Create a new route registrar instance.
     *
     * @param \Illuminate\Routing\Router $router The laravel router
     * @param \Folklore\Image\UrlGenerator $urlGenerator The url generator
     * @return void
     */
    public function __construct(Router $router, UrlGeneratorContract $urlGenerator)
    {
        $this->router = $router;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Creates a new image route.
     *
     * Examples:
     *
     * ```php
     * $router = app('router');
     * $router->image('/thumbnail/{pattern}', [
     *     'filters' => [
     *         'width' => 100,
     *         'height' => 100,
     *         'crop' => true
     *     ]
     * ]);
     * ```
     *
     * @param string $path The path of the route. It must contain `{pattern}`.
     * @param array $config Configuration options for the route.
     * @return \Illuminate\Routing\Route The route created
     */
    public function image($path, $config = [])
    {
        $as = data_get($config, 'as');
        $domain = data_get($config, 'domain', null);
        $cache = data_get($config, 'cache', false);
        $middleware = data_get($config, 'middleware', []);
        $patternName = data_get($config, 'pattern_name', $this->patternName);
        $cacheMiddleware = data_get($config, 'cache_middleware', $this->cacheMiddleware);
        $controller = data_get($config, 'uses', $this->controller);

        if ($cache) {
            $middleware[] = $cacheMiddleware;
        }

        // Here we check if the route contains any url config. If it does, we
        // create a route pattern to catch it
        $patternOptions = data_get($config, 'pattern', []);
        if (sizeof($patternOptions)) {
            $generatedPatternName = $patternName.'_'.preg_replace(
                '/[^a-z0-9]+/i',
                '_',
                preg_replace('/^image\./', '', $as)
            );
            $patternName = data_get($config, 'pattern_name', $generatedPatternName);
            $routePattern = $this->urlGenerator->pattern($patternOptions);
            $this->router->pattern($patternName, $routePattern);
        } else {
            $patternName = data_get($config, 'pattern_name', $this->patternName);
        }

        $routePath = preg_replace('/\{\s*pattern\s*\}/i', '{'.$patternName.'}', $path);

        return $this->router->get($routePath, array(
            'as' => $as,
            'domain' => $domain === null ? '':$domain,
            'middleware' => $middleware,
            'image' => Arr::except($config, $this->allowedAttributes),
            'uses' => $controller
        ));
    }

    /**
     * Set the name of the router pattern
     *
     * @param string $name The name of the pattern that will be added to the router
     * @return $this
     */
    public function setPatternName($name)
    {
        $this->patternName = $name;
        return $this;
    }

    /**
     * Get the name of the router pattern
     *
     * @return string The name of the pattern
     */
    public function getPatternName()
    {
        return $this->patternName;
    }

    /**
     * Set the middleware that will be used for caching images
     *
     * @param string $middleware The middleware name or class path
     * @return $this
     */
    public function setCacheMiddleware($middleware)
    {
        $this->cacheMiddleware = $middleware;
        return $this;
    }

    public function getCacheMiddleware()
    {
        return $this->cacheMiddleware;
    }

    public function setController($value)
    {
        $this->controller = $value;
        return $this;
    }

    public function getController()
    {
        return $this->controller;
    }
}

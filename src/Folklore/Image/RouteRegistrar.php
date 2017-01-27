<?php
namespace Folklore\Image;

use Illuminate\Routing\Router;
use Illuminate\Container\Container;
use Folklore\Image\UrlGenerator;

class RouteRegistrar
{
    protected $container;

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
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    public function __construct(Router $router, Container $container)
    {
        $this->router = $router;
        $this->container = $container;
    }

    public function image($path, $config)
    {
        $as = array_get($config, 'as');
        $domain = array_get($config, 'domain', null);
        $cache = array_get($config, 'cache', false);
        $middleware = array_get($config, 'middleware', []);
        $patternName = array_get($config, 'pattern_name', $this->cacheMiddleware);
        $cacheMiddleware = array_get($config, 'cache_middleware', $this->cacheMiddleware);
        $controller = array_get($config, 'uses', $this->controller);

        if ($cache) {
            $middleware[] = $cacheMiddleware;
        }

        // Here we check if the route contains any url config. If it does, we
        // create a route pattern to catch it
        $patternOptions = array_get($config, 'url', []);
        if (sizeof($patternOptions)) {
            $generatedPatternName = 'image_pattern_'.preg_replace(
                '/[^a-z0-9]+/i',
                '_',
                preg_replace('/^image\./', '', $as)
            );
            $patternName = array_get($config, 'pattern_name', $generatedPatternName);
            $routePattern = $this->container['image.url']->pattern($patternOptions);
            $this->router->pattern($patternName, $routePattern);
        } else {
            $patternName = array_get($config, 'pattern_name', $this->patternName);
        }

        $routePath = preg_replace('/\{\s*pattern\s*\}/i', '{'.$patternName.'}', $path);

        return $this->router->get($routePath, array(
            'as' => $as,
            'domain' => $domain,
            'middleware' => $middleware,
            'image' => array_except($config, $this->allowedAttributes),
            'uses' => $controller
        ));
    }
}

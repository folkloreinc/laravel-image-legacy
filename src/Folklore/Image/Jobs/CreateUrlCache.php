<?php

namespace Folklore\Image\Jobs;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Routing\Router;
use Illuminate\Http\Request;
use Folklore\Image\Contracts\UrlGenerator;
use Folklore\Image\Contracts\RouteResolver;
use Folklore\Image\Contracts\CacheManager;

class CreateUrlCache implements ShouldQueue
{
    use InteractsWithQueue;

    public $url;
    public $filters = [];
    public $route = null;

    /**
     * Create a new job instance.
     *
     * @param  string  $url
     * @param  array  $filters
     * @param  string  $route
     * @return void
     */
    public function __construct($url, $filters = [], $route = null)
    {
        $this->url = $url;
        $this->filters = $filters;
        $this->route = $route;
    }

    /**
     * Execute the job.
     *
     * @param  Router  $router
     * @param  UrlGenerator  $urlGenerator
     * @param  RouteResolver  $routeResolver
     * @param  CacheManager  $cacheManager
     * @return void
     */
    public function handle(
        Router $router,
        UrlGenerator $urlGenerator,
        RouteResolver $routeResolver,
        CacheManager $cacheManager
    ) {
        $route = !empty($this->route) ? $router->getRoutes()->getByName($this->route) : null;
        $routeConfig = !is_null($route) ? $routeResolver->getConfigFromRoute($route) : [];
        $finalFilters = array_merge($this->filters, !empty($route) ? [
            'pattern' => array_get($routeConfig, 'pattern', [])
        ] : []);
        $finalUrl = $urlGenerator->make($this->url, $finalFilters);

        if (!is_null($route)) {
            $method = in_array('POST', $route->methods()) ? 'POST' : 'GET';
            $request = Request::create($finalUrl, $method);
            $image = $routeResolver->resolveToImage($route->bind($request));
        } else {
            $parsedPath = $urlGenerator->parse($finalUrl);
            $image = app('image')->make($parsedPath['path'], $parsedPath['filters']);
        }

        $cachePath = !is_null($route) ? array_get($routeConfig, 'cache_path') : null;
        $cacheManager->put($image, $finalUrl, $cachePath);
    }
}

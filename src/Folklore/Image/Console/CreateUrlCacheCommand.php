<?php

namespace Folklore\Image\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Routing\Router;
use Illuminate\Http\Request;
use Folklore\Image\Contracts\UrlGenerator;
use Folklore\Image\Contracts\RouteResolver;
use Folklore\Image\Contracts\CacheManager;

class CreateUrlCacheCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'image:create_url_cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a cache for a specific url and filters';

    protected $router;
    protected $urlGenerator;
    protected $routeResolver;
    protected $cacheManager;

    public function __construct(
        Router $router,
        UrlGenerator $urlGenerator,
        RouteResolver $routeResolver,
        CacheManager $cacheManager
    ) {
        parent::__construct();

        $this->router = $router;
        $this->urlGenerator = $urlGenerator;
        $this->routeResolver = $routeResolver;
        $this->cacheManager = $cacheManager;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $url = $this->argument('url');
        $filters = $this->option('filters');
        $routeName = $this->option('route');

        $route = !empty($routeName) ? $this->router->getRoutes()->getByName($routeName) : null;
        $routeConfig = !is_null($route) ? $this->routeResolver->getConfigFromRoute($route) : [];
        $finalUrl = $this->urlGenerator->make($url, array_merge($filters, !empty($route) ? [
            'pattern' => array_get($routeConfig, 'pattern', [])
        ] : []));

        if (!is_null($route)) {
            $method = in_array('POST', $route->methods()) ? 'POST' : 'GET';
            $request = Request::create($finalUrl, $method);
            $image = $this->routeResolver->resolveToImage($route->bind($request));
        } else {
            $parsedPath = $this->urlGenerator->parse($finalUrl);
            $image = app('image')->make($parsedPath['path'], $parsedPath['filters']);
        }

        $cachePath = !is_null($route) ? array_get($routeConfig, 'cache_path') : null;
        $this->cacheManager->put($image, $finalUrl, $cachePath);

        $this->line('<info>Created:</info> '.$finalUrl.' for image '.$url);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['url', InputArgument::REQUIRED, 'The url of the image'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['filters', 'f', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'The filters to apply on the image'],
            ['route', 'r', InputOption::VALUE_REQUIRED, 'The route name', 'image'],
        ];
    }
}

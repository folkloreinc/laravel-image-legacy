<?php namespace Folklore\Image;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\Bus\Dispatcher;
use Folklore\Image\Http\ImageResponse;
use Folklore\Image\RouteRegistrar;
use Folklore\Image\Contracts\ImageHandlerFactory as ImageHandlerFactoryContract;
use Folklore\Image\Contracts\FiltersManager as FiltersManagerContract;
use Folklore\Image\Contracts\ImageManager as ImageManagerContract;
use Folklore\Image\Contracts\ImageHandler as ImageHandlerContract;
use Folklore\Image\Contracts\ImageDataHandler as ImageDataHandlerContract;
use Folklore\Image\Contracts\CacheManager as CacheManagerContract;
use Folklore\Image\Contracts\RouteResolver as RouteResolverContract;
use Folklore\Image\Contracts\UrlGenerator as UrlGeneratorContract;
use Illuminate\Contracts\Routing\ResponseFactory as ResponseFactoryContract;

class ServiceProvider extends BaseServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->bootPublishes();

        $this->bootRouter();

        $this->bootHttp();

        $this->bootConsole();

        $this->bootJobHandlers();
    }

    public function bootPublishes()
    {
        // Config file path
        $configFile = __DIR__ . '/../../config/image.php';
        $publicFile = __DIR__ . '/../../../js/dist/';
        $routesFile = __DIR__ . '/../../routes/images.php';

        // Merge files
        $this->mergeConfigFrom($configFile, 'image');

        // Publish
        $this->publishes([
            $configFile => config_path('image.php')
        ], 'config');

        $this->publishes([
            $publicFile => public_path('vendor/folklore/image')
        ], 'public');

        $this->publishes([
            $routesFile => is_dir(base_path('routes')) ?
                base_path('routes/images.php') : app_path('Http/routesImages.php')
        ], 'routes');
    }

    public function bootRouter()
    {
        $app = $this->app;
        $router = $this->app['router'];

        // Add a macro to the router for creating images route.
        $router->macro('image', function ($path, $config) use ($router, $app) {
            return $app['image.routes']->image($path, $config);
        });

        // Add default pattern to router
        $pattern = $this->app['image']->pattern();
        $router->pattern('image_pattern', $pattern);

        $map = $this->app['config']->get('image.routes.map');
        if (!is_null($map)) {
            $this->app['image']->routes();
        }
    }

    /**
     * Add the macro for image response
     *
     * @return void
     */
    public function bootHttp()
    {
        $this->app[ResponseFactoryContract::class]->macro('image', function (
            $image = null,
            $status = 200,
            $headers = []
        ) {
            return new ImageResponse($image, $status, $headers);
        });
    }

    /**
     * Add commands
     *
     * @return void
     */
    public function bootConsole()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                'image.console.create_url_cache',
            ]);
        }
    }

    /**
     * Add job handlers
     *
     * @return void
     */
    public function bootJobHandlers()
    {
        $dispatcher = $this->app->make(Dispatcher::class);
        if (method_exists($dispatcher, 'maps')) {
            $dispatcher->maps([
                \Folklore\Image\Jobs\CreateUrlCache::class =>
                    \Folklore\Image\Handlers\CreateUrlCacheHandler::class.'@handle'
            ]);
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerImage();

        $this->registerImagineManager();

        $this->registerSourceManager();

        $this->registerUrlGenerator();

        $this->registerRouteRegistrar();

        $this->registerImageHandler();

        $this->registerContracts();

        $this->registerMiddlewares();

        $this->registerConsole();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function registerImage()
    {
        $this->app->singleton('image', function () {
            $image = new Image($this->app, $this->app['router']);
            $image->setFilters($this->app['config']->get('image.filters', []));
            $image->setRouteConfig($this->app['config']->get('image.routes', []));
            return $image;
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function registerImagineManager()
    {
        $this->app->singleton('image.imagine', function () {
            return new ImagineManager($this->app);
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function registerSourceManager()
    {
        $this->app->singleton('image.source', function () {
            return new SourceManager($this->app);
        });
    }

    /**
     * Register the url generator
     *
     * @return void
     */
    public function registerUrlGenerator()
    {
        $this->app->singleton('image.url', function () {
            $router = $this->app['router'];
            $config = $this->app['config'];
            $filtersManager = $this->app->make(FiltersManagerContract::class);
            $generator = new UrlGenerator($router, $filtersManager);
            $generator->setFormat($config->get('image.url.format', ''));
            $generator->setFiltersFormat($config->get('image.url.filters_format', ''));
            $generator->setFilterFormat($config->get('image.url.filter_format', ''));
            $generator->setFilterSeparator($config->get('image.url.filter_separator', ''));
            $generator->setPlaceholdersPatterns($config->get('image.url.placeholders_patterns', ''));
            return $generator;
        });
    }

    /**
     * Register the route registrar
     *
     * @return void
     */
    public function registerRouteRegistrar()
    {
        $this->app->singleton('image.routes', function () {
            $router = $this->app['router'];
            $config = $this->app['config'];
            $urlGenerator = $this->app->make(UrlGeneratorContract::class);
            $registrar = new RouteRegistrar($router, $urlGenerator);
            $registrar->setPatternName($config->get('image.routes.pattern_name'));
            $registrar->setCacheMiddleware($config->get('image.routes.cache_middleware'));
            $registrar->setController($config->get('image.routes.controller'));
            return $registrar;
        });
    }

    /**
     * Register the image handler
     *
     * @return void
     */
    public function registerImageHandler()
    {
        $this->app->bind(ImageHandler::class, function () {
            $filtersManager = $this->app->make(FiltersManagerContract::class);
            $memoryLimit = $this->app['config']->get('image.memory_limit', '128MB');
            return new ImageHandler($filtersManager, $memoryLimit);
        });
    }

    /**
     * Register contracts
     *
     * @return void
     */
    public function registerContracts()
    {
        $this->app->bind(ImageManagerContract::class, 'image');
        $this->app->bind(ImageHandlerFactoryContract::class, 'image');
        $this->app->bind(FiltersManagerContract::class, 'image');
        $this->app->bind(ImageHandlerContract::class, ImageHandler::class);
        $this->app->bind(ImageDataHandlerContract::class, ImageDataHandler::class);
        $this->app->bind(CacheManagerContract::class, CacheManager::class);
        $this->app->bind(RouteResolverContract::class, RouteResolver::class);
        $this->app->bind(UrlGeneratorContract::class, 'image.url');
    }

    /**
     * Register the image factory
     *
     * @return void
     */
    public function registerMiddlewares()
    {
        $this->app->bind('image.middleware.cache', \Folklore\Image\Http\CacheMiddleware::class);
    }

    /**
     * Register console
     *
     * @return void
     */
    public function registerConsole()
    {
        $this->app->bind('image.console.create_url_cache', \Folklore\Image\Console\CreateUrlCacheCommand::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'image',
            'image.url',
            'image.routes',
            'image.imagine',
            'image.source',
            'image.middleware.cache'
        ];
    }
}

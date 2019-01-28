<?php namespace Folklore\Image;

use Illuminate\Support\ServiceProvider;
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

class ImageServiceProvider extends ServiceProvider
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
                \Folklore\Image\Jobs\CreateUrlCacheJob::class =>
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
        $this->app->singleton('image', function ($app) {
            $image = new Image($app, $app['router']);
            $image->setFilters($app['config']->get('image.filters', []));
            $image->setRouteConfig($app['config']->get('image.routes', []));
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
        $this->app->singleton('image.imagine', function ($app) {
            return new ImagineManager($app);
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function registerSourceManager()
    {
        $this->app->singleton('image.source', function ($app) {
            return new SourceManager($app);
        });
    }

    /**
     * Register the url generator
     *
     * @return void
     */
    public function registerUrlGenerator()
    {
        $this->app->singleton('image.url', function ($app) {
            $router = $app['router'];
            $filtersManager = $app->make(FiltersManagerContract::class);
            $generator = new UrlGenerator($router, $filtersManager);
            $config = $app['config']->get('image.url', []);
            $generator->setFormat(array_get($config, 'format', ''));
            $generator->setFiltersFormat(array_get($config, 'filters_format', ''));
            $generator->setFilterFormat(array_get($config, 'filter_format', ''));
            $generator->setFilterSeparator(array_get($config, 'filter_separator', ''));
            $generator->setPlaceholdersPatterns(array_get($config, 'placeholders_patterns', ''));
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
        $this->app->singleton('image.routes', function ($app) {
            $router = $app['router'];
            $urlGenerator = $app->make(UrlGeneratorContract::class);
            $registrar = new RouteRegistrar($router, $urlGenerator);
            $config = $app['config']->get('image.routes', []);
            $registrar->setPatternName(array_get($config, 'pattern_name'));
            $registrar->setCacheMiddleware(array_get($config, 'cache_middleware'));
            $registrar->setController(array_get($config, 'controller'));
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
        $this->app->bind(ImageHandler::class, function ($app) {
            $filtersManager = $app->make(FiltersManagerContract::class);
            $memoryLimit = $app['config']->get('image.memory_limit', '128MB');
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

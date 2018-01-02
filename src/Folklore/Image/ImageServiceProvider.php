<?php namespace Folklore\Image;

use Illuminate\Support\ServiceProvider;
use Folklore\Image\Http\ImageResponse;
use Folklore\Image\RouteRegistrar;
use Folklore\Image\Contracts\ImageManipulator as ImageManipulatorContract;
use Illuminate\Contracts\Routing\ResponseFactory as ResponseFactoryContract;

class ImageServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    protected function getRouter()
    {
        return $this->app['router'];
    }

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
    }

    public function bootPublishes()
    {
        // Config file path
        $configFile = __DIR__ . '/../../config/image.php';
        $publicFile = __DIR__ . '/../../resources/assets/';
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

        // Map routes defined in the routes files
        $router->group([
            'namespace' => $app['config']->get('image.routes.namespace', null),
            'domain' => $app['config']->get('image.routes.domain', null),
            'middleware' => $app['config']->get('image.routes.middleware', [])
        ], function ($router) use ($app) {
            $appPath = is_dir(base_path('routes')) ?
                base_path('routes/images.php'):app_path('Http/routesImages.php');
            if (is_file($appPath)) {
                require $appPath;
            } else {
                require __DIR__ . '/../../routes/images.php';
            }
        });
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
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerImage();

        $this->registerImagineManager();

        $this->registerSourceManager();

        $this->registerRouteRegistrar();

        $this->registerUrlGenerator();

        $this->registerImageManipulator();

        $this->registerMiddlewares();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function registerImage()
    {
        $this->app->singleton('image', function ($app) {
            $filters = $this->app['config']->get('image.filters', []);
            $image = new Image($app);
            $image->setFilters($filters);
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
     * Register the route registrar
     *
     * @return void
     */
    public function registerRouteRegistrar()
    {
        $this->app->singleton('image.routes', function ($app) {
            $router = $this->getRouter();
            $registrar = new RouteRegistrar($router, $app['image.url']);
            $registrar->setPatternName($app['config']['image.routes.pattern_name']);
            $registrar->setCacheMiddleware($app['config']['image.routes.cache_middleware']);
            $registrar->setController($app['config']['image.routes.controller']);
            return $registrar;
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
            $generator = new UrlGenerator($app['image'], $app['router']);

            // Set default values from config
            $config = $app['config'];
            $generator->setFormat(
                $config->get('image.url.format', '')
            );
            $generator->setFiltersFormat(
                $config->get('image.url.filters_format', '')
            );
            $generator->setFilterFormat(
                $config->get('image.url.filter_format', '')
            );
            $generator->setFilterSeparator(
                $config->get('image.url.filter_separator', '')
            );
            return $generator;
        });
    }

    /**
     * Register the image manipulator
     *
     * @return void
     */
    public function registerImageManipulator()
    {
        $this->app->bind(ImageManipulatorContract::class, function ($app) {
            $manipulator = new ImageManipulator($app['image']);
            $manipulator->setMemoryLimit($app['config']['image.memory_limit']);
            return $manipulator;
        });
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
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'image',
            'image.router',
            'image.imagine',
            'image.manipulator',
            'image.source',
            'image.middleware.cache'
        ];
    }
}

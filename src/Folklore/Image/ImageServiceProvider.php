<?php namespace Folklore\Image;

use Illuminate\Support\ServiceProvider;
use Folklore\Image\Http\ImageResponse;
use Response;

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

        // Merge files
        $this->mergeConfigFrom($configFile, 'image');

        // Publish
        $this->publishes([
            $configFile => config_path('image.php')
        ], 'config');

        $this->publishes([
            $publicFile => public_path('vendor/folklore/image')
        ], 'public');
    }

    public function bootRouter()
    {
        // Add default pattern to router
        $pattern = $this->app['image']->pattern();
        $this->app['router']->pattern('image_pattern', $pattern);
    }

    public function bootHttp()
    {
        Response::macro('image', function ($value = null) {
            return new ImageResponse($value);
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

        $this->registerRouter();

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
            return new Image($app);
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
     * Register the url generator
     *
     * @return void
     */
    public function registerRouter()
    {
        $this->app->singleton('image.router', function ($app) {
            $router = $this->getRouter();
            return new Router($app, $router);
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
            return new SourceManager($app, $app['image.imagine']);
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
            return new UrlGenerator($app);
        });
    }

    /**
     * Register the image manipulator
     *
     * @return void
     */
    public function registerImageManipulator()
    {
        $this->app->bind('image.manipulator', function ($app) {
            return new ImageManipulator($app['image'], $app['image.url']);
        });
    }

    /**
     * Register the image factory
     *
     * @return void
     */
    public function registerMiddlewares()
    {
        $this->app->bind('image.middleware.cache', '\Folklore\Image\Http\CacheMiddleware');
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

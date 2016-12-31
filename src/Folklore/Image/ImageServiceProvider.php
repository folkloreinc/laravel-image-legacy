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
        
        $this->registerImageFactory();
        
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
        $this->app->singleton('image.manager.imagine', function ($app) {
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
        $this->app->singleton('image.manager.source', function ($app) {
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
        $this->app->singleton('image.url', function ($app, $parameters) {
            return new UrlGenerator($app);
        });
    }

    /**
     * Register the image factory
     *
     * @return void
     */
    public function registerImageFactory()
    {
        $this->app->bind('\Folklore\Image\Contracts\ImageFactory', function ($app, $parameters) {
            return new ImageFactory($app, $parameters[0]);
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
            'image.manager.imagine',
            'image.manager.source',
            'image.middleware.cache'
        ];
    }
}

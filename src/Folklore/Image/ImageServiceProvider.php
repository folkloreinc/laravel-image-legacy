<?php namespace Folklore\Image;

use Illuminate\Support\ServiceProvider;

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
        // Config file path
        $configFile = __DIR__ . '/../../resources/config/image.php';
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

        $app = $this->app;
        $router = $app['router'];
        $router->pattern('image_pattern', $app['image']->pattern());
        $router->pattern('image_proxy_pattern', $app['image']->pattern(''));

        //Serve image
        $serve = config('image.serve');
        if ($serve) {
            // Create a route that match pattern
            $serveRoute = config('image.serve_route', '{image_pattern}');
            $router->get($serveRoute, array(
                'as' => 'image.serve',
                'domain' => config('image.domain', null),
                'uses' => 'Folklore\Image\ImageController@serve'
            ));
        }
        
        //Proxy
        $proxy = $this->app['config']['image.proxy'];
        if ($proxy) {
            $serveRoute = config('image.proxy_route', '{image_pattern}');
            $router->get($serveRoute, array(
                'as' => 'image.proxy',
                'domain' => config('image.proxy_domain'),
                'uses' => 'Folklore\Image\ImageController@proxy'
            ));
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('image', function ($app) {
            return new ImageManager($app);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('image');
    }
}

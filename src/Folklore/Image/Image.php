<?php namespace Folklore\Image;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Folklore\Image\Contracts\ImageHandlerFactory as ImageHandlerFactoryContract;
use Folklore\Image\Contracts\ImageHandler as ImageHandlerContract;
use Folklore\Image\Contracts\FiltersManager as FiltersManagerContract;
use Folklore\Image\Contracts\ImageManager as ImageManagerContract;
use Folklore\Image\Contracts\UrlGenerator as UrlGeneratorContract;
use Closure;

class Image implements
    ImageHandlerFactoryContract,
    FiltersManagerContract,
    ImageManagerContract
{
    protected $container;

    /**
     * All handlers
     *
     * @var array
     */
    protected $handlers = [];

    /**
     * All registered filters.
     *
     * @var array
     */
    protected $filters = [];

    /**
     * Route config
     *
     * @var array
     */
    protected $routeConfig = [];


    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Return an URL to process the image
     *
     * Examples:
     *
     * ```php
     * echo Image::url('path/to/image.jpg', 300, 300);
     * // '/path/to/image-filters(300x300).jpg'
     * ```

     * You can also omit the size parameters and pass a filters array as the second argument
     * ```php
     * echo Image::url('path/to/image.jpg', [
     *     'width' => 300,
     *     'height' => 300,
     *     'rotate' => 180
     * ]);
     * // '/path/to/image-filters(300x300-rotate(180)).jpg'
     * ```

     * There is also an `image_url()` helper available
     * ```php
     * echo image_url('path/to/image.jpg', 300, 300);
     * ```
     *
     * You can change the format of the url by changing the configuration in the
     * `config/image.php` file or by passing the same options in the filters
     * array. (see [Url Generator](url-generator.md) for available options)
     *
     * @param string $src
     * @param int|array|string $width The maximum width of the image. If an
     * array or a string is passed, it is considered as the filters argument.
     * @param int $height The maximum height of the image
     * @param array|string $filters An array of filters
     *
     * @return string The generated url containing the filters.
     */
    public function url($src, $width = null, $height = null, $filters = [])
    {
        $urlGenerator = $this->getUrlGenerator();
        return $urlGenerator->make($src, $width, $height, $filters);
    }

    /**
     * Return a pattern to match url
     *
     * @param  array    $config    Pattern configuration
     * @return string   $pattern   A regex matching the images url
     */
    public function pattern($config = [])
    {
        $urlGenerator = $this->getUrlGenerator();
        return $urlGenerator->pattern($config);
    }

    /**
     * Return an URL to process the image
     *
     * @param  string  $path
     * @return array
     */
    public function parse($path, $config = [])
    {
        $urlGenerator = $this->getUrlGenerator();
        return $urlGenerator->parse($path, $config);
    }

    /**
     * Get an ImageHandler for a specific source
     *
     * @param string|null $name The name of the source
     * @return Folklore\Image\Contracts\ImageHandlerContract The image manipulator object, bound
     * the to specified source
     */
    public function source($name = null)
    {
        $key = $name ? $name : 'default';

        if (isset($this->handlers[$key])) {
            return $this->handlers[$key];
        }

        $source = $this->getSourceManager()->driver($name);
        $handler = $this->container->make(ImageHandlerContract::class);
        $handler->setSource($source);
        
        return $this->handlers[$key] = $handler;
    }

    /**
     * Register a custom source creator Closure.
     *
     * @param  string    $driver
     * @param  \Closure  $callback
     * @return $this
     */
    public function extend($driver, Closure $callback)
    {
        $sourceManager = $this->getSourceManager();
        $sourceManager->extend($driver, $callback);

        return $this;
    }

    /**
     * Map image routes on the Laravel Router
     *
     * Add the routes from the file specified in the `config/image.php`
     * file at `routes.map`. You can pass a config array to override values
     * from the config or you can also pass a path to a routes file. This method
     * is automatically called if you have a path in your `config/image.php`.
     * To disable this you can set `routes.map` to null.
     *
     * Examples:
     *
     * Map the routes on the Laravel Router
     * ```php
     * Image::routes();
     *
     * // or with the helper
     * image()->routes();
     * ```
     *
     * Map a custom routes file
     * ```php
     * Image::routes(base_path('routes/my-custom-file.php'));
     *
     * // or an equivalent
     * Image::routes([
     *     'map' => base_path('routes/my-custom-file.php')
     * ]);
     * ```
     *
     * @param  array|string  $config A config array that will override values
     * from the `config/image.php`. If you pass a string, it is considered as
     * a path to a filtes containing routes.
     * @return array
     */
    public function routes($config = [])
    {
        $config = array_merge($this->routeConfig, is_string($config) ? [
            'map' => $config
        ] : $config);
        $groupConfig = Arr::only($config, ['domain', 'prefix', 'as', 'namespace', 'middleware']);
        $map = data_get($config, 'map', null);

        // Map routes defined in the routes files
        $this->container->make('router')->group($groupConfig, function ($router) use ($map) {
            if (!is_null($map) && is_file($map)) {
                require $map;
            } else {
                require __DIR__ . '/../../routes/images.php';
            }
        });
    }

    /**
     * Register a new filter to the manager that can be used by the `Image::url()` and `Image::make()` method.
     *
     * Examples:
     *
     * From an array
     * ```php
     * // Declare the filter in a Service Provider
     * Image::filter('small', [
     *     'width' => 100,
     *     'height' => 100,
     *     'crop' => true,
     * ]);
     *
     * // Use it when making an image
     * $image = Image::make('path/to/image.jpg', [
     *     'small' => true,
     * ]);
     *
     * // or
     *
     * $image = Image::make('path/to/image.jpg', 'small');
     * ```
     *
     * With a closure
     * ```php
     * // Declare the filter in a Service Provider
     * Image::filter('circle', function ($image, $color)
     * {
     *     // See Imagine documentation for the Image object
     *     // (https://imagine.readthedocs.io/en/latest/index.html)
     *     $color = $image->palette()->color($color);
     *     $image->draw()
     *          ->ellipse(new Point(0, 0), new Box(300, 225), $color);
     *     return $image;
     * });
     *
     * // Use it when making an image
     * $image = Image::make('path/to/image.jpg', [
     *     'circle' => '#FFCC00',
     * ]);
     * ```
     *
     * With a class path
     * ```php
     * // Declare the filter in a Service Provider
     * Image::filter('custom', \App\Filters\CustomFilter::class);
     *
     * // Use it when making an image
     * $image = Image::make('path/to/image.jpg', [
     *     'custom' => true,
     * ]);
     * ```
     *
     * @param string $name The name of the filter
     * @param \Closure|array|string|object $filter The filter can be an array of
     * filters, a closure that will get the Image object or a class path to a
     * Filter class. (more info canbe found in the
     * [Filters](../filters.md) documentation)
     * @return $this
     */
    public function filter($name, $filter)
    {
        $this->filters[$name] = $filter;

        return $this;
    }

    /**
     * Set all filters
     *
     * @param  array    $filters
     * @return $this
     */
    public function setFilters($filters)
    {
        $this->filters = $filters;
        return $this;
    }

    /**
     * Get all filters
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Get a filter
     *
     * @param  string    $name
     * @return array|Closure|string|object
     */
    public function getFilter($name)
    {
        return data_get($this->filters, $name, null);
    }

    /**
     * Check if a filter exists
     *
     * @param  string    $name
     * @return boolean
     */
    public function hasFilter($name)
    {
        return $this->getFilter($name) !== null ? true:false;
    }

    /**
     * Set route config
     *
     * @param  array    $routeConfig
     * @return $this
     */
    public function setRouteConfig($routeConfig)
    {
        $this->routeConfig = $routeConfig;
        return $this;
    }

    /**
     * Get the source manager
     *
     * @return \Folklore\Image\SourceManager
     */
    public function getSourceManager()
    {
        return $this->container->make('image.source');
    }

    /**
     * Get the source manager
     *
     * @return \Folklore\Image\Contracts\FiltersManager
     */
    public function getFiltersManager()
    {
        return $this->container->make(FiltersManagerContract::class);
    }

    /**
     * Get the url generator
     *
     * @return \Folklore\Image\Contracts\UrlGenerator
     */
    public function getUrlGenerator()
    {
        return $this->container->make(UrlGeneratorContract::class);
    }

    /**
     * Get the imagine manager
     *
     * @return \Folklore\Image\getImagineManager
     */
    public function getImagineManager()
    {
        return $this->container->make('image.imagine');
    }

    /**
     * Get the imagine instance from the manager
     *
     * @return \Imagine\Image\ImagineInterface
     */
    public function getImagine()
    {
        $manager = $this->getImagineManager();
        return $manager->driver();
    }

    /**
     * Dynamically call the default source handler
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->source(), $method], $parameters);
    }
}

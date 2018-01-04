<?php namespace Folklore\Image;

use Closure;
use Illuminate\Foundation\Application;
use Folklore\Image\Contracts\ImageManipulator as ImageManipulatorContract;

class Image
{
    protected $app;

    protected $urlGenerator;

    /**
     * All manipulators
     *
     * @var array
     */
    protected $manipulators = [];

    /**
     * All registered filters.
     *
     * @var array
     */
    protected $filters = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get an ImageManipulator for a specific source
     *
     * @param  string|null  $name
     * @return Folklore\Image\ImageManipulator
     */
    public function source($name = null)
    {
        $key = $name ? $name:'default';

        if (isset($this->manipulators[$key])) {
            return $this->manipulators[$key];
        }

        $sourceManager = $this->getSourceManager();
        $source = $sourceManager->driver($name);
        $manipulator =  $this->app->make(ImageManipulatorContract::class);
        $manipulator->setSource($source);

        return $this->manipulators[$key] = $manipulator;
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
     * Return an URL to process the image
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
        return $urlGenerator->make($src, $width, $height, $options);
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
     * Map image routes on the Laravel Router
     *
     * @param  array|string  $config Config for the routes group, you can also pass
     * a string to require a specific file in the route group
     * @return array
     */
    public function routes($config = [])
    {
        $routeConfig = $this->app['config']->get('image.routes', []);
        $config = array_merge([], $routeConfig, is_string($config) ? [
            'map' => $config
        ] : $config);
        $groupConfig = array_only($config, ['domain', 'prefix', 'as', 'namespace', 'middleware']);
        $map = array_get($config, 'map', null);

        // Map routes defined in the routes files
        $this->app['router']->group($groupConfig, function ($router) use ($map) {
            if (!is_null($map) && is_file($map)) {
                require $map;
            } else {
                require __DIR__ . '/../../routes/images.php';
            }
        });
    }

    /**
     * Register a filter
     *
     * @param  string    $name
     * @param  \Closure|array|string|object  $filter
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
        return array_get($this->filters, $name, null);
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
     * Get the imagine manager
     *
     * @return \Folklore\Image\ImageManager
     */
    public function getImagineManager()
    {
        return $this->app['image.imagine'];
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
     * Get the source manager
     *
     * @return \Folklore\Image\SourceManager
     */
    public function getSourceManager()
    {
        return $this->app['image.source'];
    }

    /**
     * Get the url generator
     *
     * @return \Folklore\Image\UrlGenerator
     */
    public function getUrlGenerator()
    {
        return $this->app['image.url'];
    }

    /**
     * Dynamically call the default source manipulator
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

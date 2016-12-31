<?php namespace Folklore\Image;

use Folklore\Image\Exception\Exception;
use Folklore\Image\Exception\FileMissingException;
use Folklore\Image\Exception\ParseException;
use Folklore\Image\Exception\FormatException;

use Illuminate\Support\Manager;

use Imagine\Image\ImageInterface;
use Imagine\Image\Box;
use Imagine\Image\Point;

class ImageManager extends Manager
{

    /**
     * Default options
     *
     * @var array
     */
    protected $defaultOptions = array(
        'width' => null,
        'height' => null,
        'quality' => 80,
        'filters' => array()
    );
    
    /**
     * All sources
     *
     * @var array
     */
    protected $sources = [];

    /**
     * All registered filters.
     *
     * @var array
     */
    protected $filters = array();
    
    public function source($name)
    {
        if (isset($this->sources[$name])) {
            return $this->sources[$name];
        }
        
        $source = $this->app['image.manager.source']->get($name);
        return $this->sources[$name] = new Image($this, $source);
    }

    /**
     * Register a custom filter.
     *
     * @param  string            $name The name of the filter
     * @param  Closure|string    $filter
     * @return void
     */
    public function filter($name, $filter)
    {
        $this->filters[$name] = $filter;
    }

    /**
     * Check if a filter exists
     *
     * @param  string            $name The name of the filter
     * @return boolean
     */
    public function hasFilter($name)
    {
        return isset($this->filters[$name]);
    }

    /**
     * Serve an image from an url
     *
     * @param  string    $path
     * @param  array    $config
     * @return Illuminate\Support\Facades\Response
     */
    public function serve($path, $config = array())
    {
        //Use user supplied quality or the config value
        $quality = array_get($config, 'quality', $this->app['config']['image.quality']);
        //if nothing works fallback to the hardcoded value
        $quality = $quality ?: $this->defaultOptions['quality'];

        //Merge config with defaults
        $config = array_merge(array(
            'quality' => $quality,
            'custom_filters_only' => $this->app['config']['image.serve_custom_filters_only'],
            'write_image' => $this->app['config']['image.write_image'],
            'write_path' => $this->app['config']['image.write_path']
        ), $config);

        $serve = new ImageServe($this, $config);
        
        return $serve->response($path);
    }
    
    /**
     * Proxy an image
     *
     * @param  string    $path
     * @param  array    $config
     * @return Illuminate\Support\Facades\Response
     */
    public function proxy($path, $config = array())
    {
        //Merge config with defaults
        $config = array_merge(array(
            'tmp_path' => $this->app['config']['image.proxy_tmp_path'],
            'filesystem' => $this->app['config']['image.proxy_filesystem'],
            'cache' => $this->app['config']['image.proxy_cache'],
            'cache_expiration' => $this->app['config']['image.proxy_cache_expiration'],
            'write_image' => $this->app['config']['image.proxy_write_image'],
            'cache_filesystem' => $this->app['config']['image.proxy_cache_filesystem']
        ), $config);
        
        $serve = new ImageProxy($this, $config);
        return $serve->response($path);
    }

    /**
     * Delete a file and all manipulated files
     *
     * @param  string    $path The path to an image
     * @return void
     */
    public function delete($path)
    {
        $files = $this->getFiles($path);

        foreach ($files as $file) {
            if (!unlink($file)) {
                throw new Exception('Unlink failed: '.$file);
            }
        }
    }

    /**
     * Delete all manipulated files
     *
     * @param  string    $path The path to an image
     * @return void
     */
    public function deleteManipulated($path)
    {
        $files = $this->getFiles($path, false);

        foreach ($files as $file) {
            if (!unlink($file)) {
                throw new Exception('Unlink failed: '.$file);
            }
        }
    }

    /**
     * Get real path
     *
     * @param  string    $path Path to an original image
     * @return string
     */
    public function getRealPath($path)
    {
        if (is_file(realpath($path))) {
            return realpath($path);
        }
        
        //Get directories
        $dirs = $this->app['config']['image.src_dirs'];
        if ($this->app['config']['image.write_path']) {
            $dirs[] = $this->app['config']['image.write_path'];
        }

        // Loop through all the directories files may be uploaded to
        foreach ($dirs as $dir) {
            $dir = rtrim($dir, '/');

            // Check that directory exists
            if (!is_dir($dir)) {
                continue;
            }

            // Look for the image in the directory
            $src = realpath($dir.'/'.ltrim($path, '/'));
            if (is_file($src)) {
                return $src;
            }
        }

        // None found
        return false;
    }

    /**
     * Get all files (including manipulated images)
     *
     * @param  string    $path Path to an original image
     * @return array
     */
    protected function getFiles($path, $withOriginal = true)
    {
        $images = array();

        //Check path
        $path = urldecode($path);
        if (!($path = $this->getRealPath($path))) {
            return $images;
        }

        // Add the source image to the list
        if ($withOriginal) {
            $images[] = $path;
        }

        // Loop through the contents of the source and write directory and get
        // all files that match the pattern
        $parts = pathinfo($path);
        $dirs = [$parts['dirname']];
        $dirs = [$parts['dirname']];
        if ($this->app['config']['image.write_path']) {
            $dirs[] = $this->app['config']['image.write_path'];
        }
        foreach ($dirs as $directory) {
            $files = scandir($directory);
            foreach ($files as $file) {
                if (strpos($file, $parts['filename']) === false || !preg_match('#'.$this->pattern().'#', $file)) {
                    continue;
                }
                $images[] = $directory.'/'.$file;
            }
        }
        
        // Return the list
        return $images;
    }

    /**
     * Get mime type from image format
     *
     * @return string
     */
    public function getMimeFromFormat($format)
    {
        switch ($format) {
            case 'gif':
                return 'image/gif';
            break;
            case 'jpg':
            case 'jpeg':
                return 'image/jpeg';
            break;
            case 'png':
                return 'image/png';
            break;
        }

        return null;
    }

    /**
     * Create an instance of the Imagine Gd driver.
     *
     * @return \Imagine\Gd\Imagine
     */
    protected function createGdDriver()
    {
        return new \Imagine\Gd\Imagine();
    }

    /**
     * Create an instance of the Imagine Imagick driver.
     *
     * @return \Imagine\Imagick\Imagine
     */
    protected function createImagickDriver()
    {
        return new \Imagine\Imagick\Imagine();
    }

    /**
     * Create an instance of the Imagine Gmagick driver.
     *
     * @return \Imagine\Gmagick\Imagine
     */
    protected function createGmagickDriver()
    {
        return new \Imagine\Gmagick\Imagine();
    }

    /**
     * Get the default image driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['image.driver'];
    }

    /**
     * Set the default image driver name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultDriver($name)
    {
        $this->app['config']['image.driver'] = $name;
    }

    /**
     * Dynamically call the default driver instance.
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

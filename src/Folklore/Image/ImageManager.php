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
     * All of the custom filters.
     *
     * @var array
     */
    protected $filters = array();

    /**
     * Return an URL to process the image
     *
     * @param  string  $src
     * @param  int     $width
     * @param  int     $height
     * @param  array   $options
     * @return string
     */
    public function url($src, $width = null, $height = null, $options = array())
    {

        // Don't allow empty strings
        if (empty($src)) {
            return;
        }

        // Extract the path from a URL if a URL was provided instead of a path
        $src = parse_url($src, PHP_URL_PATH);

        //If width parameter is an array, use it as options
        if (is_array($width)) {
            $options = $width;
            $width = null;
            $height = null;
        }
        
        $config = $this->app['config'];
        $url_parameter = isset($options['url_parameter']) ? $options['url_parameter']:$config['image.url_parameter'];
        $url_parameter_separator = isset($options['url_parameter_separator']) ? $options['url_parameter_separator']:$config['image.url_parameter_separator'];
        unset($options['url_parameter'],$options['url_parameter_separator']);

        //Get size
        if (isset($options['width'])) {
            $width = $options['width'];
        }
        if (isset($options['height'])) {
            $height = $options['height'];
        }
        if (empty($width)) {
            $width = '_';
        }
        if (empty($height)) {
            $height = '_';
        }

        // Produce the parameter parts
        $params = array();

        //Add size only if present
        if ($width != '_' || $height != '_') {
            $params[] = $width.'x'.$height;
        }

        // Build options. If the key as no value or is equal to
        // true, only the key is added.
        if ($options && is_array($options)) {
            foreach ($options as $key => $val) {
                if (is_numeric($key)) {
                    $params[] = $val;
                } elseif ($val === true || $val === null) {
                    $params[] = $key;
                } elseif (is_array($val)) {
                    $params[] = $key.'('.implode(',', $val).')';
                } else {
                    $params[] = $key.'('.$val.')';
                }
            }
        }

        //Create the url parameter
        $params = implode($url_parameter_separator, $params);
        $parameter = str_replace('{options}', $params, $url_parameter);

        // Break the path apart and put back together again
        $parts = pathinfo($src);
        $host = isset($options['host']) ? $options['host']:$this->app['config']['image.host'];
        $dir = trim($parts['dirname'], '/');

        $path = array();
        $path[] = rtrim($host, '/');

        if ($prefix = $this->app['config']->get('image.write_path')) {
            $path[] = trim($prefix, '/');
        }

        if (!empty($dir)) {
            $path[] = $dir;
        }

        $filename = array();
        $filename[] = $parts['filename'].$parameter;
        if (!empty($parts['extension'])) {
            $filename[] = $parts['extension'];
        }
        $path[] = implode('.', $filename);

        return implode('/', $path);

    }

    /**
     * Make an image and apply options
     *
     * @param  string    $path The path of the image
     * @param  array    $options The manipulations to apply on the image
     * @return ImageInterface
     */
    public function make($path, $options = array())
    {
        //Get app config
        $config = $this->app['config'];

        // See if the referenced file exists and is an image
        if (!($path = $this->getRealPath($path))) {
            throw new FileMissingException('Image file missing');
        }

        // Get image format
        $format = $this->format($path);
        if (!$format) {
            throw new FormatException('Image format is not supported');
        }

        // Check if all filters exists
        if (isset($options['filters']) && sizeof($options['filters'])) {
            foreach ($options['filters'] as $filter) {
                $filter = (array)$filter;
                $key = $filter[0];
                if (!$this->filters[$key]) {
                    throw new Exception('Custom filter "'.$key.'" doesn\'t exists.');
                }
            }
        }

        // Increase memory limit, cause some images require a lot to resize
        if ($config->get('image.memory_limit')) {
            ini_set('memory_limit', $config->get('image.memory_limit'));
        }

        //Open the image
        $image = $this->open($path);

        //Merge options with the default
        $options = array_merge($this->defaultOptions, $options);

        // Apply the custom filter on the image. Replace the
        // current image with the return value.
        if (isset($options['filters']) && sizeof($options['filters'])) {
            foreach ($options['filters'] as $filter) {
                $arguments = (array)$filter;
                array_unshift($arguments, $image);

                $image = call_user_func_array(array($this,'applyCustomFilter'), $arguments);
            }
        }

        // Resize only if one or both width and height values are set.
        if ($options['width'] !== null || $options['height'] !== null) {
            $crop = isset($options['crop']) ? $options['crop']:false;

            $image = $this->thumbnail($image, $options['width'], $options['height'], $crop);
        }

        // Apply built-in filters by checking fi a method $this->filterName
        // exists. Also if the value of the option is false, the filter
        // is ignored.
        foreach ($options as $key => $arguments) {
            $method = 'filter'.ucfirst($key);

            if ($arguments !== false && method_exists($this, $method)) {
                $arguments = (array)$arguments;
                array_unshift($arguments, $image);

                $image = call_user_func_array(array($this, $method), $arguments);
            }
        }



        return $image;
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
     * Create a thumbnail from an image
     *
     * @param  ImageInterface|string    $image An image instance or the path to an image
     * @param  int                        $width
     * @return ImageInterface
     */
    public function thumbnail($image, $width = null, $height = null, $crop = true)
    {
        //If $image is a path, open it
        if (is_string($image)) {
            $image = $this->open($image);
        }

        //Get new size
        $imageSize = $image->getSize();
        $newWidth = $width === null ? $imageSize->getWidth():$width;
        $newHeight = $height === null ? $imageSize->getHeight():$height;
        $size = new Box($newWidth, $newHeight);
        
        $ratios = array(
            $size->getWidth() / $imageSize->getWidth(),
            $size->getHeight() / $imageSize->getHeight()
        );

        $thumbnail = $image->copy();

        $thumbnail->usePalette($image->palette());
        $thumbnail->strip();

        if (!$crop) {
            $ratio = min($ratios);
        } else {
            $ratio = max($ratios);
        }

        if ($crop) {
            
            $imageSize = $thumbnail->getSize()->scale($ratio);
            $thumbnail->resize($imageSize);
            
            $x = max(0, round(($imageSize->getWidth() - $size->getWidth()) / 2));
            $y = max(0, round(($imageSize->getHeight() - $size->getHeight()) / 2));
            
            $cropPositions = $this->getCropPositions($crop);
            
            if ($cropPositions[0] === 'top') {
                $y = 0;
            } elseif ($cropPositions[0] === 'bottom') {
                $y = $imageSize->getHeight() - $size->getHeight();
            }
            
            if ($cropPositions[1] === 'left') {
                $x = 0;
            } elseif ($cropPositions[1] === 'right') {
                $x = $imageSize->getWidth() - $size->getWidth();
            }
            
            $point = new Point($x, $y);
            
            $thumbnail->crop($point, $size);
        } else {
            if (!$imageSize->contains($size)) {
                $imageSize = $imageSize->scale($ratio);
                $thumbnail->resize($imageSize);
            } else {
                $imageSize = $thumbnail->getSize()->scale($ratio);
                $thumbnail->resize($imageSize);
            }
        }

        //Create the thumbnail
        return $thumbnail;
    }

    /**
     * Get the format of an image
     *
     * @param  string    $path The path to an image
     * @return ImageInterface
     */
    public function format($path)
    {

        $format = @exif_imagetype($path);
        switch ($format) {
            case IMAGETYPE_GIF:
                return 'gif';
            break;
            case IMAGETYPE_JPEG:
                return 'jpeg';
            break;
            case IMAGETYPE_PNG:
                return 'png';
            break;
        }

        return null;
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
     * Get the URL pattern
     *
     * @return string
     */
    public function pattern($parameter = null, $pattern = null)
    {
        //Replace the {options} with the options regular expression
        $config = $this->app['config'];
        $parameter = !isset($parameter) ? $config['image.url_parameter']:$parameter;
        $parameter = preg_replace('/\\\{\s*options\s*\\\}/', '([0-9a-zA-Z\(\),\-/._]+?)?', preg_quote($parameter));
        
        if(!$pattern)
        {
            $pattern = $config->get('image.pattern', '^(.*){parameters}\.(jpg|jpeg|png|gif|JPG|JPEG|PNG|GIF)$');
        }
        $pattern = preg_replace('/\{\s*parameters\s*\}/', $parameter, $pattern);

        return $pattern;
    }

    /**
     * Parse the path for the original path of the image and options
     *
     * @param  string    $path A path to parse
     * @param  array    $config Configuration options for the parsing
     * @return array
     */
    public function parse($path, $config = array())
    {
        //Default config
        $config = array_merge(array(
            'custom_filters_only' => false,
            'url_parameter' => null,
            'url_parameter_separator' => $this->app['config']['image.url_parameter_separator']
        ), $config);

        $parsedOptions = array();
        if (preg_match('#'.$this->pattern($config['url_parameter']).'#i', $path, $matches)) {
            //Get path and options
            $path = $matches[1].'.'.$matches[3];
            $pathOptions = $matches[2];

            // Parse options from path
            $parsedOptions = $this->parseOptions($pathOptions, $config);
        }

        return array(
            'path' => $path,
            'options' => $parsedOptions
        );
    }

    /**
     * Parse options from url string
     *
     * @param  string    $option_path The path contaning all the options
     * @param  array    $config Configuration options for the parsing
     * @return array
     */
    protected function parseOptions($option_path, $config = array())
    {

        //Default config
        $config = array_merge(array(
            'custom_filters_only' => false,
            'url_parameter_separator' => $this->app['config']['image.url_parameter_separator']
        ), $config);

        $options = array();

        // These will look like (depends on the url_parameter_separator): "-colorize(CC0000)-greyscale"
        $option_path_parts = explode($config['url_parameter_separator'], $option_path);

        // Loop through the params and make the options key value pairs
        foreach ($option_path_parts as $option) {
            //Check if the option is a size or is properly formatted
            if (!$config['custom_filters_only'] && preg_match('#([0-9]+|_)x([0-9]+|_)#i', $option, $matches)) {
                $options['width'] = $matches[1] === '_' ? null:(int)$matches[1];
                $options['height'] = $matches[2] === '_' ? null:(int)$matches[2];
                continue;
            } elseif (!preg_match('#(\w+)(?:\(([\w,.]+)\))?#i', $option, $matches)) {
                continue;
            }

            //Check if the key is valid
            $key = $matches[1];
            if (!$this->isValidOption($key)) {
                throw new ParseException('The option key "'.$key.'" does not exists.');
            }

            // If the option is a custom filter, check if it's a closure or an array.
            // If it's an array, merge it with options
            if (isset($this->filters[$key])) {
                if (is_object($this->filters[$key]) && is_callable($this->filters[$key])) {
                    $arguments = isset($matches[2]) ? explode(',', $matches[2]):array();
                    array_unshift($arguments, $key);
                    $options['filters'][] = $arguments;
                } elseif (is_array($this->filters[$key])) {
                    $options = array_merge($options, $this->filters[$key]);
                }
            } elseif (!$config['custom_filters_only']) {
                if (isset($matches[2])) {
                    $options[$key] = strpos($matches[2], ',') === true ? explode(',', $matches[2]):$matches[2];
                } else {
                    $options[$key] = true;
                }
            } else {
                throw new ParseException('The option key "'.$key.'" does not exists.');
            }
        }

        // Merge the options with defaults
        return $options;
    }

    /**
     * Check if an option key is valid by checking if a
     * $this->filterName() method is present or if a custom filter
     * is registered.
     *
     * @param  string  $key Option key to check
     * @return boolean
     */
    protected function isValidOption($key)
    {
        if (in_array($key, array('crop','width','height'))) {
            return true;
        }

        $method = 'filter'.ucfirst($key);
        if (method_exists($this, $method) || isset($this->filters[$key])) {
            return true;
        }
        return false;
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
     * Apply a custom filter or an image
     *
     * @param  ImageInterface    $image An image instance
     * @param  string            $name The filter name
     * @return ImageInterface|array
     */
    protected function applyCustomFilter(ImageInterface $image, $name)
    {
        //Get all arguments following $name and add $image as the first
        //arguments then call the filter closure
        $arguments = array_slice(func_get_args(), 2);
        array_unshift($arguments, $image);
        $return = call_user_func_array($this->filters[$name], $arguments);

        // If the return value is an instance of ImageInterface,
        // replace the current image with it.
        if ($return instanceof ImageInterface) {
            $image = $return;
        }

        return $image;
    }

    /**
     * Apply rotate filter
     *
     * @param  ImageInterface    $image An image instance
     * @param  float            $degree The rotation degree
     * @return void
     */
    protected function filterRotate(ImageInterface $image, $degree)
    {
        return $image->rotate($degree);
    }

    /**
     * Apply grayscale filter
     *
     * @param  ImageInterface    $image An image instance
     * @return void
     */
    protected function filterGrayscale(ImageInterface $image)
    {
        $image->effects()->grayscale();
        return $image;
    }

    /**
     * Apply negative filter
     *
     * @param  ImageInterface    $image An image instance
     * @return void
     */
    protected function filterNegative(ImageInterface $image)
    {
        $image->effects()->negative();
        return $image;
    }

    /**
     * Apply gamma filter
     *
     * @param  ImageInterface    $image An image instance
     * @param  float            $gamma The gamma value
     * @return void
     */
    protected function filterGamma(ImageInterface $image, $gamma)
    {
        $image->effects()->gamma($gamma);
        return $image;
    }

    /**
     * Apply blur filter
     *
     * @param  ImageInterface    $image An image instance
     * @param  int            $blur The amount of blur
     * @return void
     */
    protected function filterBlur(ImageInterface $image, $blur)
    {
        $image->effects()->blur($blur);
        return $image;
    }

    /**
     * Apply colorize filter
     *
     * @param  ImageInterface    $image An image instance
     * @param  string            $color The hex value of the color
     * @return void
     */
    protected function filterColorize(ImageInterface $image, $color)
    {
        $palettes = ['RGB','CMYK'];
        $parts = explode(',', $color);
        $color = $parts[0];
        if(isset($parts[1]) && in_array(strtoupper($parts[1]), $palettes))
        {
            $className = '\\Imagine\\Image\\Palette\\'.strtoupper($parts[1]);
            $palette = new $className();
        }
        else
        {
            $palette = $image->palette();
        }
        $color = $palette->color($color);
        $image->effects()->colorize($color);
        return $image;
    }

    /**
     * Apply  interlace filter
     *
     * @param  ImageInterface    $image An image instance
     * @return void
     */
    protected function filterInterlace(ImageInterface $image)
    {
        $image->interlace(ImageInterface::INTERLACE_LINE);
        return $image;
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
     * Return crop positions from the crop parameter
     *
     * @return array
     */
    protected function getCropPositions($crop)
    {
        $crop = $crop === true ? 'center':$crop;
        
        $cropPositions = explode('_', $crop);
        if (sizeof($cropPositions) === 1) {
            if ($cropPositions[0] === 'top' || $cropPositions[0] === 'bottom' || $cropPositions[0] === 'center') {
                $cropPositions[] = 'center';
            } elseif ($cropPositions[0] === 'left' || $cropPositions[0] === 'right') {
                array_unshift($cropPositions, 'center');
            }
        }
        
        return $cropPositions;
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
}

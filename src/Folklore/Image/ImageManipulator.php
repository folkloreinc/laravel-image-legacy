<?php namespace Folklore\Image;

use Illuminate\Foundation\Application;
use Folklore\Image\Contracts\ImageManipulator as ImageManipulatorContract;
use Folklore\Image\Contracts\Source as SourceContract;
use Folklore\Image\Contracts\FilterWithValue as FilterWithValueContract;
use Folklore\Image\Exception\FileMissingException;
use Folklore\Image\Exception\FormatException;
use Folklore\Image\Filters\Thumbnail;
use Folklore\Image\Image;
use Folklore\Image\UrlGenerator;
use Imagine\Image\ImageInterface;
use Imagine\Image\Box;
use Imagine\Image\Point;

class ImageManipulator implements ImageManipulatorContract
{
    protected $manager;

    protected $urlGenerator;

    protected $source;

    protected $memoryLimit = 256;

    public function __construct(Image $manager, UrlGenerator $urlGenerator)
    {
        $this->manager = $manager;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Make an image and apply options
     *
     * @param  string    $path The path of the image
     * @param  array    $options The manipulations to apply on the image
     * @return ImageInterface
     */
    public function make($path, $options = [])
    {
        $configKeys = ['memory_limit'];
        $sizeKeys = ['width', 'height', 'crop'];

        //Get config
        $configOptions = array_only($options, $configKeys);
        $config = array_merge([
            'memory_limit' => $this->memoryLimit
        ], $configOptions);

        // See if the referenced file exists and is an image
        if (!$this->source->pathExists($path)) {
            throw new FileMissingException('Image ['.$path.'] not found.');
        }

        // Check image format
        $format = $this->source->getFormatFromPath($path);
        if (!$format) {
            throw new FormatException('Image format is not supported');
        }

        // Check if all filters exists
        $filters = array_except($options, array_merge($configKeys, $sizeKeys));
        foreach ($filters as $key => $value) {
            if (!$this->manager->hasFilter($key)) {
                throw new \Exception('Filter "'.$key.'" doesn\'t exists.');
            }
        }

        // Increase memory limit, because some images require a lot
        if (isset($config['memory_limit'])) {
            ini_set('memory_limit', $config['memory_limit']);
        }

        //Open the image
        $image = $this->source->openFromPath($path);

        // Resize only if one or both width and height values are set.
        $width = array_get($options, 'width', null);
        $height = array_get($options, 'height', null);
        if ($width !== null || $height !== null) {
            $crop = array_get($options, 'crop', false);
            $image = $this->thumbnail($image, $width, $height, $crop);
        }

        // Apply the custom filter on the image and replace the
        // current image with the return value.
        if (sizeof($filters)) {
            foreach ($filters as $key => $arguments) {
                $arguments = array_merge([$image, $key], $arguments);
                $image = call_user_func_array(array($this,'applyFilter'), $arguments);
            }
        }

        return $image;
    }

    /**
     * Serve an image from a path
     *
     * @param  string  $path
     * @param  array   $config
     * @return \Illuminate\Http\Response
     */
    public function serve($path, $config = [])
    {
        $parseData = $this->urlGenerator->parse($path, $config);
        $parsePath = $parseData['path'];
        $parseFilters = $parseData['filters'];
        $routeFilters = array_get($config, 'filters');
        $filters = array_merge($parseFilters, $routeFilters);

        //Check if file exists
        if (!$this->source->pathExists($parsePath)) {
            throw new FileMissingException('Image file missing');
        }

        //Make the image
        $image = $this->make($parsePath, $filters);

        //Get format
        $format = $this->format($parsePath);

        //Create response
        $response = response()->image($image);

        //Set output format and quality
        $response->setFormat($format);
        $response->setQuality(100);

        //Set expires
        $expires = array_get($config, 'expires');
        if ($expires) {
            $response->setMaxAge($expires);
            $expiresDate = new \DateTime();
            $expiresDate->setTimestamp(time() + $expires);
            $response->setExpires($expiresDate);
        }

        return $response;
    }

    /**
     * Save an image to the source
     *
     * @return string
     */
    public function save(ImageInterface $image, $path)
    {
        return $this->source->saveToPath($image, $path);
    }

    /**
     * Return an URL to process the image
     *
     * @param  string  $path
     * @return array
     */
    public function format($path)
    {
        return $this->source->getFormatFromPath($path);
    }

    /**
     * Return an URL to process the image
     *
     * @param  string  $src
     * @param  int     $width
     * @param  int     $height
     * @param  array   $options
     * @return string
     */
    public function url($src, $width = null, $height = null, $options = [])
    {
        return $this->urlGenerator->make($src, $width, $height, $options);
    }

    /**
     * Return an URL to process the image
     *
     * @param  string  $path
     * @return array
     */
    public function pattern($config = [])
    {
        return $this->urlGenerator->pattern($config);
    }

    /**
     * Return an URL to process the image
     *
     * @param  string  $path
     * @return array
     */
    public function parse($path, $config = [])
    {
        return $this->urlGenerator->parse($path, $config);
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
            $image = $this->source->openFromPath($image);
        }

        //Create the thumbnail
        return with(new Thumbnail())->handle($image, [
            'width' => $width,
            'height' => $height,
            'crop' => $crop
        ]);
    }

    /**
     * Apply a custom filter or an image
     *
     * @param  ImageInterface    $image An image instance
     * @param  string            $name The filter name
     * @return ImageInterface|array
     */
    protected function applyFilter(ImageInterface $image, $name)
    {
        $filters = $this->manager->getFilters();

        // Get all arguments following $name and add $image as the first
        // arguments then call the filter.
        $arguments = array_slice(func_get_args(), 2);
        array_unshift($arguments, $image);
        $filter = $filters[$name];
        if (is_callable($filter)) {
            $return = call_user_func_array($filter, $arguments);
        } else {
            $filter = is_string($filter) ? app($filter):$filter;
            if ($filter instanceof FilterWithValueContract) {
                $return = call_user_func_array([$filter, 'apply'], $arguments);
            } else {
                $return = $filter->apply($image);
            }
        }

        // If the return value is an instance of ImageInterface,
        // replace the current image with it.
        if ($return instanceof ImageInterface) {
            $image = $return;
        }

        return $image;
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
     * Get the image source
     *
     * @return SourceContract
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set the image source
     *
     * @param  SourceContract   $source The source of the factory
     * @return $this
     */
    public function setSource(SourceContract $source)
    {
        $this->source = $source;

        return $this;
    }

    public function getImagineManager()
    {
        return $this->manager->getImagineManager();
    }

    public function getImagine()
    {
        $manager = $this->getImagineManager();
        return $manager->driver();
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
        $manager = $this->getImagineManager();
        return call_user_func_array([$manager, $method], $parameters);
    }
}

<?php namespace Folklore\Image;

use Illuminate\Foundation\Application;
use Folklore\Image\Contracts\ImageManipulator as ImageManipulatorContract;
use Folklore\Image\Contracts\Source as SourceContract;
use Folklore\Image\Contracts\FilterWithValue as FilterWithValueContract;
use Folklore\Image\Exception\FileMissingException;
use Folklore\Image\Exception\FilterMissingException;
use Folklore\Image\Exception\FormatException;
use Folklore\Image\Filters\Thumbnail;
use Folklore\Image\Image;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Box;
use Imagine\Image\Point;

class ImageManipulator implements ImageManipulatorContract
{
    protected $manager;

    protected $source;

    protected $memoryLimit = null;

    public function __construct(Image $manager)
    {
        $this->manager = $manager;
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

        // Merge array filters
        $filters = array_except($options, array_merge($configKeys));
        if (sizeof($filters)) {
            $newFilters = [];
            foreach ($filters as $key => $arguments) {
                $filter = $this->manager->getFilter($key);
                if (is_array($filter)) {
                    $newFilters = array_merge($newFilters, $filter);
                } else {
                    $newFilters[$key] = $arguments;
                }
            }
            $filters = $newFilters;
        }

        // Resize only if one or both width and height values are set.
        $width = array_get($filters, 'width', null);
        $height = array_get($filters, 'height', null);
        if ($width !== null || $height !== null) {
            $crop = array_get($filters, 'crop', false);
            $thumbnail = [
                'width' => $width,
                'height' => $height,
                'crop' => $crop
            ];
            $filters = array_merge([
                'thumbnail' => $thumbnail
            ], $filters);
        }
        $filters = array_except($filters, array_merge($sizeKeys));

        // Check if all filters exists
        foreach ($filters as $key => $value) {
            if (!$this->manager->hasFilter($key)) {
                throw new FilterMissingException('Filter "'.$key.'" doesn\'t exists.');
            }
        }

        // Increase memory limit, because some images require a lot
        if (isset($config['memory_limit'])) {
            ini_set('memory_limit', $config['memory_limit']);
        }

        //Open the image
        $image = $this->source->openFromPath($path);

        // Apply the custom filter on the image and replace the
        // current image with the return value.
        if (sizeof($filters)) {
            foreach ($filters as $key => $arguments) {
                $arguments = array_merge([$image, $key], [$arguments]);
                $image = call_user_func_array(array($this,'applyFilter'), $arguments);
            }
        }

        return $image;
    }

    /**
     * Open an image from the source
     *
     * @param  string    $path The path of the image
     * @return ImageInterface
     */
    public function open($path)
    {
        return $this->source->openFromPath($path);
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
        return with(new Thumbnail())->apply($image, [
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

    /**
     * Get the memory limit
     *
     * @return string
     */
    public function getMemoryLimit()
    {
        return $this->memoryLimit;
    }

    /**
     * Set the memory limit
     *
     * @param  string   $limit The memory limit
     * @return $this
     */
    public function setMemoryLimit($limit)
    {
        $this->memoryLimit = $limit;

        return $this;
    }

    /**
     * Get the imagine manager
     *
     * @return ImagineManager
     */
    public function getImagineManager()
    {
        return $this->manager->getImagineManager();
    }

    /**
     * Get the imagine driver
     *
     * @return ImagineInterface
     */
    public function getImagine()
    {
        $imagine = $this->getImagineManager();
        return $imagine->driver();
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
        if (method_exists($this->manager, $method)) {
            return call_user_func_array([$this->manager, $method], $parameters);
        }

        $imagine = $this->getImagineManager();
        return call_user_func_array([$imagine, $method], $parameters);
    }
}

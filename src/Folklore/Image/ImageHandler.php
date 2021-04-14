<?php namespace Folklore\Image;

use Illuminate\Foundation\Application;
use Illuminate\Support\Arr;
use Folklore\Image\Contracts\FiltersManager as FiltersManagerContract;
use Folklore\Image\Contracts\ImageHandler as ImageHandlerContract;
use Folklore\Image\Contracts\Source as SourceContract;
use Folklore\Image\Contracts\FilterWithValue as FilterWithValueContract;
use Folklore\Image\Exception\FileMissingException;
use Folklore\Image\Exception\FilterMissingException;
use Folklore\Image\Exception\FormatException;
use Folklore\Image\Filters\Resize;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Box;
use Imagine\Image\Point;

class ImageHandler implements ImageHandlerContract
{
    protected $filters;

    protected $source;

    protected $memoryLimit = null;

    public function __construct(FiltersManagerContract $filters, $memoryLimit = '128MB')
    {
        $this->filters = $filters;
        $this->memoryLimit = $memoryLimit;
    }

    /**
     * Make an image and apply options
     *
     * Examples:
     *
     * Create an Image object with the image resized (and cropped) to 300x300
     * and rotated 180 degrees.
     * ```php
     * $handler = Image::source();
     * $image = $handler->make('path/to/image.jpg', [
     *     'width' => 300,
     *     'height' => 300,
     *     'crop' => true,
     *     'rotate' => 180
     * ]);
     * ```
     *
     * @param  string    $path The path of the image
     * @param  array    $options The manipulations to apply on the image
     * @return ImageInterface
     */
    public function make($path, $options = [])
    {
        $configKeys = ['memory_limit'];

        //Get config
        $configOptions = Arr::only($options, $configKeys);
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
        $filtersOptions = Arr::except($options, array_merge($configKeys));
        $filters = $this->getFiltersFromOptions($filtersOptions);

        // Check if all filters exists
        foreach ($filters as $key => $value) {
            if (!$this->filters->hasFilter($key)) {
                throw new FilterMissingException('Filter "'.$key.'" doesn\'t exists.');
            }
        }

        // Increase memory limit, because some images require a lot
        if (isset($config['memory_limit']) && !empty($config['memory_limit'])) {
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
     * Examples:
     *
     * Open the image path and return an Image object
     * ```php
     * $handler = Image::source();
     * $image = $handler->open('path/to/image.jpg');
     * ```
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
     * Examples:
     *
     * Create a resized image and save it to a new path
     * ```php
     * $handler = Image::source();
     * $image = $handler->make('path/to/image.jpg', [
     *     'width' => 300,
     *     'height' => 300,
     *     'crop' => true
     * ]);
     * $handler->save($image, 'path/to/image-resized.jpg');
     * ```

     * Or save it to a different source:
     * ```php
     * $handler = Image::source();
     * $image = $handler->make('path/to/image.jpg', [
     *     'width' => 300,
     *     'height' => 300,
     *     'crop' => true
     * ]);
     * Image::source('cloud')->save($image, 'path/to/image-resized.jpg');
     * ```
     *
     * @param  ImageInterface $image The image to save
     * @param  string $path The path where you want to save the image
     * @return string
     */
    public function save(ImageInterface $image, $path)
    {
        return $this->source->saveToPath($image, $path);
    }

    /**
     * Return an URL to process the image
     *
     * Examples:
     *
     * Open the image path and return an Image object
     * ```php
     * $handler = Image::source();
     * $format = $handler->format('path/to/image.jpg');
     * // $format = 'jpg';
     *
     * @param  string $path The path of the image
     * @return string The format fo the image
     */
    public function format($path)
    {
        return $this->source->getFormatFromPath($path);
    }

    /**
     * Create a thumbnail from an image
     *
     * @param  ImageInterface|string $image An image instance or the path to an image
     * @param  int $width The maximum width of the thumbnail
     * @param  int $height The maximum height of the thumbnail
     * @param  boolean|string $crop If this is set to `true`, it match the exact
     * size provided. You can also set a position for the cropped image (ex: 'top left')
     * @return ImageInterface
     */
    public function thumbnail($image, $width = null, $height = null, $crop = true)
    {
        //If $image is a path, open it
        if (is_string($image)) {
            $image = $this->source->openFromPath($image);
        }

        //Create the thumbnail
        return with(new Resize())->apply($image, [
            'width' => $width,
            'height' => $height,
            'crop' => $crop
        ]);
    }

    /**
     * Get filters from options
     *
     * @param  array $options Options
     * @return array $filters
     */
    protected function getFiltersFromOptions($options)
    {
        // Get filters and merge if it's an array
        $newFilters = [];
        foreach ($options as $key => $arguments) {
            $filter = $this->filters->getFilter($key);
            if (is_array($filter)) {
                $newFilters = array_merge($newFilters, $filter);
            } else {
                $newFilters[$key] = $arguments;
            }
        }
        $filters = $newFilters;

        // Convert width, height, crop options to resize filter
        $sizeKeys = ['width', 'height', 'crop'];
        $width = data_get($filters, 'width', null);
        $height = data_get($filters, 'height', null);
        if ($width !== null || $height !== null) {
            $crop = data_get($filters, 'crop', false);
            $filters['resize'] = [
                'width' => $width,
                'height' => $height,
                'crop' => $crop
            ];
        }
        $filters = Arr::except($filters, array_merge($sizeKeys));

        return $filters;
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
        $filters = $this->filters->getFilters();

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
     * @return SourceContract The source that is used
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set the image source
     *
     * Open the image path and return an Image object
     * ```php
     * use Folklore\Image\Contracts\ImageHandler;
     * $handler = app(ImageHandler::class);

     * $source = Image::getSourceManager()->driver('local');
     * $handler->setSource($source);
     * ```
     *
     * @param SourceContract $source The source of the factory
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
}

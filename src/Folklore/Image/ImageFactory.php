<?php namespace Folklore\Image;

use Illuminate\Foundation\Application;
use Folklore\Image\Contracts\ImageFactory as ImageFactoryContract;
use Folklore\Image\Contracts\Source as SourceContract;
use Folklore\Image\Contracts\UrlGenerator as UrlGeneratorContract;
use Folklore\Image\Contracts\FilterWithValue as FilterWithValueContract;
use Folklore\Image\Exception\FileMissingException;
use Folklore\Image\Exception\FormatException;
use Imagine\Image\ImageInterface;
use Imagine\Image\Box;
use Imagine\Image\Point;

class ImageFactory implements ImageFactoryContract
{
    protected $image;
    
    protected $urlGenerator;
    
    protected $config;
    
    public function __construct(Application $app, SourceContract $source, UrlGeneratorContract $urlGenerator)
    {
        $this->app = $app;
        $this->source = $source;
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
            'memory_limit' => $this->app['config']['image.memory_limit']
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
            if (!$this->app['image']->hasFilter($key)) {
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
        
        // Apply the custom filter on the image. Replace the
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
     * Server an image from a path
     *
     * @param  string  $path
     * @param  array   $config
     * @return \Illuminate\Http\Response
     */
    public function serve($path, $config = [])
    {
        $parseData = $this->urlGenerator->parse($path, $config);
        $parsePath = $parseData['path'];
        $parseOptions = $parseData['options'];
        
        //Check if file exists
        if (!$this->source->pathExists($parsePath)) {
            throw new FileMissingException('Image file missing');
        }
        
        //Make image
        $image = $this->make($parsePath, $parseOptions);
        
        //Get format
        $format = $this->format($parsePath);
        
        /*$saveOptions = array();
        $quality = array_get($config, 'quality');
        if ($format === 'jpeg') {
            $saveOptions['jpeg_quality'] = $quality;
        } elseif ($format === 'png') {
            $saveOptions['png_compression_level'] = round($quality / 100 * 9);
        }
        $content = $image->get($format, $saveOptions);*/
        
        
        //$response = response()->make($content, 200);
        $response = response()->image($image)
            ->setFormat($format);
        
        // Set mime
        $mime = $this->getMimeFromFormat($format);
        $response->header('Content-type', $mime);
        
        //Set expires
        $expires = array_get($config, 'expires');
        if ($expires) {
            $response->header('Cache-control', 'max-age='.$expires.', public');
            $response->header('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + $expires));
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
     * Apply a custom filter or an image
     *
     * @param  ImageInterface    $image An image instance
     * @param  string            $name The filter name
     * @return ImageInterface|array
     */
    protected function applyFilter(ImageInterface $image, $name)
    {
        $filters = $this->app['image']->getFilters();
        
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
     * Get mime type from image format
     *
     * @return string
     */
    protected function getMimeFromFormat($format)
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
    
    public function getSource()
    {
        return $this->source;
    }
    
    public function setSource($source)
    {
        $this->source = $source;
        
        return $this;
    }
    
    public function getUrlGenerator()
    {
        return $this->urlGenerator;
    }
    
    public function setUrlGenerator($urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
        
        return $this;
    }
    
    public function getImagineManager()
    {
        return $this->app['image.manager.imagine'];
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

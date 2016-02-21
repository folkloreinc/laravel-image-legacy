<?php namespace Folklore\Image;

use Folklore\Image\Exception\FileMissingException;

class ImageServe
{
    protected $image;
    
    protected $config = [];
    
    public function __construct($image, $config = [])
    {
        $this->image = $image;
        
        $this->config = array_merge([
            'custom_filters_only' => false,
            'write_image' => false,
            'write_path' => null,
            'quality' => 80,
            'options' => []
        ], $config);
    }
    
    public function response($path)
    {
        // Parse the current path
        $parsedPath = $this->image->parse($path, array(
            'custom_filters_only' => $this->config['custom_filters_only']
        ));
        $imagePath = $parsedPath['path'];
        $parsedOptions = $parsedPath['options'];

        // See if the referenced file exists and is an image
        if (!($imagePath = $this->image->getRealPath($imagePath))) {
            throw new FileMissingException('Image file missing');
        }

        // Make sure destination is writeable
        if ($this->config['write_image'] && !is_writable(dirname($path))) {
            throw new Exception('Destination is not writeable');
        }

        // Merge all options with the following priority:
        // Options passed as an argument to the serve method
        // Options parsed from the URL
        // Default options
        $options = array_merge($parsedOptions, $this->config['options']);

        //Make the image
        $image = $this->image->make($imagePath, $options);

        //Write the image
        if ($this->config['write_image']) {
            $destinationFolder = isset($this->config['write_path']) ? $this->config['write_path']:dirname($imagePath);
            $destinationPath = rtrim($destinationFolder, '/').'/'.basename($path);
            $image->save($destinationPath);
        }

        //Get the image format
        $format = $this->image->format($imagePath);

        //Get the image content
        $saveOptions = array();
        $quality = array_get($options, 'quality', $this->config['quality']);
        if ($format === 'jpeg') {
            $saveOptions['jpeg_quality'] = $quality;
        } elseif ($format === 'png') {
            $saveOptions['png_compression_level'] = round($quality/100 * 9);
        }
        $contents = $image->get($format, $saveOptions);

        //Create the response
        $mime = $this->image->getMimeFromFormat($format);
        $response = response()->make($contents, 200);
        $response->header('Content-Type', $mime);
        
        return $response;
    }
}

<?php

namespace Folklore\Image\Http;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\ResponseTrait;
use Imagine\Image\ImageInterface;

class ImageResponse extends StreamedResponse
{
    use ResponseTrait;
    
    protected $image;
    
    protected $format = 'jpg';
    
    protected $quality = 100;
    
    /**
     * Constructor.
     *
     * @param ImageInterface|null   $image      An imagine image object or null to set it later
     * @param int                   $status     The response status code
     * @param array                 $headers    An array of response headers
     */
    public function __construct($image = null, $status = 200, $headers = array())
    {
        // Set the default stream callback to call sendImage method
        $callback = function () {
            $this->sendImage();
        };
        
        parent::__construct($callback, $status, $headers);

        if (null !== $image) {
            $this->setImage($image);
        }
    }

    /**
     * Factory method for chainability.
     *
     * @param ImageInterface|null   $image      An imagine image object or null to set it later
     * @param int                   $status     The response status code
     * @param array                 $headers    An array of response headers
     *
     * @return StreamedResponse
     */
    public static function create($image = null, $status = 200, $headers = array())
    {
        return new static($image, $status, $headers);
    }

    /**
     * Output the image response.
     *
     * @return void
     */
    protected function sendImage()
    {
        if ($this->imagePath) {
            $this->sendImageFromPath();
            return;
        }
        
        echo $this->image->get($this->format, [
            'jpeg_quality' => $this->quality
        ]);
    }
    
    /**
     * Output the image response from path
     *
     * @return void
     */
    protected function sendImageFromPath()
    {
        $output = fopen('php://output', 'w');
        $file = fopen($this->imagePath, 'r');
        while ($buf = fread($file, 8192)) {
            fwrite($output, $buf);
        }
        fclose($file);
        fclose($output);
    }

    /**
     * Set the image of the response.
     *
     * @param  mixed  $content
     * @return $this
     */
    public function setImage(ImageInterface $image)
    {
        $this->image = $image;
        
        return $this;
    }

    /**
     * Get the image of the response.
     *
     * @return ImageInterface
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set the image path
     *
     * @param  mixed  $content
     * @return $this
     */
    public function setImagePath($path)
    {
        $this->imagePath = $path;
        
        return $this;
    }

    /**
     * Get the image path
     *
     * @return string
     */
    public function getImagePath()
    {
        return $this->imagePath;
    }

    /**
     * Set the image output format
     *
     * @param  string  $format
     * @return $this
     */
    public function setFormat($format)
    {
        $this->format = $format;
        
        return $this;
    }

    /**
     * Get the image output format
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Set the image output quality
     *
     * @param  int  $quality
     * @return $this
     */
    public function setQuality($quality)
    {
        $this->quality = $quality;
        
        return $this;
    }

    /**
     * Get the image output quality
     *
     * @return int
     */
    public function getQuality()
    {
        return $this->quality;
    }
}

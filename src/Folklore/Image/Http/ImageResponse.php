<?php

namespace Folklore\Image\Http;

use Folklore\Image\Contracts\ImageDataHandler;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\ResponseTrait;
use Imagine\Image\ImageInterface;

class ImageResponse extends StreamedResponse
{
    use ResponseTrait;

    protected $image;

    protected $imagePath;

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

        echo $this->getContent();
        return;
    }

    /**
     * Output the image response from path
     *
     * @return void
     */
    protected function sendImageFromPath()
    {
        $file = fopen($this->imagePath, 'r');
        while ($buffer = fread($file, 1024*1024)) {
            echo $buffer;
            flush();
        }
        fclose($file);
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

    /**
     * Get the image output format
     *
     * @return string
     */
    public function getContent()
    {
        if ($this->imagePath) {
            return file_get_contents($this->imagePath);
        }

        return app(ImageDataHandler::class)->get($this->image, $this->format, [
            'jpeg_quality' => $this->quality,
            'png_compression_level' => ($this->quality / 100) * 9,
        ]);
    }

    /**
     * Set the image of the response.
     *
     * @param  mixed  $content
     * @return $this
     */
    public function setImage($image)
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
        if (!is_null($path)) {
            $size = filesize($path);
            $this->header('Content-length', $size);
        }

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
     * Alias for setFormat
     *
     * @param  string  $format
     * @return $this
     */
    public function format($format)
    {
        return $this->setFormat($format);
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

        // Set mime
        $mime = $this->getMimeFromFormat($format);
        $this->header('Content-type', $mime);

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
     * Alias for setQuality
     *
     * @param  int  $quality
     * @return $this
     */
    public function quality($quality)
    {
        return $this->setQuality($quality);
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

    /**
     * Alias for setExpiresIn
     *
     * @param  int  $expires
     * @return $this
     */
    public function expiresIn($expires)
    {
        return $this->setExpiresIn($expires);
    }

    /**
     * Set the expires and max-age headers
     *
     * @param  int  $expires
     * @return $this
     */
    public function setExpiresIn($expires)
    {
        if ($expires === null) {
            return $this;
        }
        $expires = (int)$expires;
        $this->setMaxAge($expires);
        $expiresDate = new \DateTime();
        $expiresDate->setTimestamp(time() + $expires);
        $this->setExpires($expiresDate);
        return $this;
    }
}

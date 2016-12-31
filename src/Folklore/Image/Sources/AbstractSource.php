<?php

namespace Folklore\Image\Sources;

use Folklore\Image\ImagineManager;
use Folklore\Image\Contracts\Source;

abstract class AbstractSource implements Source
{
    protected $imagine;
    protected $config;
    
    public function __construct(ImagineManager $imagine, $config)
    {
        $this->imagine = $imagine;
        $this->config = $config;
    }

    /**
     * Get the format of an image
     *
     * @param  string    $path The path to an image
     * @return string|null
     */
    public function getFormatFromPath($path)
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
}

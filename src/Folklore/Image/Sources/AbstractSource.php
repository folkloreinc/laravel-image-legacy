<?php

namespace Folklore\Image\Sources;

use Imagine\Image\ImagineInterface;
use Folklore\Image\Contracts\UrlGenerator;
use Folklore\Image\Contracts\Source;

abstract class AbstractSource implements Source
{
    protected $imagine;
    protected $urlGenerator;
    protected $config;

    public function __construct(ImagineInterface $imagine, UrlGenerator $urlGenerator, $config)
    {
        $this->imagine = $imagine;
        $this->urlGenerator = $urlGenerator;
        $this->config = $config;
    }

    protected function getImagesFromFiles($files, $path = null)
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $isFile = !empty($extension);
        $basename = $isFile ? pathinfo($path, PATHINFO_BASENAME):null;
        $directory = $isFile ? pathinfo($path, PATHINFO_DIRNAME):$path;
        $images = [];
        foreach ($files as $file) {
            if (!preg_match('#'.$this->urlGenerator->pattern().'#', $file)) {
                continue;
            }
            $parsedPath = $this->urlGenerator->parse($file);
            if ($isFile && $basename !== pathinfo($parsedPath['path'], PATHINFO_BASENAME)) {
                continue;
            }
            $images[] = rtrim(ltrim($directory, '.'), '/').'/'.ltrim(ltrim($file, './'), '/');
        }

        return $images;
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

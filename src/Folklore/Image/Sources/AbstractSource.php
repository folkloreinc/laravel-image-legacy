<?php

namespace Folklore\Image\Sources;

use Folklore\Image\ImagineManager;
use Folklore\Image\UrlGenerator;
use Folklore\Image\Contracts\Source;

abstract class AbstractSource implements Source
{
    protected $imagine;
    protected $urlGenerator;
    protected $config;

    public function __construct(ImagineManager $imagine, Urlgenerator $urlGenerator, $config)
    {
        $this->imagine = $imagine;
        $this->urlGenerator = $urlGenerator;
        $this->config = $config;
    }

    protected function getImagesFromFiles($files, $path = null)
    {
        $filesystem = app('files');
        $extension = $filesystem->extension($path);
        $isFile = !empty($extension);
        $basename = $isFile ? $filesystem->basename($path):null;
        $directory = $isFile ? $filesystem->dirname($path):$path;
        $images = [];
        foreach ($files as $file) {
            if (!preg_match('#'.$this->urlGenerator->pattern().'#', $file)) {
                continue;
            }
            $parsedPath = $this->urlGenerator->parse($file);
            if ($isFile && $basename !== $filesystem->basename($parsedPath['path'])) {
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

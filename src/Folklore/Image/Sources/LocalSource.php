<?php

namespace Folklore\Image\Sources;

use Folklore\Image\ImagineManager;
use Folklore\Image\UrlGenerator;
use Imagine\Image\ImageInterface;

class LocalSource extends AbstractSource
{
    public function __construct(ImagineManager $imagine, Urlgenerator $urlGenerator, $config)
    {
        $this->imagine = $imagine;
        $this->urlGenerator = $urlGenerator;
        $this->config = $config;
    }

    public function getRealPath($path)
    {
        if (is_file(realpath($path))) {
            return realpath($path);
        }

        //Get directories
        $dirs = (array) (isset($this->config['path']) ? $this->config['path']:public_path());
        // Loop through all the directories files may be uploaded to
        foreach ($dirs as $dir) {
            $dir = rtrim($dir, '/');

            // Check that directory exists
            if (!is_dir($dir)) {
                continue;
            }

            // Look for the image in the directory
            $src = realpath($dir.'/'.ltrim($path, '/'));
            if (is_file($src)) {
                return $src;
            }
        }

        // None found
        return null;
    }

    public function pathExists($path)
    {
        $realPath = $this->getRealPath($path);
        return $realPath ? file_exists($realPath):false;
    }

    public function getFormatFromPath($path)
    {
        $path = $this->getRealPath($path);
        return parent::getFormatFromPath($path);
    }

    public function getFilesFromPath($path)
    {
        $images = array();

        //Check path
        $path = urldecode($path);
        if (!($path = $this->getRealPath($path))) {
            return $images;
        }

        // Loop through the contents of the source and write directory and get
        // all files that match the pattern
        $parts = pathinfo($path);
        $dirs = [$parts['dirname']];
        foreach ($dirs as $directory) {
            $files = scandir($directory);
            foreach ($files as $file) {
                if (strpos($file, $parts['filename']) === false || !preg_match('#'.$this->urlGenerator->pattern().'#', $file)) {
                    continue;
                }
                $images[] = $directory.'/'.$file;
            }
        }

        // Return the list
        return $images;
    }

    public function openFromPath($path)
    {
        $realPath = $this->getRealPath($path);
        return $this->imagine->open($realPath);
    }

    public function saveToPath(ImageInterface $image, $path)
    {
        $dir = isset($this->config['path']) ? $this->config['path']:public_path();
        $realPath = rtrim($dir, '/').'/'.ltrim($path, '/');
        return $image->save($realPath);
    }
}

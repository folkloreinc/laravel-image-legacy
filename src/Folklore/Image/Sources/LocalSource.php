<?php

namespace Folklore\Image\Sources;

use Imagine\Image\ImageInterface;

class LocalSource extends AbstractSource
{
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
        return scandir();
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

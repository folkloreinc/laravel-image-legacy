<?php

namespace Folklore\Image\Sources;

use Folklore\Image\ImagineManager;
use Imagine\Image\ImageInterface;
use Folklore\Image\Contracts\ImageDataHandler;

class LocalSource extends AbstractSource
{
    public function getFullPath($path)
    {
        $filesystem = app('files');

        //Get path
        $dir = isset($this->config['path']) ? $this->config['path']:'';

        // Check that directory exists
        if (!$filesystem->isDirectory($dir)) {
            return null;
        }

        // Check if the path exists
        $src = rtrim($dir, '/').'/'.ltrim($path, '/');
        if ($filesystem->exists($src)) {
            return $src;
        }

        // None found
        return null;
    }

    public function pathExists($path)
    {
        $realPath = $this->getFullPath($path);
        return $realPath ? app('files')->exists($realPath):false;
    }

    public function getFormatFromPath($path)
    {
        $path = $this->getFullPath($path);
        return parent::getFormatFromPath($path);
    }

    public function getFilesFromPath($path)
    {
        //Check path
        $path = urldecode($path);
        if (!$path = $this->getFullPath($path)) {
            return [];
        }

        $filesystem = app('files');
        $isFile = $filesystem->isFile($path);
        $basename = $isFile ? pathinfo($path, PATHINFO_BASENAME):null;
        $directory = $isFile ? pathinfo($path, PATHINFO_DIRNAME):$path;

        $files = $filesystem->allFiles($directory);
        $relativeFiles = [];
        foreach ($files as $file) {
            $relativeFiles[] = preg_replace('#'.$directory.'#', '', $file);
        }
        $images = $this->getImagesFromFiles($relativeFiles, $path);

        $ignore = isset($this->config['ignore']) ? (array)$this->config['ignore']:[];
        if (sizeof($ignore)) {
            $newImages = [];
            foreach ($images as $image) {
                $ignored = false;
                foreach ($ignore as $ignorePath) {
                    if (preg_match('#'.trim($ignorePath, '/').'#', $image)) {
                        $ignored = true;
                        continue;
                    }
                }
                if (!$ignored) {
                    $newImages[] = $image;
                }
            }
            $images = $newImages;
        }


        // Return the list
        return $images;
    }

    public function openFromPath($path)
    {
        $realPath = $this->getFullPath($path);
        return $this->imagine->open($realPath);
    }

    public function saveToPath(ImageInterface $image, $path)
    {
        $dir = isset($this->config['path']) ? $this->config['path']:public_path();
        $realPath = rtrim($dir, '/').'/'.ltrim($path, '/');
        return app(ImageDataHandler::class)->save($image, $realPath);
    }
}

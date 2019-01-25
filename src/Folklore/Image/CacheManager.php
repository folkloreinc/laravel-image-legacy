<?php

namespace Folklore\Image;

use Illuminate\Filesystem\Filesystem;
use Imagine\Image\ImageInterface;
use Folklore\Image\Contracts\ImageDataHandler;
use Folklore\Image\Contracts\CacheManager as CacheManagerContract;

class CacheManager implements CacheManagerContract
{
    protected $filesystem;
    protected $dataHandler;

    public function __construct(Filesystem $filesystem, ImageDataHandler $dataHandler)
    {
        $this->filesystem = $filesystem;
        $this->dataHandler = $dataHandler;
    }

    public function put(ImageInterface $image, $path, $directory = null)
    {
        if (is_null($directory)) {
            $directory = public_path();
        }

        $fullPath = rtrim($directory, '/').'/'.ltrim($path, '/');
        $directory = dirname($fullPath);

        // If the cache file exists, serve this file.
        if ($this->filesystem->exists($fullPath)) {
            return $fullPath;
        }

        // Check if cache directory is writable and create the directory if
        // it doesn't exists.
        $directoryExists = $this->filesystem->exists($directory);
        if ($directoryExists && !$this->filesystem->isWritable($directory)) {
            throw new \Exception('Destination is not writeable');
        }
        if (!$directoryExists) {
            $this->filesystem->makeDirectory($directory, 0755, true, true);
        }

        $this->dataHandler->save($image, $fullPath);

        return $fullPath;
    }
}

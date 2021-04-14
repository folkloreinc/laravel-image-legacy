<?php

namespace Folklore\Image\Sources;

use Folklore\Image\Contracts\ImageDataHandler;
use League\Flysystem\Adapter\Local;
use Imagine\Image\ImageInterface;
use finfo;

class FilesystemSource extends AbstractSource
{
    public function pathExists($path)
    {
        $fullPath = $this->getFullPath($path);
        return $this->existsOnDisk($fullPath);
    }

    public function getFilesFromPath($path)
    {
        $disk = $this->getDisk();

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $isFile = !empty($extension);
        $directory = $isFile ? pathinfo($path, PATHINFO_DIRNAME):$path;

        $files = $disk->allFiles($directory);
        $images = $this->getImagesFromFiles($files, $path);

        return $images;
    }

    public function getFormatFromPath($path)
    {
        $fullPath = $this->getFullPath($path);
        $disk = $this->getDisk();
        if ($disk->getAdapter() instanceof Local) {
            $localPath = $disk->getAdapter()->getPathPrefix();
            return parent::getFormatFromPath(rtrim($localPath, '/').'/'.ltrim($fullPath, '/'));
        }

        $cache = data_get($this->config, 'cache', false);
        $existsCache = $cache ? $this->existsOnCache($fullPath):false;
        if ($existsCache) {
            $cachePath = data_get($this->config, 'cache_path', null);
            if ($cachePath) {
                $cacheFullPath = $this->getCacheFullPath($fullPath);
                return parent::getFormatFromPath($cacheFullPath);
            } else {
                $cacheKey = $this->getCacheKey();
                $content = app('cache')->get($cacheKey);
                return $this->getFormatFromContent($content);
            }
        }

        $content = $disk->get($fullPath);
        return $this->getFormatFromContent($content);
    }

    public function openFromPath($path)
    {
        $fullPath = $this->getFullPath($path);
        $disk = $this->getDisk();

        if ($disk->getAdapter() instanceof Local) {
            $localPath = $disk->getAdapter()->getPathPrefix();
            return $this->imagine->open(rtrim($localPath, '/').'/'.ltrim($fullPath, '/'));
        }

        $cache = data_get($this->config, 'cache', false);
        $existsCache = $cache ? $this->existsOnCache($fullPath):false;

        $content = null;
        $pathToOpen = null;
        if ($existsCache) {
            $cachePath = data_get($this->config, 'cache_path', null);
            if ($cachePath) {
                $pathToOpen = $this->getCacheFullPath($fullPath);
            } else {
                $cacheKey = $this->getCacheKey();
                $content = app('cache')->get($cacheKey);
            }
        } else {
            $content = $disk->get($fullPath);
            if ($cache) {
                $this->saveToCache($fullPath, $content);
            }
        }

        if ($content) {
            return $this->imagine->load($content);
        } else {
            return $this->imagine->open($pathToOpen);
        }
    }

    public function saveToPath(ImageInterface $image, $path)
    {
        $fullPath = $this->getFullPath($path);
        $disk = $this->getDisk();

        if ($disk->getAdapter() instanceof Local) {
            $localPath = $disk->getAdapter()->getPathPrefix();
            return app(ImageDataHandler::class)->save($image, rtrim($localPath, '/').'/'.ltrim($fullPath, '/'));
        }

        $format = pathinfo($fullPath, \PATHINFO_EXTENSION);
        $content = app(ImageDataHandler::class)->get($image, $format);

        $disk->put($fullPath, $content);

        $cache = data_get($this->config, 'cache', false);
        if ($cache) {
            $this->saveToCache($fullPath, $content);
        }

        return $image;
    }

    public function getDisk()
    {
        $disk = $this->config['disk'];
        return $disk === 'cloud' ? app('filesystem')->cloud():app('filesystem')->disk($disk);
    }

    protected function getFullPath($path)
    {
        $prefixPath = data_get($this->config, 'path', '/');
        return rtrim($prefixPath, '/').'/'.ltrim($path, '/');
    }

    protected function getCacheFullPath($path)
    {
        $prefix = data_get($this->config, 'cache_path', null);
        $cachePath = $this->getCachePath($path);
        $extension = pathinfo($path, \PATHINFO_EXTENSION);
        return rtrim($prefix, '/').'/'.$cachePath.(empty($extension) ? '':('.'.$extension));
    }

    protected function getCachePath($path)
    {
        $key = md5($path).'_'.sha1($path);

        return 'image/'.preg_replace('/^([0-9a-z]{2})([0-9a-z]{2})/i', '$1/$2/', $key);
    }

    protected function getCacheKey($path)
    {
        $cachePath = $this->getCachePath($path);
        return preg_replace('/[^a-zA-Z0-9]+/i', '_', $cachePath);
    }

    protected function existsOnCache($path)
    {
        $cachePath = data_get($this->config, 'cache_path', null);
        if ($cachePath) {
            return file_exists($this->getCacheFullPath($path));
        }

        $cacheKey = $this->getCacheKey($path);
        return app('cache')->has($cacheKey);
    }

    protected function existsOnDisk($path)
    {
        $disk = $this->getDisk();
        return $disk->exists($path);
    }

    protected function getMimeFromContent($content)
    {
        $finfo = new finfo(FILEINFO_MIME);
        return $finfo->buffer($content);
    }

    protected function getFormatFromContent($content)
    {
        $mime = $this->getMimeFromContent($content);
        preg_match('/image\/([a-z\-]+)/i', $mime, $matches);

        switch ($matches[1]) {
            case 'jpg':
            case 'jpeg':
                return 'jpeg';
                break;
            case 'png':
                return 'png';
                break;
        }

        return $matches[1];
    }

    protected function saveToCache($path, $contents)
    {
        $cachePath = data_get($this->config, 'cache_path', null);
        if ($cachePath) {
            $filesystem = app('files');
            $fullPath = $this->getCacheFullPath($path);
            $directory = pathinfo($fullPath, PATHINFO_DIRNAME);
            if (!$filesystem->isDirectory($directory)) {
                $filesystem->makeDirectory($directory, 0755, true, true);
            }
            $filesystem->put($fullPath, $contents);
        } else {
            $cacheKey = $this->getCacheKey($path);
            $cacheExpiration = data_get($this->config, 'cache_expiration', -1);
            if ($cacheExpiration === -1) {
                app('cache')->forever($cacheKey, $contents);
            } else {
                app('cache')->put($cacheKey, $contents, $cacheExpiration);
            }
        }
    }
}

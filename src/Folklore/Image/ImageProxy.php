<?php namespace Folklore\Image;

use Folklore\Image\Exception\FileMissingException;

class ImageProxy
{
    protected $image = null;
    
    protected $config = [];
    
    public function __construct($image, $config = [])
    {
        $this->image = $image;
        
        $this->config = array_merge([
            'tmp_path' => sys_get_temp_dir(),
            'cache' => false,
            'write_image' => false,
            'filesystem' => null,
            'cache_filesystem' => null
        ], $config);
    }
    
    public function response($path)
    {
        // Increase memory limit, cause some images require a lot to resize
        if (config('image.memory_limit')) {
            ini_set('memory_limit', config('image.memory_limit'));
        }
        
        $app = app();
        
        $disk = $this->getProxyDisk();
        
        //Check if file exists
        $fullPath = $path;
        $cache = $this->config['cache'];
        $existsCache = $cache ? $this->existsOnProxyCache($fullPath):false;
        $existsDisk = !$existsCache ? $this->existsOnProxyDisk($fullPath):false;
        if ($existsCache) {
            return $this->getProxyResponseFromCache($fullPath);
        } elseif ($existsDisk) {
            $response = $this->getProxyResponseFromDisk($fullPath);
            
            if ($cache) {
                $this->saveToProxyCache($path, $response->getContent());
            }
            
            return $response;
        }
        
        $parse = $this->image->parse($fullPath);
        $originalPath = $parse['path'];
        $tmpPath = $this->config['tmp_path'];
        $extension = pathinfo($originalPath, PATHINFO_EXTENSION);
        $tmpOriginalPath = tempnam($tmpPath, 'original').'.'.$extension;
        $tmpTransformedPath = tempnam($tmpPath, 'transformed').'.'.$extension;
        
        if ($disk && !$disk->exists($originalPath)) {
            throw new FileMissingException();
        }
        
        //Download original file
        if (!$disk) {
            $downloadFile = $this->downloadFile($originalPath);
            $contents = file_get_contents($downloadFile);
        } else {
            $contents = !$disk ? $this->getRemoteFile($originalPath):$disk->get($originalPath);
        }
        file_put_contents($tmpOriginalPath, $contents);
        $contents = null;
        
        //Get mime
        $format = $this->image->format($tmpOriginalPath);
        $mime = $this->image->getMimeFromFormat($format);
        
        $this->image->make($tmpOriginalPath, $parse['options'])
                ->save($tmpTransformedPath);
        
        //Write image
        if ($this->config['write_image'] && $disk) {
            $resource = fopen($tmpTransformedPath, 'r');
            $disk
                ->getDriver()
                ->put($fullPath, $resource, [
                    'visibility' => 'public',
                    'ContentType' => $mime,
                    'CacheControl' => 'max-age='.(3600 * 24)
                ]);
            fclose($resource);
        }
        
        //Get response
        if (!$disk) {
            $response = $this->getProxyResponseFromPath($tmpTransformedPath);
        } else {
            $response = $this->getProxyResponseFromDisk($fullPath);
        }
        
        $response->header('Content-Type', $mime);
        
        //Save to cache
        $cache = $this->config['cache'];
        if ($cache) {
            $this->saveToProxyCache($path, $response->getContent());
        }
        
        unlink($tmpOriginalPath);
        unlink($tmpTransformedPath);
        
        return $response;
    }
    
    protected function downloadFile($path)
    {
        $deleteOriginalFile = true;
        $tmpPath = tempnam(config('image.proxy_tmp_path'), 'image');
        $client = new GuzzleClient();
        $response = $client->request('GET', $path, [
            'sink' => $tmpPath
        ]);
        $path = $tmpPath;
        
        return $tmpPath;
    }
    
    protected function getProxyDisk()
    {
        $filesystem = $this->config['filesystem'];
        $disk = app('filesystem')->disk($filesystem);
        
        return $disk;
    }
    
    protected function getProxyCacheDisk()
    {
        $filesystem = $this->config['cache_filesystem'];
        if (!$filesystem) {
            return null;
        }
        
        return app('filesystem')->disk($filesystem);
    }
    
    protected function getCacheKey($path)
    {
        $key = md5($path).'_'.sha1($path);
        
        return 'image/'.preg_replace('/^([0-9a-z]{2})([0-9a-z]{2})/i', '$1/$2/', $key);
    }
    
    protected function existsOnProxyCache($path)
    {
        $disk = $this->getProxyCacheDisk();
        $cacheKey = $this->getCacheKey($path);
        if ($disk) {
            return $disk->exists($cacheKey);
        }
        
        return app('cache')->has($cacheKey);
    }
    
    protected function existsOnProxyDisk($path)
    {
        $disk = $this->getProxyDisk();
        return $disk->exists($path);
    }
    
    protected function getProxyResponseFromCache($path)
    {
        $disk = $this->getProxyCacheDisk();
        $cacheKey = $this->getCacheKey($path);
        if ($disk) {
            $contents = $disk->get($cacheKey);
        } else {
            $contents = app('cache')->get($cacheKey);
        }
        
        $response = response()->make($contents, 200);
        $response->header('Cache-control', 'max-age='.(3600*24).', public');
        $contents = null;
        
        return $response;
    }
    
    protected function getProxyResponseFromPath($path)
    {
        $contents = file_get_contents($path);
        
        $response = response()->make($contents, 200);
        $response->header('Cache-control', 'max-age='.(3600*24).', public');
        $contents = null;
        
        return $response;
    }
    
    protected function getProxyResponseFromDisk($path)
    {
        $disk = $this->getProxyDisk();
        $contents = $disk->get($path);
        
        $response = response()->make($contents, 200);
        $response->header('Cache-control', 'max-age='.(3600*24).', public');
        $contents = null;
        
        return $response;
    }
    
    protected function saveToProxyCache($path, $contents)
    {
        $disk = $this->getProxyCacheDisk();
        $cacheKey = $this->getCacheKey($path);
        if ($disk) {
            $contents = $disk->put($cacheKey, $contents);
        } else {
            $contents = app('cache')->put($cacheKey, $contents);
        }
    }
}

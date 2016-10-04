<?php namespace Folklore\Image;

use GuzzleHttp\Client as GuzzleClient;
use Folklore\Image\Exception\FileMissingException;

use finfo;

class ImageProxy extends ImageServe
{
    protected $image = null;
    
    protected $config = [];
    
    public function __construct($image, $config = [])
    {
        $this->image = $image;
        
        $this->config = array_merge([
            'tmp_path' => sys_get_temp_dir(),
            'cache' => false,
            'cache_expiration' => 60*24,
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
        
        $disk = $this->getDisk();
        
        //Check if file exists
        $fullPath = $path;
        $cache = $this->config['cache'];
        $existsCache = $cache ? $this->existsOnProxyCache($fullPath):false;
        $existsDisk = !$existsCache && $disk ? $this->existsOnProxyDisk($fullPath):false;
        if ($existsCache) {
            return $this->getResponseFromCache($fullPath);
        } elseif ($existsDisk) {
            $response = $this->getResponseFromDisk($fullPath);
            
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
            $response = $this->getResponseFromPath($tmpTransformedPath);
        } else {
            $response = $this->getResponseFromDisk($fullPath);
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
        $tmpPath = tempnam($this->config['tmp_path'], 'image');
        $client = new GuzzleClient();
        $response = $client->request('GET', $path, [
            'sink' => $tmpPath
        ]);
        $path = $tmpPath;
        
        return $tmpPath;
    }
    
    protected function getDisk()
    {
        $filesystem = $this->config['filesystem'];
        if (!$filesystem) {
            return null;
        }
        
        return $filesystem === 'cloud' ? app('filesystem')->cloud():app('filesystem')->disk($filesystem);
    }
    
    protected function getCacheDisk()
    {
        $filesystem = $this->config['cache_filesystem'];
        if (!$filesystem) {
            return null;
        }
        
        return $filesystem === 'cloud' ? app('filesystem')->cloud():app('filesystem')->disk($filesystem);
    }
    
    protected function getCacheKey($path)
    {
        $key = md5($path).'_'.sha1($path);
        
        return 'image/'.preg_replace('/^([0-9a-z]{2})([0-9a-z]{2})/i', '$1/$2/', $key);
    }
    
    protected function getEscapedCacheKey($path)
    {
        $cacheKey = $this->getCacheKey($path);
        return preg_replace('/[^a-zA-Z0-9]+/i', '_', $cacheKey);
    }
    
    protected function existsOnProxyCache($path)
    {
        $disk = $this->getCacheDisk();
        if ($disk) {
            $cacheKey = $this->getCacheKey($path);
            return $disk->exists($cacheKey);
        }
        
        $cacheKey = $this->getEscapedCacheKey($path);
        return app('cache')->has($cacheKey);
    }
    
    protected function existsOnProxyDisk($path)
    {
        $disk = $this->getDisk();
        return $disk->exists($path);
    }
    
    protected function getMimeFromContent($content)
    {
        $finfo = new finfo(FILEINFO_MIME);
        return $finfo->buffer($content);
    }
    
    protected function getResponseFromCache($path)
    {
        $disk = $this->getCacheDisk();
        if ($disk) {
            $cacheKey = $this->getCacheKey($path);
            $contents = $disk->get($cacheKey);
        } else {
            $cacheKey = $this->getEscapedCacheKey($path);
            $contents = app('cache')->get($cacheKey);
        }
        
        $response = response()->make($contents, 200);
        $response->header('Cache-control', 'max-age='.(3600*24).', public');
        $response->header('Content-type', $this->getMimeFromContent($contents));
        $contents = null;
        
        return $response;
    }
    
    protected function getResponseFromPath($path)
    {
        $content = file_get_contents($path);
        $response = $this->createResponseFromContent($content);
        $response->header('Content-type', $this->getMimeFromContent($content));
        $content = null;
        
        return $response;
    }
    
    protected function getResponseFromDisk($path)
    {
        $disk = $this->getDisk();
        $content = $disk->get($path);
        $response = $this->createResponseFromContent($content);
        $response->header('Content-type', $this->getMimeFromContent($content));
        $content = null;
        
        return $response;
    }
    
    protected function getResponseExpires()
    {
        $proxyExpires = config('image.proxy_expires', null);
        return $proxyExpires ? $proxyExpires:config('image.serve_expires', 3600*24*31);
    }
    
    protected function saveToProxyCache($path, $contents)
    {
        $disk = $this->getCacheDisk();
        if ($disk) {
            $cacheKey = $this->getCacheKey($path);
            $disk->put($cacheKey, $contents);
        } else {
            $cacheKey = $this->getEscapedCacheKey($path);
            $cacheExpiration = $this->config['cache_expiration'];
            if($cacheExpiration === -1)
            {
                app('cache')->forever($cacheKey, $contents);
            }
            else
            {
                app('cache')->put($cacheKey, $contents, $cacheExpiration);
            }
        }
    }
}

<?php

namespace Folklore\Image\Http;

use Folklore\Image\Http\ImageResponse;
use Closure;
use File;

class CacheMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $path = $request->path();
        $route = $request->route();
        $routeAction = $route ? $route->getAction():[];
        $cachePath = array_get($routeAction, 'image.cache_path', public_path());
        $cacheFilePath = rtrim($cachePath, '/').'/'.ltrim($path, '/');
        $cacheDirectory = dirname($cacheFilePath);
        
        if (file_exists($cacheFilePath)) {
            return response()->image()
                ->setImagePath($cacheFilePath);
        }
    
        $response = $next($request);
        
        if (!file_exists($cacheDirectory)) {
            File::makeDirectory($cacheDirectory, 0755, true, true);
        }
        
        if (!is_writable($cacheDirectory)) {
            throw new \Exception('Destination is not writeable');
        }
        
        // If it's an ImageResponse, save the image from the Image object.
        // Otherwise, get the response content and save it.
        if ($response instanceof ImageResponse) {
            $image = $response->getImage();
            $image->save($cacheFilePath);
            $response->setImagePath($cacheFilePath);
        } else {
            $content = $response->getContent();
            File::put($cacheFilePath, $content);
        }

        return $response;
    }
}

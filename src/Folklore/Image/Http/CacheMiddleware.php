<?php

namespace Folklore\Image\Http;

use Closure;
use File;
use Folklore\Image\Http\ImageResponse;

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
        $response = $next($request);
        
        $path = $request->path();
        $cachePath = rtrim(public_path(), '/').'/'.ltrim($path, '/');
        $cacheDirectory = dirname($cachePath);
        
        if (file_exists($cachePath)) {
            return $response;
        }
        
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
            $image->save($cachePath);
            $response->setImagePath($cachePath);
        } else {
            $content = $response->getContent();
            File::put($cachePath, $content);
        }

        return $response;
    }
}

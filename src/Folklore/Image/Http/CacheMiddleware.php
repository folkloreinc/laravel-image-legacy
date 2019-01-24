<?php

namespace Folklore\Image\Http;

use Folklore\Image\Http\ImageResponse;
use Folklore\Image\Contracts\ImageDataHandler;
use Illuminate\Contracts\Filesystem\Factory;
use Closure;

class CacheMiddleware
{
    protected $filesystem;

    public function __construct()
    {
        $this->filesystem = app('files');
    }

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

        // If the cache file exists, serve this file.
        if ($this->filesystem->exists($cacheFilePath)) {
            return response()
                ->image()
                ->setImagePath($cacheFilePath);
        }

        // Get the response
        $response = $next($request);

        // Return the response if not successful
        if ($response->status() !== 200) {
            return $response;
        }

        // Check if cache directory is writable and create the directory if
        // it doesn't exists.
        $directoryExists = $this->filesystem->exists($cacheDirectory);
        if ($directoryExists && !$this->filesystem->isWritable($cacheDirectory)) {
            throw new \Exception('Destination is not writeable');
        }
        if (!$directoryExists) {
            $this->filesystem->makeDirectory($cacheDirectory, 0755, true, true);
        }

        // If it's an ImageResponse, save the image from the Image object.
        // Otherwise, ignore it.
        if ($response instanceof ImageResponse) {
            $image = $response->getImage();
            app(ImageDataHandler::class)->save($image, $cacheFilePath);
            $response->setImagePath($cacheFilePath);
        }

        return $response;
    }
}

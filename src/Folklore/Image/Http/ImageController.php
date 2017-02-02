<?php

namespace Folklore\Image\Http;

use Illuminate\Http\Request;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;

use Folklore\Image\Exception\Exception;
use Folklore\Image\Exception\FileMissingException;
use Folklore\Image\Exception\ParseException;

use App;
use Image;

class ImageController extends BaseController
{
    use DispatchesJobs, ValidatesRequests;

    public function serve(Request $request, $path)
    {
        // Get config from route
        $route = $request->route();
        $config = $route ? array_get($route->getAction(), 'image', []):[];
        $source = array_get($config, 'source');
        $quality = (float)array_get($config, 'quality', 100);
        $expires = array_get($config, 'expires', null);
        $urlConfig = array_get($config, 'pattern', []);
        $routeFilters = array_get($config, 'filters', []);

        // Parse the path
        $parseData = app('image.url')->parse($path, $urlConfig);
        $path = $parseData['path'];
        $pathFilters = $parseData['filters'];
        $filters = array_merge($pathFilters, $routeFilters);

        // Return the response
        try {
            // Make the image
            $manipulator = $source ? app('image')->source($source):app('image');
            $image = $manipulator->make($path, $filters);
            $format = $manipulator->format($path);

            return response()->image($image)
                ->setQuality($quality)
                ->setFormat($format)
                ->setExpiresIn($expires);
        } catch (ParseException $e) {
            return abort(404);
        } catch (FileMissingException $e) {
            return abort(404);
        } catch (Exception $e) {
            return abort(500);
        }
    }
}

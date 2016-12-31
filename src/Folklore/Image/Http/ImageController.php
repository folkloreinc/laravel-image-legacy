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
        $route = $request->route();
        $config = $route ? array_get($route->getAction(), 'image', []):[];
        $source = array_get($config, 'source');
        
        // Serve the image response. If there is a file missing
        // exception or parse exception, throw a 404.
        try {
            $image = $source ? app('image')->source($source):app('image');
            return $image->serve($path, $config);
        } catch (ParseException $e) {
            return abort(404);
        } catch (FileMissingException $e) {
            return abort(404);
        } catch (Exception $e) {
            return abort(500);
        }
    }
}

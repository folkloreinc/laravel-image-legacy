<?php namespace Folklore\Image;

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
    
    public function serve($path)
    {
        // Serve the image response. If there is a file missing
        // exception or parse exception, throw a 404.
        try {
            return app('image')->serve($path);
        } catch (ParseException $e) {
            return abort(404);
        } catch (FileMissingException $e) {
            return abort(404);
        } catch (Exception $e) {
            return abort(500);
        }
    }
    
    public function proxy($path)
    {
        // Serve the image response from proxy. If there is a file missing
        // exception or parse exception, throw a 404.
        try {
            return app('image')->proxy($path);
        } catch (ParseException $e) {
            return abort(404);
        } catch (FileMissingException $e) {
            return abort(404);
        } catch (Exception $e) {
            return abort(500);
        }
    }
}

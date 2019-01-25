<?php

namespace Folklore\Image\Http;

use Illuminate\Http\Request;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;

use Folklore\Image\Contracts\RouteResolver;
use Folklore\Image\Exception\Exception;
use Folklore\Image\Exception\FileMissingException;
use Folklore\Image\Exception\ParseException;

class ImageController extends BaseController
{
    use DispatchesJobs, ValidatesRequests;

    public function __construct(RouteResolver $routeResolver)
    {
        $this->routeResolver = $routeResolver;
    }

    public function serve(Request $request, $path)
    {
        // Make the image from the route and return the response
        try {
            return $this->routeResolver->resolveToResponse($request->route());
        } catch (ParseException $e) {
            return abort(404);
        } catch (FileMissingException $e) {
            return abort(404);
        }
    }
}

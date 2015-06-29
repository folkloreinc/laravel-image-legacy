<?php namespace Folklore\Image;

use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;

use Folklore\Image\Exception\Exception;
use Folklore\Image\Exception\FileMissingException;
use Folklore\Image\Exception\ParseException;

use App;
use Image;

class ImageController extends BaseController {

	use DispatchesCommands, ValidatesRequests;
	
	public function serve($path)
	{
		$app = app();
		
		//Get the full path of an image
		$fullPath = $app->make('path.public').'/'.$path;

		// Serve the image response. If there is a file missing
		// exception or parse exception, throw a 404.
		try
		{
			$response = $app['image']->serve($fullPath);

			return $response;
		}
		catch(ParseException $e)
		{
			return abort(404);
		}
		catch(FileMissingException $e)
		{
			return abort(404);
		}

	}

}

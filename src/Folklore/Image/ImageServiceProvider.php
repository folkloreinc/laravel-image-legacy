<?php namespace Folklore\Image;

use Illuminate\Support\ServiceProvider;

use Folklore\Image\Exception\Exception;
use Folklore\Image\Exception\FileMissingException;
use Folklore\Image\Exception\ParseException;

class ImageServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		// Config file path
		$configFile = __DIR__ . '/../../resources/config/image.php';
		$publicFile = __DIR__ . '/../../resources/assets/';

		// Merge files
		$this->mergeConfigFrom($configFile, 'image');

		// Publish
		$this->publishes([
			$configFile => config_path('image.php')
		], 'config');
		
		$this->publishes([
			$publicFile => public_path('vendor/folklore/image')
		], 'public');

		$app = $this->app;

		//Serve image
		if($this->app['config']['image.serve_image'])
		{
			// Create a route that match pattern
			$app->make('router')->get('{path}', function($path) use ($app)
			{
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

			})->where('path', $app['image']->pattern());
		}
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->singleton('image', function($app)
		{
			return new ImageManager($app);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('image');
	}

}

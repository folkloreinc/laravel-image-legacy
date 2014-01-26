<?php namespace Folklore\Image;

use Illuminate\Support\ServiceProvider;

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
		$this->package('folklore/laravel-image');

		$app = $this->app;

		//Serve image
		if($this->app['config']['laravel-image::serve_image']) {
			// Create a route that match pattern
			$app->make('router')->get('{path}', function($path) use ($app)
			{
				//Get the full path of an image
				$fullPath = $app->make('path.public').'/'.$path;

				//Serve the image response
				return $app['image']->serve($fullPath);

			})->where('path', $app['image']->getPattern());
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
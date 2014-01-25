<?php namespace Folklore\Image;

use Illuminate\Support\Manager;
use Illuminate\Support\Facades\Response;

use Imagine\Image\ImageInterface;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Image\Color;

class ImageManager extends Manager {

	/**
	 * Default options
	 *
	 * @var array
	 */
	protected $defaultOptions = array(
		'width' => '_',
		'height' => '_',
		'quality' => 80,
		'filters' => array()
	);

	/**
	 * All of the custom filters.
	 *
	 * @var array
	 */
	protected $filters = array();

	/**
	 * Return an URL to process the image
	 *
	 * @param  string  $src
	 * @param  int     $width
	 * @param  int     $height
	 * @param  array   $options
	 * @return string
	 */
	public function url($src, $width = null, $height = null, $options = array())
	{

		// Defaults
		if (empty($src)) return; // Don't allow empty strings
		if (empty($width)) $width = '_';
		if (empty($height)) $height = '_';
		
		// Produce the croppa syntax
		$params = array();
		$params[] = $width.'x'.$height;
		
		// Add options.  If the key has no arguments (like resize), the key will be like [1]
		if ($options && is_array($options)) {
			foreach($options as $key => $val) {
				if (is_numeric($key)) $params[] = $val;
				elseif (is_array($val)) $params[] = $key.'('.implode(',',$val).')';
				else $params[] = $key.'('.$val.')';
			}
		}
		
		// Break the path apart and put back together again
		$params = '-image('.implode('-',$params).')';
		$parts = pathinfo($src);
		$host = '//'.$this->app->make('request')->getHttpHost();
		$url = $host .$parts['dirname'].'/'.$parts['filename'].$params;
		if (!empty($parts['extension'])) $url .= '.'.$parts['extension'];

		return $url;

	}

	/**
	 * Generate the image
	 *
	 * @param  string  $url
	 * @return string
	 */
	public function generate($url)
	{

		// Check if the current url looks like an image URL.
		if (!preg_match('#'.$this->pattern().'#i', $url, $matches)) return false;

		//Get path and options
		$path = $matches[1].'.'.$matches[3];
		$options = $matches[2];
		$fullPath = $this->app->make('path.public').'/'.$path;

		// Increase memory limit, cause some images require a lot to resize
		ini_set('memory_limit', '128M');
		
		// Parse options
		$options = $this->makeOptions($options);

		// See if the referenced file exists and is an image
		if(!file_exists($fullPath))
		{
			throw new Exception('Referenced file missing');
		}
		
		// Make the destination the same path
		$dst = dirname($fullPath).'/'.basename($url);
		
		// Make sure destination is writeable
		if (!is_writable(dirname($dst)))
		{
			throw new Exception('Destination is not writeable');
		}

		//Open the image
		$image = $this->open($fullPath);

		// Execute all filters
		if(isset($options['filters']) && sizeof($options['filters'])) {
			foreach($options['filters'] as $filter) {
				// Put the image and a reference to $options as the first two arguments
				$arguments = array_merge(array($image,&$options),$filter['arguments']);
				// Call the filter and get the return value
				$return = call_user_func_array($filter['closure'],$arguments);
				// If the return value is an instance of ImageInterface,
				// replace the current image with it.
				if($return instanceof ImageInterface) {
					$image = $return;
				}
			}
		}

		//Check if filters only is enabled
		if(!$this->app['config']['image::filters_only'])
		{
			$thumbnail = $image;
		}
		else
		{
			// Get current image size
			$currentSize = $image->getSize();
			$width = $options['width'];
			$height = $options['height'];

			//If width and height are not set, skip resize
			if($width === '_' && $height === '_')
			{
				$thumbnail = $image;
			}
			//Resize the image
			else
			{
				//Set the new size
				$newWidth = $width === '_' ? $currentSize->getWidth():$width;
				$newHeight = $height === '_' ? $currentSize->getHeight():$height;
				$newSize = new Box($newWidth, $newHeight);

				//Get resize mode
				$mode = isset($options['crop']) ? ImageInterface::THUMBNAIL_OUTBOUND:ImageInterface::THUMBNAIL_INSET;

				//Create the thumbnail
				$thumbnail = $image->thumbnail($newSize,$mode);
			}

			//Rotate
			if(isset($options['rotate'])) {
				$thumbnail->rotate($options['rotate']);
			}

			//Apply built-in effects
			$effects = $thumbnail->effects();
			//Grayscale
			if(isset($options['grayscale'])) {
				$effects->grayscale();
			}
			//Negative
			if(isset($options['negative'])) {
				$effects->negative();
			}
			//Gamma
			if(isset($options['gamma'])) {
				$effects->gamma((float)$options['gamma']);
			}
			//Blur
			if(isset($options['blur'])) {
				$effects->blur((float)$options['blur']);
			}
			//Colorize
			if(isset($options['colorize'])) {
				$color = new Color('#'.$options['colorize']);
				$effects->colorize($color);
			}
		}

		//Get the image content
		$contents = $thumbnail->get('png',array(
			'quality' => isset($options['quality']) ? (int)$options['quality']:80
		));

		//Create the response
		$response = Response::make($contents, 200);
		$response->header('Content-Type', 'image/png');

		//Return the response
		return $response;
	}

	/**
	 * Return the Image URL regex
	 * 
	 * @return string
	 */
	public function pattern() {
		return $this->app['config']['image::pattern'];
	}
	
	/**
	 * Parse options from url string
	 *
	 * @param  string  $option_params
	 * @return array
	 */
	private function makeOptions($option_params) {

		$options = array();
		
		// These will look like: "-colorize(CC0000)-greyscale"
		$option_params = explode('-', $option_params);
		
		// Loop through the params and make the options key value pairs
		foreach($option_params as $option)
		{
			if (preg_match('#([0-9]+|_)x([0-9]+|_)#i', $option, $matches))
			{
				$options['width'] = is_numeric($matches[1]) ? (int)$matches[1]:$matches[1];
				$options['height'] = is_numeric($matches[2]) ? (int)$matches[2]:$matches[2];
				continue;
			}
			else if (!preg_match('#(\w+)(?:\(([\w,.]+)\))?#i', $option, $matches))
			{
				continue;
			}

			$key = $matches[1];

			if(isset($this->filters[$key])) {
				if(is_object($this->filters[$key]) && is_callable($this->filters[$key])) {
					$options['filters'][] = array(
						'closure' => $this->filters[$key],
						'arguments' => isset($matches[2]) ? explode(',', $matches[2]):array()
					);
				} else if(is_array($this->filters[$key])) {
					$options = array_merge($options,$this->filters[$key]);
				}
			} else {
				if(isset($matches[2])) {
					$options[$key] = strpos($matches[2],',') === true ? explode(',', $matches[2]):$matches[2];
				} else {
					$options[$key] = true;
				}
			}
		}

		// Merge the options with defaults
		return array_merge($this->defaultOptions, $options);
	}

	/**
	 * Register a custom filter.
	 *
	 * @param  string  $name
	 * @param  Closure|string  $filter
	 * @return void
	 */
	public function filter($name, $filter)
	{
		$this->filters[$name] = $filter;
	}

	/**
	 * Create an instance of the Imagine Gd driver.
	 *
	 * @return \Imagine\Gd\Imagine
	 */
	protected function createGdDriver()
	{
		return new \Imagine\Gd\Imagine();
	}

	/**
	 * Create an instance of the Imagine Imagick driver.
	 *
	 * @return \Imagine\Imagick\Imagine
	 */
	protected function createImagickDriver()
	{
		return new \Imagine\Imagick\Imagine();
	}

	/**
	 * Create an instance of the Imagine Gmagick driver.
	 *
	 * @return \Imagine\Gmagick\Imagine
	 */
	protected function createGmagickDriver()
	{
		return new \Imagine\Gmagick\Imagine();
	}

	/**
	 * Get the default image driver name.
	 *
	 * @return string
	 */
	public function getDefaultDriver()
	{
		return $this->app['config']['image::driver'];
	}

	/**
	 * Set the default image driver name.
	 *
	 * @param  string  $name
	 * @return void
	 */
	public function setDefaultDriver($name)
	{
		$this->app['config']['image::driver'] = $name;
	}

}
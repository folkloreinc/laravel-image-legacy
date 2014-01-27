<?php namespace Folklore\Image;

use Folklore\Image\Exception\Exception;

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
		'width' => null,
		'height' => null,
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

		// Don't allow empty strings
		if (empty($src)) return;

		//If width parameter is an array, use it as options
		if(is_array($width))
		{
			$options = $width;
			$width = null;
			$height = null;
		}

		if(isset($options['width'])) $width = $options['width'];
		if(isset($options['height'])) $height = $options['height'];

		//Get size
		if (empty($width)) $width = '_';
		if (empty($height)) $height = '_';
		
		// Produce the croppa syntax
		$params = array();
		$params[] = $width.'x'.$height;
		
		// Add options.  If the key has no arguments (like resize), the key will be like [1]
		if ($options && is_array($options)) {
			foreach($options as $key => $val) {
				if (is_numeric($key)) $params[] = $val;
				else if ($val === true) $params[] = $key;
				elseif (is_array($val)) $params[] = $key.'('.implode(',',$val).')';
				else $params[] = $key.'('.$val.')';
			}
		}
		
		//Create the url parameter
		$params = implode('-',$params);
		$parameter = str_replace('{options}',$params,$this->app['config']['image::url_parameter']);

		// Break the path apart and put back together again
		$parts = pathinfo($src);
		$host = '//'.$this->app->make('request')->getHttpHost();
		$url = $host .$parts['dirname'].'/'.$parts['filename'].$parameter;
		if (!empty($parts['extension'])) $url .= '.'.$parts['extension'];

		return $url;

	}

	/**
	 * Make an image and apply options
	 *
	 * @param  string	$path The path of the image
	 * @param  array	$options The manipulations to apply on the image
	 * @return ImageInterface
	 */
	public function make($path, $options = array()) {

		//Get app config
		$config = $this->app['config'];

		// See if the referenced file exists and is an image
		if(!($path = $this->checkForFile($path)))
		{
			throw new Exception('Image file missing');
		}

		// Get image format
		$format = $this->format($path);
		if (!$format)
		{
			throw new Exception('Image format is not supported');
		}

		//Open the image
		$image = $this->open($path);

		//Get options
		$options = array_merge($this->defaultOptions, $options);

		// Apply all custom filters
		if(isset($options['filters']) && sizeof($options['filters'])) {
			foreach($options['filters'] as $filter) {
				$arguments = (array)$filter;
				array_unshift($arguments,$image);
				$image = call_user_func_array(array($this,'applyCustomFilter'), $arguments);
			}
		}
		
		//If width and height are not set, skip resize
		if($options['width'] !== null || $options['height'] !== null)
		{
			$crop = isset($options['crop']) ? $options['crop']:false;
			$image = $this->thumbnail($image,$options['width'],$options['height'],$crop);
		}

		//Apply built-in filters
		foreach($options as $key => $arguments) {
			$method = 'filter'.ucfirst($key);
			if($arguments !== false && method_exists($this,$method)) {
				$arguments = (array)$arguments;
				array_unshift($arguments,$image);
				$image = call_user_func_array(array($this,$method),$arguments);
			}
		}


		
		return $image;
	}

	/**
	 * Serve the image
	 *
	 * @param  string	$path
	 * @param  array	$options
	 * @return string
	 */
	public function serve($path,$options = array())
	{

		//Get app config
		$config = $this->app['config'];

		// Make sure destination is writeable
		if ($config['image::write_image'] && !is_writable(dirname($path)))
		{
			throw new Exception('Destination is not writeable');
		}

		// Increase memory limit, cause some images require a lot to resize
		ini_set('memory_limit', '128M');

		// Parse the current path
		$parsedPath = $this->parsePath($path);
		$imagePath = $parsedPath['path'];

		//If custom filters only, remove all other options
		if($config['image::serve_custom_filters_only']) {
			$parsedOptions = array_intersect_key($parsedPath['options'],array('filters'=>null));
		} else {
			$parsedOptions = $parsedPath['options'];
		}

		//Merge with defaults and options argument
		$options = array_merge($this->defaultOptions,$parsedOptions,$options);

		//Make the image
		$image = $this->make($imagePath,$options);

		//Write the image
		if ($config['image::write_image'])
		{
			$destinationPath = dirname($path).'/'.basename($path);
			$image->save($destinationPath);
		}

		//Get the image format
		$format = $this->format($imagePath);

		//Get the image content
		$contents = $image->get($format,array(
			'quality' => $options['quality']
		));

		//Create the response
		$mime = $this->getMimeFromFormat($format);
		$response = Response::make($contents, 200);
		$response->header('Content-Type', $mime);

		//Return the response
		return $response;
	}

	/**
	 * Register a custom filter.
	 *
	 * @param  string			$name The name of the filter
	 * @param  Closure|string	$filter 
	 * @return void
	 */
	public function filter($name, $filter)
	{
		$this->filters[$name] = $filter;
	}

	/**
	 * Create a thumbnail from an image
	 *
	 * @param  ImageInterface|string	$image An image instance or the path to an image
	 * @param  int						$width
	 * @return ImageInterface
	 */
	public function thumbnail($image, $width = null, $height = null, $crop = true)
	{
		//If $image is a path, open it
		if (is_string($image))
		{
			$image = $this->open($image);
		}

		//Get new size
		$size = $image->getSize();
		$newWidth = $width === null ? $size->getWidth():$width;
		$newHeight = $height === null ? $size->getHeight():$height;
		$newSize = new Box($newWidth, $newHeight);

		//Get resize mode
		$mode = $crop ? ImageInterface::THUMBNAIL_OUTBOUND:ImageInterface::THUMBNAIL_INSET;

		//Create the thumbnail
		return $image->thumbnail($newSize,$mode);
	}

	/**
	 * Get the format of an image
	 *
	 * @param  string	$path The path to an image
	 * @return ImageInterface
	 */
	public function format($path)
	{

		$format = exif_imagetype($path);
		switch($format) {
			case IMAGETYPE_GIF:
				return 'gif';
			break;
			case IMAGETYPE_JPEG:
				return 'jpeg';
			break;
			case IMAGETYPE_PNG:
				return 'png';
			break;
		}

		return null;
	}

	/**
	 * Delete a file and all manipulated files
	 *
	 * @param  string	$path The path to an image
	 * @return void
	 */
	public function delete($path)
	{
		$files = $this->getAllFiles($path);

		foreach($files as $file) {
			if (!unlink($file)) throw new Exception('Unlink failed: '.$file);
		}
	}

	/**
	 * Get the URL pattern
	 * 
	 * @return string
	 */
	public function getPattern()
	{

		//Replace the {options} with the options regular expression
		$parameter = preg_quote($this->app['config']['image::url_parameter']);
		$parameter = str_replace('\{options\}','([0-9a-zA-Z\(\),\-._]+?)?',$parameter);

		return '^(.*)'.$parameter.'\.(jpg|jpeg|png|gif|JPG|JPEG|PNG|GIF)$';
	}
	
	/**
	 * Check for file in src_dirs
	 *
	 * @param  string	$path Path to an original image
	 * @return string
	 */
	protected function checkForFile($path) {

		if (is_file($path)) {
			return $path;
		}

		// Loop through all the directories files may be uploaded to
		$dirs = $this->app['config']['image::src_dirs'];
		foreach($dirs as $dir) {
			
			// Check that directory exists
			if (!is_dir($dir)) continue;
			if (substr($dir, -1, 1) != '/') $dir .= '/';
			
			// Look for the image in the directory
			$src = realpath($dir.$path);
			if (is_file($src)) {
				return $src;
			}
		}
		
		// None found
		return false;
	}

	/**
	 * Get all files (including manipulated images)
	 *
	 * @param  string	$path Path to an original image
	 * @return array
	 */
	protected function getAllFiles($path)
	{

		$images = array();

		// Need to decode the url so that we can handle things like space characters
		$path = urldecode($path);
	
		// Add the source image to the list
		if(!($path = $this->checkForFile($path)))
		{
			return $images;
		}
		$images[] = $path;
		
		// Loop through the contents of the source directory and delete
		// any images that contain the source directories filename and also match
		// the Image URL pattern
		$parts = pathinfo($path);
		$files = scandir($parts['dirname']);
		foreach($files as $file) {
			if (strpos($file, $parts['filename']) === false || !preg_match('#'.$this->getPattern().'#', $file)) continue;
			$images[] = $parts['dirname'].'/'.$file;
		}

		// Return the list
		return $images;
	}

	/**
	 * Apply a custom filter or an image
	 *
	 * @param  ImageInterface	$image An image instance
	 * @param  string			$name The filter name
	 * @return void
	 */
	protected function applyCustomFilter(ImageInterface $image, $name)
	{

		//Get arguments
		$arguments = array_slice(func_get_args(),2);
		array_unshift($arguments,$image);
		// Call the filter and get the return value
		$return = call_user_func_array($this->filters[$name],$arguments);
		// If the return value is an instance of ImageInterface,
		// replace the current image with it.
		if($return instanceof ImageInterface) {
			$image = $return;
		}
		return $image;
	}

	/**
	 * Apply rotate filter
	 *
	 * @param  ImageInterface	$image An image instance
	 * @param  float			$degree The rotation degree
	 * @return void
	 */
	protected function filterRotate(ImageInterface $image, $degree)
	{
		return $image->rotate($degree);
	}

	/**
	 * Apply grayscale filter
	 *
	 * @param  ImageInterface	$image An image instance
	 * @return void
	 */
	protected function filterGrayscale(ImageInterface $image)
	{
		$image->effects()->grayscale();
		return $image;
	}

	/**
	 * Apply negative filter
	 *
	 * @param  ImageInterface	$image An image instance
	 * @return void
	 */
	protected function filterNegative(ImageInterface $image)
	{
		$image->effects()->negative();
		return $image;
	}

	/**
	 * Apply gamma filter
	 *
	 * @param  ImageInterface	$image An image instance
	 * @param  float			$gamma The gamma value
	 * @return void
	 */
	protected function filterGamma(ImageInterface $image, $gamma)
	{
		$image->effects()->gamma($gamma);
		return $image;
	}

	/**
	 * Apply blur filter
	 *
	 * @param  ImageInterface	$image An image instance
	 * @param  int			$blur The amount of blur
	 * @return void
	 */
	protected function filterBlur(ImageInterface $image, $blur)
	{
		$image->effects()->blur($blur);
		return $image;
	}

	/**
	 * Apply colorize filter
	 *
	 * @param  ImageInterface	$image An image instance
	 * @param  string			$color The hex value of the color
	 * @return void
	 */
	protected function filterColorize(ImageInterface $image, $color)
	{
		$image->effects()->colorize($color);
		return $image;
	}

	/**
	 * Get mime type from image format
	 * 
	 * @return string
	 */
	protected function getMimeFromFormat($format)
	{

		switch($format) {
			case 'gif':
				return 'image/gif';
			break;
			case 'jpeg':
				return 'image/jpeg';
			break;
			case 'png':
				return 'image/png';
			break;
		}

		return null;
	}

	/**
	 * Parse the path for options
	 *
	 * @param  string  $path
	 * @return array
	 */
	protected function parsePath($path) {

		$parsedOptions = array();

		if (preg_match('#'.$this->getPattern().'#i', $path, $matches))
		{
			//Get path and options
			$path = $matches[1].'.'.$matches[3];
			$pathOptions = $matches[2];

			// Parse options from path
			$parsedOptions = $this->parseOptions($pathOptions);
		}

		return array(
			'path' => $path,
			'options' => $parsedOptions
		);
	}
	
	/**
	 * Parse options from url string
	 *
	 * @param  string  $option_params
	 * @return array
	 */
	protected function parseOptions($option_params) {

		$options = array();
		
		// These will look like: "-colorize(CC0000)-greyscale"
		$option_params = explode('-', $option_params);
		
		// Loop through the params and make the options key value pairs
		foreach($option_params as $option)
		{
			if (preg_match('#([0-9]+|_)x([0-9]+|_)#i', $option, $matches))
			{
				$options['width'] = $matches[1] === '_' ? null:(int)$matches[1];
				$options['height'] = $matches[2] === '_' ? null:(int)$matches[2];
				continue;
			}
			else if (!preg_match('#(\w+)(?:\(([\w,.]+)\))?#i', $option, $matches))
			{
				continue;
			}

			$key = $matches[1];

			if(isset($this->filters[$key])) {
				if(is_object($this->filters[$key]) && is_callable($this->filters[$key])) {
					$arguments = isset($matches[2]) ? explode(',', $matches[2]):array();
					array_unshift($arguments,$key);
					$options['filters'][] = $arguments;
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
		return $options;
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
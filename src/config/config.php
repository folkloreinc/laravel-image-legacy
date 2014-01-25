<?php



return array(

	/*
	|--------------------------------------------------------------------------
	| Default Image Driver
	|--------------------------------------------------------------------------
	|
	| This option controls the default image "driver" used by Imagine library
	| to manipulate images.
	|
	| Supported: "gd", "imagick", "gmagick"
	|
	*/

	'driver' => 'gd',

	/*
	|--------------------------------------------------------------------------
	| URL pattern
	|--------------------------------------------------------------------------
	|
	| Pattern used in the route to identify path that belongs to
	| image manipulation
	|
	*/
	'pattern' => '^(.*)-image\(([0-9a-zA-Z(),\-._]+?)\)\.(jpg|jpeg|png|gif|JPG|JPEG|PNG|GIF)$',

	/*
	|--------------------------------------------------------------------------
	| Custom Filters only
	|--------------------------------------------------------------------------
	|
	| Restrict options in URL to custom filters only. This prevent direct
	| manipulation of the image.
	|
	*/
	'filters_only' => false
);
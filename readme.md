# Laravel Image
Laravel Image is an image manipulation package for Laravel 4 based on the [PHP Imagine library](https://github.com/avalanche123/Imagine). It is inspired by [Croppa](https://github.com/BKWLD/croppa) as it use specially formatted urls to do the manipulations. It supports basic image manipulations such as resize, crop, rotation and flip. It also supports effects such as negative, grayscale, gamma, colorize and blur. You can also define custom filters for greater flexibility.

For example, if you have an image at this path:

    /uploads/photo.jpg

To create a 300x300 version of this image in black and white, you use the path:

    /uploads/photo-image(300x300-crop-grayscale).jpg

## Installation

#### Server Requirements:

* [gd](http://php.net/manual/en/book.image.php) or [Imagick](http://php.net/manual/fr/book.imagick.php) or [Gmagick](http://www.php.net/manual/fr/book.gmagick.php)
* [exif](http://php.net/manual/en/book.exif.php) - Required to get image format.

#### Installation:

Require the package via Composer in your `composer.json`.

    "folklore/laravel-image": "dev-master"

Run Composer to install or update the new requirement.

    $ php composer.phar install

or

    $ php composer.phar update

Add the service provider to your `app/config/app.php` file

    'Folklore\Image\ImageServiceProvider',

Add the facade to your `app/config/app.php` file

    'Image' => 'Folklore\Image\ImageFacade',

Publish the configuration file

    php artisan config:publish folklore/laravel-image

## Configuration



## Custom filters
You can create custom filters to group multiple manipulations in a single filter. Filters can be defined in the `app/start/global.php` file to ensure they are defined before any route is being executed.

```php
Image::filter('thumbnail',function($image,&$options)
{
	return $image->thumbnail(new Box(100,100))
					->effects()
					->grayscale();
});
```

This filter can be used by passing his name in the URL

    /uploads/photo-image(thumbnail).jpg

Or by using the method to generate an URL

```php
Image::url('/uploads/photo.jpg',array('thumbnail'));
```
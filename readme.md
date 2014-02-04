# Laravel Image
Laravel Image is an image manipulation package for Laravel 4 based on the [PHP Imagine library](https://github.com/avalanche123/Imagine). It is inspired by [Croppa](https://github.com/BKWLD/croppa) as it can use specially formatted urls to do the manipulations. It supports basic image manipulations such as resize, crop, rotation and flip. It also supports effects such as negative, grayscale, gamma, colorize and blur. You can also define custom filters for greater flexibility.

[![Build Status](https://travis-ci.org/Folkloreatelier/image.png?branch=master)](https://travis-ci.org/Folkloreatelier/image)

The main difference between this package and other image manipulation libraries is that you can use parameters directly in the url to manipulate the image. A manipulated version of the image is then saved in the same path as the original image, **creating a static version of the file and bypassing PHP for all future requests**.

For example, if you have an image at this URL:

    /uploads/photo.jpg

To create a 300x300 version of this image in black and white, you use the URL:

    /uploads/photo-image(300x300-crop-grayscale).jpg
    
To help you generate the URL to an image, you can use the `Image::url()` function

```php
Image::url('/uploads/photo.jpg',300,300,array('crop','grayscale'));
```

or

```html
<img src="<?=Image::url('/uploads/photo.jpg',300,300,array('crop','grayscale'))?>" />
```

Alternatively, you can programmatically manipulate images using the `Image::make()` method. It supports all the same options as the `Image::url()` method.

```php
Image::make('/uploads/photo.jpg',array(
	'width' => 300,
	'height' => 300,
	'greyscale' => true
))->save('/path/to/the/thumbnail.jpg');
```

or use directly the Imagine library

```php
$thumbnail = Image::open('/uploads/photo.jpg')
			->thumbnail(new Imagine\Image\Box(300,300));

$thumbnail->effects()->grayscale();
	
$thumbnail->save('/path/to/the/thumbnail.jpg');
```

## Installation

#### Dependencies:

* [Laravel 4.x](https://github.com/laravel/laravel)
* [Imagine 0.5.x](https://github.com/avalanche123/Imagine)

#### Server Requirements:

* [gd](http://php.net/manual/en/book.image.php) or [Imagick](http://php.net/manual/fr/book.imagick.php) or [Gmagick](http://www.php.net/manual/fr/book.gmagick.php)
* [exif](http://php.net/manual/en/book.exif.php) - Required to get image format.

#### Installation:

**1-** Require the package via Composer in your `composer.json`.
```json
{
	"require": {
		"folklore/image": "dev-master"
	}
}
```

**2-** Run Composer to install or update the new requirement.

```bash
$ composer install
```

or

```bash
$ composer update
```

**3-** Add the service provider to your `app/config/app.php` file
```php
'Folklore\Image\ImageServiceProvider',
```

**4-** Add the facade to your `app/config/app.php` file
```php
'Image' => 'Folklore\Image\Facades\Image',
```

**5-** Publish the configuration file

```bash
$ php artisan config:publish folklore/laravel-image
```

## Documentation
* [Complete documentation](https://github.com/Folkloreatelier/image/wiki)
* [Configuration options](https://github.com/Folkloreatelier/image/wiki/Configuration-options)
* [API reference](https://github.com/Folkloreatelier/image/wiki/Image-reference)

## Roadmap
Here are some features we would like to add in the future. Feel free to collaborate and improve this library.

* Crop position
* More built-in filters such as Brightness and Contrast
* Better image serving with more options and configuration
* Support for batch operations on multiple files

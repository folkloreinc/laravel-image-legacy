# Laravel Image
Laravel Image is an image manipulation package for Laravel 4 and 5 based on the [PHP Imagine library](https://github.com/avalanche123/Imagine). It is inspired by [Croppa](https://github.com/BKWLD/croppa) as it can use specially formatted urls to do the manipulations. It supports basic image manipulations such as resize, crop, rotation and flip. It also supports effects such as negative, grayscale, gamma, colorize and blur. You can also define custom filters for greater flexibility.

[![Latest Stable Version](https://poser.pugx.org/folklore/image/v/stable.svg)](https://packagist.org/packages/folklore/image)
[![Build Status](https://travis-ci.org/Folkloreatelier/laravel-image.png?branch=master)](https://travis-ci.org/Folkloreatelier/laravel-image)
[![Total Downloads](https://poser.pugx.org/folklore/image/downloads.svg)](https://packagist.org/packages/folklore/image)

The main difference between this package and other image manipulation libraries is that you can use parameters directly in the url to manipulate the image. A manipulated version of the image is then saved in the same path as the original image, **creating a static version of the file and bypassing PHP for all future requests**.

For example, if you have an image at this URL:

    /uploads/photo.jpg

To create a 300x300 version of this image in black and white, you use the URL:

    /uploads/photo-image(300x300-crop-grayscale).jpg
    
To help you generate the URL to an image, you can use the `Image::url()` method

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
	'grayscale' => true
))->save('/path/to/the/thumbnail.jpg');
```

or use directly the Imagine library

```php
$thumbnail = Image::open('/uploads/photo.jpg')
			->thumbnail(new Imagine\Image\Box(300,300));

$thumbnail->effects()->grayscale();
	
$thumbnail->save('/path/to/the/thumbnail.jpg');
```

## Features

This package use [Imagine](https://github.com/avalanche123/Imagine) for image manipulation. Imagine is compatible with GD2, Imagick, Gmagick and supports a lot of [features](http://imagine.readthedocs.org/en/latest/).

This package also provides some common filters ready to use ([more on this](https://github.com/Folkloreatelier/laravel-image/wiki/Image-filters)):
- Resize
- Crop (with position)
- Rotation
- Black and white
- Invert
- Gamma
- Blur
- Colorization
- Interlace

## Version Compatibility

 Laravel  | Image
:---------|:----------
 4.2.x    | 0.1.x
 5.0.x    | 0.2.x
 5.1.x    | 0.3.x
 5.2.x    | 0.3.x

## Installation

#### Dependencies:

* [Laravel 5.x](https://github.com/laravel/laravel)
* [Imagine 0.6.x](https://github.com/avalanche123/Imagine)

#### Server Requirements:

* [gd](http://php.net/manual/en/book.image.php) or [Imagick](http://php.net/manual/fr/book.imagick.php) or [Gmagick](http://www.php.net/manual/fr/book.gmagick.php)
* [exif](http://php.net/manual/en/book.exif.php) - Required to get image format.

#### Installation:

**1-** Require the package via Composer in your `composer.json`.
```json
{
	"require": {
		"folklore/image": "0.3.*"
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

**5-** Publish the configuration file and public files

```bash
$ php artisan vendor:publish --provider="Folklore\Image\ImageServiceProvider"
```

**6-** Review the configuration file

```
app/config/image.php
```

## Documentation
* [Complete documentation](https://github.com/Folkloreatelier/image/wiki)
* [Configuration options](https://github.com/Folkloreatelier/image/wiki/Configuration-options)

## Roadmap
Here are some features we would like to add in the future. Feel free to collaborate and improve this library.

* More built-in filters such as Brightness and Contrast
* More configuration when serving images
* Artisan command to manipulate images
* Support for batch operations on multiple files

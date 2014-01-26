# Laravel Image
Laravel Image is an image manipulation package for Laravel 4 based on the [PHP Imagine library](https://github.com/avalanche123/Imagine). It is inspired by [Croppa](https://github.com/BKWLD/croppa) as it can use specially formatted urls to do the manipulations. It supports basic image manipulations such as resize, crop, rotation and flip. It also supports effects such as negative, grayscale, gamma, colorize and blur. You can also define custom filters for greater flexibility.

## Installation

#### Dependencies:

* [Laravel 4.x](https://github.com/laravel/laravel)
* [Imagine 0.5.x](https://github.com/avalanche123/Imagine)

#### Server Requirements:

* [gd](http://php.net/manual/en/book.image.php) or [Imagick](http://php.net/manual/fr/book.imagick.php) or [Gmagick](http://www.php.net/manual/fr/book.gmagick.php)
* [exif](http://php.net/manual/en/book.exif.php) - Required to get image format.

#### Installation:

Require the package via Composer in your `composer.json`.
```json
    "folklore/laravel-image": "dev-master"
```

Run Composer to install or update the new requirement.

    $ composer install

or

    $ composer update

Add the service provider to your `app/config/app.php` file
```php
'Folklore\LaravelImage\LaravelImageServiceProvider',
```

Add the facade to your `app/config/app.php` file
```php
'Image' => 'Folklore\LaravelImage\Facades\Image',
```

Publish the configuration file

    $ php artisan config:publish folklore/laravel-image

## Configuration

## Usage

### URL based usage
Images can be manipulated by passing options directly in the URL.

For example, if you have an image at this path:

    /uploads/photo.jpg

To create a 300x300 version of this image in black and white, you use the path:

    /uploads/photo-image(300x300-crop-grayscale).jpg
    
To help you generate the URL to an image, you can use the `Image::url()` function

```php
Image::url('/uploads/photo.jpg',300,300,array('crop','grayscale'));
```

or

```html
<img src="<?=Image::url('/uploads/photo.jpg',300,300,array('crop','grayscale'))?>" />
```

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

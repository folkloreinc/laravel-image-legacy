Installation
================================================

#### Dependencies

* [Laravel 5.x](https://github.com/laravel/laravel)
* [Imagine 0.6.x|0.7.x](https://github.com/avalanche123/Imagine)

#### Server Requirements

* [gd](http://php.net/manual/en/book.image.php) or [Imagick](http://php.net/manual/fr/book.imagick.php) or [Gmagick](http://www.php.net/manual/fr/book.gmagick.php)
* [exif](http://php.net/manual/en/book.exif.php) - Required to get image format.

#### Installation

**1-** Require the package via Composer in your `composer.json`.
```json
$ composer require folklore/image
```

**2-** Add the service provider to your `app/config/app.php` file
```php
Folklore\Image\ImageServiceProvider::class,
```

**3-** Add the facade to your `app/config/app.php` file
```php
'Image' => Folklore\Image\Facades\Image::class,
```

**4-** Publish the configuration file and public files

```bash
$ php artisan vendor:publish --provider="Folklore\Image\ImageServiceProvider"
```

**5-** Review the configuration file

```
app/config/image.php
```

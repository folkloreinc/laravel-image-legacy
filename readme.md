# Laravel Image
Laravel Image is an image manipulation package for Laravel 4 based on the [PHP Imagine library](https://github.com/avalanche123/Imagine). It is inspired by [Croppa](https://github.com/BKWLD/croppa) as it use specially formatted urls to do the manipulations. It supports basic image manipulations such as resize, crop, rotation and flip. It also supports effects such as negative, grayscale, gamma, colorize and blur. You can also define custom filters for greater flexibility.

For example, if you have an image at this path:

    /uploads/photo.jpg

To create a 300x300 version of this image in black and white, you use the path:

    /uploads/photo-image(300x300-crop-grayscale).jpg

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
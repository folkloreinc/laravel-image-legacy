# Getting started
This package aims to simplify images manipulation by using the url of an image to determine what filters should be applied. This way, you don't need to implements the creation of various image formats, you simply call the url with the parameters and it automatically generates a new image for you. It also supports static caching so the next request will serve a static image instead of being handled by Laravel.

This works with two components, the Url Generator and the Image Router. First, you generate an url containing the filters you want with the Url Generator. The url is generated according to the format declared in `config/image.php` (look for `url`). When you request that url, a route is declared with a pattern corresponding to the url format and catch the request, applying the filters and responding the new image.

The package provides many built-in filters such as: Thumbnail, Rotation, Colorize, Grayscale, Blur, Negative, etc... and can easily be extended with custom filters.

## Basic Usage

### Displaying a thumbnail

```php
// Using the helper
<img src="{{ image_url('path/to/your/image.jpg', 100, 100, ['crop' => true]) }}" />

// Or using the facade
<img src="{{ Image::url('path/to/your/image.jpg', 100, 100, ['crop' => true]) }}" />
```

This will translate to: (assuming you haven't changed the default url format in `config/image.php`)
```html
<img src="/path/to/your/image-filters(100x100-crop).jpg" />
```

If you call this url, the router will catch the request and respond with a cropped 100x100 version of your image.

### Creating a new thumbnail

```php
// Using the facade
$thumbnail = Image::make('path/to/your/image.jpg', [
    'width' => 100,
    'height' => 100,
    'crop' => true
]);

// Using the helper
$thumbnail = image()->make('path/to/your/image.jpg', [
    'width' => 100,
    'height' => 100,
    'crop' => true
]);

// or with the shortcut
$thumbnail = image('path/to/your/image.jpg', [
    'width' => 100,
    'height' => 100,
    'crop' => true
]);

// Save the image on the default source (look for `'source'` in `config/image.php`)
image()->save($thumbnail, 'path/to/your/new-image.jpg');

// Or save it on the cloud source
image()->source('cloud')
    ->save($thumbnail, 'path/to/your/new-image.jpg');
```

### Definining a custom filter
While writing all the filters you want in the `url()` method works, you will probably want to declare a custom filter that "group" some values together. This way you can reuse it, and instead of remembering all the values, you can simply apply your filter, by it's name.

To do this, the simplest way is adding a custom filter as an array. In your `AppServiceProvider`, add the following lines in the `boot()` method.

```php
public function boot()
{
    parent::boot();

    // ...

    $this->app['image']->filter('thumbnail', [
        'width' => 100,
        'height' => 100,
        'crop' => true,
    ]);
}
```

Now you can simply use the filter by it's name
```php
// Using the helper
<img src="{{ image_url('path/to/your/image.jpg', 'thumbnail') }}" />

// Or using the facade
<img src="{{ Image::url('path/to/your/image.jpg', 'thumbnail') }}" />
```

Or combine with others
```php
<img src="{{ image_url('path/to/your/image.jpg', ['thumbnail', 'greyscale']) }}" />
```

## Advanced Usage

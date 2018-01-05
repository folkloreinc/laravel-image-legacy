Image
=====================

The Image Manager is the main entry point to interact with image.

You can access it with the facade:
```php
Image::method();
```

With the app helper:
```php
app('image')->method();
```

Or with the image helper:
```php
image()->method();

// Passing arguments is an alias to image()->make()
$image = image('path/to/image.jpg', 300, 300);
```

For this documentation, we will be using the facade, but any call can be changed to `app('image')`

#### Methods

- [`url($src, $width, $height, $filters)`](#url)
- [`pattern($config)`](#pattern)
- [`parse($path, $config)`](#parse)
- [`source($name)`](#source)
- [`extend($driver, $callback)`](#extend)
- [`routes($config)`](#routes)
- [`filter($name, $filter)`](#filter)
- [`setFilters($filters)`](#setFilters)
- [`getFilters()`](#getFilters)
- [`getFilter($name)`](#getFilter)
- [`hasFilter($name)`](#hasFilter)
- [`getImagineManager()`](#getImagineManager)
- [`getImagine()`](#getImagine)
- [`getSourceManager()`](#getSourceManager)
- [`getUrlGenerator()`](#getUrlGenerator)


---

<a name="url" id="url"></a>
### `url($src, $width = null, $height = null, $filters = array())`

Return an URL to process the image

#### Arguments
- `$src` `(string)` 
- `$width` `(integer|array|string)` The maximum width of the image. If an array or a string is passed, it is considered as the filters argument. 
- `$height` `(integer)` The maximum height of the image 
- `$filters` `(array|string)` An array of filters 

#### Return
`(string)` The generated url containing the filters.
        

#### Examples

```php
echo Image::url('path/to/image.jpg', 300, 300);
// '/path/to/image-filters(300x300).jpg'
```

You can also omit the size parameters and pass a filters array as the second argument
```php
echo Image::url('path/to/image.jpg', [
    'width' => 300,
    'height' => 300,
    'rotate' => 180
]);
// '/path/to/image-filters(300x300-rotate(180)).jpg'
```

There is also an `image_url()` helper available
```php
echo image_url('path/to/image.jpg', 300, 300);
```

You can change the format of the url by changing the configuration in the
`config/image.php` file or by passing the same options in the filters
array. (see [Url Generator](url-generator.md) for available options)


---

<a name="pattern" id="pattern"></a>
### `pattern($config = array())`

Return a pattern to match url

#### Arguments
- `$config` `(array)` Pattern configuration 

#### Return
`(string)` $pattern A regex matching the images url
        


---

<a name="parse" id="parse"></a>
### `parse($path, $config = array())`

Return an URL to process the image

#### Arguments
- `$path` `(string)` 
- `$config` 

#### Return
`(array)`


---

<a name="source" id="source"></a>
### `source($name = null)`

Get an ImageHandler for a specific source

#### Arguments
- `$name` `(string|null)` The name of the source 

#### Return
`(\Folklore\Image\Folklore\Image\ImageHandler)` The image manipulator object, bound the to specified source
        


---

<a name="extend" id="extend"></a>
### `extend($driver, $callback)`

Register a custom source creator Closure.

#### Arguments
- `$driver` `(string)` 
- `$callback` `(\Closure)` 

#### Return
`(\Folklore\Image\Image)`


---

<a name="routes" id="routes"></a>
### `routes($config = array())`

Map image routes on the Laravel Router

Add the routes from the file specified in the `config/image.php`
file at `routes.map`. You can pass a config array to override values
from the config or you can also pass a path to a routes file. This method
is automatically called if you have a path in your `config/image.php`.
To disable this you can set `routes.map` to null.


#### Arguments
- `$config` `(array|string)` A config array that will override values from the config/image.php. If you pass a string, it is considered as a path to a filtes containing routes. 

#### Return
`(array)`

#### Examples

Map the routes on the Laravel Router
```php
Image::routes();

// or with the helper
image()->routes();
```

Map a custom routes file
```php
Image::routes(base_path('routes/my-custom-file.php'));

// or an equivalent
Image::routes([
    'map' => base_path('routes/my-custom-file.php')
]);
```


---

<a name="filter" id="filter"></a>
### `filter($name, $filter)`

Register a new filter to the manager that can be used by the `Image::url()` and `Image::make()` method.

#### Arguments
- `$name` `(string)` The name of the filter 
- `$filter` `(\Closure|array|string|object)` The filter can be an array of filters, a closure that will get the Image object or a class path to a Filter class. (more info canbe found in the Filters documentation) 

#### Return
`(\Folklore\Image\Image)`

#### Examples

From an array
```php
// Declare the filter in a Service Provider
Image::filter('small', [
    'width' => 100,
    'height' => 100,
    'crop' => true,
]);

// Use it when making an image
$image = Image::make('path/to/image.jpg', [
    'small' => true,
]);

// or

$image = Image::make('path/to/image.jpg', 'small');
```

With a closure
```php
// Declare the filter in a Service Provider
Image::filter('circle', function ($image, $color)
{
    // See Imagine documentation for the Image object
    // (https://imagine.readthedocs.io/en/latest/index.html)
    $color = $image->palette()->color($color);
    $image->draw()
         ->ellipse(new Point(0, 0), new Box(300, 225), $color);
    return $image;
});

// Use it when making an image
$image = Image::make('path/to/image.jpg', [
    'circle' => '#FFCC00',
]);
```

With a class path
```php
// Declare the filter in a Service Provider
Image::filter('custom', \App\Filters\CustomFilter::class);

// Use it when making an image
$image = Image::make('path/to/image.jpg', [
    'custom' => true,
]);
```


---

<a name="setFilters" id="setFilters"></a>
### `setFilters($filters)`

Set all filters

#### Arguments
- `$filters` `(array)` 

#### Return
`(\Folklore\Image\Image)`


---

<a name="getFilters" id="getFilters"></a>
### `getFilters()`

Get all filters

#### Return
`(array)`


---

<a name="getFilter" id="getFilter"></a>
### `getFilter($name)`

Get a filter

#### Arguments
- `$name` `(string)` 

#### Return
`(array)`


---

<a name="hasFilter" id="hasFilter"></a>
### `hasFilter($name)`

Check if a filter exists

#### Arguments
- `$name` `(string)` 

#### Return
`(boolean)`


---

<a name="getImagineManager" id="getImagineManager"></a>
### `getImagineManager()`

Get the imagine manager

#### Return
`(\Folklore\Image\ImageManager)`


---

<a name="getImagine" id="getImagine"></a>
### `getImagine()`

Get the imagine instance from the manager

#### Return
`(\Imagine\Image\ImagineInterface)`


---

<a name="getSourceManager" id="getSourceManager"></a>
### `getSourceManager()`

Get the source manager

#### Return
`(\Folklore\Image\SourceManager)`


---

<a name="getUrlGenerator" id="getUrlGenerator"></a>
### `getUrlGenerator()`

Get the url generator

#### Return
`(\Folklore\Image\UrlGenerator)`


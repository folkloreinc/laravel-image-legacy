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

- [`source($name)`](#source)
- [`extend($driver, $callback)`](#extend)
- [`url($src, $width, $height, $filters)`](#url)
- [`pattern($config)`](#pattern)
- [`parse($path, $config)`](#parse)
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

### <a name="source" id="source"></a> `source($name = null)`

Get an ImageManipulator for a specific source

#### Arguments
- `$name` `(string|null)` 

#### Return
`(\Folklore\Image\Folklore\Image\ImageManipulator)`

---

### <a name="extend" id="extend"></a> `extend($driver, $callback)`

Register a custom source creator Closure.

#### Arguments
- `$driver` `(string)` 
- `$callback` `(\Closure)` 

#### Return
`(\Folklore\Image\Image)`

---

### <a name="url" id="url"></a> `url($src, $width = null, $height = null, $filters = array())`

Return an URL to process the image

#### Arguments
- `$src` `(string)` 
- `$width` `(integer|array|string)` The maximum width of the image. If anarray or a string is passed, it is considered as the filters argument.
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

### <a name="pattern" id="pattern"></a> `pattern($config = array())`

Return a pattern to match url

#### Arguments
- `$config` `(array)` Pattern configuration

#### Return
`(string)` $pattern   A regex matching the images url
        

---

### <a name="parse" id="parse"></a> `parse($path, $config = array())`

Return an URL to process the image

#### Arguments
- `$path` `(string)` 
- `$config` 

#### Return
`(array)`

---

### <a name="routes" id="routes"></a> `routes($config = array())`

Map image routes on the Laravel Router

#### Arguments
- `$config` `(array|string)` Config for the routes group, you can also passa string to require a specific file in the route group

#### Return
`(array)`

---

### <a name="filter" id="filter"></a> `filter($name, $filter)`

Register a filter

#### Arguments
- `$name` `(string)` 
- `$filter` `(\Closure|array|string|object)` 

#### Return
`(\Folklore\Image\Image)`

---

### <a name="setFilters" id="setFilters"></a> `setFilters($filters)`

Set all filters

#### Arguments
- `$filters` `(array)` 

#### Return
`(\Folklore\Image\Image)`

---

### <a name="getFilters" id="getFilters"></a> `getFilters()`

Get all filters

#### Return
`(array)`

---

### <a name="getFilter" id="getFilter"></a> `getFilter($name)`

Get a filter

#### Arguments
- `$name` `(string)` 

#### Return
`(array)`

---

### <a name="hasFilter" id="hasFilter"></a> `hasFilter($name)`

Check if a filter exists

#### Arguments
- `$name` `(string)` 

#### Return
`(boolean)`

---

### <a name="getImagineManager" id="getImagineManager"></a> `getImagineManager()`

Get the imagine manager

#### Return
`(\Folklore\Image\ImageManager)`

---

### <a name="getImagine" id="getImagine"></a> `getImagine()`

Get the imagine instance from the manager

#### Return
`(\Imagine\Image\ImagineInterface)`

---

### <a name="getSourceManager" id="getSourceManager"></a> `getSourceManager()`

Get the source manager

#### Return
`(\Folklore\Image\SourceManager)`

---

### <a name="getUrlGenerator" id="getUrlGenerator"></a> `getUrlGenerator()`

Get the url generator

#### Return
`(\Folklore\Image\UrlGenerator)`

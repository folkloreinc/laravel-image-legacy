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
- [`url($src, $width, $height, $options)`](#url)
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

### <a name="source" id="source"></a> `source($name)`

Get an ImageManipulator for a specific source

#### Arguments
- `(string|null)` `$name` 

#### Return
---

### <a name="extend" id="extend"></a> `extend($driver, $callback)`

Register a custom source creator Closure.

#### Arguments
- `(string)` `$driver` 
- `(\Closure)` `$callback` 

#### Return
---

### <a name="url" id="url"></a> `url($src, $width, $height, $options)`

Return an URL to process the image

#### Arguments
- `(string)` `$src` 
- `(integer)` `$width` 
- `(integer)` `$height` 
- `(array)` `$options` 

#### Return
---

### <a name="pattern" id="pattern"></a> `pattern($config)`

Return an URL to process the image

#### Arguments
- `()` `$config` 

#### Return
---

### <a name="parse" id="parse"></a> `parse($path, $config)`

Return an URL to process the image

#### Arguments
- `(string)` `$path` 
- `()` `$config` 

#### Return
---

### <a name="routes" id="routes"></a> `routes($config)`

Map image routes on the Laravel Router

#### Arguments
- `(array|string)` `$config` 

#### Return
---

### <a name="filter" id="filter"></a> `filter($name, $filter)`

Register a filter

#### Arguments
- `(string)` `$name` 
- `(\Closure|array|string|object)` `$filter` 

#### Return
---

### <a name="setFilters" id="setFilters"></a> `setFilters($filters)`

Set all filters

#### Arguments
- `(array)` `$filters` 

#### Return
---

### <a name="getFilters" id="getFilters"></a> `getFilters()`

Get all filters

#### Arguments

#### Return
---

### <a name="getFilter" id="getFilter"></a> `getFilter($name)`

Get a filter

#### Arguments
- `(string)` `$name` 

#### Return
---

### <a name="hasFilter" id="hasFilter"></a> `hasFilter($name)`

Check if a filter exists

#### Arguments
- `(string)` `$name` 

#### Return
---

### <a name="getImagineManager" id="getImagineManager"></a> `getImagineManager()`

Get the imagine manager

#### Arguments

#### Return
---

### <a name="getImagine" id="getImagine"></a> `getImagine()`

Get the imagine instance from the manager

#### Arguments

#### Return
---

### <a name="getSourceManager" id="getSourceManager"></a> `getSourceManager()`

Get the source manager

#### Arguments

#### Return
---

### <a name="getUrlGenerator" id="getUrlGenerator"></a> `getUrlGenerator()`

Get the url generator

#### Arguments

#### Return

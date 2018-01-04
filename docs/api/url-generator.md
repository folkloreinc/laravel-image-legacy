UrlGenerator
=====================

The Url Generator is used to creates url that contains filters. It is also used by the router to generates the pattern that image url should matched to be handled by manipulator.

With the `app()` helper
```php
$urlGenerator = app('image.url');
$url = urlGenerator->make('path/to/image.png', 300, 300, ['crop']);
```

You can also use it directly from the Image facade:
```php
$url = Image::url('path/to/image.png', 300, 300, ['crop']); // Alias to $urlGenerator->make();
```

Or with the `image_url()` helper
```php
$url = image_url('path/to/image.png', 300, 300, ['crop']); // Alias to $urlGenerator->make();
```

## Configuration
In the `config/image.php` file, you will find an `'url'` section containing these options:

```php
[
    // The format of the url that will be generated. The `{filters}` placeholder
    // will be replaced by the filters according to the `filters_format`.
    'format' => '{dirname}/{basename}{filters}.{extension}',

    // The format of the filters that will replace `{filters}` in the
    // url `format` above. The `{filter}` placeholder will be replaced by
    // each filter according to the `filter_format` and joined
    // by the `filter_separator`.
    'filters_format' => '-filters({filter})',

    // The format of a filter.
    'filter_format' => '{key}({value})',

    // The separator for each filter
    'filter_separator' => '-'
]
```

When you pass filters and size to the `Image::url()` or `$urlGenerator->make()` method, it uses these options to format the url in the following sequence.

1. Every filter is formatted into a string according to `filter_format`. If the filter is a boolean and is `true` or the value is `null`, only the `{key}` is used.
2. The results is joined with `filter_separator`.
3. The results replace the `{filter}` placeholder in `filters_format`
4. Finally the results (if present) replace `{filters}` placeholder in `format` and you have access to different parts of the path you passed to compose the final url.

This configuration is also used when creating an image route. The `{pattern}` placeholder in the route is replaced with a router pattern matching the format of the url according to this config.

You can override this configuration when creating an url or a route.
```php
echo $urlGenerator->make('path/to/image.jpg', [
    'width' => 300,
    'height' => 300,
    'pattern' => [
        'filters_format' => '-filters-{filters}'
    ]
]);
// '/path/to/image-filters-300x300-rotate(180).jpg'

// Or when creating a route
$router->image('{pattern}', [
    'pattern' => [
        'filters_format' => '-filters-{filters}'
    ]
])
```

#### Methods

- [`make($src, $width, $height, $filters)`](#make)
- [`pattern($config)`](#pattern)
- [`parse($path, $config)`](#parse)
- [`setFormat($value)`](#setFormat)
- [`getFormat()`](#getFormat)
- [`setFilterFormat($value)`](#setFilterFormat)
- [`getFilterFormat()`](#getFilterFormat)
- [`setFilterSeparator($value)`](#setFilterSeparator)
- [`getFilterSeparator()`](#getFilterSeparator)
- [`setFiltersFormat($value)`](#setFiltersFormat)
- [`getFiltersFormat()`](#getFiltersFormat)
- [`setPlaceholdersPatterns($value)`](#setPlaceholdersPatterns)
- [`getPlaceholdersPatterns()`](#getPlaceholdersPatterns)


---

### <a name="make" id="make"></a> `make($src, $width = null, $height = null, $filters = array())`

Make an URL from the filters passed as argument

#### Arguments
- `$src` `(string)` The source path
- `$width` `(integer|array)` The width of the image, or and array of filters
- `$height` `(integer)` The height of the image
- `$filters` `(array)` An array of filters and config filters

#### Return
`(string)` The url containing the filters
        

---

### <a name="pattern" id="pattern"></a> `pattern($config = array())`

Get the URL pattern

#### Arguments
- `$config` `(array)` Config options to change the format and filters_format

#### Return
`(string)`

---

### <a name="parse" id="parse"></a> `parse($path, $config = array())`

Parse an url

#### Arguments
- `$path` `(string)` The path to be parsed
- `$config` `(array)` Config options to change the pattern and filters_format

#### Return
`(array)`

---

### <a name="setFormat" id="setFormat"></a> `setFormat($value)`

#### Arguments
- `$value` 


---

### <a name="getFormat" id="getFormat"></a> `getFormat()`


---

### <a name="setFilterFormat" id="setFilterFormat"></a> `setFilterFormat($value)`

#### Arguments
- `$value` 


---

### <a name="getFilterFormat" id="getFilterFormat"></a> `getFilterFormat()`


---

### <a name="setFilterSeparator" id="setFilterSeparator"></a> `setFilterSeparator($value)`

#### Arguments
- `$value` 


---

### <a name="getFilterSeparator" id="getFilterSeparator"></a> `getFilterSeparator()`


---

### <a name="setFiltersFormat" id="setFiltersFormat"></a> `setFiltersFormat($value)`

#### Arguments
- `$value` 


---

### <a name="getFiltersFormat" id="getFiltersFormat"></a> `getFiltersFormat()`


---

### <a name="setPlaceholdersPatterns" id="setPlaceholdersPatterns"></a> `setPlaceholdersPatterns($value)`

#### Arguments
- `$value` 


---

### <a name="getPlaceholdersPatterns" id="getPlaceholdersPatterns"></a> `getPlaceholdersPatterns()`


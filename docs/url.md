Url Generator
================================================
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

#### Configuration
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

- [`make($path, $width, $height, $filters)`](#make)
- [`pattern($config)`](#pattern)
- [`parse($path, $config)`](#parse)

---

## <a name="make" id="make"></a>`make($path, $width = null, $height = null, $filters = [])`
Generates an url containing the filters, according to the url format in the config

##### Arguments
- `(string)` `$path` The path of the image.
- `(int | array)` `$width` The width of the image. It can be null, or can also be an array of filters.
- `(int)` `$height` The height of the image.
- `(array)` `$filters` An array of filters

##### Return
`(string)` The generated url

##### Examples

```php
$urlGenerator = app('image.url');
echo $urlGenerator->make('path/to/image.jpg', 300, 300);
// '/path/to/image-filters(300x300).jpg'
```

You can also omit the size parameters and pass a filters array as the second argument
```php
echo $urlGenerator->make('path/to/image.jpg', [
    'width' => 300,
    'height' => 300,
    'rotate' => 180
]);
// '/path/to/image-filters(300x300-rotate(180)).jpg'
```

You can also override the pattern config
```php
echo $urlGenerator->make('path/to/image.jpg', [
    'width' => 300,
    'height' => 300,
    'pattern' => [
        'filters_format' => '-filters-{filters}'
    ]
]);
// '/path/to/image-filters-300x300-rotate(180).jpg'
```


---

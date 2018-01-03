Image Router
================================================

Here is the default content of the `routes/images.php`
```php
$router->image('{pattern}', [
    // A domain that will be used by the route
    'domain' => null,

    // Any middleware you want ot add on the route.
    'middleware' => [],

    // The name of the source to get the image. If it is set to null,
    // it will use the default source.
    'source' => null,

    // Allow to specify a size as filter
    'allow_size' => true,

    // Allow to specify filters in url. You can also set this to
    // an array of specific filters to restrict this route to those
    // filters.
    //
    // Example: ["negative"]
    'allow_filters' => true,

    // Disallow some filters. Can be set to an array of filters.
    'disallow_filters' => false,

    // Any pattern options you want to override.
    'pattern' => [],

    // You can specify base filters that will be applied to any image
    // on this route.
    'filters' => [
        // 'width' => 100
    ],

    // Expires header in seconds
    'expires' => 3600 * 24 * 31,

    // Any headers you want to add on the image
    'headers' => [],

    // Cache the file on local machine
    'cache' => true,

    // The path where the images are cached. It is defined to public
    // path, so the files would be statically served on next request.
    'cache_path' => public_path()

]);
```

## Methods

- [`image($path, $actions)`](#image)

---

<a name="image" id="image"></a>
#### `image($path, $config = [])`
Generates an url containing the filters, according to the url format in the config

##### Arguments
- `(string)` `$path` The path of the route. It must contain `{pattern}`.
- `(array)` `$config` An config of the route

##### Return
`(Illuminate\Routing\Route)` The route

##### Examples

```php
$router = app('router');
$router->image('/thumbnail/{pattern}', [
    'filters' => [
        'width' => 100,
        'height' => 100,
        'crop' => true
    ]
]);
```

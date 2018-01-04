<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Image Filters
    |--------------------------------------------------------------------------
    |
    | The list of filters you can use when making an image or generating an url.
    | There is some built-in filters, and you can add or replace any. It is also
    | possible to declare a filter with an array or a closure instead of a Filter
    | Class.
    |
    */
    'filters' => [
        'blur' => \Folklore\Image\Filters\Blur::class,
        'colorize' => \Folklore\Image\Filters\Colorize::class,
        'gamma' => \Folklore\Image\Filters\Gamma::class,
        'grayscale' => \Folklore\Image\Filters\Grayscale::class,
        'interlace' => \Folklore\Image\Filters\Interlace::class,
        'negative' => \Folklore\Image\Filters\Negative::class,
        'rotate' => \Folklore\Image\Filters\Rotate::class,
        'resize' => \Folklore\Image\Filters\Resize::class
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Source
    |--------------------------------------------------------------------------
    |
    | This option define the default source to be used by the Image facade. The
    | source determine where the image files are read and saved.
    |
    */
    'source' => 'public',

    /*
    |--------------------------------------------------------------------------
    | Sources
    |--------------------------------------------------------------------------
    |
    | The list of sources where you store images.
    |
    | Supported driver: "local", "filesystem"
    |
    */
    'sources' => [

        'public' => [
            // The local driver use a local path on the machine.
            'driver' => 'local',

            // The path where the images are stored.
            'path' => public_path()
        ],

        'cloud' => [
            // The filesystem driver lets you use the filesystem from laravel.
            'driver' => 'filesystem',

            // The filesystem disk where the images are stored.
            'disk' => 'public',

            // The path on the disk where the images are stored. If set to null,
            // it will start from the root.
            'path' => null,

            // Cache the file on local machine. It can be useful for remote files.
            'cache' => true,

            // The path where you want to put cached files
            'cache_path' => storage_path('image/cache')
        ]

    ],

    /*
    |--------------------------------------------------------------------------
    | URL Generator
    |--------------------------------------------------------------------------
    |
    | The URL Generator configuration is used when generating an image url
    | and by the router to generate a pattern for catching image requests.
    | These are the defaults values and you can overide it in each routes or
    | when generating an url using the `pattern` parameter.
    |
    */
    'url' => [
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
        'filter_separator' => '-',

        // This is the regex that will replace any placeholders in the option 'format'.
        // They are used when the route pattern is generated and added to the
        // Laravel Router to match image request.
        'placeholders_patterns' => [
            'host' => '(.*?)?',
            'dirname' => '(.*?)?',
            'basename' => '([^\/\.]+?)',
            'filename' => '([^\/]+)',
            'extension' => '(jpeg|jpg|gif|png)',
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | Default configuration for image routes. See routes/image.php
    |
    */
    'routes' => [
        // Path to the routes file that will be automatically loaded. Set to null
        // to prevent auto-loading of routes.
        'map' => base_path('routes/images.php'),

        // Default domain for routes
        'domain' => null,

        // Default namespace for controller
        'namespace' => null,

        // Default middlewares for routes
        'middleware' => [],

        // The controller serving the images
        'controller' => '\Folklore\Image\Http\ImageController@serve',

        // The name of the pattern that will be added to the Laravel Router.
        'pattern_name' => 'image_pattern',

        // The middleware used when a route as `cache` enabled
        'cache_middleware' => 'image.middleware.cache'
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default image "driver" used by Imagine library
    | to manipulate images.
    |
    | Supported: "gd", "imagick", "gmagick"
    |
    */
    'driver' => 'gd',

    /*
    |--------------------------------------------------------------------------
    | Memory limit
    |--------------------------------------------------------------------------
    |
    | When manipulating an image, the memory limit is increased to this value
    |
    */
    'memory_limit' => '128M',

];

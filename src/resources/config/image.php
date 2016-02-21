<?php



return array(

    /*
    |--------------------------------------------------------------------------
    | Default Image Driver
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

    /*
    |--------------------------------------------------------------------------
    | Host
    |--------------------------------------------------------------------------
    |
    | The http host where the image are served. Used by the Image::url() method
    | to generate the right URL.
    |
    */
    'host' => '',

    /*
    |--------------------------------------------------------------------------
    | Source directories
    |--------------------------------------------------------------------------
    |
    | A list a directories to look for images
    |
    */
    'src_dirs' => array(
        public_path()
    ),

    /*
    |--------------------------------------------------------------------------
    | URL parameter
    |--------------------------------------------------------------------------
    |
    | The URL parameter that will be appended to your image filename containing
    | all the options for image manipulation. You have to put {options} where
    | you want options to be placed. Keep in mind that this parameter is used
    | in an url so all characters should be URL safe.
    |
    | Default: -image({options})
    |
    | Example: /uploads/photo-image(300x300-grayscale).jpg
    |
    */
    'url_parameter' => '-image({options})',

    /*
    |--------------------------------------------------------------------------
    | URL parameter separator
    |--------------------------------------------------------------------------
    |
    | The URL parameter separator is used to build the parameters string
    | that will replace {options} in url_parameter
    |
    | Default: -
    |
    | Example: /uploads/photo-image(300x300-grayscale).jpg
    |
    */
    'url_parameter_separator' => '-',

    /*
    |--------------------------------------------------------------------------
    | Serve image
    |--------------------------------------------------------------------------
    |
    | If true, a route will be added to catch image containing the
    | URL parameter above.
    |
    */
    'serve' => true,

    /*
    |--------------------------------------------------------------------------
    | Serve route
    |--------------------------------------------------------------------------
    |
    | 
    |
    */
    'serve_route' => '{image_path}',

    /*
    |--------------------------------------------------------------------------
    | Serve custom Filters only
    |--------------------------------------------------------------------------
    |
    | Restrict options in url to custom filters only. This prevent direct
    | manipulation of the image.
    |
    */
    'serve_custom_filters_only' => false,

    /*
    |--------------------------------------------------------------------------
    | Write image
    |--------------------------------------------------------------------------
    |
    | When serving an image, write the manipulated image in the same directory
    | as the original image so the next request will serve this static file
    |
    */
    'write_image' => false,

    /*
    |--------------------------------------------------------------------------
    | Write path
    |--------------------------------------------------------------------------
    |
    | By default, the manipulated images are saved in the same path as the
    | as the original image, you can override this path here
    |
    */
    'write_path' => null,
    
    /*
    |--------------------------------------------------------------------------
    | Proxy
    |--------------------------------------------------------------------------
    |
    | This enable or disable the proxy route
    |
    */
    'proxy' => false,

    /*
    |--------------------------------------------------------------------------
    | Proxy route
    |--------------------------------------------------------------------------
    |
    | The route that will be used to 
    |
    */
    'proxy_route' => '{image_path}',

    /*
    |--------------------------------------------------------------------------
    | Proxy route domain
    |--------------------------------------------------------------------------
    |
    | If you wind to bind your route to a specific domain.
    |
    */
    'proxy_route_domain' => null,
    
    /*
    |--------------------------------------------------------------------------
    | Proxy filesystem
    |--------------------------------------------------------------------------
    |
    | The filesystem from which the file will be proxied
    |
    */
    'proxy_filesystem' => 'cloud',
    
    /*
    |--------------------------------------------------------------------------
    | Proxy temporary directory
    |--------------------------------------------------------------------------
    |
    | Write the manipulated image back to the file system
    |
    */
    'proxy_write_image' => true,
    
    /*
    |--------------------------------------------------------------------------
    | Proxy cache
    |--------------------------------------------------------------------------
    |
    | Cache the response of the proxy on the local filesystem. The proxy will be
    | cached using the laravel cache driver.
    |
    */
    'proxy_cache' => true,
    
    /*
    |--------------------------------------------------------------------------
    | Proxy cache filesystem
    |--------------------------------------------------------------------------
    |
    | If you want the proxy to cache files on a filesystem instead of using the
    | cache driver.
    |
    */
    'proxy_cache_filesystem' => null,
    
    /*
    |--------------------------------------------------------------------------
    | Proxy temporary path
    |--------------------------------------------------------------------------
    |
    | The temporary path where the manipulated file are saved.
    |
    */
    'proxy_tmp_path' => sys_get_temp_dir(),

);

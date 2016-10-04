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
    | Pattern
    |--------------------------------------------------------------------------
    |
    | The pattern that is used to match routes that will be handled by the
    | ImageController. The {parameters} will be remplaced by the url parameters
    | pattern.
    |
    */
    'pattern' => '^(.*){parameters}\.(jpg|jpeg|png|gif|JPG|JPEG|PNG|GIF)$',

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
    | If you want to restrict the route to a specific domain.
    |
    */
    'serve_domain' => null,

    /*
    |--------------------------------------------------------------------------
    | Serve route
    |--------------------------------------------------------------------------
    |
    | The route where image are served
    |
    */
    'serve_route' => '{image_pattern}',

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
    | Serve expires
    |--------------------------------------------------------------------------
    |
    | The expires headers that are sent when sending image.
    |
    */
    'serve_expires' => (3600*24*31),

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
    | Proxy expires
    |--------------------------------------------------------------------------
    |
    | The expires headers that are sent when proxying image. Defaults to 
    | serve_expires
    |
    */
    'proxy_expires' => null,

    /*
    |--------------------------------------------------------------------------
    | Proxy route
    |--------------------------------------------------------------------------
    |
    | The route that will be used to serve proxied image
    |
    */
    'proxy_route' => '{image_proxy_pattern}',
    
    

    /*
    |--------------------------------------------------------------------------
    | Proxy route pattern
    |--------------------------------------------------------------------------
    |
    | The proxy route pattern that will be available as `image_proxy_pattern`.
    | If the value is null, the default image pattern will be used.
    |
    */
    'proxy_route_pattern' => null,

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
    | Proxy cache expiration
    |--------------------------------------------------------------------------
    |
    | The number of minuts that a proxied image can stay in cache. If the value
    | is -1, the image is cached forever.
    |
    */
    'proxy_cache_expiration' => 60*24,
    
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

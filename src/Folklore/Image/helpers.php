<?php

if (! function_exists('image')) {
    /**
     * Throw an HttpException with the given data.
     *
     * @param  string   $path The path of the image
     * @param  array    $options The manipulations to apply on the image
     * @return \Imagine\Image\ImageInterface $image
     */
    function image($path = null, $options = [])
    {
        $image = app('image');
        return is_null($path) ? $image : $image->make($path, $options);
    }
}

if (! function_exists('image_url')) {
    /**
     * Generate an image url
     *
     * @param  string     $src
     * @param  string  $message
     * @param  array   $headers
     * @return string $url
     */
    function image_url($src, $width = null, $height = null, $options = [])
    {
        return app('image')->url($src, $width, $height, $options);
    }
}

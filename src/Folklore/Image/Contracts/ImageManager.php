<?php

namespace Folklore\Image\Contracts;

interface ImageManager
{
    /**
     * Return an URL to process the image
     *
     * @param string $src
     * @param int|array|string $width The maximum width of the image. If an
     * array or a string is passed, it is considered as the filters argument.
     * @param int $height The maximum height of the image
     * @param array|string $filters An array of filters
     *
     * @return string The generated url containing the filters.
     */
    public function url($src, $width = null, $height = null, $filters = []);

    /**
     * Map image routes on the Laravel Router
     *
     * @param  array|string  $config A config array that will override values
     * from the `config/image.php`. If you pass a string, it is considered as
     * a path to a filtes containing routes.
     * @return array
     */
    public function routes($config = []);

    /**
     * Get the source manager
     *
     * @return \Folklore\Image\SourceManager
     */
    public function getSourceManager();

    /**
     * Get the source manager
     *
     * @return \Folklore\Image\Contracts\FiltersManager
     */
    public function getFiltersManager();

    /**
     * Get the url generator
     *
     * @return \Folklore\Image\Contracts\UrlGenerator
     */
    public function getUrlGenerator();

    /**
     * Get the imagine manager
     *
     * @return \Folklore\Image\ImagineManager
     */
    public function getImagineManager();

    /**
     * Get the imagine instance from the manager
     *
     * @return \Imagine\Image\ImagineInterface
     */
    public function getImagine();
}

<?php

namespace Folklore\Image\Contracts;

use Closure;

interface FiltersManager
{
    /**
     * Register a new filter to the manager that can be used by the `Image::url()` and `Image::make()` method.
     *
     * @param string $name The name of the filter
     * @param Closure|array|string|object $filter The filter
     * @return $this
     */
    public function filter($name, $filter);

    /**
     * Set all filters
     *
     * @param  array    $filters
     * @return $this
     */
    public function setFilters($filters);

    /**
     * Get all filters
     *
     * @return array
     */
    public function getFilters();

    /**
     * Get a filter
     *
     * @param  string    $name
     * @return array|Closure|string|object
     */
    public function getFilter($name);

    /**
     * Check if a filter exists
     *
     * @param  string    $name
     * @return boolean
     */
    public function hasFilter($name);
}

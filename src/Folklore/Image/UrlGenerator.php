<?php namespace Folklore\Image;

use Folklore\Image\Exception\ParseException;
use Illuminate\Foundation\Application;
use Folklore\Image\Contracts\UrlGenerator as UrlGeneratorContract;

class UrlGenerator implements UrlGeneratorContract
{
    protected $image;

    protected $host = null;

    protected $format = '{dirname}/{basename}{filters}.{extension}';

    protected $filtersFormat = '-image({filter})';

    protected $filterFormat = '{key}({value})';

    protected $filterSeparator = '-';

    public function __construct(Image $image, Router $router)
    {
        $this->image = $image;
        $this->router = $router;
    }

    /**
     * Make an URL from the filters passed as argument
     *
     * @param   string      $src        The source path
     * @param   int|array   $width      The width of the image, or and array of filters
     * @param   int         $height     The height of the image
     * @param   array       $filters    An array of filters and config filters
     * @return  string      The url containing the filters
     */
    public function make($src, $width = null, $height = null, $filters = [])
    {
        // Don't allow empty path
        if (empty($src)) {
            return;
        }

        // Extract the path from a URL if a URL was provided instead of a path
        $src = parse_url($src, PHP_URL_PATH);

        // If width filter is an array, use it as filters
        if (is_array($width)) {
            $filters = $width;
            $width = null;
            $height = null;
        }

        // Separate config from filters
        $configKeys = ['route', 'format', 'filters_format', 'filter_format', 'filter_separator'];
        $config = array_only($filters, $configKeys);
        $filters = array_except($filters, $configKeys);

        // Get config from route, if specified
        if (isset($config['route'])) {
            $route = $this->router->getRoute($config['route']);
            $routeConfig = array_get($route, 'url', []);
            $config = array_merge($routeConfig, $config);
        }

        // Create the url filters. Add the size first and the filters after
        $urlFilters = array();

        $width = $width !== null ? $width:array_get($filters, 'width', -1);
        $height = $height !== null ? $height:array_get($filters, 'height', -1);
        if ($width !== -1 || $height !== -1) {
            $urlFilters[] = ($width !== -1 ? $width:'_').'x'.($height !== -1 ? $height:'_');
            unset($filters['width']);
            unset($filters['height']);
        }

        if ($filters && is_array($filters)) {
            $filterFormat = array_get($config, 'filter_format');
            $filtersParts = $this->getUrlPartsFromFilters($filters, $filterFormat);
            $urlFilters = array_merge($urlFilters, $filtersParts);
        }

        // Create the parameter with filters
        $filtersFormat = array_get($config, 'filters_format');
        $filterSeparator = array_get($config, 'filter_separator');
        $filtersParameter = $this->getFiltersParameter($urlFilters, $filtersFormat, $filterSeparator);

        // Build the url by replacing the placeholders
        $srcParts = pathinfo($src);
        $placeholders = [
            'host' => rtrim(array_get($config, 'host', ''), '/'),
            'dirname' => trim($srcParts['dirname'], '/'),
            'basename' => $srcParts['filename'],
            'filename' => $srcParts['filename'].'.'.$srcParts['extension'],
            'extension' => $srcParts['extension'],
            'filters' => $filtersParameter
        ];
        $url = array_get($config, 'format', $this->getFormat());
        foreach ($placeholders as $key => $replace) {
            $url = preg_replace('/\{\s*'.$key.'\s*\}/i', $replace, $url);
        }

        // If a route is specified, use it to generate the url.
        if (isset($config['route'])) {
            $routeName = $this->router->getRouteName($config['route']);
            $routeUrl = route($routeName, ['__URL__']);
            return str_replace('__URL__', ltrim($url, '/'), $routeUrl);
        }

        return $url;
    }

    /**
     * Get the URL pattern
     *
     * @param array $config Config options to change the format and filters_format
     * @return string
     */
    public function pattern($config = [])
    {
        $pattern = array_get($this->patternAndMatches($config), 'pattern');
        return $pattern;
    }

    protected function patternAndMatches($config = [])
    {
        $filtersFormat = array_get($config, 'filters_format', $this->getFiltersFormat());
        $filtersPattern = preg_replace('#\\\{\s*filter\s*\\\}#', '(.*?)', preg_quote($filtersFormat, '#'));

        $placeholders = [
            'host' => '(.*?)?',
            'dirname' => '(.*?)?',
            'basename' => '([^\/\.]+?)',
            'filename' => '([^\/]+)',
            'extension' => '([^\.]+)',
            'filters' => '('.$filtersPattern.')?'
        ];
        $format = array_get($config, 'format', $this->getFormat());
        $pattern = preg_quote($format, '#');
        $pattern = preg_replace('#(\\\{\s*dirname\s*\\\})\/#i', '$1\/?', $pattern);

        // Get the positions of each placeholders in the path
        $positions = [];
        foreach ($placeholders as $key => $replace) {
            if (preg_match('#\\\{\s*('.$key.')\s*\\\}#', $pattern, $matches, PREG_OFFSET_CAPTURE)) {
                $positions[$key] = $matches[1][1];
            }
        }
        asort($positions);
        $keys = array_keys($positions);
        $filtersPosition = array_search('filters', $keys);

        // Build the pattern and get the matches position of each placeholder.
        $matches = [];
        foreach ($placeholders as $key => $replace) {
            $index = array_search($key, $keys);
            if ($index === false) {
                continue;
            }
            $index += 1;
            $pattern = preg_replace('#\\\{\s*'.$key.'\s*\\\}#', $replace, $pattern);
            if ($key === 'filters' || ($filtersPosition !== false && $index > $filtersPosition)) {
                $index += 1;
            }
            $matches[$key] = $index;
        }

        return [
            'pattern' => '^'.$pattern.'$',
            'matches' => $matches
        ];
    }

    /**
     * Parse an url
     *
     * @param string $path The path to be parsed
     * @param array $config Config options to change the pattern and filters_format
     * @return array
     */
    public function parse($path, $config = [])
    {
        // Check if the path matche the pattern,
        // otherwise return the original path.
        $filters = array();
        $patternAndMatches = $this->patternAndMatches($config);
        $pattern = array_get($patternAndMatches, 'pattern');
        $patternMatches = array_get($patternAndMatches, 'matches');
        if (preg_match('#'.$pattern.'#i', $path, $matches)) {
            //Remove the filters from the path
            $filtersPath = $matches[$patternMatches['filters']];
            $filtersFormat = array_get($config, 'filters_format', $this->getFiltersFormat());
            $filtersFormatPath = preg_replace('#\{\s*filter\s*\}#', $filtersPath, $filtersFormat);
            $path = preg_replace('#'.preg_quote($filtersFormatPath, '#').'\/?#', '', $path);
            //Parse the filters
            $filters = $this->parseFilters($filtersPath, $config);
        }

        return [
            'path' => $path,
            'filters' => $filters
        ];
    }

    protected function getFiltersParameter($filters, $filtersFormat = null, $filterSeparator = null)
    {
        if (!sizeof($filters)) {
            return '';
        }

        if ($filtersFormat === null) {
            $filtersFormat = $this->getFiltersFormat();
        }

        if ($filterSeparator === null) {
            $filterSeparator = $this->getFilterSeparator();
        }

        $urlFilters = implode($filterSeparator, $filters);
        return preg_replace('/\{\s*filter\s*\}/i', $urlFilters, $filtersFormat);
    }

    protected function getUrlPartsFromFilters($filters, $format = null)
    {
        if ($format === null) {
            $format = $this->getFilterFormat();
        }

        // If the key as no value or is equal to
        // true or null, only the key is added.
        $parts = [];
        foreach ($filters as $key => $val) {
            if (is_numeric($key)) {
                $parts[] = $val;
            } elseif ($val === true || $val === null) {
                $parts[] = $key;
            } else {
                $val = is_array($val) ? implode(',', $val):$val;
                $filter = preg_replace('/\{\s*key\s*\}/i', $key, $format);
                $filter = preg_replace('/\{\s*value\s*\}/i', $val, $filter);
                $parts[] = $filter;
            }
        }

        return $parts;
    }

    /**
     * Parse filters from url string
     *
     * @param  string   $path The path contaning all the filters
     * @param  array    $config Configuration options for the parsing
     * @return array
     */
    protected function parseFilters($path, $config = [])
    {
        if (empty($path)) {
            return [];
        }

        $filters = array();

        $filterFormat = array_get($config, 'filter_format', $this->getFilterFormat());
        $filterPattern = preg_replace('#\\\{\s*key\s*\\\}#i', '(\w+)', preg_quote($filterFormat, '#'));
        $filterPattern = preg_replace('#\\\{\s*value\s*\\\}#i', '([a-z0-9\,\.]+)', $filterPattern);

        // Loop through the params and make the options key value pairs
        $filterSeparator = array_get($config, 'filter_separator', $this->getFilterSeparator());
        $filterParts = explode($filterSeparator, $path);
        foreach ($filterParts as $filter) {
            $matches = null;
            $withValueMatches = null;
            //Check if the filter is a size or is properly formatted
            if (preg_match('/([0-9]+|_)x([0-9]+|_)/i', $filter, $matches)) {
                $filters['width'] = $matches[1] === '_' ? null:(int)$matches[1];
                $filters['height'] = $matches[2] === '_' ? null:(int)$matches[2];
                continue;
            } elseif (!preg_match('#^(\w+)$#i', $filter, $withoutValueMatches) &&
                !preg_match('/^'.$filterPattern.'$/i', $filter, $withValueMatches)
            ) {
                throw new ParseException('Filter "'.$filter.'" has invalid pattern.');
            }
            $key = array_get($withoutValueMatches, 1, array_get($withValueMatches, 1));

            // If the filter is a custom filter, check if it's a closure or an array.
            // If it's an array, merge it with filters
            $imagefilter = $this->image->getFilter($key);
            $value = isset($withValueMatches[2]) ? $withValueMatches[2]:null;
            if (isset($imagefilter) && is_array($imagefilter)) {
                $filters = array_merge($filters, $imagefilter);
            } else {
                if ($value) {
                    $filters[$key] = strpos($value, ',') === true ? explode(',', $value):$value;
                } else {
                    $filters[$key] = true;
                }
            }
        }

        return $filters;
    }

    public function setFormat($value)
    {
        $this->format = $value;
    }

    public function getFormat()
    {
        return $this->format;
    }

    public function setFilterFormat($value)
    {
        $this->filterFormat = $value;
    }

    public function getFilterFormat()
    {
        return $this->filterFormat;
    }

    public function setFilterSeparator($value)
    {
        $this->filterSeparator = $value;
    }

    public function getFilterSeparator()
    {
        return $this->filterSeparator;
    }

    public function setFiltersFormat($value)
    {
        $this->filtersFormat = $value;
    }

    public function getFiltersFormat()
    {
        return $this->filtersFormat;
    }
}

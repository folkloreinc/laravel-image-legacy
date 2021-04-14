<?php namespace Folklore\Image;

use Folklore\Image\Exception\ParseException;
use Illuminate\Routing\Router as BaseRouter;
use Illuminate\Support\Arr;
use Folklore\Image\Contracts\UrlGenerator as UrlGeneratorContract;
use Folklore\Image\Contracts\FiltersManager as FiltersManagerContract;

class UrlGenerator implements UrlGeneratorContract
{
    protected $router;

    protected $filters;

    protected $host = null;

    protected $format = '{dirname}/{basename}{filters}.{extension}';

    protected $filtersFormat = '-filters({filter})';

    protected $filterFormat = '{key}({value})';

    protected $filterSeparator = '-';

    protected $placeholdersPatterns = [
        'host' => '(.*?)?',
        'dirname' => '(.*?)?',
        'basename' => '([^\/\.]+?)',
        'filename' => '([^\/]+)',
        'extension' => '([^\.]+)',
    ];

    public function __construct(BaseRouter $router, FiltersManagerContract $filters)
    {
        $this->router = $router;
        $this->filters = $filters;
    }

    /**
     * Generates an url containing the filters, according to the url format
     * in the config.
     *
     * Examples:
     *
     * ```php
     * $urlGenerator = app('image.url');
     * echo $urlGenerator->make('path/to/image.jpg', 300, 300);
     * // '/path/to/image-filters(300x300).jpg'
     * ```

     * You can also omit the size parameters and pass a filters array as the second argument
     * ```php
     * echo $urlGenerator->make('path/to/image.jpg', [
     *     'width' => 300,
     * 'height' => 300,
     * 'rotate' => 180
     * ]);
     * // '/path/to/image-filters(300x300-rotate(180)).jpg'
     * ```

     * You can also override the pattern config
     * ```php
     * echo $urlGenerator->make('path/to/image.jpg', [
     *     'width' => 300,
     *     'height' => 300,
     *     'pattern' => [
     *         'filters_format' => '-filters-{filters}'
     *     ]
     * ]);
     * // '/path/to/image-filters-300x300-rotate(180).jpg'
     * ```
     *
     * @param string $src The source path
     * @param int|array $width The width of the image, or and array of filters
     * @param int $height The height of the image
     * @param array $filters An array of filters and config filters
     * @return string The url containing the filters
     */
    public function make($src, $width = null, $height = null, $filters = [])
    {
        // Don't allow empty path
        if (empty($src)) {
            return;
        }

        // Extract the path from a URL if a URL was provided instead of a path
        $srcParts = parse_url($src);
        $scheme = data_get($srcParts, 'scheme', 'http');
        $port = data_get($srcParts, 'port', null);
        $host = data_get($srcParts, 'host');
        if (!is_null($host) && !is_null($port) && $port !== 80) {
            $host .= ':'.$port;
        }
        $path = data_get($srcParts, 'path');

        // If width is an array, use it as filters
        if (is_array($width)) {
            $filters = $width;
            $width = null;
            $height = null;
        }
        if ($width !== null) {
            $filters['width'] = $width;
        }
        if ($height !== null) {
            $filters['height'] = $height;
        }

        // Separate config from filters
        $configKeys = ['route', 'pattern', 'host'];
        $config = Arr::only($filters, $configKeys);

        // Get config from route, if specified
        if (isset($config['route'])) {
            $route = $this->router
                ->getRoutes()
                ->getByName($config['route']);
            $routeActions = isset($route) && is_object($route) ? $route->getAction() : $route;
            $routeConfig = is_array($routeActions) ? data_get($routeActions, 'image', []) : [];
            $routeDomain = is_array($routeActions) ? data_get($routeActions, 'domain', null) : null;
            if (!is_null($routeDomain)) {
                $routeConfig['host'] = $routeDomain;
            }
            $config = array_merge($routeConfig, $config);
        }

        // Create the url parameters from filters
        $filters = Arr::except($filters, $configKeys);
        $filterFormat = data_get($config, 'pattern.filter_format');
        $urlParameters = $this->getParametersFromFilters($filters, $filterFormat);

        // Create the parameter with filters
        $filtersFormat = data_get($config, 'pattern.filters_format');
        $filterSeparator = data_get($config, 'pattern.filter_separator');
        $filtersParameter = $this->getFiltersParameter($urlParameters, $filtersFormat, $filterSeparator);

        // Build the url by replacing the placeholders
        $srcParts = pathinfo($path);
        $placeholders = [
            'host' => data_get($config, 'host', $host),
            'dirname' => $srcParts['dirname'] !== '.' ? trim($srcParts['dirname'], '/'):'',
            'basename' => $srcParts['filename'],
            'filename' => $srcParts['filename'].'.'.$srcParts['extension'],
            'extension' => $srcParts['extension'],
            'filters' => $filtersParameter
        ];
        $url = data_get($config, 'pattern.format', $this->getFormat());
        foreach ($placeholders as $key => $replace) {
            $url = preg_replace(
                '/\{\s*'.$key.'\s*\}/i',
                !is_null($replace) ? $replace : '',
                $url
            );
        }

        // If a route is specified, use it to generate the url.
        if (isset($config['route'])) {
            $routeUrl = route($config['route'], ['__URL__']);
            return str_replace('__URL__', ltrim($url, '/'), $routeUrl);
        }

        // If there was an host
        $host = '/';
        if (!is_null($placeholders['host'])) {
            $host = $placeholders['host'];
            $host = !preg_match('/^https?\:\/\//i', $url) ?
                $scheme.'://'.$host.'/' : '';
        }

        return $host.ltrim($url, '/');
    }

    /**
     * Generates a pattern, according to the url format in the config.
     *
     * Examples:
     * ```php
     * $urlGenerator = app('image.url');
     * $pattern = $urlGenerator->pattern();
     * preg_match('^'.$pattern.'$', '/path/to/image-filters(300x300).jpg'); // true
     * ```
     *
     * @param array $config Config options to change the format
     * @return string The pattern to match urls
     */
    public function pattern($config = [])
    {
        $pattern = data_get($this->patternAndMatches($config), 'pattern');
        return $pattern;
    }

    /**
     * Parse an url according to the format in the config and extract
     * the path and the filters
     *
     * Examples:
     *
     * ```php
     * $urlGenerator = app('image.url');
     * $url = '/path/to/image-filters(300x300).jpg';
     * $path = $urlGenerator->parse($url);
     * // $path['path'] = '/path/to/image.jpg';
     * // $path['filters'] = ['width' => 300, 'height' => 300];
     * ```
     *
     * @param string $path The path to be parsed
     * @param array $config Config options to change the format
     * @return array An array containing the `path` and `filters`
     */
    public function parse($path, $config = [])
    {
        // Check if the path matche the pattern,
        // otherwise return the original path.
        $filters = array();
        $patternAndMatches = $this->patternAndMatches($config);
        $pattern = data_get($patternAndMatches, 'pattern');
        $patternMatches = data_get($patternAndMatches, 'matches');
        if (preg_match('#'.$pattern.'#i', $path, $matches)) {
            //Remove the filters from the path
            $filtersPath = $matches[$patternMatches['filters']];
            $filtersFormat = data_get($config, 'filters_format', $this->getFiltersFormat());
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

    /**
     * Get the pattern and all matches with index
     *
     * @param array $config Config options to change the format
     * @return array An array containing the `pattern` and `matches`
     */
    protected function patternAndMatches($config = [])
    {
        $filtersFormat = data_get($config, 'filters_format', $this->getFiltersFormat());
        $filtersPattern = preg_replace('#\\\{\s*filter\s*\\\}#', '(.*?)', preg_quote($filtersFormat, '#'));

        $placeholdersPatterns = data_get($config, 'placeholders_patterns', $this->getPlaceholdersPatterns());
        $placeholders = array_merge([
            'filters' => '('.$filtersPattern.')?'
        ], $placeholdersPatterns);
        $format = data_get($config, 'format', $this->getFormat());
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
     * Get the parameters to be used in an url according to the filter_format
     *
     * @param array $filters The array of filters
     * @param string $format The format of each filter parameter
     * @return array $parameters
     */
    protected function getParametersFromFilters($filters, $format = null)
    {
        if ($format === null) {
            $format = $this->getFilterFormat();
        }

        $parameters = [];

        // Size parameters are treated separatly
        $width = data_get($filters, 'width', -1);
        $height = data_get($filters, 'height', -1);
        if ($width !== -1 || $height !== -1) {
            $parameters[] = ($width !== -1 ? $width:'_').'x'.($height !== -1 ? $height:'_');
            $filters = Arr::except($filters, ['width', 'height']);
        }

        // If the key as no value or is equal to
        // true or null, only the key is added.
        foreach ($filters as $key => $val) {
            if (is_numeric($key)) {
                $parameters[] = $val;
            } elseif ($val === true || $val === null) {
                $parameters[] = $key;
            } else {
                $val = is_array($val) ? implode(',', $val) : $val;
                $filter = preg_replace('/\{\s*key\s*\}/i', $key, $format);
                $filter = preg_replace('/\{\s*value\s*\}/i', $val, $filter);
                $parameters[] = $filter;
            }
        }

        return $parameters;
    }

    /**
     * Join the parameters into the filters parameter according to filters_format
     * and filter_separator
     *
     * @param array $parameters The array of filters parameters
     * @param string $filtersFormat The format of the filters parameter
     * @param string $filterSeparator The separator for each filter parameters
     * @return string $parameter
     */
    protected function getFiltersParameter($parameters, $filtersFormat = null, $filterSeparator = null)
    {
        if (!sizeof($parameters)) {
            return '';
        }

        if ($filtersFormat === null) {
            $filtersFormat = $this->getFiltersFormat();
        }

        if ($filterSeparator === null) {
            $filterSeparator = $this->getFilterSeparator();
        }

        $urlFilters = implode($filterSeparator, $parameters);
        return preg_replace('/\{\s*filter\s*\}/i', $urlFilters, $filtersFormat);
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

        $filterFormat = data_get($config, 'filter_format', $this->getFilterFormat());
        $filterPattern = preg_replace('#\\\{\s*key\s*\\\}#i', '(\w+)', preg_quote($filterFormat, '#'));
        $filterPattern = preg_replace('#\\\{\s*value\s*\\\}#i', '([a-z0-9\,\.]+)', $filterPattern);

        // Loop through the params and make the options key value pairs
        $filterSeparator = data_get($config, 'filter_separator', $this->getFilterSeparator());
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
            $key = data_get($withoutValueMatches, 1, data_get($withValueMatches, 1));

            // If the filter is a custom filter, check if it's a closure or an array.
            // If it's an array, merge it with filters
            $imagefilter = $this->filters->getFilter($key);
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

    public function setPlaceholdersPatterns($value)
    {
        $this->placeholdersPatterns = $value;
    }

    public function getPlaceholdersPatterns()
    {
        return $this->placeholdersPatterns;
    }
}

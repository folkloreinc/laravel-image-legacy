<?php namespace Folklore\Image;

use Folklore\Image\Exception\ParseException;
use Folklore\Image\Contracts\UrlGenerator as UrlGeneratorContract;

class UrlGenerator implements UrlGeneratorContract
{
    protected $image;
    
    protected $host = null;
    
    protected $pattern = '^(.*){parameters}\.(jpg|jpeg|png|gif|JPG|JPEG|PNG|GIF)$';
    
    protected $parametersFormat = '-image({options})';
    
    protected $optionFormat = '{key}({value})';
    
    protected $optionsSeparator = '-';
    
    public function __construct(Image $image, Router $router)
    {
        $this->image = $image;
        $this->router = $router;
    }
    
    /**
     * Make an URL from the options passed as argument
     *
     * @param   string      $src        The source path
     * @param   int|array   $width      The width of the image, or and array of options
     * @param   int         $height     The height of the image
     * @param   array       $options    An array of filters and config options
     * @return  string      The url containing the options parameter
     */
    public function make($src, $width = null, $height = null, $options = [])
    {
        // Don't allow empty strings
        if (empty($src)) {
            return;
        }

        // Extract the path from a URL if a URL was provided instead of a path
        $src = parse_url($src, PHP_URL_PATH);

        // If width parameter is an array, use it as options
        if (is_array($width)) {
            $options = $width;
            $width = null;
            $height = null;
        }
        
        $configKeys = ['route', 'parameters_format', 'option_format', 'options_separator'];
        $config = array_only($options, $configKeys);
        $options = array_except($options, $configKeys);
        
        // Get config from route, if specified
        if (isset($config['route'])) {
            $route = $this->router->getRoute($config['route']);
            $routeConfig = array_only($route, $configKeys);
            $config = array_merge($routeConfig, $config);
        }

        // Get size
        $width = $width !== null ? $width:array_get($options, 'width', -1);
        $height = $height !== null ? $height:array_get($options, 'height', -1);

        // Create the url options
        $urlOptions = array();

        // Add size only if present
        if ($width !== -1 || $height !== -1) {
            $urlOptions[] = ($width !== -1 ? $width:'_').'x'.($height !== -1 ? $height:'_');
        }

        // Add options
        if ($options && is_array($options)) {
            $optionFormat = array_get($config, 'option_format');
            $urlOptions += $this->getUrlPartsFromOptions($options, $optionFormat);
        }

        // Create the url parameter
        $parametersFormat = array_get($config, 'parameters_format');
        $optionsSeparator = array_get($config, 'options_separator');
        $parameter = $this->getUrlParameterFromOptions($urlOptions, $parametersFormat, $optionsSeparator);

        // Get the host and path
        $host = rtrim(array_get($config, 'host', ''), '/');
        $path = $this->getPathFromSourceAndParameter($src, $parameter);
        
        // If a route is specified, it is used to generate the url.
        if (isset($config['route'])) {
            $routeName = $this->router->getRouteName($config['route']);
            $url = route($routeName, ['__PATH__']);
            return str_replace('__PATH__', $path, $url);
        }

        return $host.'/'.$path;
    }

    /**
     * Get the URL pattern
     *
     * @param array $config Config options to change the pattern and parameters_format
     * @return string
     */
    public function pattern($config = [])
    {
        $pattern = array_get($config, 'pattern', $this->getPattern());
        $parametersFormat = array_get($config, 'parameters_format', $this->getParametersFormat());
        $format = preg_quote($parametersFormat);
        $formatPattern = preg_replace('/\\\{\s*options\s*\\\}/', '(.*?)?', $format);
        $pattern = preg_replace('/\{\s*parameters\s*\}/', $formatPattern, $pattern);

        return $pattern;
    }
    
    /**
     * Parse an url
     *
     * @param string $path The path to be parsed
     * @param array $config Config options to change the pattern and parameters_format
     * @return array
     */
    public function parse($path, $config = [])
    {
        $options = array();
        if (preg_match('#'.$this->pattern($config).'#i', $path, $matches)) {
            //Get path and options
            $path = $matches[1].'.'.$matches[3];
            
            // Parse options from path
            $optionsPath = $matches[2];
            $options = $this->parseOptions($optionsPath, $config);
        }

        return [
            'path' => $path,
            'options' => $options
        ];
    }
    
    protected function getUrlParameterFromOptions($options, $parametersFormat = null, $optionsSeparator = null)
    {
        if (!sizeof($options)) {
            return '';
        }
        
        if ($parametersFormat === null) {
            $parametersFormat = $this->getParametersFormat();
        }
        
        if ($optionsSeparator === null) {
            $optionsSeparator = $this->getOptionsSeparator();
        }
        
        $urlOptions = implode($optionsSeparator, $options);
        return preg_replace('/\{\s*options\s*\}/i', $urlOptions, $parametersFormat);
    }
    
    protected function getPathFromSourceAndParameter($src, $parameter)
    {
        $srcParts = pathinfo($src);
        $path = [];
        
        //Get directory
        $dir = trim($srcParts['dirname'], '/');
        if (!empty($dir)) {
            $path[] = $dir;
        }
        
        //Get filename
        $filename = array();
        $filename[] = $srcParts['filename'].$parameter;
        if (!empty($srcParts['extension'])) {
            $filename[] = $srcParts['extension'];
        }
        $path[] = implode('.', $filename);
        
        return implode('/', $path);
    }
    
    protected function getUrlPartsFromOptions($options, $format = null)
    {
        if ($format === null) {
            $format = $this->getOptionFormat();
        }
        
        // If the key as no value or is equal to
        // true or null, only the key is added.
        $parts = [];
        foreach ($options as $key => $val) {
            if (is_numeric($key)) {
                $parts[] = $val;
            } elseif ($val === true || $val === null) {
                $parts[] = $key;
            } else {
                $val = is_array($val) ? implode(',', $val):$val;
                $option = preg_replace('/\{\s*key\s*\}/', $key, $format);
                $option = preg_replace('/\{\s*value\s*\}/', $val, $option);
                $parts[] = $option;
            }
        }
        
        return $parts;
    }

    /**
     * Parse options from url string
     *
     * @param  string   $optionPath The path contaning all the options
     * @param  array    $config Configuration options for the parsing
     * @return array
     */
    protected function parseOptions($path, $config = [])
    {
        $options = array();
        
        $optionFormat = array_get($config, 'option_format', $this->getOptionFormat());
        $optionPattern = preg_replace('/\\\{\s*key\s*\\\}/i', '(\w+)', preg_quote($optionFormat));
        $optionPattern = preg_replace('/\\\{\s*value\s*\\\}/i', '([a-z0-9\,\.]+)', $optionPattern);
        
        // Loop through the params and make the options key value pairs
        $optionsSeparator = array_get($config, 'options_separator', $this->getOptionsSeparator());
        $optionParts = explode($optionsSeparator, $path);
        foreach ($optionParts as $option) {
            //Check if the option is a size or is properly formatted
            if (preg_match('#([0-9]+|_)x([0-9]+|_)#i', $option, $matches)) {
                $options['width'] = $matches[1] === '_' ? null:(int)$matches[1];
                $options['height'] = $matches[2] === '_' ? null:(int)$matches[2];
                continue;
            } elseif (!preg_match('#^(\w+)$#i', $option, $withoutValueMatches) &&
                !preg_match('#^'.$optionPattern.'$#i', $option, $withValueMatches)
            ) {
                throw new ParseException('Option "'.$option.'" has invalid pattern.');
            }
            
            $key = array_get($withoutValueMatches, 1, array_get($withValueMatches, 1));

            // If the option is a custom filter, check if it's a closure or an array.
            // If it's an array, merge it with options
            $filter = $this->image->getFilter($key);
            $value = isset($withValueMatches[2]) ? $withValueMatches[2]:null;
            if (isset($filter)) {
                if (is_object($filter) && is_callable($filter)) {
                    $arguments = $value ? explode(',', $value):true;
                    $options[$key] = $arguments;
                } elseif (is_array($filter)) {
                    $options = array_merge($options, $filter);
                }
            } else {
                if ($value) {
                    $options[$key] = strpos($value, ',') === true ? explode(',', $value):$value;
                } else {
                    $options[$key] = true;
                }
            }
        }

        // Merge the options with defaults
        return $options;
    }

    /**
     * Check if an option key is valid by checking if a
     * $this->filterName() method is present or if a custom filter
     * is registered.
     *
     * @param  string  $key Option key to check
     * @return boolean
     */
    protected function isValidOption($key)
    {
        if (in_array($key, array('crop','width','height'))) {
            return true;
        }

        return $this->image->hasFilter($key);
    }
    
    public function setPattern($value)
    {
        $this->pattern = $value;
    }
    
    public function getPattern()
    {
        return $this->pattern;
    }
    
    public function setOptionFormat($value)
    {
        $this->optionFormat = $value;
    }
    
    public function getOptionFormat()
    {
        return $this->optionFormat;
    }
    
    public function setOptionsSeparator($value)
    {
        $this->optionsSeparator = $value;
    }
    
    public function getOptionsSeparator()
    {
        return $this->optionsSeparator;
    }
    
    public function setParametersFormat($value)
    {
        $this->parametersFormat = $value;
    }
    
    public function getParametersFormat()
    {
        return $this->parametersFormat;
    }
}

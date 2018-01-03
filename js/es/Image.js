import _classCallCheck from 'babel-runtime/helpers/classCallCheck';
import _createClass from 'babel-runtime/helpers/createClass';
import URL from 'url-parse';
import parsePath from 'parse-filepath';
import isEmpty from 'lodash/isEmpty';
import isObject from 'lodash/isObject';
import isArray from 'lodash/isArray';
import isString from 'lodash/isString';
import pick from 'lodash/pick';
import omit from 'lodash/omit';
import get from 'lodash/get';
import trimStart from 'lodash/trimStart';
import trimEnd from 'lodash/trimEnd';
import trim from 'lodash/trim';

var Image = function () {
    function Image(opts) {
        _classCallCheck(this, Image);

        this.options = Object.assign({
            format: '{dirname}/{basename}{filters}.{extension}',
            filters_format: '-image({filter})',
            filter_format: '{key}({value})',
            filter_separator: '-'
        }, opts);
    }

    _createClass(Image, [{
        key: 'url',
        value: function url(path, width, height, opts) {
            // Don't allow empty path
            if (isEmpty(path)) {
                return '';
            }

            // Extract the path from a URL if a URL was provided instead of a path
            var src = new URL(path).pathname;
            var options = Object.assign({}, this.options, isObject(width) && !isArray(width) ? width : null, isObject(opts) && !isArray(opts) ? opts : null);
            if (isArray(width) || isString(width)) {
                (isString(width) ? [width] : width).forEach(function (key) {
                    options[key] = true;
                });
            }
            if (isArray(opts) || isString(opts)) {
                (isString(opts) ? [opts] : opts).forEach(function (key) {
                    options[key] = true;
                });
            }

            // Separate config from filters
            var configKeys = ['route', 'format', 'filters_format', 'filter_format', 'filter_separator'];
            var config = pick(options, configKeys);
            var filters = omit(options, configKeys);
            var filterFormat = get(config, 'filter_format');
            var filtersFormat = get(config, 'filters_format');
            var filterSeparator = get(config, 'filter_separator');

            if (width !== null && !isObject(width) && !isArray(width) && !isString(width)) {
                filters.width = width;
            }
            if (height !== null) {
                filters.height = height;
            }

            var urlParameters = this.getParametersFromFilters(filters, filterFormat);
            var filtersParameter = this.getFiltersParameter(urlParameters, filtersFormat, filterSeparator);

            // Build the url by replacing the placeholders
            var srcParts = parsePath(src);
            var placeholders = {
                host: trimEnd(get(config, 'host', ''), '/'),
                dirname: srcParts.dirname !== '.' ? trim(srcParts.dirname, '/') : '',
                basename: srcParts.name,
                filename: '' + srcParts.name + srcParts.extname,
                extension: srcParts.extname.replace(/^\./, ''),
                filters: filtersParameter
            };

            var url = Object.keys(placeholders).reduce(function (fullUrl, key) {
                return fullUrl.replace(new RegExp('{\\s*' + key + '\\s*}', 'gi'), placeholders[key]);
            }, get(config, 'format'));

            return '/' + trimStart(url, '/');
        }
    }, {
        key: 'getParametersFromFilters',
        value: function getParametersFromFilters(allFilters, filterFormat) {
            var format = filterFormat || this.options.filter_format;
            var parameters = [];

            // Size parameters are treated separatly
            var width = get(allFilters, 'width', -1);
            var height = get(allFilters, 'height', -1);
            var filters = omit(allFilters, ['width', 'height']);
            if (width !== -1 || height !== -1) {
                parameters.push((width !== -1 ? width : '_') + 'x' + (height !== -1 ? height : '_'));
            }

            // If the key as no value or is equal to
            // true or null, only the key is added.
            Object.keys(filters).forEach(function (key) {
                var val = filters[key];
                if (val === true || val === null) {
                    parameters.push(key);
                } else {
                    var strVal = isArray(val) ? val.join(',') : val;
                    var parameter = format.replace(/\{\s*key\s*\}/i, key).replace(/\{\s*value\s*\}/i, strVal);
                    parameters.push(parameter);
                }
            });

            return parameters;
        }
    }, {
        key: 'getFiltersParameter',
        value: function getFiltersParameter(parameters, filtersFormat, filterSeparator) {
            if (isEmpty(parameters)) {
                return '';
            }

            var format = filtersFormat || this.options.filters_format;
            var separator = filterSeparator || this.options.filter_separator;
            var urlFilters = parameters.join(separator);
            return format.replace(/\{\s*filter\s*\}/i, urlFilters);
        }
    }]);

    return Image;
}();

export default Image;
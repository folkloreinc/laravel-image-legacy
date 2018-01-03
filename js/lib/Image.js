'use strict';

Object.defineProperty(exports, "__esModule", {
    value: true
});

var _extends2 = require('babel-runtime/helpers/extends');

var _extends3 = _interopRequireDefault(_extends2);

var _classCallCheck2 = require('babel-runtime/helpers/classCallCheck');

var _classCallCheck3 = _interopRequireDefault(_classCallCheck2);

var _createClass2 = require('babel-runtime/helpers/createClass');

var _createClass3 = _interopRequireDefault(_createClass2);

var _urlParse = require('url-parse');

var _urlParse2 = _interopRequireDefault(_urlParse);

var _parseFilepath = require('parse-filepath');

var _parseFilepath2 = _interopRequireDefault(_parseFilepath);

var _isEmpty = require('lodash/isEmpty');

var _isEmpty2 = _interopRequireDefault(_isEmpty);

var _isObject = require('lodash/isObject');

var _isObject2 = _interopRequireDefault(_isObject);

var _isArray = require('lodash/isArray');

var _isArray2 = _interopRequireDefault(_isArray);

var _isString = require('lodash/isString');

var _isString2 = _interopRequireDefault(_isString);

var _pick = require('lodash/pick');

var _pick2 = _interopRequireDefault(_pick);

var _omit = require('lodash/omit');

var _omit2 = _interopRequireDefault(_omit);

var _get = require('lodash/get');

var _get2 = _interopRequireDefault(_get);

var _trimStart = require('lodash/trimStart');

var _trimStart2 = _interopRequireDefault(_trimStart);

var _trimEnd = require('lodash/trimEnd');

var _trimEnd2 = _interopRequireDefault(_trimEnd);

var _trim = require('lodash/trim');

var _trim2 = _interopRequireDefault(_trim);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var Image = function () {
    function Image(opts) {
        (0, _classCallCheck3.default)(this, Image);

        this.options = (0, _extends3.default)({
            format: '{dirname}/{basename}{filters}.{extension}',
            filters_format: '-image({filter})',
            filter_format: '{key}({value})',
            filter_separator: '-'
        }, opts);
    }

    (0, _createClass3.default)(Image, [{
        key: 'url',
        value: function url(path, width, height, opts) {
            // Don't allow empty path
            if ((0, _isEmpty2.default)(path)) {
                return '';
            }

            // Extract the path from a URL if a URL was provided instead of a path
            var src = new _urlParse2.default(path).pathname;
            var options = (0, _extends3.default)({}, this.options, (0, _isObject2.default)(width) ? width : null, (0, _isObject2.default)(opts) ? opts : null);
            if ((0, _isArray2.default)(width) || (0, _isString2.default)(width)) {
                ((0, _isString2.default)(width) ? [width] : width).forEach(function (key) {
                    options[key] = true;
                });
            }
            if ((0, _isArray2.default)(opts) || (0, _isString2.default)(opts)) {
                ((0, _isString2.default)(opts) ? [opts] : opts).forEach(function (key) {
                    options[key] = true;
                });
            }

            // Separate config from filters
            var configKeys = ['route', 'format', 'filters_format', 'filter_format', 'filter_separator'];
            var config = (0, _pick2.default)(options, configKeys);
            var filters = (0, _omit2.default)(options, configKeys);
            var filterFormat = (0, _get2.default)(config, 'filter_format');
            var filtersFormat = (0, _get2.default)(config, 'filters_format');
            var filterSeparator = (0, _get2.default)(config, 'filter_separator');

            if (width !== null && !(0, _isObject2.default)(width) && !(0, _isArray2.default)(width)) {
                filters.width = width;
            }
            if (height !== null) {
                filters.height = height;
            }

            var urlParameters = this.getParametersFromFilters(filters, filterFormat);
            var filtersParameter = this.getFiltersParameter(urlParameters, filtersFormat, filterSeparator);

            // Build the url by replacing the placeholders
            var srcParts = (0, _parseFilepath2.default)(src);
            var placeholders = {
                host: (0, _trimEnd2.default)((0, _get2.default)(config, 'host', ''), '/'),
                dirname: srcParts.dirname !== '.' ? (0, _trim2.default)(srcParts.dirname, '/') : '',
                basename: srcParts.basename,
                filename: srcParts.basename + '.' + srcParts.extname,
                extension: srcParts.extname,
                filters: filtersParameter
            };

            var url = Object.keys(placeholders).reduce(function (fullUrl, key) {
                return fullUrl.replace(new RegExp('{\\s*' + key + '\\s*}', 'gi'), placeholders[key]);
            }, (0, _get2.default)(config, 'format'));

            return '/' + (0, _trimStart2.default)(url, '/');
        }
    }, {
        key: 'getParametersFromFilters',
        value: function getParametersFromFilters(allFilters, filterFormat) {
            var format = filterFormat || this.options.filter_format;
            var parameters = [];

            // Size parameters are treated separatly
            var width = (0, _get2.default)(allFilters, 'width', -1);
            var height = (0, _get2.default)(allFilters, 'height', -1);
            var filters = (0, _omit2.default)(allFilters, ['width', 'height']);
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
                    var strVal = (0, _isArray2.default)(val) ? val.join(',') : val;
                    var parameter = format.replace(/\{\s*key\s*\}/i, key).replace(/\{\s*value\s*\}/i, strVal);
                    parameters.push(parameter);
                }
            });

            return parameters;
        }
    }, {
        key: 'getFiltersParameter',
        value: function getFiltersParameter(parameters, filtersFormat, filterSeparator) {
            if ((0, _isEmpty2.default)(parameters)) {
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

exports.default = Image;
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

class UrlGenerator {
    constructor(opts) {
        this.options = {
            format: '{dirname}/{basename}{filters}.{extension}',
            filters_format: '-filters({filter})',
            filter_format: '{key}({value})',
            filter_separator: '-',
            ...opts,
        };
    }

    make(path, width, height, opts) {
        // Don't allow empty path
        if (isEmpty(path)) {
            return '';
        }

        // Extract the path from a URL if a URL was provided instead of a path
        const src = (new URL(path)).pathname;
        const options = {
            ...this.options,
            ...(isObject(width) && !isArray(width) ? width : null),
            ...(isObject(opts) && !isArray(opts) ? opts : null),
        };
        if (isArray(width) || isString(width)) {
            (isString(width) ? [width] : width).forEach((key) => {
                options[key] = true;
            });
        }
        if (isArray(opts) || isString(opts)) {
            (isString(opts) ? [opts] : opts).forEach((key) => {
                options[key] = true;
            });
        }

        // Separate config from filters
        const configKeys = ['route', 'format', 'filters_format', 'filter_format', 'filter_separator'];
        const config = pick(options, configKeys);
        const filters = omit(options, configKeys);
        const filterFormat = get(config, 'filter_format');
        const filtersFormat = get(config, 'filters_format');
        const filterSeparator = get(config, 'filter_separator');

        if (width !== null && !isObject(width) && !isArray(width) && !isString(width)) {
            filters.width = width;
        }
        if (height !== null) {
            filters.height = height;
        }

        const urlParameters = this.getParametersFromFilters(filters, filterFormat);
        const filtersParameter = this.getFiltersParameter(
            urlParameters,
            filtersFormat,
            filterSeparator,
        );

        // Build the url by replacing the placeholders
        const srcParts = parsePath(src);
        const placeholders = {
            host: trimEnd(get(config, 'host', ''), '/'),
            dirname: srcParts.dirname !== '.' ? trim(srcParts.dirname, '/') : '',
            basename: srcParts.name,
            filename: `${srcParts.name}${srcParts.extname}`,
            extension: srcParts.extname.replace(/^\./, ''),
            filters: filtersParameter,
        };

        const url = Object.keys(placeholders).reduce((fullUrl, key) => (
            fullUrl.replace(new RegExp(`{\\s*${key}\\s*}`, 'gi'), placeholders[key])
        ), get(config, 'format'));

        return `/${trimStart(url, '/')}`;
    }

    getParametersFromFilters(allFilters, filterFormat) {
        const format = filterFormat || this.options.filter_format;
        const parameters = [];

        // Size parameters are treated separatly
        const width = get(allFilters, 'width', -1);
        const height = get(allFilters, 'height', -1);
        const filters = omit(allFilters, ['width', 'height']);
        if (width !== -1 || height !== -1) {
            parameters.push(`${width !== -1 ? width : '_'}x${height !== -1 ? height : '_'}`);
        }

        // If the key as no value or is equal to
        // true or null, only the key is added.
        Object.keys(filters).forEach((key) => {
            const val = filters[key];
            if (val === true || val === null) {
                parameters.push(key);
            } else if (val !== false) {
                const strVal = isArray(val) ? val.join(',') : val;
                const parameter = format.replace(/\{\s*key\s*\}/i, key)
                    .replace(/\{\s*value\s*\}/i, strVal);
                parameters.push(parameter);
            }
        });

        return parameters;
    }

    getFiltersParameter(parameters, filtersFormat, filterSeparator) {
        if (isEmpty(parameters)) {
            return '';
        }

        const format = filtersFormat || this.options.filters_format;
        const separator = filterSeparator || this.options.filter_separator;
        const urlFilters = parameters.join(separator);
        return format.replace(/\{\s*filter\s*\}/i, urlFilters);
    }
}

export default UrlGenerator;

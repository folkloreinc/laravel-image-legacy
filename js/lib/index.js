'use strict';

Object.defineProperty(exports, "__esModule", {
    value: true
});
exports.UrlGenerator = undefined;

var _extends2 = require('babel-runtime/helpers/extends');

var _extends3 = _interopRequireDefault(_extends2);

var _UrlGenerator = require('./UrlGenerator');

var _UrlGenerator2 = _interopRequireDefault(_UrlGenerator);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var urlGenerator = new _UrlGenerator2.default((0, _extends3.default)({}, LARAVEL_IMAGE_CONFIG || null)); /* globals LARAVEL_IMAGE_CONFIG */


var image = {
    url: function url() {
        return urlGenerator.make.apply(urlGenerator, arguments);
    }
};

exports.UrlGenerator = _UrlGenerator2.default;
exports.default = image;
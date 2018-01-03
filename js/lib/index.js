'use strict';

Object.defineProperty(exports, "__esModule", {
    value: true
});
exports.Image = undefined;

var _extends2 = require('babel-runtime/helpers/extends');

var _extends3 = _interopRequireDefault(_extends2);

var _Image = require('./Image');

var _Image2 = _interopRequireDefault(_Image);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var image = new _Image2.default((0, _extends3.default)({}, LARAVEL_IMAGE_CONFIG || null)); /* globals LARAVEL_IMAGE_CONFIG */
exports.Image = _Image2.default;
exports.default = image;
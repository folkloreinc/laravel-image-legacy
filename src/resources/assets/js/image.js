(function (root, factory) {
	if (typeof define === 'function' && define.amd) {
		// AMD. Register as an anonymous module.
		define([], factory);
	} else if (typeof exports === 'object') {
		// Node. Does not work with strict CommonJS, but
		// only CommonJS-like environments that support module.exports,
		// like Node.
		module.exports = factory();
	} else {
		// Browser globals (root is window)
		if(typeof(root.Folklore) === 'undefined') {
			root.Folklore = {};
		}
		root.Folklore.Image = factory();
	}
}(this, function () {
	
	'use strict';

	var URL_PARAMETER = '-image({options})';
	
	// Build a image formatted URL
	function url(src, width, height, options) {

		// Don't allow empty strings
		if (!src || !src.length) return;

		//If width parameter is an array, use it as options
		if(width instanceof Object)
		{
			options = width;
			width = null;
			height = null;
		}

		//Get size
		if (!width) width = '_';
		if (!height) height = '_';
		
		// Produce the image option
		var params = [];

		//Add size if presents
		if(width != '_' || height != '_') {
			params.push(width+'x'+height);
		}
		
		// Add options.
		if (options && options instanceof Object) {
			var val, key;
			for (key in options) {
				val = options[key];
				if (val === true || val === null) {
					params.push(key);
				}
				else if (val instanceof Array) {
					params.push(key+'('+val.join(',')+')');
				}
				else {
					params.push(key+'('+val+')');
				}
			}
		}
		
		params = params.join('-');
		var parameter = URL_PARAMETER.replace('{options}',params);

		// Break the path apart and put back together again
		return src.replace(/^(.+)(\.[a-z]+)$/i, "$1"+parameter+"$2");
		
	}
	
	// Expose public methods.
	return {
		url: url
	};
}));

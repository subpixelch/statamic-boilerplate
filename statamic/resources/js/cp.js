var $ = require('jquery');

global.cp_url = function(url) {
    url = Statamic.cpRoot + '/' + url;
    return url.replace(/\/+/g, '/');
};

global.resource_url = function(url) {
    url = Statamic.resourceUrl + '/' + url;
    return url.replace(/\/+/g, '/');
};

// Get url segments from the nth segment
global.get_from_segment = function(count) {
    return Statamic.urlPath.split('/').splice(count).join('/');
};

global.format_input_options = function(options) {

	if (typeof options[0] === 'string') {
		return options;
	}

	var formatted = [];
	_.each(options, function(value, key, list) {
	    formatted.push({'value': key, 'text': value});
	});

	return formatted;
};

global.dd = function(args) {
    console.log(args);
};

global.Cookies = require('cookies-js');

require('./l10n/l10n');
require('./vendor/sticky');

$(document).ready(function(){
	$(".sticky").sticky({
		topSpacing: 0,
		className: 'stuck',
		widthFromWrapper: false
	});
});

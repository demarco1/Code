/**
 * Make MediaWiki environment look present for the websocket.js code
 */
if(window.mw === undefined) {
	window.mw = { data: {}, config: {} };
	window.mw.config.get = function(k, d) {
		if(!(k in window.mw.data)) return d;
		var val = window.mw.data[k];
		if( val.charAt(0) === '{' || val.charAt(0) === '[' ) val = JSON.parse( val );
		return val;
	};
	window.mw.config.set = function(k, v) {
		window.mw.data = v;
	};
};

/**
 * Make MediaWiki environment look present for the websocket.js code
 */
window.mw = {
	data: {},
	config: {
		get: function(k) {
			if(!(k in window.mw.data)) console.log('No config value: ' + k);
			var val = window.mw.data[k];
			if( val.charAt(0) === '{' || val.charAt(0) === '[' ) {
				val = JSON.parse( val );
			}
			return val;
		}
	}
};

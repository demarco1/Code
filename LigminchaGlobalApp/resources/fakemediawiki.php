/**
 * Make MediaWiki environment look present for the websocket.js code
 */
window.mw = {
	data: {},
	config: {
		get: function(k) {
			return window.mw.data[k];
		}
	}
};

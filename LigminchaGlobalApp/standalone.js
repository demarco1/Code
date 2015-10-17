/**
 * Make MediaWiki environment look present for the websocket.js code
 */
var window.mw = {
	config: {
		get: function(k) {
			// TODO: return var set by our fake addJsConfigVars()
		}
	}
};

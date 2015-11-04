'use strict';

/**
 * Some useful string functions
 */
String.prototype.ucfirst = function() {
	return this.charAt(0).toUpperCase() + this.slice(1);
};
String.prototype.ucwords = function() {
	return this.split(' ').map(function(s) { return s.ucfirst(); }).join(' ');
};

/**
 * Preload images
 */
(new Image()).src = '/images/loader.gif';

/**
 * App initialisation
 */

// Remove automatic data synchronisation with the back-end
Backbone.sync = function(method, model, options) { };

// When a new object is added to the collection, upgrade it to the proper model sub-class and re-render the list
lg.ligminchaGlobal.on('add', lg.upgradeObject, lg);

// Populate the ligminchaGlobal collection with the initial objects sent from the backend
var objects = mw.config.get('GlobalObjects');
for(var i in objects) lg.ligminchaGlobal.create(objects[i]);

// Get the session is there is one
lg.session = mw.config.get('session');
lg.user = false;
if(session) {
	console.log('Session ID sent from server: ' + lg.session.substr(0,5));
	lg.session = lg.getObject(session);
	lg.user = lg.getObject(session.owner);
} else console.log('No session ID sent from server')

// Connect the WebSocket if there's an active session
if(lg.session && typeof webSocket === 'object') {

	// The wsClientID is the SSO session id + a unique ID for this socket
	// TODO: we won't need the second socket ID later because there will be only one socket per session
	mw.data.wsClientID = mw.data.session + ':' + lg.hash(lg.uuid()).substr(0,5);

	// Creation the connection
	lg.ws = webSocket.connect();

	// Subscribe to the LigminchaGlobal messages and send them to the recvQueue function
	webSocket.subscribe( 'LigminchaGlobal', function(data) { lg.recvQueue(data.msg) });

	// Initialise the per-second ticker
	lg.ticker();
}

// Populate the welcome notice depending on if there's a session
lg.template(lg.session ? 'welcome-user' : 'welcome-anon', lg.user, function(html) { $('div.welcome').html(html); });

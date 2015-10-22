'use strict';

var LG_LOG      = 1;
var LG_SERVER   = 2;
var LG_USER     = 3;
var LG_SESSION  = 4;
var LG_SYNC     = 5;
var LG_VERSION  = 6;
var LG_DATABASE = 7;

lg.classes = [0, 'Log', 'Server', 'User', 'Session', 'Sync', 'Version', 'Database'];

// renders the full list of servers calling ServerView for each one.
lg.AppView = Backbone.View.extend({
	el: '#objectapp',
	initialize: function () {
		lg.ligminchaGlobal.on('add', this.render, this);
		lg.ligminchaGlobal.on('remove', this.render, this);
		//lg.ligminchaGlobal.fetch(); // Loads list from local storage
	},

	// Add all the server objects to the list using each server object as its own model
	render: function(){
		$('#server-list').html('');
		var servers = lg.select({type: LG_SERVER});
		for( var i in servers ) {
			var view = new lg.ServerView({model: servers[i]});
			$('#server-list').append(view.render().el);
		}
	},
});



/**
 * App initialisation
 */

// Remove automatic data synchronisation with the back-end
Backbone.sync = function(method, model, options) { };

// Initialise our app
lg.appView = new lg.AppView();

// Populate the ligminchaGlobal collection with the initial objects sent from the backend
var objects = mw.config.get('GlobalObjects');
for(var i in objects) lg.ligminchaGlobal.create(objects[i]);

// Connect the WebSocket
if(typeof webSocket === 'object') {

	// The wsClientID is the SSO session id + a unique ID for this socket
	// TODO: we won't need the second socket ID later because there will be only one socket per session
	mw.data.wsClientID = mw.data.session + ':' + lg.hash(lg.uuid()).substr(0,5);

	// Creation the connection
	lg.ws = webSocket.connect();

	// Subscribe to the LigminchaGlobal messages and send them to the recvQueue function
	webSocket.subscribe( 'LigminchaGlobal', function(data) { lg.recvQueue(data.msg) });
}

// Initialise the per-second ticker
lg.ticker();

'use strict';

var LG_LOG      = 1;
var LG_SERVER   = 2;
var LG_USER     = 3;
var LG_SESSION  = 4;
var LG_SYNC     = 5;
var LG_VERSION  = 6;
var LG_DATABASE = 7;

/**
 * Backbone Views
 */

// renders individual server item
lg.ServerView = Backbone.View.extend({
	tagName: 'li',
	//template: _.template($('#item-template').html()),
	render: function(){
		//this.$el.html(this.template(this.model.toJSON()));
		var server = this.model.attributes;
		var html = server.tag;
		var users = lg.select({type: LG_USER, ref1: server.id});
		if(users) {
			html += '<ul>';
			for( var i in users ) {
				var user = users[i].attributes;
				html += '<li>' + user.id + '</li>';
				var sessions = lg.select({type: LG_SESSION, owner: user.id});
				if(sessions) {
					html += '<ul>';
					for( var j in sessions ) {
						var session = sessions[i].attributes;
						html += '<li>' + session.id + '</li>';
					}
					html += '</ul>';
				}
				
			}
			html += '</ul>';
		}

		this.$el.html(html);
		return this; // enable chained calls
	},
	initialize: function(){
		this.model.on('change', this.render, this);
		this.model.on('destroy', this.remove, this); // remove: Convenience Backbone's function for removing the view from the DOM.
	},
	events: {
		'click .destroy' : 'destroy'
	},
	destroy: function(){
		this.model.destroy();
	}
});

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

// Initialise our app
lg.appView = new lg.AppView();

// Populate the ligminchaGlobal collection with the initial objects sent from the backend
var objects = mw.config.get('GlobalObjects');
for( var i in objects) lg.ligminchaGlobal.create(objects[i]);

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

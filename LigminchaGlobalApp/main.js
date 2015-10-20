'use strict';
var lg = {};
var mw = window.mw;

/**
 * Backbone Models
 */

// This is the model common to all global objects
lg.GlobalObject = Backbone.Model.extend({
	defaults: {
		title: '',
		completed: false
	},
	toggle: function(){
		//this.save({ completed: !this.get('completed')});
	}
});

// Server sub-class
lg.Server = lg.GlobalObject.extend({
});

// User sub-class
lg.User = lg.GlobalObject.extend({
});

// Session sub-class
lg.Session = lg.GlobalObject.extend({
});

/**
 * LigminchaGlobal - Backbone Collection for all the distributed objects locally available
 */
lg.LigminchaGlobal = Backbone.Collection.extend({
	model: lg.GlobalObject,
	url: 'localhost',
	//localStorage: new Store("ligminchaGlobal")
});

// instance of the Collection
lg.ligminchaGlobal = new lg.LigminchaGlobal();


/**
 * Backbone Views
 */

// renders individual server item
lg.ServerView = Backbone.View.extend({
	tagName: 'li',
	template: _.template($('#item-template').html()),
	render: function(){
		this.$el.html(this.template(this.model.toJSON()));
		this.input = this.$('.edit');
		return this; // enable chained calls
	},
	initialize: function(){
		this.model.on('change', this.render, this);
		this.model.on('destroy', this.remove, this); // remove: Convenience Backbone's function for removing the view from the DOM.
	},
	events: {
		'dblclick label' : 'edit',
		'keypress .edit' : 'updateOnEnter',
		'blur .edit'     : 'close',
		'click .toggle'  : 'toggleCompleted',
		'click .destroy' : 'destroy'
	},
	edit: function(){
		this.$el.addClass('editing');
		this.input.focus();
	},
	close: function(){
		var value = this.input.val().trim();
		if(value) {
			this.model.save({title: value});
		}
		this.$el.removeClass('editing');
	},
	updateOnEnter: function(e){
		if(e.which == 13){
			this.close();
		}
	},
	toggleCompleted: function(){
		this.model.toggle();
	},
	destroy: function(){
		this.model.destroy();
	}
});

// renders the full list of servers calling ServerView for each one.
lg.AppView = Backbone.View.extend({
	el: '#objectapp',
	initialize: function () {
		this.input = this.$('#new-object');
		lg.ligminchaGlobal.on('add', this.addAll, this);
		lg.ligminchaGlobal.on('reset', this.addAll, this);
		lg.ligminchaGlobal.fetch(); // Loads list from local storage
	},
	events: {
		'keypress #new-object': 'createObjectOnEnter'
	},
	createObjectOnEnter: function(e){
		if ( e.which !== 13 || !this.input.val().trim() ) { // ENTER_KEY = 13
			return;
		}
		lg.ligminchaGlobal.create(this.newAttributes());
		this.input.val(''); // clean input box
	},
	addOne: function(obj){
		var view = new lg.ServerView({model: obj}); // <======================================================================================================
		$('#server-list').append(view.render().el);
	},
	addAll: function(){
		this.$('#server-list').html(''); // clean the server list
		console.log(lg.ligminchaGlobal.toArray());
		lg.ligminchaGlobal.each(this.addOne, this);
	},
	newAttributes: function(){
		return {
			title: this.input.val().trim(),
			completed: false
		}
	}
});


/**
 * Utility functions
 */

// Return the reference to an objects model given its GUID
// TODO: we should maintain indexes for the main parameters for this method and select/selectOne
lg.getObject = function(id) {
	var found = false;
	lg.LigminchaGlobal.each(function(obj) {
//		if(obj.id == id)
	}, this);
};

// Hash that is compatible with the server-side
lg.hash = function(s) {
	var h = CryptoJS.SHA1(s) + "";
	return h.toUpperCase();
};

// Generate a new globally unique ID
lg.uuid = function() {
	return this.hash(Math.random() + "");
};

// Receive sync-object queue from a remote server (The JS version of the PHP LigminchaGlobalDistributed::recvQueue)
lg.recvQueue = function(queue) {
	var origin = queue.shift();
	var session = queue.shift();

	// Process each of the sync objects (this may lead to further re-routing sync objects being made)
	for( var i in queue ) {
		this.process( queue[i].tag, queue[i].data, origin );
	}
};

// Send the list of sync-objects (The JS version of the PHP LigminchaGlobalDistributed::sendQueue)
lg.sendQueue = function(queue) {
};

// Encodes data into the format requred by distributed.php
lg.encodeData = function(json) {
	return JSON.stringify(json);
};

// Decodes distributed queue data
lg.decodeData = function(data) {
	return JSON.parse(data);
};

// Process an inbound sync object (JS version of LigminchaGlobalSync::process)
lg.process = function(crud, fields, origin) {
	if(crud == 'U') {
		// update/create
		console.log('update received for ' + fields.id);
	} else if(crud == 'D') {
		// delete
		console.log('delete received');
	} else console.log('Unknown CRUD method "' + crud + '"');
};

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

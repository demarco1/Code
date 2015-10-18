'use strict';
var lg = {};
var mw = window.mw;

/**
 * Models
 */
lg.Server = Backbone.Model.extend({
	defaults: {
		title: '',
		completed: false
	},
	toggle: function(){
		this.save({ completed: !this.get('completed')});
	}
});

/**
 * Collections
 */
lg.ServerList = Backbone.Collection.extend({
	model: lg.Server,
	localStorage: new Store("ligminchaGlobal")
});

// instance of the Collection
lg.serverList = new lg.ServerList();


/**
 * Views
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
		lg.serverList.on('add', this.addAll, this);
		lg.serverList.on('reset', this.addAll, this);
		lg.serverList.fetch(); // Loads list from local storage
	},
	events: {
		'keypress #new-object': 'createObjectOnEnter'
	},
	createObjectOnEnter: function(e){
		if ( e.which !== 13 || !this.input.val().trim() ) { // ENTER_KEY = 13
			return;
		}
		lg.serverList.create(this.newAttributes());
		this.input.val(''); // clean input box
	},
	addOne: function(obj){
		var view = new lg.ServerView({model: obj}); // <======================================================================================================
		$('#server-list').append(view.render().el);
	},
	addAll: function(){
		this.$('#server-list').html(''); // clean the server list
		lg.serverList.each(this.addOne, this);
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

// Hash that is compatible with the server-side
lg.hash = function(s) {
	if(s === undefined) s = Math.random() + "";
	var h = CryptoJS.SHA1(s) + "";
	return h.toUpperCase();
};

// Receive sync-object queue from a remote server (The JS version of the PHP LigminchaGlobalDistributed method ofthe same name)
lg.recvQueue = function( data ) {
};

// Send the list of sync-objects (The JS version of the PHP LigminchaGlobalDistributed method ofthe same name)
lg.sendQueue = function( queue ) {
};

// Encodes data into the format requred by distributed.php
lg.encodeData = function(json) {
	return JSON.stringify(json);
};

// Decodes distributed queue data
lg.decodeData = function(data) {
	return JSON.parse(data);
};


/**
 * App initialisation
 */

// Initialise our app
lg.appView = new lg.AppView(); 

// Connect the WebSocket
// The wsClientID is the SSO session id + a unique ID for this socket
// TODO: we won't need the second socket ID later because there will be only one socket per session
if(typeof webSocket === 'object') {
	mw.data.wsClientID = mw.data.session + ':' + lg.hash().substr(0,5);
	lg.ws = webSocket.connect();
	webSocket.subscribe( 'LigminchaGlobal', function(data) { console.log(data.msg) } );
}

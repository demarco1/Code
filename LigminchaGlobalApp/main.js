'use strict';
var lg = {};


/**
 * Set up mw.config.get for websocket.js
 */
var window.mw = {
	config: {
		get: function(k) {
			// TODO: return var set by our fake addJsConfigVars()
		}
	}
};


/**
 * Models
 */
lg.Object = Backbone.Model.extend({
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
lg.ObjectList = Backbone.Collection.extend({
	model: lg.Object,
	localStorage: new Store("ligminchaGlobal")
});

// instance of the Collection
lg.objectList = new lg.ObjectList();


/**
 * Views
 */

// renders individual object list (li)
lg.ObjectView = Backbone.View.extend({
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
		'blur .edit' : 'close',
		'click .toggle': 'toggleCompleted',
		'click .destroy': 'destroy'
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

// renders the full list of objects calling ObjectView for each one.
lg.AppView = Backbone.View.extend({
	el: '#objectapp',
	initialize: function () {
		this.input = this.$('#new-object');
		lg.objectList.on('add', this.addAll, this);
		lg.objectList.on('reset', this.addAll, this);
		lg.objectList.fetch(); // Loads list from local storage
	},
	events: {
		'keypress #new-object': 'createObjectOnEnter'
	},
	createObjectOnEnter: function(e){
		if ( e.which !== 13 || !this.input.val().trim() ) { // ENTER_KEY = 13
			return;
		}
		lg.objectList.create(this.newAttributes());
		this.input.val(''); // clean input box
	},
	addOne: function(obj){
		var view = new lg.ObjectView({model: obj});
		$('#object-list').append(view.render().el);
	},
	addAll: function(){
		this.$('#object-list').html(''); // clean the object list
		lg.objectList.each(this.addOne, this);
	},
	newAttributes: function(){
		return {
			title: this.input.val().trim(),
			completed: false
		}
	}
});

// Initialise our app
lg.appView = new lg.AppView(); 


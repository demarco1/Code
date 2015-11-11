/**
 * This is the model common to all global objects
 */
'use strict';

lg.GlobalObject = Backbone.Model.extend({

	// Object default properties
	defaults: {
		exists: false,
		id: lg.uuid(),
	},

	// Set/reset or read a binary flag from this object
	flag: function(flag, val) {
		if( val === true ) this.flags |= flag;
		else if( val === false ) this.flags &= ~flag;
		else return (this.flags & flag) ? true : false;
	},

	// Either fields is supplied from changes coming in, or current state is queued for sending out
	// TODO: needs origin, session added
	update: function(fields) {
		if(fields) this.attributes = fields;
		else lg.sendQueue([0,0,fields]);
	},
});

// LigminchaGlobal is a Backbone Collection class for all the distributed objects locally available
lg.LigminchaGlobal = Backbone.Collection.extend({
	model: lg.GlobalObject,
});

// Instance of the Collection
lg.ligminchaGlobal = new lg.LigminchaGlobal();

// Base view class for individual objects
lg.ObjectView = Backbone.View.extend({
	tagName: 'div',
	render: function() {
		var obj = this.model.attributes;
		var html = '';
		var cls = lg.typeToClass(obj.type);
		if('properties' in this.model) this.model.properties(this.$el);
		else {
			// generic object
			this.$el.html('Generic object');
		}
		this.$el.dialog({
			modal: true,
			resizable: false,
			width: 400,
			title: cls + ' properties',
			buttons: {
				'save': function() {
					var template = jQuery('select.template', this).val();
					var cur = ('template' in obj.data) ? obj.data.template : 'maple';
					if(template !== obj.data.template) {
						console.log('Template changed from "' + cur + '" to "' + template + '"');
						obj.data.template = template;
						obj.update();
					}
					jQuery(this).dialog('close');
					this.remove();
				},
				'cancel': function() {
					jQuery(this).dialog('close');
					this.remove();
				},
			}
		});
		if('activate' in this.model) this.model.activate(this.$el);
		return this;
	},
	initialize: function(){
		this.render();
		this.model.on('change', this.render, this);
		this.model.on('destroy', this.remove, this);
	},
});

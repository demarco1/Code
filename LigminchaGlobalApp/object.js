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

	update: function(fields, local) {
		this.attributes = fields;

		// If this change originated locally, send notification to the master server
		// TODO: needs testing and origin, session added
		if(local) lg.sendQueue([0,0,fields]);
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
		var html = lg.typeToClass(obj.type);
		this.$el.html(html).dialog({
			modal: true,
			resizable: false,
			width: 400,
			title: obj.id.substr(0,5),
			buttons: {
				'close': function() {
					$(this).dialog('close');
					this.remove();
				},
			}
		});
		return this;
	},
	initialize: function(){
		this.render();
		this.model.on('change', this.render, this);
		this.model.on('destroy', this.remove, this);
	},
});

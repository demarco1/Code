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

	update: function(fields) {
		this.attributes = fields;
	},
});

// LigminchaGlobal is a Backbone Collection class for all the distributed objects locally available
lg.LigminchaGlobal = Backbone.Collection.extend({
	model: lg.GlobalObject,
});

// Instance of the Collection
lg.ligminchaGlobal = new lg.LigminchaGlobal();

lg.ligminchaGlobal.update = null;

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

lg.ligminchaGlobal.update = null;

/**
 * This is the model common to all global objects
 */
'use strict';

lg.GlobalObject = Backbone.Model.extend({

	// Object default properties
	defaults: {
		exists: false,
		id: lg.uuid()
	},

	// Set/reset or read a binary flag from this object
	flag: function(flag, val) {
		if( val === true ) this.flags |= flag;
		else if( val === false ) this.flags &= ~flag;
		else return (this.flags & flag) ? true : false;
	},
	update: function(fields) {
		//this.save({ completed: !this.get('completed')});
	},
});


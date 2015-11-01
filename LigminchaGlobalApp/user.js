'use strict';

lg.User = lg.GlobalObject.extend({
	constructor: function(attributes, options) {
		attributes.type = LG_USER;
		Backbone.Model.apply( this, arguments );
	}
});

// Return the current user object
lg.User.getCurrent = function() {
	// TODO
};

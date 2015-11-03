'use strict';

lg.User = lg.GlobalObject.extend({
	constructor: function(attributes, options) {
		attributes.type = LG_USER;
		Backbone.Model.apply( this, arguments );
	}

	// Render user-specific properties
	properties: function(popup) {
		lg.template('user-popup', this.attributes, function(html) { popup.html(html); });
	},

});

// Return the current user object
lg.User.getCurrent = function() {
	// TODO
};

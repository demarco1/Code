'use strict';

lg.User = lg.GlobalObject.extend({
	constructor: function(attributes, options) {
		attributes.type = LG_USER;
		Backbone.Model.apply( this, arguments );
	},

	// Render user-specific properties
	properties: function(popup) {
		lg.template('user-popup', this.attributes, function(html) { popup.html(html); });
	},

	// Return whether or not the user is online (has any sessions)
	online: function() {
		return lg.select({type: LG_SESSION, owner: this.id}).length > 0;
	},

	// Returns the server this user registered with
	server: function() {
		return lg.getObject(this.attributes.ref1);
	},

	// Full name optionally including server
	fullName: function(server) {
		var name = this.attributes.data.realname;
		if(server) name += ' (' + this.server.data.name + ')';
		return name;
	},

});


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

});

// Return the current user object
lg.User.getCurrent = function() {
	// TODO
};

lg.User.usersOnline = function(notself) {
	var list = [];
	var users = lg.select({type: LG_USER});
	for(var i in users) {
		if(users[i] !== lg.user) {
			var user = users[i].attributes;
			var sessions = lg.select({type: LG_SESSION, owner: user.id});
			if(sessions.length) {
				var name = user.data.realname;
				name += ' (' + lg.getObject(user.ref1).data.name + ')';
				list.push(name);
			}
		}
	}
	return list;
};

lg.User.chatMenu = function() {
	var users = this.usersOnline();
	if(users.length > 0) {
		var html = 'Chat (' + users.length + ' user' + (users.length == 1 ? '' : 's') + ')&nbsp;&nbsp;▼<ul>';
		for(var i in users) html += '<li>' + users[i] + '</li>';
		html += '</ul>';
		return html;
	} else return 'There are no other users online';
};

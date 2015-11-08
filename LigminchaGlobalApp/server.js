'use strict';

lg.Server = lg.GlobalObject.extend({

	constructor: function(attributes, options) {
		attributes.type = LG_SERVER;
		Backbone.Model.apply( this, arguments );
	},

	// Render server-specific properties
	properties: function(popup) {
		lg.template('server-popup', this.attributes, popup);
	},

	// Activate the new-version button
	activate: function(popup) {
		$('#server-popup button.new-version').click(function() { lg.Version.createNew(); });
	},
});

// Return the master server object
lg.Server.getMaster = function() {
	if(!('master' in this)) this.master = lg.getObject(lg.getConfig('masterServer'));
	return this.master;
},


// Renders individual server item with contained users and sessions
lg.ServerView = Backbone.View.extend({
	tagName: 'li',
	render: function() {
		var server = this.model.attributes;
		var html = lg.session
			? '<span class="title" id="' + server.id + '">' + server.data.name + '</span>'
			: '<a href="http://' + server.tag + '">' + server.data.name + '</a>';
		var users = lg.select({type: LG_USER, ref1: server.id});
		if(users) {
			html += '<ul>';
			for( var i in users ) {
				var user = users[i].attributes;
				var sessions = lg.select({type: LG_SESSION, owner: user.id});
				var col = sessions.length ? 'green' : 'red';
				html += '<li style="color:' + col + '"><span id="' + user.id + '">' + user.data.realname + '</span></li>';
				if(sessions.length) {
					html += '<ul class="sessions">';
					for( var j in sessions ) {
						var session = sessions[j].attributes;
						html += '<li>' + session.tag + ' (' + session.id.short() + ')</li>';
					}
					html += '</ul>';
				}
			}
			html += '</ul>';
		}
		this.$el.html(html);
		$('span', this.$el).css('cursor','pointer').click(function() {
			var id = $(this).attr('id');
			if(id) new lg.ObjectView({model: lg.getObject(id)});
		});
		return this; // enable chained calls
	},
	initialize: function(){
		this.model.on('change', this.render, this);
		this.model.on('destroy', this.remove, this); // remove: Convenience Backbone's function for removing the view from the DOM.
	},
	events: {
		'click .destroy' : 'destroy'
	},
	destroy: function(){
		this.model.destroy();
	}
});

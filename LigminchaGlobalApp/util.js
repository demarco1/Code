// Some useful string functions
String.prototype.ucfirst = function() {
	return this.charAt(0).toUpperCase() + this.slice(1);
};
String.prototype.ucwords = function() {
	return this.split(' ').map(function(s) { return s.ucfirst(); }).join(' ');
};
String.prototype.short = function() {
	return this.substr(0,5);
};

// Notification popups
lg.message = function(msg, delay, type) {
	if(typeof delay !== 'number') delay = 0;
	if(type === undefined) type = 'info';
	msg = $('<div class="' + type + ' message">' + msg + '</div>');
	$('#notify').hide().html(msg).fadeIn(300);
	if(delay) msg.delay(delay).fadeOut(300);
};

// Load a template and precompile ready for use
// - template is the name of the template to load (/templates/NAME.html will be loaded)
// - args is the object containing the parameters to populate the template with
// - target is either a function to pass the final result to, or a jQuery selector or element to set the html for
lg.template = function(template, args, target) {
	function render(html, target) { typeof target == 'function' ? target(html) : $(target).html(html); }
	if(!('templates' in this)) this.templates = mw.config.get('templates');
	if(template in this.templates) render(this.templates[template](args), target);
	else {
		render('<div class="loading"></div>', target);
		$.ajax({
			type: 'GET',
			url: '/templates/' + template + '.html',
			context: this,
			dataType: 'html',
			success: function(html) {
				this.templates[template] = _.template(html);
				render(this.templates[template](args), target);
			}
		});
	}
};

// Get a list of the tags from Github
lg.tagList = function() {
	var html = '';
	for(var i in mw.config.get('tags')) html += '<option>' + i + '</option>';
	return html;
};

// Return list of users currently online (can exclude self)
lg.usersOnline = function(notself) {
	var self = notself ? lg.user : false;
	var list = [];
	var users = lg.select({type: LG_USER});
	for(var i in users) {
		if(users[i] !== self && users[i].online()) {
			var name = users[i].attributes.data.realname;
			name += ' (' + lg.getObject(users[i].attributes.ref1).data.name + ')';
			list.push(name);
		}
	}
	return list;
};

// Returns the content for the chat menu in the toolbar
lg.chatMenu = function() {
	var users = this.usersOnline();
	if(users.length > 0) {
		var html = 'Chat (' + users.length + ' user' + (users.length == 1 ? '' : 's') + ')&nbsp;&nbsp;▼<ul>';
		for(var i in users) html += '<li>' + users[i] + '</li>';
		html += '</ul>';
		return html;
	} else return 'There are no other users online';
};

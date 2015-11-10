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

// Our config is using the fake MediaWiki layer
lg.getConfig = function(k, d) { return mw.config.get(k, d); };
lg.setConfig = function(k, v) { mw.config.set(k, v); };

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
	if(!('templates' in this)) {
		this.templates = {};
		var t = lg.getConfig('templates');
		for(var i in t) this.templates[i] = _.template(t[i]);
	}
	if(template in this.templates) render(this.templates[template](args), target);
	else {
		render('<div class="loading"></div>', target);
		$.ajax({
			type: 'GET',
			url: lg.host + '/templates/' + template + '.html',
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
	for(var i in lg.getConfig('tags')) html += '<option>' + i + '</option>';
	return html;
};

// Return list of users currently online (can exclude self)
lg.usersOnline = function(notself) {
	var self = notself ? lg.user : false;
	var list = [];
	var users = lg.select({type: LG_USER});
	for(var i in users) {
		if(users[i] !== self && users[i].online()) list.push(users[i].fullName(true));
	}
	return list;
};

// Returns the content for the chat menu in the toolbar
lg.chatMenu = function() {
	if(lg.user) {
		var users = this.usersOnline(true);
		if(users.length > 0) {
			var html = '<li id="chat">Chat (' + users.length + ' user' + (users.length == 1 ? '' : 's') + ')&nbsp;&nbsp;▼<ul>';
			for(var i in users) html += '<li>' + users[i] + '</li>';
			html += '</ul></li>';
			return html;
		} else return '<li id="chat">There are no other users online</li>';
	} else return '<li id="chat" style="display:none"></li>';
};

// Re-render the chatmenu
lg.updateChatMenu = function() {
	if(lg.toolbar) this.updateParent('#lg-toolbar #chat', this.chatMenu());
	else $('#chat').replaceWith(this.chatMenu());
	console.log('Sessions changed, updated chat menu');
};

// Returns the content for the personal menu in the toolbar
lg.personalMenu = function() {
	return lg.user
		? lg.user.fullName(true) + '&nbsp;&nbsp;▼<ul>\
				<li><a>Profile</a></li>\
				<li><a href="http://' + lg.user.server().tag + '/index.php/login" target="_parent">Log out</a></li>\
			</ul>'
		: '<span class="w">You are not logged in</span>';
};

// Returns link to local sangha site if logged in
lg.sanghaLink = function() {
	return lg.user ? '<li><a href="http://' + lg.user.server().tag + '" target="_parent">' + lg.user.server().data.name + '</a></li>' : '';
};

// Returns the admin menu if logged in
lg.adminMenu = function() {
	return lg.user ? '<li>Admin&nbsp;&nbsp;▼\
		<ul>\
			<li><a>Control panel</a></li>\
			<li><a>Manage users</a></li>\
			<li><a>Manage servers</a></li>\
		</ul>\
	</li>' : '';
};

// Set up the host domain depending on whether we're running in toolbar or full app mode
lg.toolbar = lg.getConfig('toolbar', false);
console.log(lg.toolbar);
console.log(lg.getConfig('wgServer'));
if(lg.toolbar) {
	lg.host = lg.getConfig('wgServer');
	console.log('Running in toolbar-only mode within ' + lg.host);
} else console.log('Running in full application mode');

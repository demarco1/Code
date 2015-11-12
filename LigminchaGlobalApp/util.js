'use strict';

(function($, lg) {

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
	// - target is either a function to pass the final result to, or a $ selector or element to set the html for
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
				var html = '<li id="chat">Chat (<span class="hl">' + users.length + ' user' + (users.length == 1 ? '' : 's') + '</span>)&nbsp;&nbsp;▼<ul>';
				for(var i in users) html += '<li>' + users[i] + '</li>';
				html += '</ul></li>';
				return html;
			} else return '<li id="chat">There are no other users online</li>';
		} else return '<li id="chat" style="display:none"></li>';
	};

	// Re-render the chatmenu
	lg.updateChatMenu = function() {
		$('#lg-toolbar #chat').replaceWith(this.chatMenu());
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

	// Returns links to local sangha sites with one highlighted if logged in
	lg.sanghaLinks = function() {
		var servers = lg.select({type: LG_SERVER});
		var local = lg.user ? lg.user.server() : false;
		var html = '';
		for(var i in servers) {
			var server = servers[i];
			var hl = local === server ? 'class="hl" ' : '';
			html += '<li><a ' + hl + 'href="http://' + server.attributes.tag + '" target="_parent">' + server.attributes.data.name + '</a></li>';
		}
		return html;
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

	// Returns submenu items for latest activity items sorted by time, ten at most
	lg.activity = function() {
		var html = '';
		var items = lg.select({type: LG_LOG, tag: 'Info'});
		items.sort(function(a, b) { return b.creation - a.creation; });
		var l = items.length;
		if(l > 10) l = 10;
		for(var i = 0; i < l; i++) {
			var date = new Date(items[i].attributes.creation*1000);
			var h = date.getHours();
			var m = ('0' + date.getMinutes()).substr(-2);
			html += '<li>' + h + ':' + m + ' ' + items[i].attributes.data + '</li>';
		}
		return html;
	};

	// Render a select list of global objects from the passed query
	// - atts is the attributes to give the select element
	// - cur is the current value to be selected if any
	// - key is the attribute of the object to use as the displayed value (tag by default)
	lg.selectListObj = function(query, atts, cur, key) {
		if(key === undefined) key = 'tag';
		var html = '<select';
		for(var i in atts) html += ' ' + i + '="' + atts[i] + '"';
		html += '>'
		var opts = this.select(query);
		for(var i in opts) {
			var optatts = opts[i].attributes;
			var opt = (key in optatts) ? optatts[key] : optatts.data[key];
			var s = opt == cur ? ' selected' : '';
			html += '<option value="' + optatts.id + '"' + s + '>' + opt + '</option>';
		}
		html += '</select>';
		return html;
	};

	// Render a select list of array items (opts)
	// - atts is the attributes to give the select element
	// - cur is the current value to be selected if any
	lg.selectList = function(opts, atts, cur) {
		var html = '<select';
		for(var i in atts) html += ' ' + i + '="' + atts[i] + '"';
		html += '>'
		for(var i in opts) {
			var s = opts[i] == cur ? ' selected' : '';
			html += '<option' + s + '>' + opts[i] + '</option>';
		}
		html += '</select>';
		return html;
	};

	// Temp demo for changing template allows changing of the header image
	lg.templateList = function(template) {
		if(template === undefined) template = 'maple';
		return lg.selectList(['blue-flower','maple','raindrops','walden-pond','windows'], {class: 'template'}, template);
	};

}(jQuery, window.lg));

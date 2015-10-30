'use strict';

lg.Version = lg.GlobalObject.extend({

});

// This is a "static" method for opening a dialog to create a new LG_VERSION global object
lg.Version.createNew = function() {
	$('<div>test</div>').dialog({
		modal: true,
		resizable: false,
		width: 400,
		title: 'Create a new version',
		buttons: {
			'save': function() {
			},
			'cancel': function() {
				$(this).dialog('close');
				this.remove();
			},
		}
	});
};

// Return an options list of all the LG_VERSION objects with the passed one selected
lg.Version.versionOptions = function(selected) {
	var options = '';
	var versions = lg.select({type: LG_VERSION});
	for(var i in versions) options += '<option>' + versions[i].attributes.tag + '</option>';
	return options;
};

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

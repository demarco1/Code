'use strict';

lg.Version = lg.GlobalObject.extend({

});

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

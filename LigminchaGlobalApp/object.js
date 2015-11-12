/**
 * This is the model common to all global objects
 */
'use strict';

(function($, lg) {

	lg.GlobalObject = Backbone.Model.extend({

		// Object default properties
		defaults: {
			exists: false,
			id: lg.uuid(),
		},

		// Set/reset or read a binary flag from this object
		flag: function(flag, val) {
			if( val === true ) this.flags |= flag;
			else if( val === false ) this.flags &= ~flag;
			else return (this.flags & flag) ? true : false;
		},

		// Either fields is supplied from changes coming in, or current state is sent out
		// TODO: needs origin, session added
		update: function(fields) {
			if(fields) {
				this.attributes = fields;
				return;
			}

			// Get the master server that we'll send the updated state of this object to
			var master = lg.Server.getMaster();

			// Create an LG_SYNC object for the object we want to send
			var sync = {
				type: LG_SYNC,
				ref1: master.id,
				ref2: this.id,
				data: this.attributes,
				tag: 'U',
			};

			// Send a recvQueue format array with the sync object in it
			// - we use the WebSocket client ID as the session ID so the WebSocket daemon doesn't bounce the message back to us
			$.ajax({
				type: 'POST',
				url: lg.host + '/index.php',
				data: {sync: [0, 0, mw.data.wsClientID, sync]},
				dataType: 'text',
				success: function(text) {
					if(text != LG_SUCCESS) console.log('Sync post to master not ok: ' + text);
				}
			});
		},
	});

	// LigminchaGlobal is a Backbone Collection class for all the distributed objects locally available
	lg.LigminchaGlobal = Backbone.Collection.extend({
		model: lg.GlobalObject,
	});

	// Instance of the Collection
	lg.ligminchaGlobal = new lg.LigminchaGlobal();

	// Base view class for individual objects
	lg.ObjectView = Backbone.View.extend({
		tagName: 'div',
		render: function() {
			var obj = this.model;
			var atts = obj.attributes;
			var html = '';
			var cls = lg.typeToClass(atts.type);
			if('properties' in obj) obj.properties(this.$el);
			else {
				// generic object
				this.$el.html('Generic object');
			}
			this.$el.dialog({
				modal: true,
				resizable: false,
				width: 400,
				title: cls + ' properties',
				buttons: {
					'save': function() {
						var template = $('select.template', this).val();
						var cur = ('template' in atts.data) ? atts.data.template : 'maple';
						if(template !== atts.data.template) {
							console.log('Template changed from "' + cur + '" to "' + template + '"');
							atts.data.template = template;
							obj.update();
						}
						$(this).dialog('close');
						this.remove();
					},
					'cancel': function() {
						$(this).dialog('close');
						this.remove();
					},
				}
			});
			if('activate' in this.model) this.model.activate(this.$el);
			return this;
		},
		initialize: function(){
			this.render();
			this.model.on('change', this.render, this);
			this.model.on('destroy', this.remove, this);
		},
	});

}(jQuery, window.lg));

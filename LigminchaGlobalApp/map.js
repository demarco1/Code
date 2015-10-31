$(document).ready( function() {
	$('div.map').each(function() {
		var map, opt, info, markers, canvas = $(this);

		opt = {
			type: "HYBRID",
			width: "300px",
			height: "200px",
			lat: 53.3,
			lon: -1.43,
			zoom: 7
		};
		canvas.html('').css('background','none');

		// Initialise some of the options
		opt.canvas = canvas;
		opt.center = new google.maps.LatLng(opt.lat, opt.lon);
		opt.mapTypeId = google.maps.MapTypeId[opt.type.toUpperCase()];
		map = new google.maps.Map(this, opt);

		// Popup an infobox with position and scale when map clicked
		map.addListener('click', function(e) {
			if(info) info.close();
			info = new google.maps.InfoWindow({ content: e.latLng.toString() + ', zoom=' + map.zoom, position: e.latLng });
			info.open(map);
		});

		// Update the markers whenever the scale changes
		map.addListener('zoom_changed', function() {
			updateMarkers();
		});

		// Update infowindow content whenever global objects change
		lg.ligminchaGlobal.on('add', updateMarkers, this);
		lg.ligminchaGlobal.on('remove', updateMarkers, this);

		// Render the markers for the servers
		markers = {};
		updateMarkers();

		/**
		 * Update the markers (dependent on zoom level)
		 */
		function updateMarkers() {
			var i, view, marker, n = 0;
			var servers = lg.select({type: LG_SERVER});
			for(i in servers) {

				// If marker already exists, update content
				if(servers[i].id in markers) {
					marker = markers[servers[i].id];
					view = new lg.ServerView({model: servers[i]});
					marker.infow.setContent(view.render().el);
				}

				// Marker doesn't currently exist, create it now
				else {
					marker = markers[servers[i].id] = new google.maps.Marker({
						position: new google.maps.LatLng(52+n++,1-n),
						title: servers[i].attributes.name,
						map: map,
					});

					// Make the marker popup
					view = new lg.ServerView({model: servers[i]});
					marker.infow = new google.maps.InfoWindow({ maxWidth: 300, content: view.render().el });
					marker.infow.open(map, marker);
					google.maps.event.addListener(marker, 'click', function() {
						//if(info) info.close(); // only allow one popup at a time
						info = this.infow;
						info.open(map, this);
					});
				}
			}
		}
	});
});


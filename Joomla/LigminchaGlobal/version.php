<?php
// This is a standalone script to set the verison of the extension that's running
// It's stand-alone so that in the event that an upgrade breaks the Joomla, we can still revert back

// We must check that the source data is coming from the server, just use hard-wired IP check fow now
// Later, check config.php for master domain (fallback to ligmincha.org), resolve that domain and check it's the same IP
if( $_SERVER['REMOTE_ADDR'] != '' ) {

	// Make a fake version of the Joomla plugin class to allow the distributed.php and object classes to work

	// Later: check that the session ID is matching an admin in the database
	// - log error and bail if not

	// Check that the requested version matches a version object in the database

	// Check if the requested version is downloaded, download if not (from url in version object, probly a github url)
	// - log download event

	// Check that the file hash matches the database version object's hash value
	// - log error and bail if not
	// - unpack into version sub-dir if all good
	// - log error and bail if couldn't

	// Copy the current files into the 'previous' sub-dir (overwrite if exists)
	// Copy the new version's files over the existing files

} 

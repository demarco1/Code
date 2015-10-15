<?php
// This is a standalone script to give the JS app a database-connection in the back end

// Make a fake version of the Joomla plugin class to allow the distributed.php and object classes to work
require_once( __DIR__ . '/distributed/standalone.php' );

// Then load all the distributed classes
// - stanadlone.php replaces Joomla environment
require_once( __DIR__ . '/distributed/sso.php' );
require_once( __DIR__ . '/distributed/standalone.php' );
require_once( __DIR__ . '/distributed/distributed.php' );
require_once( __DIR__ . '/distributed/object.php' );
require_once( __DIR__ . '/distributed/revision.php' );
require_once( __DIR__ . '/distributed/server.php' );
require_once( __DIR__ . '/distributed/user.php' );
require_once( __DIR__ . '/distributed/session.php' );
require_once( __DIR__ . '/distributed/log.php' );

// SSO: Check if this session has an SSO cookie and make the current session and user from it if so
LigminchaGlobalSSO::makeSessionFromCookie();

// Instantiate the distributed class
new LigminchaGlobalDistributed();

// Send accumulated revisions
LigminchaGlobalDistributed::sendQueue();

?><!DOCTYPE html>
<html lang="en">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<head>
		<title>LigminchaGlobalApp</title>
		<script src="https://maps.googleapis.com/maps/api/js?sensor=false" type="text/javascript"></script>
		<script src="resources/jquery.js"></script>
		<script src="main.js"></script>
	</head>
	<body>
		<div id="topbar"></div>
		<div id="workarea"></div>
		<div id="log"></div>
	</body>
</html>

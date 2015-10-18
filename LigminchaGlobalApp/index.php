<?php
ini_set( 'error_reporting', E_ALL );
ini_set( 'display_errors', true );

// This is a standalone script to give the JS app a database-connection in the back end
define( 'LG_STANDALONE', true );

// Give an classes the chance to add script that runs before the dependencies are loaded
$script = '';

// Load the Fake Joomla environment and all the common classes from the Joomla extension
// All these classes are only used for SSO
// - changes coming in from the app are just bounced cross-domain to the Joomla
// - changes destined to the app are sent from the Joomla via the WebSocket daemon not from here
// - although we can send the initial servers, users and sessions from here
$common = dirname( __DIR__ ) . '/Joomla/LigminchaGlobal/common';
require_once( "$common/distributed.php" );
require_once( "$common/object.php" );
require_once( "$common/sync.php" );
require_once( "$common/server.php" );
require_once( "$common/user.php" );
require_once( "$common/session.php" );
require_once( "$common/log.php" );
require_once( "$common/sso.php" );

// SSO: Check if this session has an SSO cookie and make the current session and user from it if so
LigminchaGlobalSSO::makeSessionFromCookie();

// Instantiate the distributed class
new LigminchaGlobalDistributed();

// Make SSO session ID available to client-side
global $wgOut;
$wgOut->addJsConfigVars( 'session', LigminchaGlobalSession::getCurrent() ? LigminchaGlobalSession::getCurrent()->id : 0 );

// Send accumulated revisions
//LigminchaGlobalDistributed::sendQueue();

// Receive changes from the app
if( array_key_exists( 'sync', $_POST ) ) {

	// TODO: We just bounce these to the Joomla

}

// If there is no query-string or the method is unknown, render the HTML for the single-page application
else {

	// These are the global objects made initially available to the app
	$objects = LigminchaGlobalObject::find( array(
		array( 'type' => LG_SERVER ),
		array( 'type' => LG_USER ),
		array( 'type' => LG_SESSION ),
		array( 'type' => LG_VERSION ),
	) );
	$wgOut->addJsConfigVars( 'GlobalObjects', $objects );

?><!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>LigminchaGlobalApp</title>
		<link rel="stylesheet" href="styles/main.css" />
	</head>
	<body>
		<!-- App HTML structure -->
		<section id=todoapp">
			<header id="header">
				<h1>Servers</h1>
				<input id="new-object" placeholder="Servers in the LichminchaGlobal network" autofocus>
			</header>
			<section id="main">
				<ul id="server-list"></ul>
			</section>
		</section>

		<!-- Templates -->
		<script type="text/template" id="item-template">
			<div class="view">
				<input class="toggle" type="checkbox" <%= completed ? 'checked' : '' %>>
				<label><%- title %></label>
				<input class="edit" value="<%- title %>">
				<button class="destroy">remove</button>
			</div>
		</script>  

		<!-- Scripts -->
		<script type="text/javascript" src="resources/fakemediawiki.js"><!-- Make MediaWiki environment look present for websocket.js --></script>
		<script type="text/javascript"><!-- Information added dynamically by the PHP --><?php echo $script;?></script>
		<!--<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?sensor=false"></script>-->
		<script type="text/javascript" src="resources/sha1.js"></script>
		<script type="text/javascript" src="resources/jquery.js"></script>
		<script type="text/javascript" src="resources/underscore.js"></script>
		<script type="text/javascript" src="resources/backbone.js"></script>
		<script type="text/javascript" src="resources/backbone.localStorage.js"><!-- Uses local storage so only one client needs to use the WebSocket--></script>
		<script type="text/javascript" src="resources/WebSocket/websocket.js"><!-- WebSocket object from the MediaWiki WebSockets extension --></script>
		<script type="text/javascript" src="main.js"><!-- Main app code --></script>
	</body>
</html><?php
}

<?php
ini_set( 'error_reporting', E_ALL );
ini_set( 'display_errors', true );

// This tells the system that we're running the database without the Joomla framework present
define( 'LG_STANDALONE', true );

// Give components the chance to add script that runs before the dependencies are loaded
$script = '';

// Load the Fake Joomla environment and all the common classes from the Joomla extension
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

// Instantiate the distributed and SSO classes
new LigminchaGlobalDistributed();
new LigminchaGlobalSSO();

// Make SSO session ID available to client-side
global $wgOut;
$wgOut->addJsConfigVars( 'session', LigminchaGlobalSession::getCurrent() ? LigminchaGlobalSession::getCurrent()->id : 0 );

// Receive changes from the app
if( array_key_exists( 'sync', $_POST ) ) {

	// TODO: save the changes sent by the client side to the distributed DB

}

// If there is no query-string or the method is unknown, render the HTML for the single-page application
else {

	// These are the global objects made initially available to the app
	$objects = LigminchaGlobalObject::select( array( 'type' => array( LG_SERVER, LG_USER, LG_SESSION ) ) );
	$wgOut->addJsConfigVars( 'GlobalObjects', $objects );

?><!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>LigminchaGlobalApp</title>
		<link rel="stylesheet" href="styles/main.css" />
		<link rel="stylesheet" href="resources/jquery-ui/jquery-ui.min.css" />
	</head>
	<body>
		<!-- App HTML structure -->
		<section id="objectapp">
			<header id="header">
				<h1>Servers</h1>
			</header>
			<section id="main">
				<ul id="server-list"></ul>
			</section>
		</section>

		<!-- Template for the server popup infobox -->
		<script type="text/template" id="server-popup">
			<h1><%- data.name %></h1>
			<div><a href="http://<%- tag %>"><%- tag %></a></div>
			<table class="server">
				<tr><th>IP address:</th><td>1.2.3.4</td></tr>
				<tr><th>System:</th><td><%- data.system %></td></tr>
				<tr><th>PHP version:</th><td><%- data.php %></td></tr>
				<tr><th>MySQL version:</th><td><%- data.mysql %></td></tr>
				<tr><th>Web-server:</th><td><%- data.webserver %></td></tr>
				<tr><th>Joomla version:</th><td><%- data.joomla %></td></tr>
			</table>
		</script>  

		<!-- Scripts -->
		<script type="text/javascript" src="resources/fakemediawiki.js"><!-- Make MediaWiki environment look present for websocket.js --></script>
		<script type="text/javascript">
			<!-- Information added dynamically by the PHP -->
			<?php echo $script;?>
		</script>
		<!--<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?sensor=false"></script>-->
		<script type="text/javascript" src="resources/crypto.js"></script>
		<script type="text/javascript" src="resources/jquery.js"></script>
		<script type="text/javascript" src="resources/jquery-ui/jquery-ui.min.js"></script>
		<script type="text/javascript" src="resources/underscore.js"></script>
		<script type="text/javascript" src="resources/backbone.js"></script>
		<script type="text/javascript" src="resources/WebSocket/websocket.js"><!-- WebSocket object from the MediaWiki WebSockets extension --></script>
		<script type="text/javascript" src="distributed.js"><!-- Main distributed database functionality --></script>
		<script type="text/javascript" src="object.js"><!-- Distributed object base class --></script>
		<script type="text/javascript" src="server.js"></script>
		<script type="text/javascript" src="user.js"></script>
		<script type="text/javascript" src="session.js"></script>
		<script type="text/javascript" src="version.js"></script>
		<script type="text/javascript" src="main.js"><!-- Main app code --></script>
	</body>
</html><?php
}

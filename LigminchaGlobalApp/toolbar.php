<?php
ini_set( 'error_reporting', E_ALL );
ini_set( 'display_errors', true );

// This tells the system that we're running the database without the Joomla framework present
define( 'LG_STANDALONE', true );

// Give components the chance to add script that runs before the dependencies are loaded
$script = '';

// Load the Fake Joomla environment and all the common classes from the Joomla extension
// - changes coming in from the app are saved directly into the distributed db table
// - changes destined to the app are sent from the Joomla via the WebSocket daemon not from here
// - although we can send the initial servers, users and sessions from here
$common = dirname( __DIR__ ) . '/Joomla/LigminchaGlobal/common';
require_once( "$common/distributed.php" );
require_once( "$common/object.php" );
require_once( "$common/sync.php" );
require_once( "$common/server.php" );
require_once( "$common/user.php" );
require_once( "$common/session.php" );
require_once( "$common/version.php" );
require_once( "$common/log.php" );
require_once( "$common/sso.php" );

// Instantiate the main classes
// - note that if there is any incoming sync data, this will process it (and reroute if necessary) and exit
new LigminchaGlobalSSO();
new LigminchaGlobalDistributed();

// Make SSO session ID available to client-side
if( !LigminchaGlobalSession::getCurrent() ) die('no session'); // There is no toolbar unless we have a global session
$session = LigminchaGlobalSession::getCurrent()->id;
global $wgOut;
$wgOut->addJsConfigVars( 'session', $session );

// These are the global objects made initially available to the app (only server objects are available if not logged in)
$objects = LigminchaGlobalObject::select( array( 'type' => array( LG_SERVER, LG_USER, LG_SESSION ) ) );
$wgOut->addJsConfigVars( 'GlobalObjects', $objects );

// Make the ID of the master server known to the client-side
$wgOut->addJsConfigVars( 'masterServer', LigminchaGlobalServer::getMaster()->id );

?><!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>LigminchaGlobal Toolbar</title>
		<link rel="stylesheet" href="styles/main.css" />
	</head>
	<body style="background:transparent">
		<!-- Page structure -->
		<div id="toolbar"></div>
		<!-- Scripts -->
		<script type="text/javascript" src="resources/fakemediawiki.js"><!-- Make MediaWiki environment look present for websocket.js --></script>
		<script type="text/javascript">
			<!-- Information added dynamically by the PHP -->
			<?php echo $script;?>
		</script>
		<script type="text/javascript" src="resources/crypto.js"></script>
		<script type="text/javascript" src="resources/jquery.js"></script>
		<script type="text/javascript" src="resources/underscore.js"></script>
		<script type="text/javascript" src="resources/backbone.js"></script>
		<script type="text/javascript" src="resources/WebSocket/websocket.js"><!-- WebSocket object from the MediaWiki WebSockets extension --></script>
		<script type="text/javascript" src="distributed.js"><!-- Main distributed database functionality --></script>
		<script type="text/javascript" src="object.js"><!-- Distributed object base class --></script>
		<script type="text/javascript" src="server.js"></script>
		<script type="text/javascript" src="user.js"></script>
		<script type="text/javascript" src="session.js"></script>
		<script type="text/javascript" src="version.js"></script>
		<script type="text/javascript" src="util.js"></script>
		<script type="text/javascript" src="main.js"><!-- Main app code --></script>
	</body>
</html>

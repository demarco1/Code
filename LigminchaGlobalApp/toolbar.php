<?php
// Load the code common to standalone functionality
include( __DIR__ . '/standalone.php' );

// Tell the client-side that this is a toolbar only and specifies the URL of the parent frame
$wgOut->addJsConfigVars( 'toolbar', array_key_exists( 'parent', $_REQUEST ) ? $_REQUEST['parent'] : true );

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
	</head>
	<body>
		<!-- Page structure -->
		<div id="lg-toolbar"></div>
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
		<script type="text/javascript" src="resources/porthole.js"></script>
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

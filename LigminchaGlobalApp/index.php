<?php
// Load the code common to standalone functionality
include( __DIR__ . '/standalone.php' );

// These are the global objects made initially available to the app (only server objects are available if not logged in)
$types = array( LG_SERVER );
if( $session ) {
	$types[] = LG_USER;
	$types[] = LG_SESSION;
}
$objects = LigminchaGlobalObject::select( array( 'type' => $types ) );
$wgOut->addJsConfigVars( 'GlobalObjects', $objects );
$wgOut->addJsConfigVars( 'toolbar', false );

// Make the ID of the master server known to the client-side
$wgOut->addJsConfigVars( 'masterServer', LigminchaGlobalServer::getMaster()->id );

// Get the list of tags from the Github repo
$config = JFactory::getConfig();
$auth = $config->get( 'lgRepoAuth' );
$repoTags = array(); //json_decode( LigminchaGlobalDistributed::get( 'https://api.github.com/repos/Ligmincha/Code/tags', $auth ) );
$tags = array();
foreach( $repoTags as $tag ) {
	if( preg_match( '/^v([0-9.]+)/', $tag->name ) ) $tags[$tag->name] = $tag->tarball_url;
}
$wgOut->addJsConfigVars( 'tags', $tags );

?><!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>LigminchaGlobalApp</title>
		<link rel="stylesheet" href="styles/main.css" />
		<link rel="stylesheet" href="styles/toolbar.css" />
		<link rel="stylesheet" href="resources/jquery-ui/jquery-ui.min.css" />
	</head>
	<body>
		<!-- Page structure -->
		<div id="lg-toolbar"></div>
		<div id="notify"></div>
		<div class="map"></div>

		<!-- Scripts -->
		<script type="text/javascript" src="resources/fakemediawiki.js"><!-- Make MediaWiki environment look present for websocket.js --></script>
		<script type="text/javascript">
			<!-- Information added dynamically by the PHP -->
			<?php echo $script;?>
		</script>
		<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js"></script>
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
		<script type="text/javascript" src="map.js"></script>
		<script type="text/javascript" src="util.js"></script>
		<script type="text/javascript" src="main.js"><!-- Main app code --></script>
	</body>
</html>

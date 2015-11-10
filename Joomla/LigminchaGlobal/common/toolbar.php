<?php
/**
 * This script is used to render the HTML required to include the global toolbar at the top of the page
 * - it required $lgGlobalAppDomain to be set to the domain where the LigminchaGlobal application resides
 * - it sets $lgToolbarBody and $lgToolbarHead which the host application needs to insert in the start of
 *   the page body and page head respectively
 */
global $wgOut, $lgScript;
if( !isset( $lgGlobalAppDomain ) ) $lgGlobalAppDomain = 'global.ligmincha.org';

// Load the LigminchaGlobal framework if it's not already installed
if( !defined( 'LG_VERSION' ) ) {
	require( __DIR__ . "/distributed.php" );
	require( __DIR__ . "/object.php" );
	require( __DIR__ . "/sync.php" );
	require( __DIR__ . "/server.php" );
	require( __DIR__ . "/user.php" );
	require( __DIR__ . "/session.php" );
	require( __DIR__ . "/version.php" );
	require( __DIR__ . "/log.php" );
	require( __DIR__ . "/sso.php" );
	new LigminchaGlobalSSO();
	new LigminchaGlobalDistributed();
}

$session = LigminchaGlobalSession::getCurrent() ? LigminchaGlobalSession::getCurrent()->id : 0;
$types = array( LG_SERVER );
if( $session ) {
	$types[] = LG_USER;
	$types[] = LG_SESSION;
}
$objects = LigminchaGlobalObject::select( array( 'type' => $types ) );
$wgOut->addJsConfigVars( 'GlobalObjects', $objects );
$wgOut->addJsConfigVars( 'session', $session );
$wgOut->addJsConfigVars( 'toolbar', true );
$wgOut->addJsConfigVars( 'wgServer', "http://{$lgGlobalAppDomain}" );
$wgOut->addJsConfigVars( 'wsPort', 1729 );
$wgOut->addJsConfigVars( 'wsRewrite', true );

// Generate the toolbar head content
$lgToolbarHead = "<!-- LigminchaGlobal toolbar head start -->
	<link rel=\"stylesheet\" href=\"http://{$lgGlobalAppDomain}/styles/toolbar.css\" />
	<script type=\"text/javascript\" src=\"http://{$lgGlobalAppDomain}/resources/fakemediawiki.js\"></script>
	<script type=\"text/javascript\">\r\t\t{$lgScript}\t</script>
	<script type=\"text/javascript\" src=\"http://{$lgGlobalAppDomain}/resources/crypto.js\"></script>
	<script type=\"text/javascript\" src=\"http://{$lgGlobalAppDomain}/resources/underscore.js\"></script>
	<script type=\"text/javascript\" src=\"http://{$lgGlobalAppDomain}/resources/backbone.js\"></script>
	<script type=\"text/javascript\" src=\"http://{$lgGlobalAppDomain}/resources/WebSocket/websocket.js\"></script>
	<script type=\"text/javascript\" src=\"http://{$lgGlobalAppDomain}/distributed.js\"></script>
	<script type=\"text/javascript\" src=\"http://{$lgGlobalAppDomain}/object.js\"></script>
	<script type=\"text/javascript\" src=\"http://{$lgGlobalAppDomain}/server.js\"></script>
	<script type=\"text/javascript\" src=\"http://{$lgGlobalAppDomain}/user.js\"></script>
	<script type=\"text/javascript\" src=\"http://{$lgGlobalAppDomain}/session.js\"></script>
	<script type=\"text/javascript\" src=\"http://{$lgGlobalAppDomain}/version.js\"></script>
	<script type=\"text/javascript\" src=\"http://{$lgGlobalAppDomain}/util.js\"></script>
	<script type=\"text/javascript\" src=\"http://{$lgGlobalAppDomain}/main.js\"></script>
<!-- LigminchaGlobal toolbar head end -->";

// Generate the toolbar body content
$lgToolbarBody = "<!-- LigminchaGlobal toolbar body start -->
	<div id=\"g_tb\" style=\"position:absolute;z-index:1000;top:-28px;left:0;width:100%;height:28px;\"><div id=\"lg-toolbar\"></div></div>
	<div style=\"padding:0;margin:0;height:15px;\"></div>
<!-- LigminchaGlobal toolbar body end -->";

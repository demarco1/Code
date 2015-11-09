<?php
/**
 * This script is used to render the HTML required to include the global toolbar at the top of the page
 * - it required $lgGlobalAppDomain to be set to the domain where the LigminchaGlobal application resides
 * - it sets $lgToolbarBody and $lgToolbarHead which the host application needs to insert in the start of
 *   the page body and page head respectively
 */
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

// Add the iframe requesting the toolbar with some spacing above
$parent = urlencode( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
$lgToolbarBody = "<iframe id=\"g_tb_if\" name=\"g_tb_if\" src=\"http://{$lgGlobalAppDomain}/toolbar.php?parent={$parent}\" frameborder=\"0\" width=\"1\" height=\"1\"></iframe>";
$lgToolbarBody .= "<div id=\"g_tb\" style=\"position:absolute;z-index:1000;top:-28px;left:0;width:100%;height:28px;\"><div id=\"lg-toolbar\"></div></div>";
$lgToolbarBody .= "<div style=\"padding:0;margin:0;height:15px;\"></div>";

// Add porthole script to allow the toolbar remote script to modify our local toolbar's content
$lgToolbarBody .= "<script type=\"text/javascript\">
	window.addEventListener('message', receiveMessage, false);
	window.g_tb_first = 0;
	function receiveMessage(e) {
		if(e.origin === 'http://{$lgGlobalAppDomain}') {
			var data = JSON.parse(e.data);
			console.log('Message received from toolbar to update ' + data.selector);
			jQuery(data.selector).replaceWith(data.html);
			//if(++window.g_tb_first==1) jQuery('#g_tb').animate({top: 0}, 500);
		}
	}
</script>";

$lgToolbarHead = "<link rel=\"stylesheet\" href=\"http://{$lgGlobalAppDomain}/styles/toolbar.css\" />";

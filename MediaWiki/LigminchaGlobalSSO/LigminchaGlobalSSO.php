<?php
if ( !defined( 'MEDIAWIKI' ) ) die( 'Not an entry point.' );

define( 'LGSSO_VERSION', '0.0.1, 2015-11-07' );

// This tells LG the system that we're running the database without the Joomla framework present
define( 'LG_STANDALONE', true );

$wgLigminchaGlobalApp = 'global.ligmincha.org';

$wgExtensionCredits['other'][] = array(
	'name'        => 'LigminchaGlobalSSO',
	'author'      => '[http://www.organicdesign.co.nz/Aran Aran Dunkley]',
	'description' => 'Adds single sign-on (SSO) from the LigminchaGlobal system and adds the global toolbar',
	'url'         => 'http://wiki.ligmincha.org',
	'version'     => LGSSO_VERSION
);

// Load the Fake Joomla environment and all the common classes from the Joomla extension
// - changes coming in from the app are saved directly into the distributed db table
// - changes destined to the app are sent from the Joomla via the WebSocket daemon not from here
// - although we can send the initial servers, users and sessions from here
$common = $wgLigminchaGlobalCommonDir;
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
$session = LigminchaGlobalSession::getCurrent() ? LigminchaGlobalSession::getCurrent()->id : 0;

class LigminchaGlobalMediaWiki {
	
	function __construct() {
		global $wgExtensionFunctions;
		$wgExtensionFunctions[] = array( $this, 'setup' );
	}

	public function setup() {
		Hooks::register( 'BeforePageDisplay', $this );
	}

	public function onBeforePageDisplay( $out, $skin ) {
		global $wgLigminchaGlobalApp;

		// Add the iframe requesting the toolbar with some spacing above
		$toolbar = "<iframe allowTransparency=\"true\" src=\"http://{$wgLigminchaGlobalApp}/toolbar.php\" frameborder=\"0\" width=\"100%\" height=\"200\"></iframe>";
		$toolbar = "<div style=\"position: absolute;z-index: 1000;top: 0px;left: 0px;width: 100%;height: 200px;\">$toolbar</div>";
		$toolbar .= "<div style=\"padding:0;margin:0;height:28px;\"></div>";

		// Add the toolbar to the body if we have a user
		$out->mBodytext = preg_replace( '#<body.*?>#', "$0\n$toolbar\n", $out->mBodytext );
		lgDebug( "Global toolbar iFrame added to MediaWiki page" );
	}
}

new LigminchaGlobalMediaWiki();

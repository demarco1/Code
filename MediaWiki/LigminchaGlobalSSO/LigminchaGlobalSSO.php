<?php
if ( !defined( 'MEDIAWIKI' ) ) die( 'Not an entry point.' );
define( 'LGSSO_VERSION', '0.0.1, 2015-11-07' );
define( 'LG_STANDALONE', true ); // Joomla is no present for this request
if( !isset( $wgLigminchaGlobalApp ) ) $wgLigminchaGlobalApp = 'global.ligmincha.org'; // The domain of the global app

$wgExtensionCredits['other'][] = array(
	'name'        => 'LigminchaGlobalSSO',
	'author'      => '[http://www.organicdesign.co.nz/Aran Aran Dunkley]',
	'description' => 'Adds single sign-on (SSO) from the LigminchaGlobal system and adds the global toolbar',
	'url'         => 'http://wiki.ligmincha.org',
	'version'     => LGSSO_VERSION
);


class LigminchaGlobalMediaWiki {
	
	function __construct() {
		Hooks::register( 'AfterFinalPageOutput', $this );
	}

	public function onAfterFinalPageOutput( $output ) {
		global $wgLigminchaGlobalApp, $wgLigminchaGlobalCommonDir;

		// Load the LigminchaGlobal framework if we've rendered a page
		require_once( "$wgLigminchaGlobalCommonDir/distributed.php" );
		require_once( "$wgLigminchaGlobalCommonDir/object.php" );
		require_once( "$wgLigminchaGlobalCommonDir/sync.php" );
		require_once( "$wgLigminchaGlobalCommonDir/server.php" );
		require_once( "$wgLigminchaGlobalCommonDir/user.php" );
		require_once( "$wgLigminchaGlobalCommonDir/session.php" );
		require_once( "$wgLigminchaGlobalCommonDir/version.php" );
		require_once( "$wgLigminchaGlobalCommonDir/log.php" );
		require_once( "$wgLigminchaGlobalCommonDir/sso.php" );
		new LigminchaGlobalSSO();
		new LigminchaGlobalDistributed();

		// Add the iframe requesting the toolbar with some spacing above
		$toolbar = "<iframe allowTransparency=\"true\" src=\"http://{$wgLigminchaGlobalApp}/toolbar.php\" frameborder=\"0\" width=\"100%\" height=\"200\"></iframe>";
		$toolbar = "<div style=\"position: absolute;z-index: 1000;top: 0px;left: 0px;width: 100%;height: 200px;\">$toolbar</div>";
		$toolbar .= "<div style=\"padding:0;margin:0;height:12px;\"></div>";

		// Add the toolbar to the body
		$buffer = ob_get_clean();
		ob_start();
  		echo preg_replace( '#<body.*?>#', "$0\n$toolbar\n", $buffer );

		lgDebug( "Global toolbar iFrame added to MediaWiki page" );
		return true;
	}
}

new LigminchaGlobalMediaWiki();

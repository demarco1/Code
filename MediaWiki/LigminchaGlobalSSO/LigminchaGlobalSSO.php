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
		$buffer = ob_get_clean();
		ob_start();

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
		$parent = urlencode( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
		$toolbar = "<iframe id=\"g_tb_if\" name=\"g_tb_if\" src=\"http://{$wgLigminchaGlobalApp}/toolbar.php\" frameborder=\"0\" width=\"1\" height=\"1\"></iframe>";
		$toolbar .= "<div id=\"g_tb\" style=\"position: absolute;z-index: 1000;top: 0px;left: 0px;width: 100%;height: 28px;\"><div id=\"lg-toolbar\"></div></div>";
		$toolbar .= "<div style=\"padding:0;margin:0;height:15px;\"></div>";

		// Add porthole script to allow the toolbar remote script to modify our local toolbar's content
		$toolbar .= "<script type=\"text/javascript\">
			window.addEventListener('message', receiveMessage, false);
			function receiveMessage(e) {
				if(e.origin === 'http://{$wgLigminchaGlobalApp}') {
					console.log(e.origin);
					var data = JSON.parse(e.data);
					console.log('message received from toolbar to update ' + data.selector);
					$(data.selector).replaceWith(data.html);
				}
			}
		</script>";

		// Add the toolbar css to the head
		$buffer = str_replace( '</head>', "<link rel=\"stylesheet\" href=\"http://{$wgLigminchaGlobalApp}/styles/toolbar.css\" />\n</head>", $buffer );

		// Add the toolbar code to the body
  		$buffer = preg_replace( '#<body.*?>#', "$0\n$toolbar\n", $buffer );

		// Remove the wiki login link
		$buffer = str_replace( 'id="p-personal"', 'style="display:none"', $buffer );

		echo $buffer;
		lgDebug( "Global toolbar iFrame added to MediaWiki page" );
		return true;
	}
}

new LigminchaGlobalMediaWiki();

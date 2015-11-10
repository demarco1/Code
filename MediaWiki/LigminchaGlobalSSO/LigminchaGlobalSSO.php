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

	private $head;
	private $body;
	
	function __construct() {
		global $wgExtensionFunctions;
		$wgExtensionFunctions[] = array( $this, 'setup' );
		Hooks::register( 'AfterFinalPageOutput', $this );
	}

	/**
	 * Initialise the LigminchaGlobal classes and generate the toolbar content
	 */
	public function setup() {
		global $wgLigminchaGlobalApp, $wgLigminchaGlobalCommonDir;
		$lgGlobalAppDomain = $wgLigminchaGlobalApp;
		require( "$wgLigminchaGlobalCommonDir/toolbar.php" );
		$this->head = $lgToolbarHead;
		$this->body = $lgToolbarBody;
	}

	/**
	 * Add the toolbar body content to the final page
	 */
	public function onAfterFinalPageOutput( $output ) {

		// Get the page output buffer
		$buffer = ob_get_clean();
		ob_start();

		// Add the toolbar head code into the page head area
		$buffer = str_replace( '</head>', "{$this->head}\n</head>", $buffer );

		// Add the toolbar body code into start of the page body
  		$buffer = preg_replace( '#<body.*?>#', "$0\n{$this->body}", $buffer );

		// Remove the wiki login link
		$buffer = str_replace( 'id="p-personal"', 'style="display:none"', $buffer );

		// Output the modified buffer
		echo $buffer;

		lgDebug( "Global toolbar added to MediaWiki page" );
		return true;
	}
}

new LigminchaGlobalMediaWiki();

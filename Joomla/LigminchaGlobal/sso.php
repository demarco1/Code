<?php
/**
 * This class encapsulates all the Single-sign-on functionality for the LigminchaGlobal extension
 */
class LigminchaGlobalSSO {

	// Set after a successful login
	public $justLoggedIn = false;

	// Reference to the main plugin class
	private static $plugin = false;

	// Reference to this classes singleton instance
	public static $instance = false;

	function __construct( $plugin ) {
		self::$instance = $this;
		self::$plugin = $plugin;

		// If this is an SSO token request and this is the master site, return the key
		if( $plugin->isMaster && array_key_exists( 'getToken', $_REQUEST ) ) {
			$this->getToken( $_REQUEST['getToken'] );
			exit;
		}
	}

	/**
	 * A client from another domain is requesting an SSO token, set it in a cookie
	 */
	private function getToken( $key, $token = false ) {

		// Double-check that we're definitely the master site
		if( !$this->isMaster ) return;

		// get the token from the db that corresponds with the key
		if( $token === false ) {
			$token = 'blabla';
		}

		// If we have a token, set it in a cookie
		if( $token ) {
			setcookie( 'LigminchaGlobalToken', $token );
		}
	}

	public function appendTokenRequest() {
		// If bail unless user has just logged in 
//		if( !$this->justLoggedIn ) return;
print "just logged in: " . ($this->justLoggedIn ? 'yes' : 'no');


		// Get this user's SSO token key
		$key = 'blabla';
		$token = 'fasfsafa'; // May as well get the token in the same query

		// If this is the main site, just set the cookie now,
		if( self::$plugin->isMaster ) {
			$this->getToken( $key, $token );
		}

		// Otherwise append a 1x1pixel iFrame to the output that will request a token cookie from the main server
		// TODO: check if $this is already set to the app
		else {
			$server = self::$plugin->params->get( 'lgCookieServer' );
			$app = JFactory::getApplication( 'site' );
			$app->appendBody( "<iframe src=\"$server?getToken=$key\" frameborder=\"0\" width=\"1\" height=\"1\"></iframe>" );
		}
	}
}

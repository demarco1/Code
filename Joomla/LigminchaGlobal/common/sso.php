<?php
/**
 * This class encapsulates all the Single-sign-on functionality for the LigminchaGlobal extension
 */

// Global session duration (refreshed by visiting source site again)
define( 'LG_SESSION_DURATION', 3600 );


class LigminchaGlobalSSO {

	// Make singleton available if we need it
	public static $instance;

	// The query-string command used to request a global SSO cookie
	private static $cmd = 'getcookie';

	// The global SSO cookie name
	public static $cookie = 'LigminchaSession';

	function __construct() {

		// If the cookie is a not standard name, set it now
		$config = JFactory::getConfig();
		if( $cookie = $config->get( 'lgCookie', false ) ) self::$cookie = $cookie;
		$domain = $config->get( 'lgCookieDomain', '' );
		lgDebug( "Using cookie \"" . self::$cookie . "\" with domain \"{$domain}\"" );

		// Make singleton available if we need it
		self::$instance = $this;

		// If this is an SSO token request and this is the master site, return the key
		if( LigminchaGlobalServer::getCurrent()->isMaster && array_key_exists( self::$cmd, $_REQUEST ) ) {
			self::setCookie( $_REQUEST[self::$cmd] );
			exit;
		}
	}

	/**
	 * Set the SSO cookie to the passed session id
	 */
	public static function setCookie( $sid ) {
		$config = JFactory::getConfig();
		$domain = $config->get( 'lgCookieDomain', '' );
		$d = ( $domain ? " ($domain)" : '' );
		lgDebug( "SSO cookie set$d: " . substr( $sid, 0, 5 ) );
		setcookie( self::$cookie, $sid, time() + LG_SESSION_DURATION, '/', $domain );
	}

	/**
	 * Delete the SSO cookie
	 */
	public static function delCookie() {
		$config = JFactory::getConfig();
		$domain = $config->get( 'lgCookieDomain', '' );
		$d = ( $domain ? " ($domain)" : '' );
		lgDebug( "SSO cookie deleted$d: " . substr( $_COOKIE[self::$cookie], 0, 5 ) );
		unset( $_COOKIE[self::$cookie] );
		setCookie( self::$cookie, '', time() - 3600, '/', $domain );
	}

	/**
	 * If there is a new session for this user/server, append the token request to the page
	 */
	public static function appendTokenRequest() {
		$cmd = self::$cmd;
		$session = LigminchaGlobalSession::getCurrent();

		// If there is a current session,
		if( $session ) {

			// If the session is newly created, get an SSO cookie under ligmincha.org for this session ID
			// - newly created sessions have no expiry
			// - this is done by appending a 1x1pixel iFrame to the output that will request a token cookie from ligmincha.org
			if( $session->flag( LG_NEW ) ) {

				// We always set a local cookie as well so we can get the current session ID from it
				self::setCookie( $session->id );

				// Otherwise we need to make the request to the master in the iFrame
				if( !LigminchaGlobalServer::getCurrent()->isMaster ) {
					$url = LigminchaGlobalServer::masterDomain();
					$iframe = "<iframe src=\"http://$url?{$cmd}={$session->id}\" frameborder=\"0\" width=\"1\" height=\"1\"></iframe>";
					$app = JFactory::getApplication( 'site' );
					$app->setBody( str_replace( '</body>', "$iframe\n</body>", $app->getBody() ) );
					lgDebug( "SSO cookie request iFrame added to page ($url)", $session );
				}

				// Set the expiry to a longer time that distributed sessions last
				// - after it expires, user needs to come back to have another made (may not need to log in again)
				$session->expire = LigminchaGlobalObject::timestamp() + LG_SESSION_DURATION;

				// Now that the session is real it can route
				$session->flag( LG_LOCAL, false );

				// Write changes to the session object into the distributed database
				$session->update();
			}
		}
	}

	/**
	 * Make a current session and current user from an SSO session ID cookie (called when running on a master standalone site)
	 */
	public static function makeSessionFromCookie() {
		if( array_key_exists( self::$cookie, $_COOKIE ) ) {
			if( $session = LigminchaGlobalSession::selectOne( array( 'id' => $_COOKIE[self::$cookie] ) ) ) {
				if( $user = LigminchaGlobalUser::newFromId( $session->owner ) ) {
					LigminchaGlobalSession::setCurrent( $session );
					LigminchaGlobalUser::setCurrent( $user );
					lgDebug( 'Session established from existing SSO cookie', $session );
				} else lgDebug( 'SSO session cookie found, but user is no longer logged in (' . substr( $_COOKIE[self::$cookie], 0, 5 ) . ')' );
			} else lgDebug( 'SSO session cookie found, but not in database (' . substr( $_COOKIE[self::$cookie], 0, 5 ) . ')' );
			if( !$session || !$user ) self::delCookie();
		} else lgDebug( 'No SSO session cookie found ' . var_export( $_COOKIE, true ) );
	}

	/**
	 * Render an iFrame that requests the global toolbar
	 */
	public static function toolbar() {

		// Get the url of the global app
		$config = JFactory::getConfig();
		$lgGlobalAppDomain = $config->get( 'lgGlobalApp', 'global.ligmincha.org' );

		// Include the code to render the toolbar
		require( __DIR__ . '/toolbar.php' );

		// Add the toolbar to the body if we have a user
		$app = JFactory::getApplication( 'site' );
		$page = $app->getBody();

		// Add the toolbar head code into the page head area
		$page = str_replace( '</head>', "{$lgToolbarHead}\n</head>", $page );

		// Add the toolbar body code into start of the page body
  		$page = preg_replace( '#<body.*?>#', "$0\n{$lgToolbarBody}", $page );

		// Update the page content
		$app->setBody( $page );

		lgDebug( "Global toolbar iFrame added to Joomla page" );
	}
}


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
		lgDebug( 'SSO cookie set: ' . substr( $sid, 0, 5 ) );
		return setcookie( self::$cookie, $sid, time() + LG_SESSION_DURATION );
	}

	/**
	 * Delete the SSO cookie
	 */
	public static function delCookie() {
		lgDebug( 'SSO cookie deleted: ' . substr( $_COOKIE[self::$cookie], 0, 5 ) );
		return setCookie( self::$cookie, '', time() - 86400 );
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
					$url = plgSystemLigminchaGlobal::$instance->params->get( 'lgCookieServer' );
					$iframe = "<iframe src=\"$url?{$cmd}={$session->id}\" frameborder=\"0\" width=\"1\" height=\"1\"></iframe>";
					$app = JFactory::getApplication( 'site' );
					$app->setBody( str_replace( '</body>', "$iframe\n</body>", $app->getBody() ) );
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
				}
			}
			if( !$session || !$user ) self::delCookie();
		}
	}
}

// If we're running on a non-standard port, add it to the cookie name (so that different ports act like different domains for testing)
$port = $_SERVER['SERVER_PORT'];
if( $port != 80 && $port != 443 ) LigminchaGlobalSSO::$cookie .= $port;

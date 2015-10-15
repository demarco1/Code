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
	private static $cookie = 'LigminchaSession';

	function __construct() {

		// Make singleton available if we need it
		self::$instance = $this;

		// If this is an SSO token request and this is the master site, return the key
		if( LigminchaGlobalServer::getCurrent()->isMaster && array_key_exists( self::$cmd, $_REQUEST ) ) {
			setcookie( $this->cookie, $_REQUEST[$this->cmd] );
			exit;
		}
	}

	/**
	 * If there is a new session for this user/server, append the token request to the page
	 */
	public static function appendTokenRequest() {
		$cookie = self::$cookie;
		$cmd = self::$cmd;
		$session = LigminchaGlobalSession::getCurrent();

		// If this is the main site, just set the cookie now,
		if( LigminchaGlobalServer::getCurrent()->isMaster ) setcookie( $cookie, $session->id );
		else {

			// If there is a current session,
			if( $session ) {

				// If the session is newly created, get an SSO cookie under ligmincha.org for this session ID
				// - newly created sessions have no expiry
				// - this is done by appending a 1x1pixel iFrame to the output that will request a token cookie from ligmincha.org
				if( $session->flag( LG_NEW ) ) {
					$url = plgSystemLigminchaGlobal::$instance->params->get( 'lgCookieServer' );
					$iframe = "<iframe src=\"$url?{$cmd}={$session->id}\" frameborder=\"0\" width=\"1\" height=\"1\"></iframe>";
					$app = JFactory::getApplication( 'site' );
					$app->setBody( str_replace( '</body>', "$iframe\n</body>", $app->getBody() ) );

					// Set the expiry to a longer time that distributed sessions last
					// - after it expires, user needs to come back to have another made (may not need to log in again)
					$session->expire = time() + LG_SESSION_DURATION;

					// Now that the session is real it can route
					$session->flag( LG_LOCAL, false );

					// Write changes to the session object into the distributed database
					$session->update();
				}
			}
		}
	}

	/**
	 * Make a current session and current user from an SSO session ID cookie (called when running on a master site)
	 */
	public static function makeSessionFromCookie() {
		if( array_key_exists( LigminchaGlobalSSO::$cookie, $_COOKIE ) ) {
			if( $session = LigminchaGlobalSession::findOne( array( 'id' => $_COOKIE[LigminchaGlobalSSO::$cookie] ) ) ) {
				if( $user = LigminchaGlobalUser::newFromId( $session->owner ) ) {
					LigminchaGlobalSession::setCurrent( $session );
					LigminchaGlobalUser::setCurrent( $user );
				}
			}
		}
	}
}

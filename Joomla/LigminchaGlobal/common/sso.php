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
		$config = JFactory::getConfig();
		$domain = $config->get( 'lgCookieDomain', '' );
		$d = ( $domain ? " ($domain)" : '' );
		lgDebug( "SSO cookie set$d: " . substr( $sid, 0, 5 ) );
		return setcookie( self::$cookie, $sid, time() + LG_SESSION_DURATION, '', $domain );
	}

	/**
	 * Delete the SSO cookie
	 */
	public static function delCookie() {
		$config = JFactory::getConfig();
		$domain = $config->get( 'lgCookieDomain', '' );
		$d = ( $domain ? " ($domain)" : '' );
		lgDebug( "SSO cookie deleted$d: " . substr( $_COOKIE[self::$cookie], 0, 5 ) );
		return setCookie( self::$cookie, '', time() - 86400, '', $domain );
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
	 * Render the Ligmincha Global SSO toolbar
	 */
	public static function toolbar() {
		if( $user = LigminchaGlobalUser::getCurrent() ) {
			$spacer = 'width:0px;border-left:1px solid #333;border-right:1px solid #555;margin:0 15px;font-size:1px;';
			$spl = "<div style=\"float:left;$spacer\">&nbsp;</div>";
			$spr = "<div style=\"float:right;$spacer\">&nbsp;</div>";
			$toolbar = "<div style=\"float:left;padding-left:20px;\">Ligmincha Global Toolbar</div>{$spl}<div style=\"float:left\">Sites&nbsp;&nbsp;▼</div>{$spl}";
			$toolbar .= "<div style=\"float:right;padding-right:20px\">" . $user->data['realname'] . "&nbsp;&nbsp;▼</div>{$spr}";
			$toolbar = "<div style=\"position:absolute;top:0px;left:0px;width:100%;background-color:#464646;background-image: -moz-linear-gradient(center bottom , #373737, #464646 5px); color: #ccc; font: 13px/28px sans-serif; height: 28px;\">$toolbar</div>";
			$toolbar = "<div style=\"padding:0;margin:0;height:28px;\"></div>$toolbar";
			return $toolbar;
		}
	}
}

// If we're running on a non-standard port, add it to the cookie name (so that different ports act like different domains for testing)
$port = $_SERVER['SERVER_PORT'];
if( $port != 80 && $port != 443 ) LigminchaGlobalSSO::$cookie .= $port;

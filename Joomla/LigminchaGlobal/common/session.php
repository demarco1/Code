<?php
/**
 * Global Session
 */
class LigminchaGlobalSession extends LigminchaGlobalObject {

	// Current instance
	private static $current = null;

	function __construct() {
		$this->type = LG_SESSION;
		parent::__construct();
	}

	/**
	 * Get/create current session instance
	 */
	public static function getCurrent() {
		$update = false;
		if( is_null( self::$current ) ) {

			// If there's a current user, get/make a current session
			if( LigminchaGlobalUser::getCurrent() ) {

				// None found, create new
				// - there will already be a current session established if one existed thanks to SSO::makeSessionFromCookie
				self::$current = new LigminchaGlobalSession();

				// Doesn't exist, make the data structure for our new server object
				self::$current->ref1 = LigminchaGlobalServer::getCurrent()->id;
				self::$current->tag = self::getBrowser();

				// Session only lives for five seconds in this initial form and doesn't route
				self::$current->expire = self::timestamp() + 2;
				self::$current->flag( LG_LOCAL, true );
				self::$current->flag( LG_PRIVATE, true );

				// Save our new instance to the DB
				$update = true;

				// And save the ID in the SSO cookie
				LigminchaGlobalSSO::setCookie( self::$current->id );
				
				lgDebug( 'New session created and SSO cookie set', self::$current );
			} else self::$current = false;
		}

		// Update the expiry if the session existed (but only if it's increasing by more than a minute to avoid sync traffic)
		if( self::$current && !self::$current->flag( LG_NEW ) ) {
			$expiry = self::timestamp() + LG_SESSION_DURATION;
			if( $expiry - self::$current->expire > 60 ) {
				self::$current->expire = $expiry;
				$update = true;
			}
		}

		// Avoid multiple calls to update above
		if( $update ) {
			self::$current->update();
		}

		return self::$current;
	}

	/**
	 * This is used from standalone context to set the session from the SSO cookie
	 */
	public static function setCurrent( $session ) {
		self::$current = $session;
	}

	/**
	 * Destroy current session
	 */
	public static function delCurrent() {

		// Delete the global object
		if( $session = LigminchaGlobalSession::getCurrent() ) LigminchaGlobalDistributed::del( array( 'id' => $session->id ) );

		// Delete the SSO cookie
		LigminchaGlobalSSO::delCookie();
	}

	/**
	 * Make a new object given an id
	 */
	public static function newFromId( $id, $type = false ) {
		return parent::newFromId( $id, LG_SESSION );
	}

	/**
	 * Add this type to $cond
	 */
	public static function select( $cond = array() ) {
		$cond['type'] = LG_SESSION;
		return parent::select( $cond );
	}

	public static function selectOne( $cond = array() ) {
		$cond['type'] = LG_SESSION;
		return parent::selectOne( $cond );
	}

	public static function getBrowser() {
		if(!array_key_exists('HTTP_USER_AGENT', $_SERVER)) return false;
		$ExactBrowserNameUA=$_SERVER['HTTP_USER_AGENT'];
		if (strpos(strtolower($ExactBrowserNameUA), "safari/") and strpos(strtolower($ExactBrowserNameUA), "opr/")) {
			// OPERA
			$ExactBrowserNameBR="Opera";
		} elseIf (strpos(strtolower($ExactBrowserNameUA), "safari/") and strpos(strtolower($ExactBrowserNameUA), "chrome/")) {
			// CHROME
			$ExactBrowserNameBR="Chrome";
		} elseIf (strpos(strtolower($ExactBrowserNameUA), "msie")) {
			// INTERNET EXPLORER
			$ExactBrowserNameBR="InternetExplorer";
		} elseIf (strpos(strtolower($ExactBrowserNameUA), "firefox/")) {
			// FIREFOX
			$ExactBrowserNameBR="Firefox";
		} elseIf (strpos(strtolower($ExactBrowserNameUA), "safari/") and strpos(strtolower($ExactBrowserNameUA), "opr/")==false and strpos(strtolower($ExactBrowserNameUA), "chrome/")==false) {
			// SAFARI
			$ExactBrowserNameBR="Safari";
		} else {
			// OUT OF DATA
			$ExactBrowserNameBR="Device";
		};
		return $ExactBrowserNameBR;
	}
}


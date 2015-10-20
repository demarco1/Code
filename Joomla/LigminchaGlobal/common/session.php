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
	 * Get/create current object instance
	 */
	public static function getCurrent() {
		$update = false;
		if( is_null( self::$current ) ) {

			// If there's a current user, get/make a current session
			if( LigminchaGlobalUser::getCurrent() ) {

				// Load the existing session for this user if one exists, else create a new one
				if( !self::$current = LigminchaGlobalSession::selectOne( array(
					'ref1'  => LigminchaGlobalServer::getCurrent()->id,
					'owner' => LigminchaGlobalUser::getCurrent()->id
				) ) ) {

					// None found, create new
					self::$current = new LigminchaGlobalSession();

					// TODO: Doesn't exist, make the data structure for our new server object
					self::$current->ref1 = LigminchaGlobalServer::getCurrent()->id;

					// Session only lives for five seconds in this initial form and doesn't route
					self::$current->expire = self::timestamp() + 2;
					self::$current->flag( LG_LOCAL, true );
					self::$current->flag( LG_PRIVATE, true );

					// Save our new instance to the DB
					$update = true;
				}
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
	 * - destroy all sessions associated with this user/server in case there are more than one somehow
	 */
	public static function delCurrent() {
		LigminchaGlobalDistributed::del( array(
			'type' => LG_SESSION,
			'ref1' => LigminchaGlobalServer::getCurrent()->id,
			'owner' => LigminchaGlobalUser::getCurrent()->id
		) );
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
}


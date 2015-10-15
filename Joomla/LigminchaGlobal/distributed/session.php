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
		if( is_null( self::$current ) ) {

			// If there's a current user, get/make a current session
			if( LigminchaGlobalUser::getCurrent() ) {

				// Load the existing session for this user if one exists, else create a new one
				if( !self::$current = LigminchaGlobalObject::findOne( array(
					'type'  => LG_SESSION,
					'ref1'  => LigminchaGlobalServer::getCurrent()->id,
					'owner' => LigminchaGlobalUser::getCurrent()->id
				) ) ) {

					// None found, create new
					self::$current = new LigminchaGlobalSession();

					// TODO: Doesn't exist, make the data structure for our new server object
					self::$current->ref1 = LigminchaGlobalServer::getCurrent()->id;

					// Session only lives for five seconds in this initial form and doesn't route
					self::$current->expire = time() + 2;
					self::$current->flag( LG_LOCAL, true );
					self::$current->flag( LG_PRIVATE, true );

					// Save our new instance to the DB
					self::$current->update();
				}
			} else self::$current = false;
		}
		return self::$current;
	}

	/**
	 * This is used from standalone context to set the session from the SSO cookie
	 */
	public static setCurrent( $session ) {
		self::$current = $session;
	}

	/**
	 * Destroy current session
	 * - destroy all sessions associated with this user/server in case there are more than one somehow
	 */
	public static function delCurrent() {
		LigminchaGlobalObject::del( array(
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
}


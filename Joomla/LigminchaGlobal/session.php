<?php
/**
 * Global Session
 */
class LigminchaGlobalSession extends LigminchaGlobalObject {

	// Current instance
	private static $current;

	function __construct( $id = false ) {
		$this->type = LG_SESSION;

		// This will load the whole object if the UUID exists
		parent::__construct( $id );

		// Make a server uuid from the current server if none supplied (this replaces the random one made by the parent constructor)
		if( $id === false ) {

			// See if there is a current session for this user
			if( $session = LigminchaGlobalObject::findOne( array(
				'type' => LG_SESSION,
				'ref1' => LigminchaGlobalServer::getCurrent()->id,
				'owner' => LigminchaGlobalUser::getCurrent()->id
			) ) ) {
				$this->id = $session->id;
				$this->load();
			} else {

				// TODO: Doesn't exist, make the data structure for our new server object
				$this->ref1 = LigminchaGlobalServer::getCurrent()->id;

				// Session only lives for five seconds in this initial form and doesn't route
				$this->expire = time() + 2;
				$this->flag( LG_LOCAL, false );

				// Save our new instance to the DB
				$this->update();
			}
		}
	}

	/**
	 * Get/create current object instance
	 */
	public static function getCurrent() {
		if( !self::$current ) {
			if( LigminchaGlobalUser::getCurrent() ) {
				self::$current = new self();
			} else return false;
		}
		return self::$current;
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
}

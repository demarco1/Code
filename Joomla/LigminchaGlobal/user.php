<?php
/**
 * Global User
 */
class LigminchaGlobalUser extends LigminchaGlobalObject {

	// Current instance
	private static $current;

	function __construct( $id = false ) {
		$this->type = LG_USER;
		parent::__construct( $id );

		// If id was true return the empty new object
		if( $id === true ) return;

		// Make a user uuid from the current server and jUser ID (this replaces the random one made by the parent constructor)
		if( $id === false ) {

			// Make a new uuid from the server's secret
			$server = LigminchaGlobalServer::getCurrent();
			$jUser = JFactory::getUser();
			if( $jUser->id ) {
				$this->obj_id = $this->hash( $server->obj_id . ':' . $jUser->id );

				// Try and load the object data now that we know its uuid
				if( !$this->load() ) {

					// TODO: Doesn't exist, make the data structure for our new user object from $jUser
					$this->ref1 = $server->obj_id;
					$this->tag = $jUser->id;

					// Save our new instance to the DB
					$this->update();
				}
			}
		}
	}

	/**
	 * Get/create current object instance
	 */
	public static function getCurrent() {
		if( !self::$current ) {
			$jUser = JFactory::getUser();
			if( $jUser->id == 0 ) return false;
			self::$current = new self();
		}
		return self::$current;
	}
}

<?php
/**
 * Global User
 */
class LigminchaGlobalUser extends LigminchaGlobalObject {

	// Current instance
	private static $current = null;

	function __construct() {
		$this->type = LG_USER;
		parent::__construct();
	}

	/**
	 * Get/create current object instance
	 */
	public static function getCurrent() {
		if( is_null( self::$current ) ) {

			// Get the Joomla user, bail if none
			$jUser = JFactory::getUser();
			if( $jUser->id == 0 ) self::$current = false;

			// Make a new uuid from the server ID and the user's Joomla ID
			$server = LigminchaGlobalServer::getCurrent();
			$id = self::hash( $server->id . ':' . $jUser->id );
			self::$current = self::newFromId( $id );

			// Try and load the object data now that we know its uuid
			if( !self::$current->load() ) {

				// TODO: Doesn't exist, make the data structure for our new user object from $jUser
				self::$current->ref1 = $server->id;
				self::$current->tag = $jUser->id;

				// Save our new instance to the DB
				self::$current->update();
			}
		}
		return self::$current;
	}

	/**
	 * Make a new object given an id
	 */
	public static function newFromId( $id, $type = false ) {
		return parent::newFromId( $id, LG_USER );
	}
}

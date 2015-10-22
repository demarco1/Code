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
	 * Get/create current object instance from the current Joomla user, create if non-existent
	 */
	public static function getCurrent() {
		if( is_null( self::$current ) ) {
			$jUser = JFactory::getUser();
			self::$current = $jUser->id ? self::checkUser( $jUser ) : false;
		}
		return self::$current;
	}

	/**
	 * This is used from standalone context to set the session from the SSO cookie
	 */
	public static function setCurrent( $user ) {
		self::$current = $user;
	}

	/**
	 * Make sure all the local users have a global object
	 */
	public static function checkAll() {
		$db = JFactory::getDbo();
		$db->setQuery( "SELECT id FROM `#__users`" );
		$db->query();
		foreach( array_keys( $db->loadRowList(0) ) as $id ) {
			self::checkUser( JFactory::getUser( $id ) );
		}
	}

	/**
	 * Check if the passed Joomla user exists as a global user, create if not
	 */
	public static function checkUser( $jUser ) {

		// Make a new uuid from the server ID and the user's Joomla ID
		$server = LigminchaGlobalServer::getCurrent();
		$id = self::hash( $server->id . ':' . $jUser->id );
		$user = self::newFromId( $id );

		// Try and load the object data now that we know its uuid
		if( !$user->load() ) {

			// Doesn't exist, make the data structure for our new user object from $jUser
			$user->ref1 = $server->id;
			$user->tag = $jUser->id;
			$user->data = array(
				'realname' => $jUser->name,
				'username' => $jUser->username,
				'email' => $jUser->email,
			);

			// Save our new instance to the DB
			$user->update();
		}

		return $user;
	}

	/**
	 * Make a new object given an id
	 */
	public static function newFromId( $id, $type = false ) {
		return parent::newFromId( $id, LG_USER );
	}

	/**
	 * Add this type to $cond
	 */
	public static function select( $cond = array() ) {
		$cond['type'] = LG_USER;
		return parent::select( $cond );
	}

	public static function selectOne( $cond = array() ) {
		$cond['type'] = LG_USER;
		return parent::selectOne( $cond );
	}
}

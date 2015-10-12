<?php
/**
 * Global Server
 */
class LigminchaGlobalServer extends LigminchaGlobalObject {

	// Current instance
	private static $current;

	public $isMaster = false;

	function __construct( $id = false ) {

		// Is this the master server?
		$this->checkMaster();

		// This goes first so that parent constructor will raise an error if the current uuid type doesn't match
		$this->type = LG_SERVER;

		// This will load the whole object if the UUID exists
		parent::__construct( $id );

		// Make a server uuid from the current server if none supplied (this replaces the random one made by the parent constructor)
		if( $id === false ) {

			// Make a new uuid from the server's secret
			$config = JFactory::getConfig();
			$secret = $config->get( 'secret' );
			$this->obj_id = sha1( $secret );

			// Try and load the object data now that we know its uuid
			if( !$this->load() ) {

				// TODO: Doesn't exist, make the data structure for our new server object

				// Save our new instance to the DB
				$this->update();
			}
		}

	}

	/**
	 * Determine whether or not this is the master site
	 */
	private function checkMaster() {
		$this->isMaster = ( $_SERVER['HTTP_HOST'] == 'ligmincha.org' );
	}

	/**
	 * Get/create current object instance
	 */
	public static function getCurrent() {
		if( !self::$current ) {
			self::$current = new self();
		}
		return self::$current;
	}
}

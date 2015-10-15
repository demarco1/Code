<?php
/**
 * Global Server
 */
class LigminchaGlobalServer extends LigminchaGlobalObject {

	// Current instance
	private static $current = null;

	// Master server
	private static $master = null;

	public $isMaster = false;

	function __construct() {
		$this->checkMaster();
		$this->type = LG_SERVER;
		parent::__construct();
	}

	/**
	 * Determine whether or not this is the master site
	 */
	private function checkMaster() {
		$this->isMaster = ( $_SERVER['HTTP_HOST'] == self::masterDomain() );
	}

	/**
	 * What is the master domain?
	 */
	public static function masterDomain() {
		static $master;
		if( !$master ) {
			$config = JFactory::getConfig();
			if( !$master = $config->get( 'lgMaster' ) ) $master = 'ligmincha.org';
		}
		return $master;
	}

	/**
	 * Get the master server object
	 */
	public static function getMaster() {
		if( is_null( self::$master ) ) {
			self::$master = self::getCurrent()->isMaster ? self::getCurrent() : self::findOne( array( 'tag' => self::masterDomain() ) );
			if( !self::$master ) die( 'Unable to determine master server' );
		}
		return self::$master;
	}

	/**
	 * Get/create current object instance
	 */
	public static function getCurrent() {
		if( is_null( self::$current ) ) {

			// Make a new uuid from the server's secret
			$config = JFactory::getConfig();
			$id = self::hash( $config->get( 'secret' ) );
			self::$current = self::newFromId( $id );

			// Try and load the object data now that we know its uuid
			if( !self::$current->load() ) {

				// Make it easy to find this server by domain
				self::$current->tag = $_SERVER['HTTP_HOST'];

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
		$obj = parent::newFromId( $id, LG_SERVER );
		$obj->checkMaster();
		return $obj;
	}

}

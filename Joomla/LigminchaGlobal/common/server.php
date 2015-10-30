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
	 * - we have to allow master server to be optional so that everything keeps working prior to its object having been loaded
	 */
	public static function getMaster() {
		if( !self::$master ) {
			$domain = self::masterDomain();
			self::$master = self::getCurrent()->isMaster ? self::getCurrent() : self::selectOne( array( 'tag' => $domain ) );

			// Give our server a version and put our server on the update queue after we've established the master
			if( self::$master ) {

				// Set the version (just use the first ver object for now while testing)
				if( $versions = LigminchaGlobalVersion::select() ) self::$current->ref1 = $versions[0]->id;

				// No version objects, create one now
				else {
					$ver = new LigminchaGlobalVersion( '0.0.0' );
					self::$current->ref1 = $ver->id;
				}

				self::getCurrent()->update();
			}
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

			// If the object was newly created, populate with default initial data and save
			if( !self::$current->tag ) {
				
				// Make it easy to find this server by domain
				self::$current->tag = $_SERVER['HTTP_HOST'];

				// Server information
				self::$current->data = self::serverData();

				// Save our new instance to the DB (if we have a master yet)
				if( self::$master ) self::$current->update();
			}
		}

		// If we have a master, ensure the server data is up to date
		if( self::$master ) {
			static $checked = false;
			if( !$checked ) {
				$checked = true;
				if( json_encode( self::$current->data ) !== json_encode( self::serverData() ) ) {
					self::$current->data = self::serverData();
					self::$current->update();
				}
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

	/**
	 * Get the server and env data
	 */
	public static function serverData() {
		$config = JFactory::getConfig();
		$version = new JVersion;
		return array(
			'name'      => $config->get( 'sitename' ),
			'webserver' => $_SERVER['SERVER_SOFTWARE'],
			'system'    => php_uname('s') . ' (' . php_uname('m') . ')',
			'php'       => preg_replace( '#^([0-9.]+).*$#', '$1', phpversion() ),
			'mysql'     => mysqli_init()->client_info,
			'joomla'    => $version->getShortVersion(),
		);
	}

	/**
	 * Add this type to $cond
	 */
	public static function select( $cond = array() ) {
		$cond['type'] = LG_SERVER;
		return parent::select( $cond );
	}

	public static function selectOne( $cond = array() ) {
		$cond['type'] = LG_SERVER;
		return parent::selectOne( $cond );
	}
}

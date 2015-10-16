<?php
/**
 * This is a fake Joomla environment so that the distributed object classes can function stand-alone
 */
class JFactory {

	private static $config;

	private static $db;

	/**
	 * Return a connection to the database
	 */
	public static function getDbo() {
		if( !self::$db ) self::$db = new Database();
		return self::$db;
	}

	/**
	 * Return a config object populated with the config from the Joomlas config file
	 */
	public static function getConfig() {
		if( !self::$config ) self::$config = new Config();
		return self::$config;
	}

	/**
	 * Return a fake user with id set to zero
	 */
	public static function getUser() {
		$jUser = new StdClass();
		$jUser->id = 0;
		return $jUser;
	}
}

class Config {

	function __construct() {

		// TODO: Load config data

	}

	public function get( $prop, $default = false ) {
		return property_exists( $this, $prop ) ? $this->$prop : $default;
	}

}

class Database {

	private $query;
	
	private $prefix;

	function __construct() {
		$config   = JFactory::getConfig();
		$database = $config->get( 'db' );
		$host     = $config->get( 'host' ),
		$user     = $config->get( 'user' ),
		$password = $config->get( 'password' ),
		$prefix   = $this->prefix = $config->get( 'dbprefix' ),

		// Mysqlconnect
	}

	public function setQuery( $sql ) {

		// Make the table references into refs to ligmincha_global with the proper prefix
		$sql = preg_replace( '/`#__.+?`/', "`{$this->prefix}ligmincha_global", $sql );
	}

	public function query() {
	}

	public function loadAssoc() {
	}

	public function loadAssocList( $a, $b ) {
	}

	public function quote( $s ) {
	}
}

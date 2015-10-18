<?php
/**
 * This is a fake Joomla environment so that the distributed object and sso classes can function stand-alone
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

	private $config;

	function __construct() {

		// TODO: need to figure out a nice way to access the config when the code is symlinked
		require_once( '/var/www/joomla/configuration.php' );

		$this->config = new JConfig;

	}

	public function get( $prop, $default = false ) {
		return property_exists( $this->config, $prop ) ? $this->config->$prop : $default;
	}

}

class Database {

	private $query;
	private $conn;
	private $prefix;

	function __construct() {
		$config   = JFactory::getConfig();
		$host     = $config->get( 'host' );
		$database = $config->get( 'db' );
		$user     = $config->get( 'user' );
		$password = $config->get( 'password' );
		$prefix   = $this->prefix = $config->get( 'dbprefix' );
		$this->conn = new mysqli( $host, $user, $password, $database );
		if ($this->conn->connect_errno) die( "Failed to connect to MySQL: (" . $this->conn->connect_errno . ") " . $this->conn->connect_error );
	}

	public function setQuery( $sql ) {

		// Make the table references into refs to ligmincha_global with the proper prefix
		$this->query = preg_replace( '/`#__.+?`/', '`' . $this->prefix . LigminchaGlobalDistributed::$table . '`', $sql );
	}

	public function query() {
		if( !$this->result = $this->conn->query( $this->query ) ) die( "Query failed: (" . $this->conn->errno . ") " . $this->conn->error . " SQL: \"" . $this->query . "\"" );
	}

	public function loadAssoc() {
		if( !$row = $this->result->fetch_assoc() ) $row = false;
		$this->result->free();
		return $row;
	}

	public function loadAssocList( $a = false, $b = false ) {
		$list = array();
		while( $row = $this->result->fetch_assoc() ) $list[] = $b ? $row[$b] : $row;
		$this->result->free();
		return $list;
	}

	public function loadRowList( $index = 0 ) {
		$list = array();
		while( $row = $this->result->fetch_row() ) {
			$list[$row[$index]] = array( $row[$index] );
		}
		$this->result->free();
		return $list;
	}

	public function quote( $s ) {
	}
}

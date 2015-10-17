<?php
/**
 * This is a fake Joomla & MediaWiki environment so that the distributed object, sso and WebSocket classes can function stand-alone
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
		$host     = $config->get( 'host' );
		$user     = $config->get( 'user' );
		$password = $config->get( 'password' );
		$prefix   = $this->prefix = $config->get( 'dbprefix' );

		// Mysqlconnect
	}

	public function setQuery( $sql ) {

		// Make the table references into refs to ligmincha_global with the proper prefix
		$sql = preg_replace( '/`#__.+?`/', '`' . $this->prefix . LigminchaGlobalDistributed::$table, $sql );
	}

	public function query() {
	}

	public function loadAssoc() {
	}

	public function loadAssocList( $a, $b ) {
	}

	public function loadRowList( $a ) {
	}

	public function quote( $s ) {
	}
}


/**
 * MediaWiki environment
 */
define( 'MEDIAWIKI', true );
class MediaWiki {

	var $msgKey;

	function __construct( $msgkey = false ) {
		$this->msgKey = $msgKey;
	}

	function addModules( $ext ) {
	}

	function addJsConfigVars( $name, $value ) {
		// TODO: set vars that will be retrieved by our fake mw.config.get()
	}

	// For wfMessage()
	function text() {
		return $this->msgKey;
	}

}
function wfMessage( $msgkey ) {
	return new MediaWiki( $msgKey );
}
$wgExtensionCredits = array( 'other' => array() );
$wgExtensionMessagesFiles = array();
$wgOut = new MediaWiki();
$wgResourceModules = array();
$wgExtensionAssetsPath = '';
$config = JFactory::getConfig();
$wgDBname = $config->get( 'db' );
$wgDBprefix = $config->get( 'dbprefix' );

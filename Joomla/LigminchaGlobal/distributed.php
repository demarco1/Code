<?php
/**
 * This class encapsulates all the distributed database functionality for the LigminchaGlobal extension
 */

// Entry types
define( 'LG_LOG', 1 );

// Flags
define( 'LG_QUEUED', 1 << 0 );
define( 'LG_PRIVATE', 1 << 1 );

class LigminchaGlobalDistributed {

	// Our distributed data table
	private $table = '#__ligmincha_global';

	// Table structure
	private $tableStruct = array(
		'id'    => 'BINARY(20) NOT NULL',
		'type'  => 'INT UNSIGNED NOT NULL',
		'ref1'  => 'BINARY(20)',
		'ref2'  => 'BINARY(20)',
		'time'  => 'INT UNSIGNED',
		'flags' => 'INT UNSIGNED',
		'owner' => 'BINARY(20) NOT NULL',
		'group' => 'TEXT',
		'name'  => 'TEXT',
		'data'  => 'TEXT',
	);

	// Reference to the main plugin class
	private static $plugin = false;

	// Reference to this classes singleton instance
	public static $instance = false;

	function __construct( $plugin ) {
ini_set('error_reporting',E_ALL);
		self::$instance = $this;
		self::$plugin = $plugin;
		$this->checkTable();
	}

	/**
	 * Check that the local distributed database table exists and has a matching structure
	 */
	private function checkTable() {
		$db = JFactory::getDbo();

		// Get the current structure
		$db->setQuery( "DESCRIBE `{$this->table}`" );
		$db->query();

		// If the table exists, check that it's the correct format
		if( $db ) {
			$curFields = $db->loadAssocList( null, 'Field' );

			// For now only adding missing fields is supported, not removing, renaming or changing types
			$alter = array();
			foreach( $this->tableStruct as $field => $type ) {
				if( !in_array( $field, $curFields ) ) $alter[$field] = $type;
			}
			if( $alter ) {
				$cols = array();
				foreach( $alter as $field => $type ) $cols[] = "ADD COLUMN `$field` $type";
				$db->setQuery( "ALTER TABLE `{$this->table}` " . implode( ',', $cols ) );
				$db->query();
				$this->log( LG_LOG, 'ligmincha_global table fields added: (' . implode( ',', array_keys( $alter ) ) . ')' );
			}
		}

		// Otherwise create the table now
		else {
			$query = "CREATE TABLE IF NOT EXISTS `$tbl` (" . implode( ',', $this->tableStruct ) . ",PRIMARY KEY (id))";
			$db->setQuery( $query );
			$db->query();
			$this->log( LG_LOG, 'ligmincha_global table added' );
		}
	}

	/**
	 * Send all queued changes
	 */
	private function sendQueue() {

		// Select all entries with LG_QUEUED flag set and reset the flag

	}

	/**
	 * Log an event in the global DB
	 */
	private function log( $text, $user = false ) {

		// If user set to true, get the current user's ID
		if( $user === true ) {
			// TODO
		}

		// TODO: set ref1 to the siteID, ref2 to the user if applicable, set timestamp
		// should use LG_PRIVATE and LG_QUEUED flags

	}

	/**
	 * Generate a new globally unique ID
	 */
	private function uuid() {
		static $uuid = uniqid( $_SERVER['HTTP_HOST'], true );
		$uuid = sha1( $uuid . microtime() . uniqid() );
		return $uuid;
	}

}

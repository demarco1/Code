<?php
/**
 * This class encapsulates all the distributed database functionality for the LigminchaGlobal extension
 */

// Entry types
define( 'LG_LOG',    1 );
define( 'LG_SERVER', 2 );
define( 'LG_SESSION', 3 );
define( 'LG_USER',   4 );
define( 'LG_GROUP',  5 );
define( 'LG_SSO_COOKIE_REQUEST',  6 );

// Flags
define( 'LG_QUEUED',  1 << 0 );
define( 'LG_PRIVATE', 1 << 1 );
define( 'LG_NEW',     1 << 2 );

class LigminchaGlobalDistributed extends LigminchaGlobalBase {

	// Our distributed data table
	private $table = '#__ligmincha_global';

	// Sub-classes to use for revision types (non-existent class means generic base class)
	private $classes = array(
		LG_LOG => 'LogEntry',
		LG_SERVER => 'Server',
		LG_SESSION => 'Session',
		LG_USER => 'User',
		LG_GROUP => 'Group',
		LG_SSO_COOKIE_REQUEST => 'CookieRequest',
	);

	// Table structure
	private $tableStruct = array(
		'rev_id' => 'BINARY(20) NOT NULL',
		'obj_id' => 'BINARY(20) NOT NULL',
		'ref1'   => 'BINARY(20)',
		'ref2'   => 'BINARY(20)',
		'type'   => 'INT UNSIGNED NOT NULL',
		'time'   => 'INT UNSIGNED',
		'flags'  => 'INT UNSIGNED',
		'owner'  => 'BINARY(20) NOT NULL',
		'group'  => 'TEXT',
		'name'   => 'TEXT',
		'data'   => 'TEXT',
	);

	function __construct( $plugin ) {
ini_set('error_reporting',E_ALL);
		parent::__construct( $plugin );
		$this->checkTable();
	}

	/**
	 * Check that the local distributed database table exists and has a matching structure
	 */
	private function checkTable() {
		$db = JFactory::getDbo();

		// Create the table if it doesn't exist
		$def = array();
		foreach( $this->tableStruct as $field => $type ) $def[] = "`$field` $type";
		$query = "CREATE TABLE IF NOT EXISTS `{$this->table}` (" . implode( ',', $def ) . ",PRIMARY KEY (rev_id))";
		$db->setQuery( $query );
		$db->query();
		$this->log( LG_LOG, 'ligmincha_global table added' );

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
		static $uuid;
		if( !$uuid ) $uuid = uniqid( $_SERVER['HTTP_HOST'], true );
		$uuid = sha1( $uuid . microtime() . uniqid() );
		return $uuid;
	}

	/**
	 * Remove all expired items
	 */
	private function expire() {
	}

}


<?php
// Entry types
define( 'LG_LOG',     1 );
define( 'LG_SERVER',  2 );
define( 'LG_USER',    3 );
define( 'LG_SESSION', 4 );
define( 'LG_REVISION',5 );
define( 'LG_VERSION', 6 );

// Flags
define( 'LG_NEW',     1 << 0 ); // This item was just created (has never been modified)
define( 'LG_LOCAL',   1 << 1 ); // This item's changes never routes anywhere
define( 'LG_PRIVATE', 1 << 2 ); // This item's changes only route to the main server

// Database update methods
define( 'LG_UPDATE', 1 );
define( 'LG_DELETE', 2 );

/**
 * Class representing a single generic object in the distributed database
 */
class LigminchaGlobalObject {

	// Sub-classes to use for revision types (non-existent class means generic base class)
	public static $classes = array(
		LG_LOG     => 'Log',
		LG_SERVER  => 'Server',
		LG_SESSION => 'Session',
		LG_USER    => 'User',
		LG_GROUP   => 'Group',
	);

	// Methods of this class used for updating data (these are the commands that are sent in the remote queue)
	public static $methods = array(
		LG_UPDATE => 'update',
		LG_DELETE => 'del',
	);

	// Whether the object exists in the database
	public $exists = false;

	// Properties for the database row fields
	var $id;
	var $ref1;
	var $ref2;
	var $tag;
	var $type;
	var $creation;
	var $modified;
	var $expire;
	var $flags;
	var $owner;
	var $group;
	var $name;
	var $data;

	function __construct( $id = false ) {

		// Create a new object with default properties
		if( $id === false || $id === true ) {
			$this->id = $this->uuid();
		}

		// Load the data from the db into this instance (if it exists)
		else {
			$this->id = $id;
			$this->load();
		}

	}

	/**
	 * Make a new object given an id
	 */
	public static function newFromId( $id ) {
		if( !$row = self::get( $this->obj_od ) ) return false;
		$class = self::typeToClass( $row['type'] );
		$obj = new $class( true );
		$obj->load();
		return $obj;
	}

	/**
	 * Load the data into this object from the DB (return false if no data found)
	 */
	protected function load() {

		// Get the objects row from the database
		if( !$row = self::get( $this->id ) ) return false;

		// TODO: Also check if it's a matching type of type already set
		foreach( $row as $field => $val ) {
			$prop = "$field";
			$this->$prop = $val;
		}

		// Mark this object as existing in the database
		$this->exists = true;

		return true;
	}

	/**
	 * Load an object's row from the DB given its ID
	 */
	private static function get( $id ) {
		$db = JFactory::getDbo();
		$table = '`' . LigminchaGlobalDistributed::$table . '`';
		$all = self::sqlFields();
		$db->setQuery( "SELECT $all FROM $table WHERE `id`=0x$id" );
		$db->query();
		if( !$row = $db->loadAssoc() ) return false;
		return $row;
	}

	/**
	 * Update or create an object in the database and queue the changes if necessary
	 * - $session is passed if this changed arrived from a remote queue, or zero if from server
	 */
	public function update( $session = false ) {
		$db = JFactory::getDbo();
		$table = '`' . LigminchaGlobalDistributed::$table . '`';

		// Bail if no type
		if( $this->type < 1 ) die( 'Typeless distributed objects not allowed!' );

		// Update an existing object in the database
		if( $this->exists ) {

			// TODO: Validate cond

			// Update automatic properties
			$this->flag( LG_NEW, false );
			$this->modified = time();

			$sqlVals = $this->makeValues( false );
			$db->setQuery( "UPDATE $table SET $sqlVals WHERE `id`=0x{$this->id}" );
			$db->query();
		}

		// Create a new object in the database
		else {
			$this->flag( LG_NEW, true );
			$this->modified = null;
			$this->creation = time();

			// The entry is owned by the user unless it's a server/revision/user object
			$this->owner = ( $this->type == LG_SERVER || $this->type == LG_USER || $this->type == LG_REVISION )
				? null : LigminchaGlobalUser::getCurrent()->id;

			$sqlVals = $this->makeValues();
			$db->setQuery( "REPLACE INTO $table SET $sqlVals" );
			$db->query();
		}

		// If this update item has queue flag set and originated here, queue update for routing
		if( !$this->flag( LG_LOCAL ) && !$session ) {
			new LigminchaGlobalRevision( LG_UPDATE, $this->fields() );
		}
	}

	/**
	 * Delete objects matching the condition array
	 */
	public static function del( $cond, $session = false ) {
		$db = JFactory::getDbo();
		$table = '`' . LigminchaGlobalDistributed::$table . '`';

		// Make the condition SQL syntax, bail if nothing
		$sqlcond = self::makeCond( $cond );
		if( empty( $sqlcond ) ) return false;

		// TODO: validate cond

		// Check if any of the objects should not be queued (don't do queries that involve both types!)
		// TODO
		$queue = true;

		// Do the deletion
		$db->setQuery( "DELETE FROM $table WHERE $sqlcond" );
		$db->query();

		// If the items deleted all had their queue flags set and the deletion originated here, queue for routing
		if( $queue && !$session ) {
			new LigminchaGlobalRevision( LG_DELETE, $cond );
		}
	}

	/**
	 * Set, reset or return a flag bit
	 */
	public function flag( $flag, $set = null ) {
		if( $set === true ) $this->flags |= $flag;
		elseif( $set === false ) $this->flags &= ~$flag;
		else return (bool)($this->flags & $flag);
	}

	/**
	 * Find an object in the DB given the passed conditions
	 * TODO: really inefficient at the moment
	 */
	public static function find( $cond ) {
		$db = JFactory::getDbo();
		$table = '`' . LigminchaGlobalDistributed::$table . '`';
		$all = self::sqlFields();
		$sqlcond = self::makeCond( $cond );
		if( empty( $sqlcond ) ) return false;
		$db->setQuery( "SELECT $all FROM $table WHERE $sqlcond" );
		$db->query();
		if( !$result = $db->loadAssocList() ) return false;
		foreach( $result as $i => $assoc ) $result[$i] = self::newFromFields( $assoc );
		return $result;
	}

	/**
	 * Return just a single row instead of a list of rows
	 */
	public static function findOne( $cond ) {
		$result = self::find( $cond );
		return $result ? $result[0] : false;
	}

	/**
	 * Make a hash of the passed content for an object ID
	 */
	public function hash( $content ) {
		return strtoupper( sha1( $content ) );
	}

	/**
	 * Generate a new globally unique ID
	 */
	protected function uuid() {
		static $uuid;
		if( !$uuid ) $uuid = uniqid( $_SERVER['HTTP_HOST'], true );
		$uuid .= microtime() . uniqid();
		return $this->hash( $uuid );
	}

	/**
	 * Set the object's data field
	 */
	public function setData( $data ) {
		if( is_array( $data ) ) $data = json_encode( $data );
		$this->data = $data;
	}

	/**
	 * Get an object's data
	 */
	public function getData() {
		$data = $this->data;
		$c1 = substr( $data, 0, 1 );
		if( $c1 == '[' || $c1 == '{' ) $data = json_decode( $data );
		return $data;
	}

	/**
	 * Make object's properties into SQL set-values list
	 */
	private function makeValues( $priKey = true ) {
		$vals = array();
		foreach( LigminchaGlobalDistributed::$tableStruct as $field => $type ) {
			if( $priKey || $field != 'id' ) {
				$prop = "$field";
				$val = self::safeField( $this->$prop, $type );
				$vals[] = "`$field`=$val";
			}
		}
		return implode( ',', $vals );
	}

	/**
	 * Make an SQL condition from the array
	 */
	private static function makeCond( $cond ) {
		$sqlcond = array();
		foreach( $cond as $field => $val ) {
			$val = self::safeField( $val, LigminchaGlobalDistributed::$tableStruct[$field] );
			$sqlcond[] = "`$field`=$val";
		}
		$sqlcond = implode( ' AND ', $sqlcond );
		return $sqlcond;
	}

	/**
	 * Check if the passed database-condition only access owned objects
	 */
	private static function validateCond( $cond ) {
		// TODO
	}

	/**
	 * Make sure a hash is really a hash and use NULL if not
	 * TODO: check better here
	 */
	private static function safeHash( $hash ) {
		$hash = preg_replace( '/[^a-z0-9]/i', '', $hash );
		$hash = $hash ? "0x$hash" : 'NULL';
		return $hash;
	}

	/**
	 * Format a field value ready for an SQL query
	 */
	private static function safeField( $val, $type ) {
		$db = JFactory::getDbo();
		switch( substr( $type, 0, 1 ) ) {
			case 'I': $val = intval( $val );
					  break;
			case 'B': $val = self::safeHash( $val );
					  break;
			default: $val = $db->quote( $val );
		}
		return $val;
	}

	/**
	 * Format the returned SQL fields accounting for hex cols
	 */
	public static function sqlFields() {
		static $fields;
		if( $fields ) return $fields;
		$fields = array();
		foreach( LigminchaGlobalDistributed::$tableStruct as $field => $type ) {
			if( substr( $type, 0, 1 ) == 'B' ) $fields[] = "hex(`$field`) as `$field`";
			else $fields[] = "`$field`";
		}
		$fields = implode( ',', $fields );
		return $fields;
	}

	/**
	 * Convert a DB row assoc array into a LigminchaGlobalObject or sub-class
	 */
	public static function newFromFields( $fields ) {
		$class = 'LigminchaGlobalObject';
		if( array_key_exists( $fields['type'], self::$classes ) ) {
			$c = 'LigminchaGlobal' . self::$classes[$fields['type']];
			if( !class_exists( $c ) ) $class = $c;
		}
		$obj = new $class( true );
		foreach( $fields as $field => $val ) $obj->$field = $val;
		return $obj;
	}

	/**
	 * Given an object type constant, get the glass name
	 */
	public static function typeToClass( $type ) {
		$class = 'LigminchaGlobalObject';
		if( array_key_exists( $row['type'], self::$classes ) ) {
			$c = 'LigminchaGlobal' . self::$classes[$row['type']];
			if( !class_exists( $c ) ) $class = $c;
		}
		return $class;
	}

	/**
	 * Convert an object into an assoc array
	 */
	public function fields() {
		$fields = array();
		foreach( LigminchaGlobalDistributed::$tableStruct as $field => $type ) {
			$fields[$field] = $this->$field;
		}
		return $fields;
	}
}






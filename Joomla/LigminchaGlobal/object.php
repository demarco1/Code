<?php
/**
 * Class representing a single generic object in the distributed database
 */
class LigminchaGlobalObject {

	// Properties for the database row fields
	var $rev_id;
	var $obj_id;
	var $ref1;
	var $ref2;
	var $tag;
	var $type;
	var $time;
	var $expire;
	var $flags;
	var $owner;
	var $group;
	var $name;
	var $data;

	function __construct( $id = false ) {

		// Create a new object with default properties
		if( $id === false ) {
			$this->rev_id = $this->uuid();
			$this->obj_id = $this->uuid();
			$this->flags |= LG_NEW;
		}

		// Load the data from the db into this instance (if it exists)
		else {
			$this->obj_id = $id;
			$this->load();
		}

	}

	/**
	 * Load the data into this object from the DB (return false if no data found)
	 */
	protected function load() {
		$db = JFactory::getDbo();
		$table = '`' . LigminchaGlobalDistributed::$table . '`';
		$all = self::fields();

		// Make sure all the binary refs are in hex format
		$db->setQuery( "SELECT $all FROM $table WHERE `obj_id`=0x{$this->obj_id} ORDER BY `time` DESC LIMIT 1" );
		$db->query();
		if( !$row = $db->loadAssoc() ) return false;

		// TODO: Also check if it's a matching type of type already set
		foreach( $row as $field => $val ) {
			$prop = "$field";
			$this->$prop = $val;
		}

		return true;
	}

	/**
	 * Update or create the object in the database and queue the changes if necessary
	 */
	public function update() {

		// Bail if no type
		if( $this->type < 1 ) {
			print_r($this);
			die( 'Typeless distributed objects not allowed!' );
		}

		// Bail if we're not the owner

		$db = JFactory::getDbo();
		$table = '`' . LigminchaGlobalDistributed::$table . '`';

		if( $this->flags | LG_NEW ) {

			// The entry is owned by the user unless it's a server object
			$this->owner = ( $this->type == LG_SERVER || $this->type == LG_USER ) ? null : LigminchaGlobalUser::getCurrent()->obj_id;

			// Timestamp
			$this->time = time();

			$vals = array();
			foreach( LigminchaGlobalDistributed::$tableStruct as $field => $type ) {
				$prop = "$field";
				$val = self::safeField( $this->$prop, $type );
				$vals[] = "`$field`=$val";
			}
			$db->setQuery( "REPLACE INTO $table SET " . implode( ',', $vals ) );
			$db->query();

		} else {

			// make a new revision for the current object
			

		}

	}

	/**
	 * Find an object in the DB given the passed conditions
	 * TODO: really inefficient at the moment
	 */
	public static function find( $cond ) {
		$db = JFactory::getDbo();
		$table = '`' . LigminchaGlobalDistributed::$table . '`';
		$all = self::fields();

		// Make an SQL condition from the array
		$sqlcond = array();
		foreach( $cond as $field => $val ) {
			$val = self::safeField( $val, LigminchaGlobalDistributed::$tableStruct[$field] );
			$sqlcond[] = "`$field`=$val";
		}
		$cond = implode( ' AND ', $sqlcond );
		if( $cond ) $cond = " AND $cond";

		// First we get the current revision of all the objects matching the static params
		$cond1 = array();
		if( array_key_exists( 'type', $cond ) ) $cond1[] = '`type`=' . intval( $cond['type'] );
		if( array_key_exists( 'owner', $cond ) ) $cond1[] = '`owner`=' . self::safeHash( $cond['owner'] );
		$cond1 = implode( ' AND ', $cond1 );
		$db->setQuery( "SELECT DISTINCT hex(`rev_id`) as rev_id FROM $table WHERE $cond1 ORDER BY `time` DESC LIMIT 100" );
		$db->query();
		$set = implode( ',', array_map( __CLASS__ . '::safeHash', array_keys( $db->loadAssocList('rev_id') ) ) );
		if( empty( $set ) ) return false;

		$db->setQuery( "SELECT $all FROM $table WHERE `rev_id` IN ($set)$cond" );
		$db->query();

		return $db->loadAssocList();
	}

	/**
	 * Generate a new globally unique ID
	 */
	protected function uuid() {
		static $uuid;
		if( !$uuid ) $uuid = uniqid( $_SERVER['HTTP_HOST'], true );
		$uuid = sha1( $uuid . microtime() . uniqid() );
		return $uuid;
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
	public static function fields() {
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
}






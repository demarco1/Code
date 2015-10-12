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

		// Make sure all the binary refs are in hex format
		$cols = array();
		foreach( LigminchaGlobalDistributed::$tableStruct as $field => $type ) {
			if( substr( $type, 0, 1 ) == 'B' ) $cols[] = "hex(`$field`) as `$field`";
			else $cols[] = "`$field`";
		}
		$db->setQuery( "SELECT " . implode( ',', $cols ) . " FROM $table WHERE `obj_id`=0x{$this->obj_id} ORDER BY `time` DESC LIMIT 1" );
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
			$this->owner = ( $this->type == LG_SERVER || $this->type == LG_USER ) ? null : LigminchaGlobalUser::getCurrent();

			// Timestamp
			$this->time = time();

			$vals = array();
			foreach( LigminchaGlobalDistributed::$tableStruct as $field => $type ) {
				$prop = "$field";
				$val = $this->$prop;
				switch( substr( $type, 0, 1 ) ) {
					case 'I': $val = intval( $val );
					          break;
					case 'B': $val = preg_replace( '/[^a-z0-9]/i', '', $val );
					          $val = $val ? "0x$val" : 'NULL';
					          break;
					default: $val = $db->quote( $val );
				}
				$vals[] = "`$field`=$val";
			}
			$db->setQuery( "REPLACE INTO $table SET " . implode( ',', $vals ) );
			$db->query();

		} else {

			// make a new revision for the current object
			

		}

	}

	/**
	 * Generate a new globally unique ID
	 */
	protected function uuid() {
		static $uuid;
		if( !$uuid ) $uuid = uniqid( $_SERVER['HTTP_HOST'], true );
		$uuid = sha1( $uuid . microtime() . uniqid() );
		return strtoupper( $uuid );
	}
}





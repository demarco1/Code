<?php
/**
 * Class representing a single generic object in the distributed database
 */
class LigminchaGlobalObject {

	private $rev_id;
	private $obj_id;
	private $ref1;
	private $ref2;
	private $type;
	private $time;
	private $flags;
	private $owner;
	private $group;
	private $name;
	private $data;

	function __construct( $uuid = false ) {

		if( $uuid === false ) {
			$this->uuid = $this->uuid();
			$this->flags |= LG_NEW;
		}

		$this->uuid = $uuid;

	}

	/**
	 * Update or create the object in the database and queue the changes if necessary
	 */
	public function update() {

		if( $this->flags | LG_NEW ) {

			// insert new revision/object

		} else {

			// make a new revision for the current object

		}

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
}






<?php
/**
 * Global Sync object (temporary objects that are local-only and represent CRUD changes to main objects queued for routing)
 */
class LigminchaGlobalSync extends LigminchaGlobalObject {

	public $stream; // Used before this reveision is sent to determine which stream it's in

	/**
	 * Construct a new sync object
	 * - this will be automatically put into the database for sending unless $target is false
	 */
	function __construct( $crud = null, $fields = null, $target = null ) {
		if( $fields && array_key_exists( 'type', $fields ) && $fields['type'] == LG_SYNC ) die( 'Can\'t construct sync object containing a sync object!' );
		$this->type = LG_SYNC;
		parent::__construct();

		// We have to allow construction with no args so newFromFields can work
		if( is_null( $crud ) ) return;

		// Set the cmd and data
		if( is_null( $fields ) || is_null( $target ) ) die( 'fields and target are mandatory parameters for LG_SYNC object construction' );
		if( $target ) $this->ref1 = $target->id;
		if( $crud == 'U' ) $this->ref2 = $fields['id'];
		$this->tag = $crud;
		$this->data = $fields;

		// Sync objects are always unconditionally local
		$this->flag( LG_LOCAL, true );

		// Store the new object in the database (if it has a target)
		if( $target ) $this->update();
	}

	/**
	 * Process an incoming sync item
	 * - this causes the crud updates that the object represents to be replicated locally
	 * - more sync objects may be created for re-routing purposes
	 */
	public static function process( $crud, $fields, $origin ) {
		if( $crud == 'U' ) $method = 'update';
		elseif( $crud == 'D' ) $method = 'del';
		else die( "Unknown CRUD method \"$crud\"" );
		if( !is_array( $fields ) ) $fields = json_decode( $fields, true );
		call_user_func( "LigminchaGlobalDistributed::$method", $fields, $origin );
	}

	/**
	 * Create outgoing sync objects(s) from local changes or for re-routing
	 * 
	 * Make one or more sync objects depending on the context of this change
	 * - The sync re-routing logic is in here
	 * - $origin is the server the change came from if set
	 * - $private is the owner's server if set
	 */
	public static function create( $crud, $fields, $origin, $private = false ) {
		$server = LigminchaGlobalServer::getCurrent();

		// Origin is set if this change is the result of a received foreign sync object
		if( $origin ) {

			// This is the only condition for which re-routing ever occurs
			if( $server->isMaster && $private === false ) {

				// Create targetted sync objects for all except self and origin
				foreach( LigminchaGlobalServer::select() as $s ) {
					if( $s->id != $server->id && $s->id != $origin ) new LigminchaGlobalSync( $crud, $fields, $s );
				}

			}
		}

		// This change originated locally
		else {

			// If this is the master, then targets depend whether its private or not
			if( $server->isMaster ) {

				// If private then we just have a single targetted sync object to the owner
				if( $private ) new LigminchaGlobalSync( $crud, $fields, $private );

				// If public, make targetted sync objects for all except self
				else {
					foreach( LigminchaGlobalServer::select() as $s ) {
						if( $s->id != $server->id ) new LigminchaGlobalSync( $crud, $fields, $s );
					}
				}
			}

			// If not the master then changes go unconditionally to the master
			else {
				new LigminchaGlobalSync( $crud, $fields, LigminchaGlobalServer::getMaster() );
			}
		}
	}

	/**
	 * Make a new object given an id
	 */
	public static function newFromId( $id, $type = false ) {
		return parent::newFromId( $id, LG_SYNC );
	}

	/**
	 * Add this type to $cond
	 */
	public static function select( $cond = array() ) {
		$cond['type'] = LG_SYNC;
		return parent::select( $cond );
	}

	public static function selectOne( $cond = array() ) {
		$cond['type'] = LG_SYNC;
		return parent::selectOne( $cond );
	}
}

<?php
/**
 * This class encapsulates all the distributed database functionality for the LigminchaGlobal extension
 */

define( 'LG_SUCCESS', 'ok' );
define( 'LG_ERROR', 'error' );

function lgDebug( $msg, $obj = false ) {
	$obj = $obj === false ? '' : ' (' . substr( $obj->id, 0, 5 ) . ')';
	file_put_contents( '/var/www/lg.log', $_SERVER['HTTP_HOST'] . ": $msg$obj\n", FILE_APPEND );
	return $msg;
}

// If we're in stand-alone mode, make a fake version of the Joomla plugin class to allow the distributed.php and object classes to work
if( LG_STANDALONE ) {
	require_once( __DIR__ . '/FakeJoomla.php' );
}

// Need fake MediaWiki environment here
if( !defined( 'MEDIAWIKI' ) ) {
	require_once( __DIR__ . '/FakeMediaWiki.php' );
}

// Lazy-load the Fake MediaWiki environment and the OD Websocket class from the MediaWiki extension
if( !defined( 'WEBSOCKET_VERSION' ) ) {
	require_once( __DIR__ . '/WebSocket/WebSocket.php' );
	WebSocket::$log = '/var/www/extensions/MediaWiki/WebSocket/ws.log'; // tmp to match my current daemon
	WebSocket::$rewrite = true;
	WebSocket::setup();
}


class LigminchaGlobalDistributed {

	// Make singleton available if we need it
	public static $instance;

	// The query-string command for routing changes
	private static $cmd = 'sync';

	// The queue of changes to route at the end of the request
	private static $queue = array();

	// Our distributed data table
	public static $table = 'ligmincha_global';

	// Table structure
	public static $tableStruct = array(
		'id'       => 'BINARY(20) NOT NULL',
		'ref1'     => 'BINARY(20)',
		'ref2'     => 'BINARY(20)',
		'type'     => 'INT UNSIGNED NOT NULL',
		'creation' => 'DOUBLE',
		'modified' => 'DOUBLE',
		'expire'   => 'DOUBLE',
		'flags'    => 'INT UNSIGNED',
		'owner'    => 'BINARY(20)',
		'group'    => 'TEXT',
		'tag'      => 'TEXT',
		'data'     => 'TEXT',
	);

	function __construct() {
		$sa = LG_STANDALONE ? ' (standalone)' : '';
		$ip = $_SERVER['REMOTE_ADDR'];
		lgDebug( "Request started for $ip$sa: " . join( ',', array_keys( $_REQUEST ) ) );

		// Make singleton available if we need it
		self::$instance = $this;

		// Check that the local distributed database table exists and has a matching structure
		$this->checkTable();

		// Delete any objects that have reached their expiry time
		$this->expire();

		// Instantiate the main global objects
		LigminchaGlobalServer::getMaster();
		$server = LigminchaGlobalServer::getCurrent();
		LigminchaGlobalUser::checkAll();
		LigminchaGlobalSSO::makeSessionFromCookie();

		// If this is a changes request,
		if( array_key_exists( self::$cmd, $_REQUEST ) ) {

			// Commit the data (and re-route if master)
			$data = $_REQUEST[self::$cmd];
			if( $data ) print self::recvQueue( $data );

			// If the changes data is empty, then it's a request for initial table data
			elseif( $server->isMaster ) print self::encodeData( $this->initialTableData() );

			// If we're the master, always send queue incase any re-routing
			if( $server->isMaster ) self::sendQueue();
			exit;
		}
	}

	/**
	 * Return table name formatted for use in a query
	 */
	public static function sqlTable() {
		return '`#__' . self::$table . '`';
	}

	/**
	 * Check if the passed table exists (accounting for current table-prefix)
	 */
	private function tableExists( $table ) {
		$config = JFactory::getConfig();
		$prefix = $config->get( 'dbprefix' );
		$db = JFactory::getDbo();
		$db->setQuery( "SHOW TABLES" );
		$db->query();
		return array_key_exists( $prefix . $table, $db->loadRowList( 0 ) );
	}

	/**
	 * Create the distributed database table and request initial sync data to populate it with
	 */
	private function createTable() {
		$db = JFactory::getDbo();
		$table = self::sqlTable();
		$def = array();
		foreach( self::$tableStruct as $field => $type ) $def[] = "`$field` $type";
		$query = "CREATE TABLE $table (" . implode( ',', $def ) . ",PRIMARY KEY (id))";
		$db->setQuery( $query );
		$db->query();
		lgDebug( 'Table created' );

		// TODO: Create an LG_DATABASE object to represent this new node in the distributed database

		// Otherwise, collect initial data to populate table from master server
		if( !LigminchaGlobalServer::getCurrent()->isMaster ) {
			$master = LigminchaGlobalServer::masterDomain();
			lgDebug( "Requesting initial table data from master ($master)" );
			if( $data = self::post( $master, array( self::$cmd => '' ) ) ) {
				lgDebug( "Data successfully received from master ($master)" );
				self::recvQueue( $data );
			} else die( lgDebug( "Failed to get initial table content from master ($master)" ) );
		}

		new LigminchaGlobalLog( 'ligmincha_global table created', 'Database' );
	}

	/**
	 * Return the list of sync objects that will populate a newly created distributed database table
	 */
	private function initialTableData() {
		lgDebug( 'Table data requested' );

		// Just populate new tables with all the server, user and version objects
		$objects = LigminchaGlobalObject::select( array( 'type' => array( LG_SERVER, LG_USER, LG_VERSION ) ) );

		// Ensure that the master server is first
		usort( $objects, function( $a, $b ) {
			if( property_exists( $a, 'isMaster' ) && $a->isMaster ) return -1;
			if( property_exists( $b, 'isMaster' ) && $b->isMaster ) return 1;
			return 0;
		} );

		// Create a normal update sync queue from these objects, but with no target so they won't be added to the database for sending
		$queue = array( 0, LigminchaGlobalServer::getCurrent()->id, 0 );
		foreach( $objects as $obj ) $queue[] = new LigminchaGlobalSync( 'U', $obj->fields(), false );

		lgDebug( 'Table data returned (Objects: ' . count( $objects ) . ')' );
		return $queue;
	}

	/**
	 * Check that the local distributed database table exists and has a matching structure
	 */
	private function checkTable() {

		// If the table doesn't exist,
		if( !$this->tableExists( self::$table ) ) $this->createTable();

		// Otherwise check that it's the right structure
		else {
			$db = JFactory::getDbo();
			$table = self::sqlTable();

			// Get the current structure
			$db->setQuery( "DESCRIBE $table" );
			$db->query();

			$curFields = $db->loadAssocList( null, 'Field' );

			// For now only adding missing fields is supported, not removing, renaming or changing types
			$alter = array();
			foreach( self::$tableStruct as $field => $type ) {
				if( !in_array( $field, $curFields ) ) $alter[$field] = $type;
			}
			if( $alter ) {
				$cols = array();
				foreach( $alter as $field => $type ) $cols[] = "ADD COLUMN `$field` $type";
				$db->setQuery( "ALTER TABLE $table " . implode( ',', $cols ) );
				$db->query();
				new LigminchaGlobalLog( 'ligmincha_global table fields added: (' . implode( ',', array_keys( $alter ) ) . ')', 'Database' );
			}
		}
	}

	/**
	 * Send all queued sync-objects
	 */
	public static function sendQueue() {

		// Get all LG_SYNC items, bail if none
		if( !$queue = LigminchaGlobalSync::select() ) return false;

		// Make data streams for each target from the sync objects
		$streams = array();
		$server = LigminchaGlobalServer::getCurrent()->id;
		$session = LigminchaGlobalSession::getCurrent() ? LigminchaGlobalSession::getCurrent()->id : 0;
		foreach( $queue as $sync ) {
			$target = LigminchaGlobalServer::newFromId( $sync->ref1 )->id;
			if( array_key_exists( $target, $streams ) ) $streams[$target][] = $sync;
			else $streams[$target] = array( 0, $server, $session, $sync );
		}

		//print '<pre>'; print_r($streams); print '</pre>';

		// Encode and send each stream
		foreach( $streams as $target => $stream ) {

			// Get the target domain from it's tag
			$url = LigminchaGlobalServer::newFromId( $target )->tag;

			// Zip up the data in JSON format
			// TODO: encrypt using shared secret or public key
			$data = self::encodeData( $stream );

			// If we're standalone or the master, ensure no data is routed to the master, mark it as successful so the sync objects are cleared
			if( LigminchaGlobalServer::getCurrent()->isMaster && $url == LigminchaGlobalServer::masterDomain() ) {
				$result = LG_SUCCESS;
			}

			// Otherwise post the queue data to the server
			else $result = self::post( $url, array( self::$cmd => $data ) );

			// If result is success, remove all sync objects destined for this target server
			if( $result == LG_SUCCESS ) LigminchaGlobalDistributed::del( array( 'type' => LG_SYNC, 'ref1' => $target ), false, true );
			else die( lgDebug( "Failed to post outgoing sync data to $url result($result)" ) );
		}

		return true;
	}

	/**
	 * Receive sync-object queue from a remote server
	 */
	private static function recvQueue( $data ) {

		// Decode the data
		$queue = $orig = self::decodeData( $data );

		if( !is_array( $queue ) ) die( lgDebug( "Problem with received sync data: $data" ) );
		$ip = array_shift( $queue );
		$origin = array_shift( $queue );
		$session = array_shift( $queue );

		// If we're the master, forward this queue to the WebSocket if it's active
		if( LigminchaGlobalServer::getCurrent()->isMaster ) {
			self::sendToWebSocket( $orig, $session );
		}

		// Process each of the sync objects (this may lead to further re-routing sync objects being made)
		foreach( $queue as $sync ) {
			LigminchaGlobalSync::process( $sync['tag'], $sync['data'], $origin );
		}

		// Let client know that we've processed their sync data
		return 'ok';
	}

	/**
	 * Send data to the local WebSocket daemon if active
	 */
	private static function sendToWebSocket( $queue, $session ) {
		if( WebSocket::isActive() ) {

			// Set the ID of this WebSocket message to the session ID of the sender so the WS server doesn't bounce it back to them
			WebSocket::$clientID = $session;

			// Send queue to all clients
			lgDebug( 'Sending to WebSocket' );
			WebSocket::send( 'LigminchaGlobal', $queue );
			lgDebug( 'Sent to WebSocket' );
		} else lgDebug( 'Not sending to WebSocket: not active' );
	}

	/**
	 * Remove all expired items (these changes are not routed because all servers handle expiration themselves)
	 */
	private function expire() {
		$db = JFactory::getDbo();
		$table = self::sqlTable();
		$db->setQuery( "DELETE FROM $table WHERE `expire` > 0 AND `expire`<" . LigminchaGlobalObject::timestamp() );
		$db->query();
	}

	/**
	 * Encode data for sending
	 */
	private static function encodeData( $data ) {
		return json_encode( $data );
	}

	/**
	 * Decode incoming data (unless it's already an array which is the case when it comes from Ajax)
	 */
	private static function decodeData( $data ) {
		lgDebug( 'Decoding data' );
		$data = is_array( $data ) ? $data : json_decode( $data, true );
		lgDebug( 'Data decoded (Objects: ' . ( count($data) - 3 ) . ')' );
		return $data;
	}

	/**
	 * Decode the data field of the passed DB row
	 * - it may already be an array since it may have arrived from incoming sync queue
	 */
	public static function decodeDataField( $data ) {
		if( is_array( $data ) ) return $data;
		if( substr( $data, 0, 1 ) == '{' || substr( $data, 0, 1 ) == '[' ) $data = json_decode( $data, true );
		return $data;
	}

	/**
	 * POST data to the passed URL
	 */
	public static function post( $url, $data ) {
		$options = array(
			CURLOPT_POST => 1,
			CURLOPT_HEADER => 0,
			CURLOPT_URL => $url,
			CURLOPT_FRESH_CONNECT => 1,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_FORBID_REUSE => 1,
			CURLOPT_TIMEOUT => 5,
			CURLOPT_USERAGENT => "Mozilla",
			CURLOPT_POSTFIELDS => http_build_query( $data )
		);
		$ch = curl_init();
		curl_setopt_array( $ch, $options );
		$result = curl_exec( $ch );
		curl_close( $ch );
		return $result;
	}

	public static function get( $url, $auth = false ) {
		$options = array(
			CURLOPT_URL => $url,
			CURLOPT_TIMEOUT => 10,
			CURLOPT_FRESH_CONNECT => 1,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_FORBID_REUSE => 1,
			CURLOPT_USERAGENT => "Mozilla",
		);
		if( $auth ) $options[CURLOPT_USERPWD] = $auth;
		$ch = curl_init();
		curl_setopt_array( $ch, $options );
		$result = curl_exec( $ch );
		curl_close( $ch );
		return $result;
	}

	/**
	 * Make an SQL condition from the array
	 */
	public static function sqlCond( $cond ) {
		$sqlcond = array();
		foreach( $cond as $field => $val ) {
			if( is_array( $val ) ) {
				$or = array();
				foreach( $val as $v ) {
					$v = self::sqlField( $v, self::$tableStruct[$field] );
					$or[] = "`$field`=$v";
				}
				$sqlcond[] = '(' . implode( ' OR ', $or ) . ')';
			} else {
				$val = self::sqlField( $val, self::$tableStruct[$field] );
				$sqlcond[] = "`$field`=$val";
			}
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
	 */
	public static function sqlHash( $hash ) {
		if( preg_match( '/[^a-z0-9]/i', $hash ) ) die( "Bad hash \"$hash\"" );
		$hash = $hash ? "0x$hash" : 'NULL';
		return $hash;
	}

	/**
	 * Format a field value ready for an SQL query
	 */
	public static function sqlField( $val, $type ) {
		if( is_null( $val ) ) return 'NULL';
		if( is_array( $val ) ) $val = json_encode( $val );
		$db = JFactory::getDbo();
		switch( substr( $type, 0, 1 ) ) {
			case 'I': $val = is_numeric( $val ) ? intval( $val ) : die( "Bad integer: \"$val\"" );;
					  break;
			case 'D': $val = is_numeric( $val ) ? $val : die( "Bad number: \"$val\"" );
					  break;
			case 'B': $val = self::sqlHash( $val );
					  break;
			default: $val = $db->quote( $val );
		}
		if( empty( (string)$val ) ) return '""';
		return $val;
	}

	/**
	 * Format the returned SQL fields accounting for hex cols
	 */
	public static function sqlFields() {
		static $fields;
		if( $fields ) return $fields;
		$fields = array();
		foreach( self::$tableStruct as $field => $type ) {
			if( substr( $type, 0, 1 ) == 'B' ) $fields[] = "hex(`$field`) as `$field`";
			else $fields[] = "`$field`";
		}
		$fields = implode( ',', $fields );
		return $fields;
	}

	/**
	 * Load an object's row from the DB given its ID
	 */
	public static function getObject( $id ) {
		if( !$id ) die( __METHOD__ . ' called without an ID' );
		$db = JFactory::getDbo();
		$table = self::sqlTable();
		$all = self::sqlFields();
		$db->setQuery( "SELECT $all FROM $table WHERE `id`=0x$id" );
		$db->query();
		if( !$row = $db->loadAssoc() ) return false;
		$row['data'] = self::decodeDataField( $row['data'] );
		return $row;
	}

	/**
	 * This is the update interface used by incoming sync objects being processed
	 * - Create a local object from the sync object so we can call the regular update method on it
	 */
	public static function update( $fields, $origin ) {
		if( !is_array( $fields ) ) die( 'Fields must be an array' );
		$obj = LigminchaGlobalObject::newFromFields( $fields );
		$obj->exists = (bool)self::getObject( $obj->id );
		$obj->update( $origin );
	}

	/**
	 * Delete objects matching the condition array
	 * - this is used for processing sync objects and normal delete calls alike
	 */
	public static function del( $cond, $origin = false, $silent = false ) {
		$db = JFactory::getDbo();
		$table = self::sqlTable();

		// Make the condition SQL syntax, bail if nothing
		$sqlcond = self::sqlCond( $cond );
		if( empty( $sqlcond ) ) return false;

		// TODO: validate cond
		// TODO: check no LG_LOCAL in results

		// Do the deletion
		$db->setQuery( "DELETE FROM $table WHERE $sqlcond" );
		$db->query();

		// Add sync object(s) depending on the context of this change
		if( !$silent ) LigminchaGlobalSync::create( 'D', $cond, $origin );
	}
}




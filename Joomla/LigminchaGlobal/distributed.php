<?php
/**
 * This class encapsulates all the distributed database functionality for the LigminchaGlobal extension
 */

// TYPE-SPECIFIC FLAGS (top eight bits - only need to be unique within the scope of their type)

class LigminchaGlobalDistributed {

	// Make singleton available if we need it
	public static $instance;

	// The query-string command for routing changes
	private static $cmd = 'changes';

	// The queue of changes to route at the end of the request
	private static $queue = array();

	// Our distributed data table
	public static $table = '#__ligmincha_global';

	// Table structure
	public static $tableStruct = array(
		'id'       => 'BINARY(20) NOT NULL',
		'ref1'     => 'BINARY(20)',
		'ref2'     => 'BINARY(20)',
		'type'     => 'INT UNSIGNED NOT NULL',
		'creation' => 'INT UNSIGNED',
		'modified' => 'INT UNSIGNED',
		'expire'   => 'INT UNSIGNED',
		'flags'    => 'INT UNSIGNED',
		'owner'    => 'BINARY(20)',
		'group'    => 'TEXT',
		'tag'      => 'TEXT',
		'data'     => 'TEXT',
	);

	function __construct() {

		// Make singleton available if we need it
		self::$instance = $this;

		// Check that the local distributed database table exists and has a matching structure
		$this->checkTable();

		// Delete any objects that have reached their expiry time
		$this->expire();

		// If this is a changes request commit the data (and re-route if master)
		if( array_key_exists( self::$cmd, $_POST ) ) {
			self::recvQueue( $_POST['changes'] );
			exit;
		}
	}

	/**
	 * Check that the local distributed database table exists and has a matching structure
	 */
	private function checkTable() {
		$db = JFactory::getDbo();
		$table = '`' . self::$table . '`';

		// Create the table if it doesn't exist
		$def = array();
		foreach( self::$tableStruct as $field => $type ) $def[] = "`$field` $type";
		$query = "CREATE TABLE IF NOT EXISTS $table (" . implode( ',', $def ) . ",PRIMARY KEY (id))";
		$db->setQuery( $query );
		$db->query();
		$this->log( LG_LOG, 'ligmincha_global table added' );

		// Get the current structure
		$db->setQuery( "DESCRIBE $table" );
		$db->query();

		// If the table exists, check that it's the correct format
		if( $db ) {
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
				$this->log( LG_LOG, 'ligmincha_global table fields added: (' . implode( ',', array_keys( $alter ) ) . ')' );
			}
		}
	}

	/**
	 * Send all queued changes
	 */
	public static function sendQueue() {

		// Get all LG_REVISION items, bail if none
		if( !$revs = LigminchaGlobalObject::find( array( 'type' => LG_REVISION ) ) ) return false;

		// If this is the master, then use zero for session ID
		$server = LigminchaGlobalServer::getCurrent();
		$master = LigminchaGlobalServer::masterDomain();
		$sid = $master ? 0 : $server->id;

		// TODO: if we're the master then we must make a queue for each client filtered by owner/private
		if( $server->isMaster ) {

			// Loop through all clients (skip self) and make an empty stream for each
			$streams = array();
			$clients = LigminchaGlobalServer::find( array( 'type' => NS_SERVER ) );
			foreach( $clients as $client ) {
				if( $client->id != $server->id ) {
					$streams[$client->tag] = array( $sid ); // session ID is first element of a queue
				}
			}
		}

		// Otherwise just one stream to the master domain
		else $streams = array( $master => array( $sid ) );


/// DOH!!!!
/// streams should be many revisions with target server, not stream arrays
//// because they need to be deleted only after recipient acknowledges!
//////////////////////////////////////////////////////////

		// Prepare streams array (one for each recipient, with target domain as key)
		$streams = array();

		// Add all the revision data
		foreach( $revs as $rev ) {

			// Determine the recipient domain of this revision (no tagrget server id in ref1 means use master server)
			$target = $rev->ref1 ? LigminchaGlobalObject::newFromId( $rev->ref1 )->tag : $master;

			// Add revision to this domains stream (create if no stream yet)
			if( array_key_exists( $target, $streams ) ) $streams[$target] = array( $sid );
			else $streams[$target][] = $rev;

			// TODO: If we're the master, then we check this revision to see if it's for all streams, or just one
			if( $master ) {

				// TODO: If its a delete we have to select the cond and check if any are private
				// if just upd, check local object private flag
				if( private ) {

					// This is private so it only goes to the owner's domain
					$owner = $rev
					$stream

				} else {

					foreach( $streams as $stream ) $stream[] = $rev;
				}
			}

			// If we're not master, then this just 
			else {
			}

			$streams[$master][] = array( $rev->tag, $rev->getData() );
		}

		foreach( $streams as $stream ) {

			// Zip up the data in JSON format
			// TODO: encrypt using shared secret or public key
			$data = self::encodeQueue( $queue )

		foreach( $queue as $i ) { print_r($i); print "<br>"; }

			// Post the queue data to the server
			if( LigminchaGlobalServer::getCurrent()->isMaster ) {
			}else {
			$result = self::post( LigminchaGlobalServer::masterDomain(), $data );
			}

			// TODO: if result is success, remove all LG_REVISION items
			if( $result == 200 ) {
				$db = JFactory::getDbo();
				$table = '`' . self::$table . '`';
				$db->setQuery( "DELETE FROM $table WHERE `type`=" . LG_REVISION );
				$db->query();
			}
		}

		return true;
	}

	/**
	 * Encode an object to put on the output queue array
	 */
	private static function decodeQueueItem( $cmd, $fields ) {
		return array( $cmd, $fields );
	}

	/**
	 * Encode the entire queue array ready for sending as a stream
	 */
	private static function encodeQueue( $queue ) {
		return gzcompress( json_encode( $queue ) );
	}

	/**
	 * Decode received queue data
	 */
	private static function decodeQueue( $data ) {
		return json_decode( gzuncompress( $data ), true );
	}

	/**
	 * Receive changes from remote queue
	 */
	private static function recvQueue( $data ) {

		// Unzip and decode the data
		// TODO: decrypt using shared secret or public key
		$queue =  self::decodeQueue( $data );
		$session = array_shift( $queue );

		// TODO: We do actually have to process these revisions - i.e. update the db with the changes
		// - after that the ackknowledgment is sent so the sender can remove the revisions

		if( LigminchaGlobalServer::getCurrent()->isMaster ) {
			// TODO: check group and re-route
			// - loop through queue, and any that are not private are put back on the queue
			foreach( $queue as $item ) {
				if( !$this->flag( LG_PRIVATE ) ) {
					new LigminchaGlobalRevision( $item[0], $item[1] );
				}
			}
		} else {
			// TODO: Check these changes are from the master
		}
	}

	/**
	 * Log an event in the global DB
	 */
	private function log( $text, $user = false ) {

		// If user set to true, get the current user's ID
		if( $user === true ) {
			// TODO
		}


	}

	/**
	 * Remove all expired items (these changes are not routed because all servers handle expiration themselves)
	 */
	private function expire() {
		$db = JFactory::getDbo();
		$table = '`' . self::$table . '`';
		$db->setQuery( "DELETE FROM $table WHERE `expire` > 0 AND `expire`<" . time() );
		$db->query();
	}

	/**
	 * POST data to the passed URL
	 */
	private static post( $url, $data ) {
		$options = array(
			CURLOPT_POST => 1,
			CURLOPT_HEADER => 0,
			CURLOPT_URL => $url,
			CURLOPT_FRESH_CONNECT => 1,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_FORBID_REUSE => 1,
			CURLOPT_TIMEOUT => 5,
			CURLOPT_POSTFIELDS => http_build_query( $data )
		);
		$ch = curl_init();
		curl_setopt_array( $ch, $options );
		if( !$result = curl_exec( $ch ) ) new LigminchaGlobalLog( "POST request to \"$url\" failed", 'Error' );
		curl_close( $ch );
		return $result;
	}
}


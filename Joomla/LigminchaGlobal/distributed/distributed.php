<?php
/**
 * This class encapsulates all the distributed database functionality for the LigminchaGlobal extension
 */


// If we're in stand-alone mode, make a fake version of the Joomla plugin class to allow the distributed.php and object classes to work
if( LG_STANDALONE ) {
	require_once( __DIR__ . '/distributed/standalone.php' );
}


class LigminchaGlobalDistributed {

	// Make singleton available if we need it
	public static $instance;

	// The query-string command for routing changes
	private static $cmd = 'changes';

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

		// Instantiate the main global objects
		$server = LigminchaGlobalServer::getCurrent();
		if( !LG_STANDALONE ) {
			LigminchaGlobalUser::getCurrent();
			LigminchaGlobalSession::getCurrent();
		}

		// If this is a changes request commit the data (and re-route if master)
		// - if the changes data is empty, then it's a request for initial table data
		if( array_key_exists( self::$cmd, $_POST ) ) {
			$data = $_POST['changes'];
			if( $data ) self::recvQueue( $_POST['changes'] );
			elseif( $server->isMaster ) print self::encodeData( $this->initialTableData() );
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
	 * Create the distributed database table and request initial revisions to populate it with
	 */
	private function createTable() {
		$db = JFactory::getDbo();
		$table = self::sqlTable();
		$def = array();
		foreach( self::$tableStruct as $field => $type ) $def[] = "`$field` $type";
		$query = "CREATE TABLE $table (" . implode( ',', $def ) . ",PRIMARY KEY (id))";
		$db->setQuery( $query );
		$db->query();

		// TODO: Create an LG_DATABASE object to represent this new node in the distributed database

		// Collect initial data to populate table from master server
		$master = LigminchaGlobalServer::masterDomain();
		$data = self::post( $master, array( self::$cmd => '' ) );
		if( $data ) self::recvQueue( $data );
		else die( 'Failed to get initial table content from master' );

		new LigminchaGlobalLog( 'ligmincha_global table created', 'Database' );
	}

	/**
	 * Return the list of revisions that will populate a newly created distributed database table
	 */
	private function initialTableData() {

		// Just populate new tables with the master (should be current server) server for now
		$master = LigminchaGlobalServer::getMaster();

		// Create a normal update revision from the object, but with no target so it won't be added to the database for sending
		$rev = new LigminchaGlobalRevision( LG_UPDATE, $master->fields(), false );

		// Unencode the data field (since it's not going into the DB)
		$rev->data = $rev->getData();

		// Create a revision queue that will be processed by the client in the normal way
		$queue = array( LigminchaGlobalServer::getCurrent()->id, 0, $rev );

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
	 * Send all queued changes
	 */
	public static function sendQueue() {

		// Get all LG_REVISION items, bail if none
		if( !$revs = LigminchaGlobalObject::find( array( 'type' => LG_REVISION ) ) ) return false;

		// Make data streams for each target from the revisions
		$streams = array();
		$server = LigminchaGlobalServer::getCurrent()->id;
		$session = LigminchaGlobalServer::getCurrent() ? LigminchaGlobalServer::getCurrent()->id : 0;
		foreach( $revs as $rev ) {
			$target = LigminchaGlobalServer::newFromId( $rev->ref1 )->tag;
			$rev->data = $rev->getData(); // unencode the data field since its not going to the DB
			if( array_key_exists( $target, $streams ) ) $streams[$target][] = $rev;
			else $streams[$target] = array( $server, $session, $rev );
		}

		print '<pre>'; print_r($streams); print '</pre>';

		// Encode and send each stream
		foreach( $streams as $target => $stream ) {

			// Zip up the data in JSON format
			// TODO: encrypt using shared secret or public key
			$data = self::encodeData( $stream );

			// Post the queue data to the server
			$result = self::post( $target, array( self::$cmd => $data ) );

			// If result is success, remove all LG_REVISION items for this target server
			// (can't use obj::del yet because it doesn't check LOCAL to not make further revisions)
			if( $result == 200 ) {
				$db = JFactory::getDbo();
				$table = self::sqlTable();
				$db->setQuery( "DELETE FROM $table WHERE `type`=" . LG_REVISION . " AND `ref1`=0x$target" );
				$db->query();
			}
		}

		return true;
	}

	/**
	 * Receive changes from remote queue
	 */
	private static function recvQueue( $data ) {

		// Unzip and decode the data
		// TODO: decrypt using shared secret or public key
		$queue =  self::decodeData( $data );
		$origin = array_shift( $queue );
		$session = array_shift( $queue );

		// Process each of the revisions (this may lead to further re-routing revisions being made)
		foreach( $queue as $rev ) {
			LigminchaGlobalRevision::process( $rev['tag'], $rev['data'], $origin );
		}
	}

	/**
	 * Remove all expired items (these changes are not routed because all servers handle expiration themselves)
	 */
	private function expire() {
		$db = JFactory::getDbo();
		$table = self::sqlTable();
		$db->setQuery( "DELETE FROM $table WHERE `expire` > 0 AND `expire`<" . time() );
		$db->query();
	}

	/**
	 * Encode data for sending
	 */
	private static function encodeData( $data ) {
		return gzcompress( json_encode( $data ) );
	}

	/**
	 * Decode incoming data
	 */
	private static function decodeData( $data ) {
		return json_decode( gzuncompress( $data ), true );
	}

	/**
	 * POST data to the passed URL
	 */
	private static function post( $url, $data ) {
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


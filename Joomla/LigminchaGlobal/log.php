<?php
/**
 * Global Log item
 */
class LigminchaGlobalLog extends LigminchaGlobalObject {

	function __construct( $message, $tag = '' ) {

		// This goes first so that parent constructor will raise an error if the current uuid type doesn't match
		$this->type = LG_LOG;

		// Give the new object an ID
		parent::__construct();

		// Set the cmd and data
		$this->ref1 = LigminchaGlobalServer::getCurrent()->id;
		$this->ref2 = LigminchaGlobalUser::getCurrent()->id;
		$this->tag = $tag;
		$this->setData( $message );

		// Store the new log entry in the database
		$this->update();
	}
}

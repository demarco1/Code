<?php
/**
 * Global Revision (temporary objects that are local-only and represent changes to main objects queued for routing)
 */
class LigminchaGlobalRevision extends LigminchaGlobalObject {

	function __construct( $cmd, $fields ) {

		// This goes first so that parent constructor will raise an error if the current uuid type doesn't match
		$this->type = LG_REVISION;
		$this->flag( LG_LOCAL, true );

		// Give the new object an ID
		parent::__construct( $id );

		// Set the cmd and data
		$this->tag = $cmd;
		$this->setData( $fields );
		$this->update();
	}
}

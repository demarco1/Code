<?php
/**
 * Global User
 */
class LigminchaGlobalUser extends LigminchaGlobalObject {

	private $server;

	function __construct( $args ) {
		parent::__construct( $args );
		$this->type = LG_USER;
	}

	public function getUser() {

		// Create if non-existent
		if( !$this->server ) {
		}

		return $this->server;
}

<?php
/**
 * Global Session
 */
class LigminchaGlobalSession extends LigminchaGlobalObject {

	private $user;

	function __construct( $args ) {
		parent::__construct( $args );
		$this->type = LG_SESSION;
	}

	public function getUser() {

		// Create if non-existent
		if( !$this->user ) {
		}

		return $this->user;
	}

}

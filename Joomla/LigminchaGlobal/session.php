<?php
/**
 * Global Session
 */
class LigminchaGlobalSession extends LigminchaGlobalObject {

	function __construct() {
		$this->type = LG_SESSION;
		parent::__construct();
		$this->ref1 = LigminchaGlobalServer::getCurrent()->obj_id;

		// Session only lives for five seconds in this initial form (with LG_NEW flag set)
		$this->expire = 5;
	}
}

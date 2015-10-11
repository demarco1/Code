<?php
/**
 * Global Server
 */
class LigminchaGlobalSession extends LigminchaGlobalObject {

	function __construct( $args ) {
		parent::__construct( $args );
		$this->type = LG_SERVER;
		$domain = $_SERVER['HTTP_HOST'];
		$config = JFactory::getConfig();
		$secret = $config->get( 'secret' );
		$uuid = sha1( $secret );
	return;
}

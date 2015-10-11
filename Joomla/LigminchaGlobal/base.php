<?php
/**
 * A few properties common to all the main Ligmincha functionality classes
 */
class LigminchaGlobalBase {

	// Reference to the main plugin class
	private static $plugin = false;

	// Reference to this classes singleton instance
	public static $instance = false;

	// Have a local link to the current session, user and server
	private $session;
	private $user;
	private $server;

	function __construct( $plugin ) {
		self::$instance = $this;
		$this->plugin   = $plugin;
		$this->session  = $plugin->session;
		$this->user     = $this->session->getUser();
		$this->server   = $this->user->getServer();
	}

}

<?php
/**
 * This class encapsulates all the Single-sign-on functionality for the LigminchaGlobal extension
 */
class LigminchaGlobalSSO {

	private $session;
	private $server;

	function __construct() {
		$this->server = LigminchaGlobalServer::getCurrent();

		// If this is an SSO token request and this is the master site, return the key
		if( $this->server->isMaster && array_key_exists( 'getToken', $_REQUEST ) ) {
			$this->getToken( $_REQUEST['getToken'] );
			exit;
		}
	}

	/**
	 * Start a new session (called after login completed)
	 */
	public function startSession() {
		$this->session = new LigminchaGlobalSession();
	}

	/**
	 * If there is a new session for this user/server, append the token request to the page
	 */
	public function appendTokenRequest() {

		// Order all wrong here.... we should have a session already, and just check if it's LG_NEW below...
return;
		$user = $this->session->getUser(); // can't do this if there's no session yet...

		// Bail unless user has just logged in
		if( !$user ) return;

		// TODO: check if any new session in DB for this user/server
		// NOTE: the session should be set up as soon as we have a user (
		$cond = array(
			'type' => LG_SESSION,
			'ref1' => $user->obj_id,
			'ref2' => $server->obj_id,
			'flags' => LG_NEW
		);
		if( $session = someting($cond) ) {

			// If this is the main site, just set the cookie now,
			if( $this->server->isMaster ) setcookie( 'LigminchaGlobalToken', $session->obj_id );

			// Otherwise append a 1x1pixel iFrame to the output that will request a token cookie from the main server
			// TODO: check if $this is already set to the app
			else {
				$url = plgSystemLigminchaGlobal::$instance->params->get( 'lgCookieServer' );
				$app = JFactory::getApplication( 'site' );
				$app->appendBody( "<iframe src=\"$url?getToken={$session->obj_id}\" frameborder=\"0\" width=\"1\" height=\"1\"></iframe>" );
			}
		}
	}
}

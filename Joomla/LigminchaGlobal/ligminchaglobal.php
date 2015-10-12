<?php
/**
 * @copyright	Copyright (C) 2015 Ligmincha International
 * @license		GNU General Public License version 2 or later
 * 
 * See http://wiki.ligmincha.org/LigminchaGlobal_extension for details
 *
 */

// No direct access
//defined('_JEXEC') or die;

// Instantiate the main LigminchaGobal classes
require_once( __DIR__ . '/distributed.php' );
require_once( __DIR__ . '/object.php' );
require_once( __DIR__ . '/server.php' );
require_once( __DIR__ . '/session.php' );
require_once( __DIR__ . '/user.php' );
require_once( __DIR__ . '/sso.php' );

/**
 * @package		Joomla.Plugin
 * @subpackage	System.ligminchaglobal
 * @since 2.5
 */
class plgSystemLigminchaGlobal extends JPlugin {

	public static $instance;

	private $sso;
	private $distributed;

	/**
	 * Called after initialisation of the environment
	 */
	public function onAfterInitialise() {

		self::$instance = $this;

		// Instantiate the main functionality singletons
		$this->distributed = new LigminchaGlobalDistributed();
		$this->sso = new LigminchaGlobalSSO();
	}

	/**
	 * Called after a user has successfully completed the login process
	 */
	public function onUserAfterLogin( $options ) {
		if( array_key_exists( 'user', $options ) ) {
			$this->sso->startSession();
		}
	}

	/**
	 * Called after the page has rendered but before it's been sent to the client
	 */
	public function onAfterRender() {
		$this->sso->appendTokenRequest( $this );
	}

	/**
	 * Called on removal of the extension
	 */
	public function onExtensionAfterUnInstall() {

		// We should backup and remove the db table here

	}
}

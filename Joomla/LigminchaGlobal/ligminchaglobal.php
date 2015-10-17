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

// Notify the LigminchaGobal code that this request is not standalone (i.e. the Joomla framework is present)
define( 'LG_STANDALONE', false );

// Instantiate the main LigminchaGobal classes
require_once( __DIR__ . '/distributed/distributed.php' );
require_once( __DIR__ . '/distributed/object.php' );
require_once( __DIR__ . '/distributed/sync.php' );
require_once( __DIR__ . '/distributed/server.php' );
require_once( __DIR__ . '/distributed/session.php' );
require_once( __DIR__ . '/distributed/log.php' );
require_once( __DIR__ . '/distributed/user.php' );
require_once( __DIR__ . '/sso.php' );

/**
 * @package		Joomla.Plugin
 * @subpackage	System.ligminchaglobal
 * @since 2.5
 */
class plgSystemLigminchaGlobal extends JPlugin {

	// Singleton instance
	public static $instance;

	/**
	 * Called after initialisation of the environment
	 */
	public function onAfterInitialise() {

		// Make this instance available for use by other classes
		self::$instance = $this;

		// Instantiate the main functionality singletons
		new LigminchaGlobalDistributed();
		new LigminchaGlobalSSO();
	}

	/**
	 * Called after a user has successfully completed the login process
	 */
	public function onUserAfterLogin( $options ) {
		if( array_key_exists( 'user', $options ) ) {
			LigminchaGlobalSession::getCurrent();
		}
	}

	/**
	 * Destroy this users global session after logout
	 */
	public function onUserAfterLogout( $options ) {
		LigminchaGlobalSession::delCurrent();
	}

	/**
	 * Called after the page has rendered but before it's been sent to the client
	 */
	public function onAfterRender() {
		LigminchaGlobalSSO::appendTokenRequest();
		LigminchaGlobalDistributed::sendQueue();
	}

	/**
	 * Called on removal of the extension
	 */
	public function onExtensionAfterUnInstall() {

		// We should backup and remove the db table here

	}
}

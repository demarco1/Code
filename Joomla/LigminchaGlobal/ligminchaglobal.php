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

// Instantiate the common LigminchaGobal classes
$common = __DIR__ . '/common';
require_once( "$common/distributed.php" );
require_once( "$common/object.php" );
require_once( "$common/sync.php" );
require_once( "$common/server.php" );
require_once( "$common/user.php" );
require_once( "$common/session.php" );
require_once( "$common/log.php" );
require_once( "$common/sso.php" );

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

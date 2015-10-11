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
require_once( __DIR__ . '/sso.php' );

/**
 * @package		Joomla.Plugin
 * @subpackage	System.ligminchaglobal
 * @since 2.5
 */
class plgSystemLigminchaGlobal extends JPlugin {

	// This site's global ID
	public $siteID = false;

	// Is this the master site?
	public $isMaster = false;

	/**
	 * Called after initialisation of the environment
	 */
	public function onAfterInitialise() {

		// Determine if this is the master site
		$this->checkMaster();

		// Set the global site ID
		$this->checkSite();

		// Initialise the LG classes
		new LigminchaGlobalDistributed( $this );
		new LigminchaGlobalSSO( $this );
	}

	/**
	 * Called after a user has successfully completed the login process
	 */
	public function onUserAfterLogin( $options ) {
		LigminchaGlobalSSO::$instance->justLoggedIn = true;
		header('X-Foo: baz');
	}

	/**
	 * Called after the page has rendered but before it's been sent to the client
	 */
	public function onAfterRender() {
		LigminchaGlobalSSO::$instance->appendTokenRequest( $this );
	}

	/**
	 * Called on removal of the extension
	 */
	public function onExtensionAfterUnInstall() {

		// We should backup and remove the db table here

	}

	/**
	 * Determine whether or not this is the master site
	 */
	private function checkMaster() {
		$this->isMaster = ( $_SERVER['HTTP_HOST'] == 'ligmincha.org' );
	}

	/**
	 * Check that this site exists in the global table, add it if not, set the siteID
	 */
	private function checkSite() {
		if( !$this->isMaster ) {

			// TODO

		} else $this->siteID = 0;
	}


}

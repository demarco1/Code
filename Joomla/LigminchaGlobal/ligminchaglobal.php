<?php
/**
 * @copyright	Copyright (C) 2015 Ligmincha International
 * @license		GNU General Public License version 2 or later
 * 
 * See http://wiki.ligmincha.org/LigminchaGlobal_extension for details
 *
 */

// No direct access
defined('_JEXEC') or die;

/**
 * @package		Joomla.Plugin
 * @subpackage	System.ligminchaglobal
 * @since 2.5
 */
class plgSystemLigminchaGlobal extends JPlugin {

	// Set after a successful login
	private $justLoggedIn = false;

	// Is this the master site?
	private $isMaster = false;

	/**
	 * Called after initialisation of the environment
	 */
	public function onAfterInitialise() {

		// Deterime if this is the master site
		$this->checkMaster();

		// If this is an SSO token request and this is the master site, return the key
		if( $this->isMaster && array_key_exists( 'getToken', $_REQUEST ) ) {
			$this->getToken( $_REQUEST['getToken'] );
			exit;
		}

		// Add the distributed database table if it doesn't already exist
		$db = JFactory::getDbo();
		$tbl = '#__ligmincha_global';
		$query = "CREATE TABLE IF NOT EXISTS `$tbl` (
			id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
			type   INT UNSIGNED NOT NULL,
			time   INT UNSIGNED,
			flags  INT UNSIGNED,
			tags   TEXT,
			name   TEXT,
			data   TEXT,
			PRIMARY KEY (id)
		)";
		$db->setQuery( $query );
		$db->query();

	}

	/**
	 * Called after a user has successfully completed the login process
	 */
	public function onUserAfterLogin( $options ) {
		$this->justLoggedIn = true;
	}

	/**
	 * Called after the page has rendered but before it's been sent to the client
	 */
	public function onAfterRender() {

		// If bail unless user has just logged in 
		if( !$this->justLoggedIn ) return;

		// Get this user's SSO token key
		$key = 'blabla';
		$token = 'fasfsafa'; // May as well get the token in the same query

		// If this is the main site, just set the cookie now,
		if( $this->isMaster ) {
			$this->getToken( $key, $token );
		}

		// Otherwise append the iFrame to the output (Don't think $this is set to the app, but should test)
		else {
			$url = "http://ligmincha.org/index.php?getToken=$key";
			$app = &JFactory::getApplication( 'site' );
			$app->appendBody( "<iframe src=\"$url\" frameborder=\"0\" width=\"1\" height=\"1\"></iframe>" );
		}
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
	 * A client from another domain is requesting an SSO token, set it in a cookie
	 */
	private function getToken( $key, $token = false ) {

		// Double-check that we're definitely the master site
		if( !$this->isMaster ) return;

		// get the token from the db that corresponds with the key
		if( $token === false ) {
			$token = 'blabla';
		}

		// If we have a token, set it in a cookie
		if( $token ) {
			setcookie( 'LigminchaGlobalToken', $token );
		}
	}
}

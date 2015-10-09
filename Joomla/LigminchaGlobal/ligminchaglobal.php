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

define( 'LG_LOG', 1 );

/**
 * @package		Joomla.Plugin
 * @subpackage	System.ligminchaglobal
 * @since 2.5
 */
class plgSystemLigminchaGlobal extends JPlugin {

	// Distributed DB table structure
	private $tableStruct = array(
		'id'    => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
		'type'  => 'INT UNSIGNED NOT NULL',
		'ref1'  => 'INT UNSIGNED NOT NULL',
		'ref2'  => 'INT UNSIGNED NOT NULL',
		'time'  => 'INT UNSIGNED',
		'flags' => 'INT UNSIGNED',
		'tags'  => 'TEXT',
		'name'  => 'TEXT',
		'data'  => 'TEXT',
	);

	// This site's global ID
	private $siteID = false;

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

		// Set the global site ID
		$this->checkSite();

		// If this is an SSO token request and this is the master site, return the key
		if( $this->isMaster && array_key_exists( 'getToken', $_REQUEST ) ) {
			$this->getToken( $_REQUEST['getToken'] );
			exit;
		}

		// Ensure the local distributed DB table matches the current structure
		//$this->checkTable();
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

		// Otherwise append a 1x1pixel iFrame to the output that will request a token cookie from the main server
		// TODO: check if $this is already set to the app
		else {
			$server = $this->params->get( 'lgCookieServer' );
			$app = &JFactory::getApplication( 'site' );
			$app->appendBody( "<iframe src=\"$server?getToken=$key\" frameborder=\"0\" width=\"1\" height=\"1\"></iframe>" );
		}
	}

	/**
	 * Called on removal of the extension
	 */
	public function onExtensionAfterUnInstall() {

		// We should backup and remove the db table here

	}

	/**
	 * Check that the local distributed database table exists and has a matching structure
	 */
	private function checkTable() {
		$db = JFactory::getDbo();
		$tbl = '#__ligmincha_global';

		// Get the current structure
		$db->setQuery( "DESCRIBE TABLE `$tbl`" );
		$db->query();

		// If the table exists, check that it's the correct format
		if( $db ) {
			$curFields = $db->loadAssoc( null, 'Field' );

			// For now only adding missing fields is supported, not removing, renaming or changing types
			$alter = array();
			foreach( $this->tableStruct as $field => $type ) {
				if( !in_array( $field, $curFields ) ) $alter[] = '';
			}
			if( $alter ) {
				$this->log( LG_LOG, 'ligmincha_global table fields added: (' . implode( ',', $alter ) . ')' );
			}
		}

		// Otherwise create the table now
		else {
			$query = "CREATE TABLE IF NOT EXISTS `$tbl` (" . implode( ',', $this->tableStruct ) . ",PRIMARY KEY (id))";
			$db->setQuery( $query );
			$db->query();
			$this->log( LG_LOG, 'ligmincha_global table added' );
		}
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

	/**
	 * Log an event in the global DB
	 */
	private function log( $text, $user = false ) {

		// If user set to true, get the current user's ID
		if( $user === true ) {
			// TODO
		}

		// TODO: set ref1 to the siteID, ref2 to the user if applicable, set timestamp

	}
}

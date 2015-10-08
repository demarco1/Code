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
 * @subpackage	System.mwsso
 * @since 2.5
 */
class plgSystemLigminchaGlobal extends JPlugin {

	/**
	 * Called after initialisation of the environment
	 */
	public function onAfterInitialise() {

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
	 * Called on removal of the extension
	 */
	public function onExtensionAfterUnInstall() {

		// We should backup and remove the db table here

	}

}

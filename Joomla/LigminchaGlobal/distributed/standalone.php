<?php
/**
 * This is a fake Joomla environment so that the distributed object classes can function stand-alone
 */



class JFactory {

	/**
	 * Return a config object populated with the config from the Joomlas config file
	 */
	public static function getConfig() {
	}

	/**
	 * Return a fake user with id set to zero
	 */
	public static function getUser() {
		$jUser = new StdClass();
		$jUser->id = 0;
		return $jUser;
	}
}

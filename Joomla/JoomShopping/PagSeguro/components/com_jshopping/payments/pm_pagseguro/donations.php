<?php
/**
 * This script allows donations to be made to PagSeguro with only the amount needing to be specified
 */

// Set URI to the loja so that the jshopping component is loaded
$_SERVER['REQUEST_URI'] = '/loja';

// Load the Joomla framework
define('_JEXEC', 1);
define('DS', DIRECTORY_SEPARATOR);
define('JPATH_BASE', dirname(__FILE__));
require_once JPATH_BASE.'/includes/defines.php';
require_once JPATH_BASE.'/includes/framework.php';
$app = JFactory::getApplication('site');
$app->initialise();
$app->route();
$app->dispatch();

// Get the PagSeguro payment type ID
$db = JFactory::getDbo();
$db->setQuery( "SELECT `payment_id` FROM `#__jshopping_payment_method` WHERE `scriptname`='pm_pagseguro'" );
if( $row = $db->loadRow() ) {

	// Get the PagSeguro account email and API token
	$pm_method = JSFactory::getTable( 'paymentMethod', 'jshop' );
	$pm_method->load( $row[0] );
	$pmconfigs = $pm_method->getConfigs();
	$email = $pmconfigs['email_received'];
	$token = $pmconfigs['token'];


}

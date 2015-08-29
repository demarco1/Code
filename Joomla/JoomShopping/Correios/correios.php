<?php
/**
 * Modifies Joomshopping shipping types (PAC, SEDEX and Carta registrada) to calculate correios cost during checkout
 * - see components/com_jshopping/controllers/checkout.php for relavent events
 *
 * @copyright	Copyright (C) 2015 Aran Dunkley
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

/**
 * @package		Joomla.Plugin
 * @subpackage	System.correios
 * @since 2.5
 */
class plgSystemCorreios extends JPlugin {

	public static $cartaPrices = array();    // the table of prices per weight for carta registrada
	public static $cartaPricesMod = array(); // the table of prices per weight for carta registrada (módico)
	public static $allbooks;                 // whether the order consists only of book or not (whether carta registrada is allowed or not)

	public function onAfterInitialise() {

		// If this is a local request and carta=update get the weight/costs and cartaupdate set the config
		if( $this->isLocal() && array_key_exists( 'cartaupdate', $_REQUEST ) ) $this->updateWeightCosts();

		// And the Carta registrada prices
		foreach( array( 100, 150, 200, 250, 300, 350, 400, 450 ) as $d ) {
				self::$cartaPrices[$d] = str_replace( ',', '.', $this->params->get( "carta$d" ) );
				self::$cartaPricesMod[$d] = str_replace( ',', '.', $this->params->get( "cartam$d" ) );
		}

		// Install our extended shipping type if not already there
		// (should be done from onExtensionAfterInstall but can't get it to be called)
		// (or better, should be done from the xml with install/uninstall element, but couldn't get that to work either)
		$db = JFactory::getDbo();
		$tbl = '#__jshopping_shipping_ext_calc';
		$db->setQuery( "SELECT 1 FROM `$tbl` WHERE `name`='Correios'" );
		$row = $db->loadRow();
		if( !$row ) {

			// Add the shipping type extension
			$query = "INSERT INTO `$tbl` "
				. "(`name`, `alias`, `description`, `params`, `shipping_method`, `published`, `ordering`) "
				. "VALUES( 'Correios', 'sm_correios', 'Correios', '', '', 1, 1 )";
			$db->setQuery( $query );
			$db->query();

			// Add our freight cost cache table
			$tbl = '#__correios_cache';
			$query = "CREATE TABLE IF NOT EXISTS `$tbl` (
				id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
				cep    INT UNSIGNED NOT NULL,
				weight INT UNSIGNED NOT NULL,
				time   INT UNSIGNED NOT NULL,
				pac    DECIMAL(5,2) NOT NULL,
				sedex  DECIMAL(5,2) NOT NULL,
				PRIMARY KEY (id)
			)";
			$db->setQuery( $query );
			$db->query();

			// Copy the sm_ligmincha_freight class into the proper place
			// (there's probably a proper way to do this from the xml file)
			$path = JPATH_ROOT . '/components/com_jshopping/shippings/sm_correios';
			$file = 'sm_correios.php';
			if( !is_dir( $path ) ) mkdir( $path );
			copy( __DIR__ . "/$file", "$path/$file" );
		}

	}

	/**
	 * Called on removal of the extension
	 */
	public function onExtensionAfterUnInstall() {

		// Remove our extended shipping type
		$db = JFactory::getDbo();
		$tbl = '#__jshopping_shipping_ext_calc';
		$db->setQuery( "DELETE FROM `$tbl` WHERE `name`='Correios'" );
		$db->query();

		// Remove our freight cost cache table
		$tbl = '#__correios_cache';
		$db->setQuery( "DROP TABLE IF EXISTS `$tbl`" );
		$db->query();

		// Remove the script
		$path = JPATH_ROOT . '/components/com_jshopping/shippings/sm_correios';
		$file = 'sm_correios.php';
		if( file_exists( "$path/$file" ) ) unlink( "$path/$file" );
		if( is_dir( $path ) ) rmdir( $path );
	}

	/**
	 * If the order is not all books, remove the Carta registrada options
	 * (the $allbooks settings is updated in checkout by sm_correios class)
	 */
	public function onBeforeDisplayCheckoutStep4View( &$view ) {
		if( !self::$allbooks ) {
			$tmp = array();
			for( $i = 0; $i < count( $view->shipping_methods ); $i++ ) {
				if( !preg_match( '|carta\s*registrada|i', $view->shipping_methods[$i]->name ) ) {
					$tmp[] = $view->shipping_methods[$i];
				}
			}
			$view->shipping_methods = $tmp;
		}
	}

	/**
	 * Return whether request not from a local IP address
	 */
	private function isLocal() {
		if( preg_match_all( "|inet6? addr:\s*([0-9a-f.:]+)|", `/sbin/ifconfig`, $matches ) && !in_array( $_SERVER['REMOTE_ADDR'], $matches[1] ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Get the weight costs from the Correios site and update the config data
	 */
	private function updateWeightCosts() {

		// Get the tracking costs for Nacional and Módico
		$tracking = file_get_contents( 'http://www.correios.com.br/para-voce/consultas-e-solicitacoes/precos-e-prazos/servicos-adicionais-nacionais' );
		if( preg_match( '|<table class="conteudo-tabela">.+?<td>Registro Nacional.+?([1-9][0-9.,]+).+?<td>Registro Módico.+?([1-9][0-9.,]+)|is', $tracking, $m ) ) {
			$tracking = str_replace( ',', '.', $m[2] );

			// Get the weight/costs table
			$weights = file_get_contents( 'http://www.correios.com.br/para-voce/consultas-e-solicitacoes/precos-e-prazos/servicos-nacionais_pasta/carta' );
			if( preg_match( '|Carta não Comercial.+?Mais de 100 até 150</td>\s*(.+?)<tr class="rodape-tabela">|si', $weights, $m ) ) {
				if( preg_match_all( '|<td>([0-9,]+)</td>\s*<td>([0-9,]+)</td>\s*<td>[0-9,]+</td>\s*<td>[0-9,]+</td>\s*<td>[0-9,]+</td>\s*|is', $m[1], $n ) ) {

					// Update the plugin's parameters with the formatted results
					foreach( $n[1] as $i => $v ) {
						$n[1][$i] = number_format( (float)(str_replace( ',', '.', $n[1][$i] ) + $tracking), 2, ',', '' );
						$d = 100 + 50 * $i;
						$this->params->set( "cartam$d", $n[1][$i] );
						$this->params->set( "carta$d", $n[2][$i] );
					}

					// Write the updates to the plugin' parameters field in the extensions table
					$params = (string)$this->params;
					$db = JFactory::getDbo();
					$db->setQuery( "UPDATE `#__extensions` SET `params`='$params' WHERE `name`='plg_system_correios'" );
					$db->query();
				}
			}
		}
	}
}


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
	public static $bookCats = array();       // which categories contain books

	public function onAfterInitialise() {

		// If this is a local request and carta=update get the weight/costs and cartaupdate set the config
		if( $this->isLocal() && array_key_exists( 'cartaupdate', $_REQUEST ) ) $this->updateWeightCosts();

		// Set which cats allow carta registrada from the config form
		self::$bookCats = preg_split( '/\s*,\s*/', $this->params->get( "cartaCats" ) );

		// And the Carta registrada prices
		foreach( array( 0, 50, 100, 150, 200, 250, 300, 350, 400, 450 ) as $d ) {
				$e = $d ? $d : 20;
				self::$cartaPrices[$d] = str_replace( ',', '.', $this->params->get( "carta$e" ) );
				self::$cartaPricesMod[$d] = str_replace( ',', '.', $this->params->get( "cartam$e" ) );
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
			if( !is_link( "$path/$file" ) ) symlink( __DIR__ . "/$file" , "$path/$file" );
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
	 * Change the CSS of the email to inline as many webmail services like gmail ignore style tags
	 * - this is done with François-Marie de Jouvencel's class from https://github.com/djfm/cssin
	 */
	private function inlineStyles( $mailer ) {
		require_once( JPATH_ROOT . '/plugins/system/correios/cssin/src/CSSIN.php' );
		$cssin = new FM\CSSIN();
		$inline = $cssin->inlineCSS( 'http://' . $_SERVER['HTTP_HOST'], $mailer->Body );
		$inline = preg_replace( '|line-height\s*:\s*100%;|', 'line-height:125%', $inline ); // a hack to increase line spacing a bit
		$mailer->Body = $inline;
	}

	/**
	 * Return the shipping method name given it's id
	 */
	public static function getShippingMethodName( $id ) {
		$type = JSFactory::getTable( 'shippingMethod', 'jshop' );
		$type->load( $id );
		return $type->getProperties()['name_pt-BR'];
	}

	/**
	 * Given a product list from a cart or order, make a list of carta registrada packages
	 * - each package is in the form of [ total_weight, { title: [ weight, qty ] } ]
	 */
	public static function makeCartaPackages( $items ) {
		$packages = array();

		// Ensure that all the items are stdClass objects (since orders are, but cart isn't)
		// - and that there are no 0g items in the list
		$tmp = array();
		foreach( $items as $i => $item ) {
			if( is_array( $item ) ) {
				if( array_key_exists( 'quantity', $item ) ) $item['product_quantity'] = $item['quantity'];
				$items[$i] = self::arrayToObject( $item );
				print_r($item);
				print_r($items[$i]);
			}
			if( $item[$i]->weight ) $tmp[] = $item[$i];
		}
		$items = $tmp;
die;
		// If all the items were 0g, return an empty package
		if( count( $items ) == 0 ) return array();

		// Keep creating packages until no items left to add
		while( self::minWeight( $items ) !== false ) {

			// Start a new package and get it's array index
			$packages[] = array( 0, array() );
			$i = count( $packages ) - 1;

			// Keep adding items to the new package until none left or can't fit another
			while( self::minWeight( $items ) !== false && self::minWeight( $items ) + $packages[$i][0] <= 0.5 ) {

				// Find the heaviest item that fits
				$max = 0;
				$j = 0;
				foreach( $items as $k => $item ) {
					$weight = $item->weight;
					if( $weight > $max && $item->product_quantity > 0 && $weight + $packages[$i][0] <= 0.5 ) {
						$max = $weight;
						$j = $k;
					}
				}

				// Add the item to the package and remove from the items-to-process list
				$name = $items[$j]->product_name;
				$weight = $items[$j]->weight;
				if( !array_key_exists( $name, $packages[$i][1] ) ) $packages[$i][1][$name] = array( $weight, 0 );
				$packages[$i][0] += $weight;
				$packages[$i][1][$name][1]++;
				$items[$j]->product_quantity--;
			}
		}
		return $packages;
	}

	/**
	 * Return the minimum product weight from the passed list of products
	 * - ignore products of zero quantity
	 * - return false if no products with any quantity
	 */
	private static function minWeight( $items ) {
		$min = 10000;
		foreach( $items as $item ) {
			if( $item->weight < $min && $item->product_quantity > 0 ) $min = $item->weight;
		}
		return $min == 10000 ? false : $min;
	}

	/**
	 * Convert array into stdClass object
	 */
	private static function arrayToObject( $d ) {
		if( is_array( $d ) ) return (object)array_map( 'self::arrayToObject', $d );
		else return $d;
	}

	/**
	 * Add a manifest for carta registrada orders that have more than one package
	 */
	private function addManifest( $order, $mailer ) {

		// If we've already rendered the manifest (or determined that there isn't one) for this order just use that
		static $html = false;
		if( $html === false ) {
			$html = '';

			// Only have manifest if the order is using a Carta Registrada shipping type
			$type = self::getShippingMethodName( $order->shipping_method_id );
			if( !preg_match( '/carta\s*registrada/i', $type ) ) return;

			// Organise the order into packages of 500g or less
			$packages = self::makeCartaPackages( $order->products );

			// Only have manifest if more than one package
			if( count( $packages ) < 2 ) return;

			// Render the manifest
			$hr = "<tr><td colspan=\"4\"><div style=\"height:1px;border-top:1px solid #999;\"></td></tr>";
			$space = "<tr><td colspan=\"4\"><div style=\"height:10px;\"></td></tr>";
			$html = "<tr><td colspan=\"5\" class=\"bg_gray\"><h3 style=\"font-size:14px;margin:2px\">Package details</h3></td></tr>\n$space\n";
			$html .= "<tr><td colspan=\"5\"><i>These details are included because this order contains more than one package.</i></td></tr>\n$space\n";
			foreach( $packages as $i => $package ) {
				$p = $i + 1;
				$html .= "<tr><td colspan=\"5\"><table width=\"100%\">\n$space\n<tr><th colspan=\"4\" align=\"left\">Package $p</th></tr>\n";
				$html .= "$hr\n<tr><td align=\"left\"><b>Product</b></td><td align=\"right\"><b>Unit weight</b></td><td align=\"right\"><b>Qty</b></td><td align=\"right\"><b>Total</b></td></tr>\n$hr\n";
				foreach( $package[1] as $title => $item ) {
					$weight = $item[0] * 1000;
					$qty = $item[1];
					$total = $weight * $qty;
					$html .= "$space\n<tr><td width=\"75%\">$title</td><td align=\"right\">{$weight}g</td><td align=\"right\">$qty</td><td align=\"right\">{$total}g</td></tr>\n";
				}
				$weight = $package[0] * 1000;
				$html .= "$space\n<tr><td colspan=\"4\" align=\"right\"><b>Total package weight: &nbsp; {$weight}g</td></tr>\n$space\n";
				$html .= "</table></td></tr>";
			}
		}

		// Add the table to the end of the message
		$mailer->Body = preg_replace( "|(.+)(</table>.+?</table>)|s", "$1$html$2", $mailer->Body );
	}

	/**
	 * Set the order mailout events to call our inline method
	 */
	public function onBeforeSendOrderEmailClient( $mailer, $order, &$manuallysend, &$pdfsend ) {
		$this->inlineStyles( $mailer );
	}
	public function onBeforeSendOrderEmailAdmin( $mailer, $order, &$manuallysend, &$pdfsend ) {
		$this->addManifest( $order, $mailer );
		$this->inlineStyles( $mailer );
	}
	public function onBeforeSendOrderEmailVendor( $mailer, $order, &$manuallysend, &$pdfsend, &$vendor, &$vendors_send_message, &$vendor_send_order ) {
		$this->addManifest( $order, $mailer );
		$this->inlineStyles( $mailer );
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
		$debug = array_key_exists( 'debug', $_REQUEST );
		$info = $info2 = $err = '';
		$correios = 'http://www.correios.com.br/para-voce/consultas-e-solicitacoes/precos-e-prazos';

		// Get the tracking costs for Nacional and Módico
		$tracking = file_get_contents( "$correios/servicos-adicionais-nacionais" );
		if( preg_match( '|<table class="conteudo-tabela">.+?<td>Registro Nacional.+?([1-9][0-9.,]+).+?<td>Registro Módico.+?([1-9][0-9.,]+)|is', $tracking, $m ) ) {
			$tracking = str_replace( ',', '.', $m[2] );

			// Get the weight/costs table (starting at the 100-150 gram entry)
			$weights = file_get_contents( "$correios/servicos-nacionais_pasta/carta" );
			if( preg_match( '|Carta não Comercial.+?Mais de 20 até 50</td>\s*(.+?)<tr class="rodape-tabela">|si', $weights, $m ) ) {
				if( preg_match_all( '|<td>([0-9,]+)</td>\s*<td>([0-9,]+)</td>\s*<td>[0-9,]+</td>\s*<td>[0-9,]+</td>\s*<td>[0-9,]+</td>\s*|is', $m[1], $n ) ) {

					// Update the plugin's parameters with the formatted results
					foreach( $n[1] as $i => $v ) {

						// Get the index into the price config in 50 gram divisions
						$d = 50 * $i;
						$e = $d ? $d : 20;

						// Set the Módico price checking for changes
						$k = "cartam$e";
						$v = $n[1][$i];
						if( $debug ) print "$e: $v + $tracking\n";
						$v = number_format( (float)(str_replace( ',', '.', $v ) + $tracking), 2, ',', '' );
						$o = $this->params->get( $k );
						if( $v != $o ) {
							$this->params->set( $k, $v );
							$info .= "Registro Módico price for $e-" . ($d + 50) . "g changed from $o to $v\n";
						}

						// Set the Nacional price checking for changes
						$k = "carta$e";
						$v = $n[2][$i];
						if( $debug ) print "$e: $v\n";
						$o = $this->params->get( $k );
						if( $v != $o ) {
							$this->params->set( $k, $v );
							$info2 .= "Registro Nacional price for $e-" . ($d + 50) . "g changed from $o to $v\n";
						}
					}

					// If changes, write them to the plugin's parameters field in the extensions table
					if( $debug ) exit;
					if( $info || $info2 ) {
						$params = (string)$this->params;
						$db = JFactory::getDbo();
						$db->setQuery( "UPDATE `#__extensions` SET `params`='$params' WHERE `name`='plg_system_correios'" );
						$db->query();
					}
				} else $err .= "ERROR: Found weight/cost table but couldn't extract the data.\n";
			} else $err .= "ERROR: Couldn't find weight/cost table.\n";
		} else $err .= "ERROR: Couldn't retrieve tracking prices.\n";

		// If any info, email it
		$info .= $info2 . $err;
		$config = JFactory::getConfig();
		$from = $config->get( 'mailfrom' );
		if( !$to = $config->get( 'webmaster' ) ) $to = $from;
		$mailer = JFactory::getMailer();
		$mailer->addRecipient( $to );
		if( !$err && $to != $from ) $mailer->addRecipient( $from );
		$mailer->setSubject( 'Notification from Correios extension' );
		$mailer->setBody( $info );
		$mailer->isHTML( false );
		$send = $mailer->Send();
	}
}

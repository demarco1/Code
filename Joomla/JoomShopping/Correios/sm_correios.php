<?php

// No direct access
defined('_JEXEC') or die;

class sm_correios extends shippingextRoot {

	var $version = 2;

	var $cost = array(); // local cache of the shipping prices for this request

	function showShippingPriceForm( $params, &$shipping_ext_row, &$template ) {
	}

	function showConfigForm( $config, &$shipping_ext, &$template ) {
	}

	/**
	 * This method is called for each of the shipping methods options in the checkout
	 * (and also on subsequent pages for the chosen method)
	 */
	function getPrices( $cart, $params, $prices, &$shipping_ext_row, &$shipping_method_price ) {
		$weight = $cart->getWeightProducts();
		$id = $shipping_method_price->shipping_method_id;
		$type = plgSystemCorreios::getShippingMethodName( $id );
print '<pre>' . JshopHelpersMetadata::checkoutAddress() . '</pre>';
die;
		// Redirect the page stright to payment methods if weight is zero
		if( $weight == 0 ) {
			static $done = false;
			if( !$done ) {
				$done = true;
				header( "Location: /finalizar-compra/step4save?sh_pr_method_id=$id" );
			}
		}

		// Check if all products are in cats that allow carta registrada and that all are 500g or less
		plgSystemCorreios::$allbooks = true;
		foreach( $cart->products as $item ) {
			if( !in_array( $item['category_id'], plgSystemCorreios::$bookCats ) || $item['weight'] > 0.5 ) {
				plgSystemCorreios::$allbooks = false;
			}
		}

		// If it's one of ours, calculate the price
		if( $type == 'PAC' ) {
			$prices['shipping'] = $this->getFreightPrice( $weight, 1 );
			$prices['package'] = 0;
		}

		elseif( $type == 'SEDEX' ) {
			$prices['shipping'] = $this->getFreightPrice( $weight, 2 );
			$prices['package'] = 0;
		}

		elseif( preg_match( '/carta\s*registrada/i', $type ) ) {
			$packages = plgSystemCorreios::makeCartaPackages( $cart->products );
			$costs = preg_match( '/módico/i', $type ) ? plgSystemCorreios::$cartaPricesMod : plgSystemCorreios::$cartaPrices;
			$price = 0;
			foreach( $packages as $package ) {
				$weight = $package[0];
				$i = 50*(int)($weight*20); // price divisions are in multiples of 50 grams
				$price += $costs[$i];
			}
			$prices['shipping'] = $price;
			$prices['package'] = 0;
		}
		return $prices;
	}

	/**
	 * Return the cost for a given a weight and shipping type
	 */
	private function getFreightPrice( $weight, $type ) {
		if( $weight == 0 ) return 0;
		$client = JSFactory::getUser();
		$cep2 = preg_replace( '|[^\d]|', '', $client->d_zip ? $client->d_zip : $client->zip );

		// Local cache (stores in an array so that no lookups are required for the same data in the same request)
		if( array_key_exists( $type, $this->cost ) ) return $this->cost[$type];

		// DB cache (costs for pac and sedex are stored in the database per weight and CEP for a day)
		$cost = $this->getCache( $weight, $cep2 );
		if( $cost ) return $cost[$type];

		// Not cached locally or in the database, get prices from the external API and store locally and in database
		$vendor = JSFactory::getTable('vendor', 'jshop');
		$vendor->loadMain();
		$cep1 = preg_replace( '|[^\d]|', '', $vendor->zip );

		// Get prices for both types since they're cached together
		if( is_numeric( $result = $this->correios( $cep1, $cep2, $weight, 1 ) ) ) $this->cost[1] = $result;
		else return JError::raiseWarning( '', "Error: $result" );
		if( is_numeric( $result = $this->correios( $cep1, $cep2, $weight, 2 ) ) ) $this->cost[2] = $result;
		else return JError::raiseWarning( '', "Error: $result" );

		// Store the price pair in the cache
		$this->setCache( $weight, $cep2, $this->cost );

		// Return just the requested price
		return $this->cost[$type];
	}

	/**
	 * Call the Correios API with passed params
	 * - $type is 1 for PAC or 2 for SEDEX
	 */
	function correios( $cep1, $cep2, $weight, $type ) {
		$service = $type == 1 ? 41106 : 40010;
		$url = 'http://ws.correios.com.br/calculador/CalcPrecoPrazo.aspx?nCdEmpresa=&sDsSenha='
			. "&sCepOrigem=$cep1"
			. "&sCepDestino=$cep2"
			. "&nVlPeso=$weight"
			. "&nCdServico=$service"
			. '&nCdFormato=1&nVlComprimento=20&nVlAltura=5&nVlLargura=20&nVlDiametro=0' // Parcel size
			. '&sCdMaoPropria=n&nVlValorDeclarado=0&sCdAvisoRecebimento=n'
			. '&StrRetorno=XML&nIndicaCalculo=1'; // Return as XML and include price only, not time
		$result = file_get_contents( $url );
		if( preg_match( '|<valor>([0-9.,]+)</valor>|i', $result, $m ) ) $result = str_replace( ',', '.', $m[1] );
		return $result;
	}

	/**
	 * Check if a database cache entry exists for this weight and destination
	 */
	private function getCache( $weight, $cep ) {
		$weight *= 1000;
		$db = JFactory::getDbo();
		$tbl = '#__correios_cache';

		// Only keep cache entries forr a day
		$expire = time() - 86400;
		$query = "DELETE FROM `$tbl` WHERE time < $expire";
		$db->setQuery( $query );
		$db->query();

		// Load and return the item if any match our parameters
		$db->setQuery( "SELECT pac,sedex FROM `$tbl` WHERE cep=$cep AND weight=$weight ORDER BY time DESC LIMIT 1" );
		$row = $db->loadRow();
		return $row ? array( 1 => $row[0], 2 => $row[1] ) : false;
	}

	/**
	 * Create a database cache entry for this weight and destination
	 */
	private function setCache( $weight, $cep, $costs ) {
		$weight *= 1000;
		$db = JFactory::getDbo();
		$tbl = '#__correios_cache';

		// Delete any of the same parameters
		$query = "DELETE FROM `$tbl` WHERE cep=$cep AND weight=$weight";
		$db->setQuery( $query );
		$db->query();

		// Insert new item with these parameters
		$pac = $costs[1];
		$sedex = $costs[2];
		$query = "INSERT INTO `$tbl` (cep, weight, time, pac, sedex) VALUES( $cep, $weight, " . time() . ", $pac, $sedex )";
		$db->setQuery( $query );
		$db->query();
	}
}

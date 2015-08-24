<?php
#defined('_JEXEC') or die('Restricted access');

class pm_pagseguro extends PaymentRoot{

	private $payment_status = 0;
	private $err = false;

	function showPaymentForm( $params, $pmconfigs ) {
		include( dirname( __FILE__ ) . "/paymentform.php" );
	}

	function showAdminFormParams( $params ) {
		$lang = JFactory::getLanguage();
		$lang->load( 'com_jshopping', JPATH_COMPONENT . '/payments/pm_pagseguro', $lang->getTag() );

		$array_params = array('email_received', 'token', 'test_token', 'transaction_end_status', 'transaction_pending_status', 'transaction_failed_status');
		foreach ($array_params as $key) {
			if (!isset($params[$key])) $params[$key] = '';
		} 
		$orders = JSFactory::getModel('orders', 'JshoppingModel'); // Admin model
		include( dirname( __FILE__ ) . "/adminparamsform.php" );
	}

	function checkTransaction( $pmconfigs, $order, $act ) {
		$jshopConfig = JSFactory::getConfig();
		if( $this->payment_status > 0 && $this->err === false ) {
			if( $this->payment_status == 3 || $this->payment_status == 4 ) {
				return array(1, '', $transaction, $transactiondata);
			} elseif( $this->payment_status < 3 ) {
				$reason = constant( '_JSHOP_PAGSEGURO_STATUS_' . $this->payment_status );
				saveToLog( "payment.log", "Status pending. Order ID " . $order->order_id . ". Reason: $reason" );
				return array( 2, $reason, $transaction, $transactiondata );
			} else {
				return array( 3, "Status $payment_status. Order ID ".$order->order_id, $transaction, $transactiondata);
			}
		} else return array( 0, "Error: $err", $transaction, $transactiondata );
	}

	function showEndForm( $pmconfigs, $order ) {
		$jshopConfig = JSFactory::getConfig();
		$pm_method = $this->getPmMethod();
		$item_name = sprintf(_JSHOP_PAYMENT_NUMBER, $order->order_number);
		$sandbox = $pmconfigs['testmode'] ? 'sandbox.' : '';
		$email = $pmconfigs['email_received'];
		$token = $pmconfigs[$sandbox ? 'test_token' : 'token'];
		$address_override = (int)$pmconfigs['address_override'];
		$_country = JSFactory::getTable('country', 'jshop');
		$_country->load($order->d_country);
		$country = $_country->country_code_2;
		$order->order_total = $this->fixOrderTotal($order);

		// Return links
		$uri = JURI::getInstance();
		$liveurlhost = $uri->toString( array( 'scheme', 'host', 'port' ) );
		if ($pmconfigs['notifyurlsef']) {
			$notify_url = $liveurlhost.SEFLink("index.php?option=com_jshopping&controller=checkout&task=step7&act=notify&js_paymentclass=".$pm_method->payment_class."&no_lang=1");
		} else {
			$notify_url = JURI::root()."index.php?option=com_jshopping&controller=checkout&task=step7&act=notify&js_paymentclass=".$pm_method->payment_class."&no_lang=1";
		}
		$return = $liveurlhost.SEFLink("index.php?option=com_jshopping&controller=checkout&task=step7&act=return&js_paymentclass=".$pm_method->payment_class);
		$cancel_return = $liveurlhost.SEFLink("index.php?option=com_jshopping&controller=checkout&task=step7&act=cancel&js_paymentclass=".$pm_method->payment_class);

		// Build data for the request
		$vendor = JSFactory::getTable('vendor', 'jshop');
		$vendor->loadMain();
		$data = array(
				'email' => $email,
				'token' => $token,
				'senderName' => $vendor->shop_name,
				'senderAreaCode' => 11, // Hacked to Sao Paulo, but shipping not calculated so doesn't matter
				'senderEmail' => $order->email,
				'currency' => $order->currency_code_iso,
				'redirectURL' => $return,
				'reference' => $order->order_id,
				'itemId1' => $order->order_id,
				'itemDescription1' => $item_name,
				'itemAmount1' => $order->order_total,
				'itemQuantity1' => 1,
				'shippingCost' => number_format($order->order_shipping, 2, '.', ''),
				'shippingType' => 3,
				'shippingAddressStreet' => $order->d_street,
				'shippingAddressPostalCode' => $order->d_zip,
				'shippingAddressCity' => $order->d_city,
				'shippingAddressState' => $order->d_state,
				'shippingAddressCountry' => 'BRA'
		);

		// Post the order data to PagSeguro
		$options = array(
			CURLOPT_POST => 1,
			CURLOPT_HEADER => 0,
			CURLOPT_URL => "https://ws.{$sandbox}pagseguro.uol.com.br/v2/checkout/",
			CURLOPT_FRESH_CONNECT => 1,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_FORBID_REUSE => 1,
			CURLOPT_TIMEOUT => 4,
			CURLOPT_POSTFIELDS => http_build_query( $data )
		);
		$ch = curl_init();
		curl_setopt_array( $ch, $options );
		if( !$result = curl_exec( $ch ) ) die( 'Error: ' . curl_error( $ch ) );
		curl_close( $ch );

		// If we received a code, redirect the client to PagSeguro tp complete the order
		$code = preg_match( '|<code>(.+?)</code>|', $result, $m ) ? $m[1] : false;
		if( $code ) {
			JFactory::getApplication()->enqueueMessage( "Code: $code" );
			header( "Location: https://{$sandbox}pagseguro.uol.com.br/v2/checkout/payment.html?code=$code" );
		} else die( "Error: $result" );
	}

	/**
	 * Query PagSeguro for the local order ID and payment status from the returned PagSeguro transaction ID
	 */
	function getUrlParams($pmconfigs) {
		$params = array();
		$sandbox = $pmconfigs['testmode'] ? 'sandbox.' : '';
		$email = $pmconfigs['email_received'];
		$token = $pmconfigs[$sandbox ? 'test_token' : 'token'];
		$tx = $_GET['tx'];
		$url = "https://ws.{$sandbox}pagseguro.uol.com.br/v3/transactions/$tx?email=$email&token=$token";
		$result = @file_get_contents( $url );
		if( preg_match( '|<reference>(.+?)</reference>.*?<status>(\d+?)</status>|s', $result, $m ) ) {
			$params['order_id'] = $m[1];
			$params['hash'] = "";
			$params['checkHash'] = 0;
			$params['checkReturnParams'] = $pmconfigs['checkdatareturn'];
			$this->payment_status = $m[2];
		} else $this->err = trim( stripslashes( $result ) );
		return $params;
	}

	function fixOrderTotal($order) {
		$total = $order->order_total;
		if ($order->currency_code_iso=='HUF') {
			$total = round($total);
		} else {
			$total = number_format($total, 2, '.', '');
		}
		return $total;
	}
}
?>

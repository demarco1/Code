<?php
defined('_JEXEC') or die('Restricted access');

class pm_pagseguro extends PaymentRoot{
	
	function showPaymentForm( $params, $pmconfigs ) {
		include( dirname( __FILE__ ) . "/paymentform.php" );
	}

	function showAdminFormParams( $params ) {
		$array_params = array('email_received', 'token', 'transaction_end_status', 'transaction_pending_status', 'transaction_failed_status');
		foreach ($array_params as $key) {
			if (!isset($params[$key])) $params[$key] = '';
		} 
		$orders = JSFactory::getModel('orders', 'JshoppingModel'); // Admin model
		include( dirname( __FILE__ ) . "/adminparamsform.php" );
	}

	function checkTransaction( $pmconfigs, $order, $act ) {
		$jshopConfig = JSFactory::getConfig();

		if ($pmconfigs['testmode']) {
			$host = "www.sandbox.paypal.com";
		} else {
			$host = "www.paypal.com";
		}

		$post = JRequest::get('post');
		$order->order_total = $this->fixOrderTotal($order);
		$email_received = $_POST['business'];
		if ($email_received=="") $email_received = $_POST['receiver_email'];

		$opending = 0;
		if ($order->order_total != $_POST['mc_gross'] || $order->currency_code_iso != $_POST['mc_currency']) {
			$opending = 1;
		}

		$payment_status = trim($post['payment_status']);
		$transaction = $post['txn_id'];
		$transactiondata = array('txn_id'=>$post['txn_id'],'payer_email'=>$post['payer_email'], 'mc_gross'=>$post['mc_gross'], 'mc_currency'=>$post['mc_currency'], 'payment_status'=>$post['payment_status']);

		if (strtolower($pmconfigs['email_received']) != strtolower($email_received)) {
			return array(0, 'Error email received. Order ID '.$order->order_id, $transaction, $transactiondata);
		}

		$req = 'cmd=_notify-validate';
		if  (function_exists('get_magic_quotes_gpc')) {
			$get_magic_quotes_exists = true;
		}
		foreach($_POST as $key => $value) {
			if($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
				$value = urlencode(stripslashes($value));
			} else {
				$value = urlencode($value);
			}
			$req .= "&$key=$value";
		}

		$ch = curl_init('https://'.$host.'/cgi-bin/webscr');
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($ch, CURLOPT_SSLVERSION, 4);
		curl_setopt($ch, CURLOPT_USERAGENT, 'PayPal-PHP-SDK');
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
		if( !($res = curl_exec($ch)) ) {
			saveToLog("payment.log", "PagSeguro failed: ".curl_error($ch).'('.curl_errno($ch).')');
			curl_close($ch);
			exit;
		} else {
			curl_close($ch);
		}
		saveToLog("paymentdata.log", "RES: $res");

		if (strcmp ($res, "VERIFIED") == 0) {
			if ($payment_status == 'Completed') {
				if ($opending) {
					saveToLog("payment.log", "Status pending. Order ID ".$order->order_id.". Error mc_gross or mc_currency.");
					return array(2, "Status pending. Order ID ".$order->order_id, $transaction, $transactiondata);
				} else {
					return array(1, '', $transaction, $transactiondata);
				}
			} elseif ($payment_status == 'Pending') {
				saveToLog("payment.log", "Status pending. Order ID ".$order->order_id.". Reason: ".$_POST['pending_reason']);
				return array(2, trim(stripslashes($_POST['pending_reason'])), $transaction, $transactiondata);
			} else {
				return array(3, "Status $payment_status. Order ID ".$order->order_id, $transaction, $transactiondata);
			}
		} elseif (strcmp ($res, "INVALID") == 0) {
			return array(0, 'Invalid response. Order ID '.$order->order_id, $transaction, $transactiondata);
		}
	}

	function showEndForm( $pmconfigs, $order ) {
		$jshopConfig = JSFactory::getConfig();
		$pm_method = $this->getPmMethod();
		$item_name = sprintf(_JSHOP_PAYMENT_NUMBER, $order->order_number);
		$email = $pmconfigs['email_received'];
		$token = $pmconfigs['token'];
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
		$data = array(
			'email' => $email,
			'token' => $token,
			'senderName' => $vendor->shop_name,
			'senderAreaCode' => $vendor->zip,
			'senderEmail' => $vendor->email,
			'currency' => $order->currency_code_iso,
			'redirectURL' => $return,
			'reference' => $order->order_id,
			'itemId1' => $order->order_id,
			'itemDescription1' => $item_name,
			'itemAmount1' => $order->order_total,
			'itemQuantity1' => 1,
			'receiverEmail' => $order->email,
			'shippingCost' => 0, // need to find shipping cost
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
			CURLOPT_URL => 'https://ws.pagseguro.uol.com.br/v2/checkout/',
			CURLOPT_FRESH_CONNECT => 1,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_FORBID_REUSE => 1,
			CURLOPT_TIMEOUT => 4,
			CURLOPT_POSTFIELDS => http_build_query( $data )
		);
		$ch = curl_init();
		curl_setopt_array( $ch, $data );
		if( $result = curl_exec( $ch ) ) return $result;
		else die( curl_error( $ch ) );
		curl_close( $ch );

		// If we received a code, redirect the client to PagSeguro tp complete the order
		$code = preg_match( '|<code>(.+?)</code>|', $result, $m ) ? $m[1] : false;
		if( $code ) {
			JFactory::getApplication()->enqueueMessage( "Code: $code" );
			header( "Location: https://pagseguro.uol.com.br/v2/checkout/payment.html?code=$code" );
		} else {
			die( curl_error( $result ) );
		}
	}

	function getUrlParams($pmconfigs) {
		$params = array(); 
		$params['order_id'] = JRequest::getInt("custom");
		$params['hash'] = "";
		$params['checkHash'] = 0;
		$params['checkReturnParams'] = $pmconfigs['checkdatareturn'];
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

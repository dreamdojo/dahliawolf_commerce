<?
class Orders_Controller extends _Controller {

	public function place_order($params = array()) {
		$this->load('Customer');
		$this->load('Orders');
		$this->load('Cart');
		$this->load('Order_Cart_Rule');
		$this->load('Order_Invoice');
		$this->load('Order_Payment');
		$this->load('Order_Detail');
		$this->load('Order_Detail_Tax');
		$this->load('Payment_Method', ADMIN_API_HOST, ADMIN_API_USER, ADMIN_API_PASSWORD, ADMIN_API_DATABASE);
		$this->load('Commission');
		$this->load('Store_Credit');

		$data = array();

		if (empty($params['payment_info']) || !is_array($params['payment_info'])) {
			_Model::$Exception_Helper->bad_request_exception('Payment info is empty.');
		}

		$validate_names = array(
			'user_id' => NULL
			, 'id_shop' => NULL
			, 'id_lang' => NULL
			, 'id_cart' => NULL
			, 'shipping_address_id' => NULL
			, 'billing_address_id' => NULL
			, 'id_delivery'
			, 'payment_info' => array(
				'amount' => NULL
				, 'payment_method_id' => NULL
				, 'cc_name' => NULL
				, 'cc_number' => NULL
				, 'cc_exp_month' => NULL
				, 'cc_exp_year' => NULL
				, 'cc_cvv' => NULL
				, 'description' => NULL
			)
		);

		$validate_params = array_merge($validate_names, $params);

		// Validations
		$input_validations = array(
			'user_id' => array(
				'label' => 'User Id'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'id_shop' => array(
				'label' => 'Shop Id'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'id_lang' => array(
				'label' => 'Language Id'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'id_cart' => array(
				'label' => 'Cart Id'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'shipping_address_id' => array(
				'label' => 'Shipping Address Id'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'billing_address_id' => array(
				'label' => 'Billing Address Id'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'id_delivery' => array(
				'label' => 'Delivery Id'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
		);

		// Payment info validations
		$this->Validate->add('amount', 'Total', $validate_params['payment_info']['amount'])
			->add_validation('is_set')
			->add_validation('is_decimal', '2')
			->add_validation('is_positive');

		$this->Validate->add('payment_method_id', 'Payment Method', $validate_params['payment_info']['payment_method_id'])
			->add_validation('is_set')
			->add_validation('is_int');

		$cc_payment = false;
		$paypal_payment = false;
		if (!empty($validate_params['payment_info']) && !empty($validate_params['payment_info']['payment_method_id'])) {
			// Payment method
			$pm = $this->Payment_Method->get_row(
				array(
					'payment_method_id' => $validate_params['payment_info']['payment_method_id']
					, 'active' => '1'
				)
			);

			if (empty($pm)) {
				_Model::$Exception_Helper->request_failed_exception('Payment method not found.');
			}

			if ($pm['name'] == 'Credit Card') {
				$cc_payment = true;
			}
			else if ($pm['name'] == 'PayPal') {
				$paypal_payment = true;
			}
		}

		if ($cc_payment) { // CC validations
			$this->Validate->add('cc_name', 'Name on Card', $validate_params['payment_info']['cc_name'])
				->add_validation('is_set');
			$this->Validate->add('cc_number', 'Card Number', $validate_params['payment_info']['cc_number'])
				->add_validation('is_set')
				->add_validation('is_int');
			$this->Validate->add('cc_exp_month', 'Card Expiration Month', $validate_params['payment_info']['cc_exp_month'])
				->add_validation('is_set');
			$this->Validate->add('cc_exp_year', 'Card Expiration Year', $validate_params['payment_info']['cc_exp_year'])
				->add_validation('is_set')
				->add_validation('is_int');
			$this->Validate->add('cc_cvv', 'Card CVV', $validate_params['payment_info']['cc_cvv'])
				->add_validation('is_set')
				->add_validation('is_int');
		}

		$this->Validate->add_many($input_validations, $validate_params, true);
		$this->Validate->run();

		$now = _Model::$date_time;

		// Get customer id
		$customer = $this->Customer->get_row(
			array(
				'user_id' => $params['user_id']
			)
		);

		if (empty($customer)) {
			_Model::$Exception_Helper->request_failed_exception('Customer not found.');
		}

		// Get billing address for avs
		$Address_Controller = new Address_Controller();
		$billing_address_info = $Address_Controller->get_hq_user_address_info(
			array(
				'user_id' => $params['user_id']
				, 'address_id' => $params['billing_address_id']
			)
		);

		if (empty($billing_address_info)) {
			_Model::$Exception_Helper->request_failed_exception('Billing address not found.');
		}

		$shipping_address_info = $Address_Controller->get_hq_user_address_info(
			array(
				'user_id' => $params['user_id']
				, 'address_id' => $params['shipping_address_id']
			)
		);

		if (empty($shipping_address_info)) {
			_Model::$Exception_Helper->request_failed_exception('Shipping address not found.');
		}

		// Get cart
		$Cart_Controller = new Cart_Controller();
		$cart_result = $Cart_Controller->get_cart_from_db(
			array(
				'user_id' => $params['user_id']
				, 'id_shop' => $params['id_shop']
				, 'id_lang' => $params['id_lang']
				, 'id_cart' => $params['id_cart']
				, 'shipping_address_id' => $params['shipping_address_id']
				, 'id_delivery' => $params['id_delivery']
				, 'error_on_invalid_carrier' => true
			)
		);
		$cart = $cart_result['data'];

		if (empty($cart)) {
			_Model::$Exception_Helper->request_failed_exception('Customer Cart not found.');
		}
		else if (empty($cart['products'])) {
			_Model::$Exception_Helper->request_failed_exception('Cart is empty.');
		}
		// User may have updated cart in other tab
		//else if (($params['payment_info']['amount'] + 0) != ($cart['cart']['totals']['grand_total'] + 0)) {
		else if (!numbers_are_equal($params['payment_info']['amount'], $cart['cart']['totals']['grand_total'])) {
			_Model::$Exception_Helper->request_failed_exception('Cart total does not match payment total. Payment amount: ' . $var);
		}
		else if (empty($cart['cart']['carrier'])) {
			_Model::$Exception_Helper->request_failed_exception('Invalid Carrier.');
		}

		// If redeeming commissions, check that cart_commission amount does not exceed user's commissions or cart total
		if (!empty($cart['cart_commission'])) {
			$user_total_commissions = $this->Commission->get_user_total($params['user_id']);
			if ($user_total_commissions['total_commissions'] < $cart['cart_commission']['amount']) {
				_Model::$Exception_Helper->request_failed_exception('Commission redemption amount exceeds total earned commissions.');
			}
			if ($cart['cart']['totals']['grand_total'] < $cart['cart_commission']['amount']) {
				_Model::$Exception_Helper->request_failed_exception('Commission redemption amount exceeds cart total.');
			}
		}
		// If redeeming store credits, check that cart_store_credit amount does not exceed user's store credits or cart total
		if (!empty($cart['cart_store_credit'])) {
			$user_total_credits = $this->Store_Credit->get_user_total($params['user_id']);
			if ($user_total_credits['total_credits'] < $cart['cart_store_credit']['amount']) {
				_Model::$Exception_Helper->request_failed_exception('Store credit redemption amount exceeds total store credits.');
			}
			if ($cart['cart']['totals']['products'] < $cart['cart_store_credit']['amount']) {
				_Model::$Exception_Helper->request_failed_exception('Store credit redemption amount exceeds cart total.');
			}
		}

		// Process Payment
		$payment_success = false;
		$transaction_id = NULL;
		$authorization_transaction_id = NULL;

		if ($cc_payment) {
			$calls = array(
				'process_credit_card' => array(
					'amount' => $params['payment_info']['amount']
					, 'name' => $params['payment_info']['cc_name']
					, 'number' => $params['payment_info']['cc_number']
					, 'exp_month' => $params['payment_info']['cc_exp_month']
					, 'exp_year' => $params['payment_info']['cc_exp_year']
					, 'cvv' => $params['payment_info']['cc_cvv']
					, 'description' => !empty($params['payment_info']['description']) ? $params['payment_info']['description'] : ''
					, 'address' => array(
						"first_name" => $billing_address_info['address']['first_name'],
						"last_name" => $billing_address_info['address']['last_name'],
						"street" => $billing_address_info['address']['street'],
						"street_2" => $billing_address_info['address']['street_2'],
						"city" => $billing_address_info['address']['city'],
						"state"	=> $billing_address_info['address']['state'],
						"zip" => $billing_address_info['address']['zip'],
						'country' => $billing_address_info['address']['country']
					)
					, 'shipping_address' => array(
						"first_name" => $shipping_address_info['address']['first_name'],
						"last_name" => $shipping_address_info['address']['last_name'],
						"street" => $shipping_address_info['address']['street'],
						"street_2" => $shipping_address_info['address']['street_2'],
						"city" => $shipping_address_info['address']['city'],
						"state"	=> $shipping_address_info['address']['state'],
						"zip" => $shipping_address_info['address']['zip'],
						'country' => $shipping_address_info['address']['country']
					)
				)
			);

			$API = new API(API_KEY_DEVELOPER, PRIVATE_KEY_DEVELOPER);
			$cc_result = $API->rest_api_request('payment', $calls);
			$cc_result_decoded = json_decode($cc_result, true);
			if ($cc_result_decoded == '') {
				_Model::$Exception_Helper->request_failed_exception($cc_result);
			}
			$api_errors = api_errors_to_array($cc_result_decoded);

			if (!empty($api_errors)) {
				return static::wrap_result(false, NULL, _Model::$Status_Code->get_status_code_request_failed(), $api_errors);
			}

			if ($cc_result_decoded['data']['process_credit_card']['data']['approved']) { // Payment success
				$payment_success = true;
				if ($cc_result_decoded['data']['process_credit_card']['data']['is_authorization']) {
					$authorization_transaction_id = $cc_result_decoded['data']['process_credit_card']['data']['transaction_id'];
					$is_authorization = true;
				}
				else {
					$transaction_id = $cc_result_decoded['data']['process_credit_card']['data']['transaction_id'];
					$is_authorization = false;
				}
			}
			else { // Payment failed
				_Model::$Exception_Helper->request_failed_exception('Your payment could not be processed: ' . $cc_result_decoded['data']['process_credit_card']['data']['response_reason_text']);
			}

		}
		else if ($paypal_payment) { // Process Paypal
			if (empty($cart['cart']['paypal_token'])) {
				_Model::$Exception_Helper->request_failed_exception('PayPal token is not set.');
			}

			$payment_success = true;
			$calls = array(
				'complete_paypal_purchase' => array(
					'amount' => $cart['cart']['totals']['grand_total']
					, 'token' => $cart['cart']['paypal_token']
				)
			);

			$API = new API(API_KEY_DEVELOPER, PRIVATE_KEY_DEVELOPER);
			$result = $API->rest_api_request('payment', $calls);
			$result_decoded = json_decode($result, true);

			$api_errors = api_errors_to_array($result_decoded);

			if (!empty($api_errors)) {
				// make user reauthorize amount
				$this->Cart->save(
					array(
						'id_cart' => $cart['cart']['id_cart']
						, 'paypal_token' => NULL
					)
				);

				return static::wrap_result(false, NULL, _Model::$Status_Code->get_status_code_request_failed(), $api_errors);
			}

			$payment_success = true;

			if ($result_decoded['data']['complete_paypal_purchase']['data']['is_authorization']) {
				$authorization_transaction_id = $result_decoded['data']['complete_paypal_purchase']['data']['payment_details']['PAYMENTINFO_0_TRANSACTIONID'];
				$is_authorization = true;
			}
			else {
				$transaction_id = $result_decoded['data']['complete_paypal_purchase']['data']['payment_details']['PAYMENTINFO_0_TRANSACTIONID'];
				$is_authorization = false;
			}
		}

		$payment_success = true;
		if (!$payment_success) {
			_Model::$Exception_Helper->request_failed_exception('Your order could not be processed.');
		}

		// Order
		$total_discount_tax_excl = $cart['cart']['totals']['discounts'];
		$total_discounts_tax_incl = ($cart['cart']['totals']['discounts'] - $cart['cart']['totals']['discount_tax']);
		$total_paid_tax_incl = $cart['cart']['totals']['grand_total'];
		$total_paid_tax_excl = ($cart['cart']['totals']['products'] + (-1 * $cart['cart']['totals']['discounts']) + $cart['cart']['totals']['shipping'] + $cart['cart']['totals']['wrapping']);
		$total_products = $cart['cart']['totals']['products'];
		$total_products_wt = $cart['cart']['totals']['product_weight'];
		$total_shipping_tax_incl = ($cart['cart']['totals']['shipping'] + $cart['cart']['totals']['shipping_tax']);
		$total_shipping_tax_excl = $cart['cart']['totals']['shipping'];
		$total_wrapping_tax_incl = ($cart['cart']['totals']['wrapping'] + $cart['cart']['totals']['wrapping_tax']);
		$total_wrapping_tax_excl = $cart['cart']['totals']['wrapping'];

		$order_data = array(
			'id_customer' => $customer['id_customer']
			, 'id_shop' => $params['id_shop']
			, 'id_carrier' => $cart['cart']['carrier']['id_carrier']
			, 'id_delivery' => $params['id_delivery']
			, 'id_lang' => $params['id_lang']
			, 'id_cart' => $cart['cart']['id_cart']
			, 'id_currency' => 1
			, 'id_address_delivery' => $params['shipping_address_id']
			, 'id_address_invoice' => $params['billing_address_id']
			//, 'current_state'
			, 'total' => $cart['cart']['totals']['grand_total']
			, 'total_discounts' => $cart['cart']['totals']['discounts']
			, 'total_discounts_tax_incl' => $total_discounts_tax_incl
			, 'total_discounts_tax_excl' => $total_discount_tax_excl
			, 'total_paid' => $is_authorization ? 0 : $cart['cart']['totals']['grand_total']
			, 'total_paid_tax_incl' => $is_authorization ? 0 : $total_paid_tax_incl
			, 'total_paid_tax_excl' => $is_authorization ? 0 : $total_paid_tax_excl
			, 'total_products' => $total_products
			, 'total_products_wt' => $total_products_wt
			, 'total_shipping' => $cart['cart']['totals']['shipping']
			, 'total_shipping_tax_incl' => $total_shipping_tax_incl
			, 'total_shipping_tax_excl' => $total_shipping_tax_excl
			, 'carrier_tax_rate' => !empty($cart['cart']['carrier']['tax_info']) && is_numeric($cart['cart']['carrier']['tax_info']['rate']) ? $cart['cart']['carrier']['tax_info']['rate'] : 0
			, 'total_wrapping' => $cart['cart']['totals']['wrapping']
			, 'total_wrapping_tax_incl' => $total_wrapping_tax_incl
			, 'total_wrapping_tax_excl' => $total_wrapping_tax_excl
			, 'date_add' => $now
			, 'product_tax' => is_numeric($cart['cart']['totals']['product_tax']) ? $cart['cart']['totals']['product_tax'] : 0
			, 'shipping_tax' => is_numeric($cart['cart']['totals']['shipping_tax']) ? $cart['cart']['totals']['shipping_tax'] : 0
			, 'discount_tax' => is_numeric($cart['cart']['totals']['discount_tax']) ? $cart['cart']['totals']['discount_tax'] : 0
			, 'wrapping_tax' => is_numeric($cart['cart']['totals']['wrapping_tax']) ? $cart['cart']['totals']['wrapping_tax'] : 0
			, 'payment_status' => $is_authorization ? 'Authorized' : 'Paid'
		);

		$data['id_order'] = $this->Orders->save($order_data);

		// Order state
		/*
		$order_state_data = array(
			'invoice' => 0
			, 'send_email' => 0
			, 'module_name' => NULL
			, 'color' => NULL
			, 'unremovable' => 0
			, 'hidden' => 0
			, 'logable' => 0
			, 'delivery' => 0
			, 'shipped' => 0
			, 'paid' => 0
			, 'deleted' => 0
		);
		$this->Order_State->save($order_state_data);
		*/
		// Order invoice
		// needs more details
		$order_invoice_data = array(
			'id_order' => $data['id_order']
			, 'total_discount_tax_excl' => $total_discount_tax_excl
			, 'total_discount_tax_incl' => $total_discounts_tax_incl
			, 'total_paid_tax_excl' => $is_authorization ? 0 : $total_paid_tax_excl
			, 'total_paid_tax_incl' => $is_authorization ? 0 : $total_paid_tax_incl
			, 'total_products' => $total_products
			, 'total_products_wt' => $total_products_wt
			, 'total_shipping_tax_excl' => $total_shipping_tax_excl
			, 'total_shipping_tax_incl' => $total_shipping_tax_incl
			, 'total_wrapping_tax_excl' => $total_wrapping_tax_excl
			, 'total_wrapping_tax_incl' => $total_wrapping_tax_incl
			, 'date_add' => $now
		);

		$id_order_invoice = $this->Order_Invoice->save($order_invoice_data);

		// Order products
		if (!empty($cart['products'])) {
			foreach ($cart['products'] as $order_product) {
				$product_price = ($order_product['product_info']['on_sale'] == '1') ? $order_product['product_info']['sale_price'] : $order_product['product_info']['price'];

				$unit_tax = !empty($order_product['tax_info']) && is_numeric($order_product['tax_info']['unit_amount']) ? $order_product['tax_info']['unit_amount'] : 0;
				$unit_price_tax_excl = $product_price;
				$unit_price_tax_incl = $unit_price_tax_excl + $unit_tax;
				$total_product_tax = !empty($order_product['tax_info']) && is_numeric($order_product['tax_info']['total_amount']) ? $order_product['tax_info']['total_amount'] : 0;
				$total_price_tax_excl = ($product_price * $order_product['quantity']);
				$total_price_tax_incl = $total_price_tax_excl + $total_product_tax;
				$order_detail_data = array(
					'id_order' => $data['id_order']
					, 'id_order_invoice' => $id_order_invoice
					, 'id_shop' => $params['id_shop']
					, 'product_id' => $order_product['id_product']
					, 'product_attribute_id' => $order_product['id_product_attribute']
					, 'product_name' => $order_product['product_info']['product_name']
					, 'product_quantity' => $order_product['quantity']
					, 'product_price' => $product_price
					, 'product_ean13' => $order_product['product_info']['ean13']
					, 'product_upc' => $order_product['product_info']['upc']
					, 'product_reference' => $order_product['product_info']['reference']
					, 'product_supplier_reference' => $order_product['product_info']['supplier_reference']
					, 'product_weight' => $order_product['product_info']['weight']
					, 'tax_name' => !empty($order_product['tax_info']) && $order_product['tax_info']['tax_name'] != '' ? $order_product['tax_info']['tax_name'] : ''
					, 'tax_rate' => !empty($order_product['tax_info']) && is_numeric($order_product['tax_info']['rate']) ? $order_product['tax_info']['rate'] : 0
					, 'total_price_tax_incl' => $total_price_tax_incl
					, 'total_price_tax_excl' => $total_price_tax_excl
					, 'unit_price_tax_incl' => $unit_price_tax_incl
					, 'unit_price_tax_excl' => $unit_price_tax_excl
					, 'unit_tax' => $unit_tax
					, 'total_shipping_price_tax_incl' => 0
					, 'total_shipping_price_tax_excl' => 0
					, 'original_product_price' => $order_product['product_info']['price']
				);

				$id_order_detail = $this->Order_Detail->save($order_detail_data);

				$order_detail_tax_data = array(
					'id_order_detail' => $id_order_detail
					, 'id_tax' => !empty($order_product['tax_info']) && is_numeric($order_product['tax_info']['id_tax']) ? $order_product['tax_info']['id_tax'] : ''
					, 'unit_amount' => $unit_tax
					, 'total_amount' => $total_product_tax
				);

				$this->Order_Detail_Tax->save($order_detail_tax_data);

				// Credit commissions
				// For each product, credit product.user_id with product.commission * quantity
				if (!empty($order_product['product_info']['user_id'])) {
					$commission_data = array(
						'user_id' => $order_product['product_info']['user_id']
						, 'id_order' => $data['id_order']
						, 'id_product' => $order_product['product_info']['id_product']
						, 'id_order_detail' => $id_order_detail
						, 'product_quantity' => $order_product['quantity']
						, 'commission' => $order_product['product_info']['commission'] * $order_product['quantity']
					);
					$this->Commission->save($commission_data);
				}
			}
		}

		// Order payment
		$order_payment_data = array(
			'id_order' => $data['id_order']
			, 'payment_method_id' => $params['payment_info']['payment_method_id']
			, 'amount' => $is_authorization ? NULL : $cart['cart']['totals']['grand_total']
			, 'transaction_id' => $is_authorization ? NULL : $transaction_id
			, 'amount_authorized' => $is_authorization ? $cart['cart']['totals']['grand_total'] : NULL
			, 'authorization_transaction_id' => $is_authorization ? $authorization_transaction_id : NULL
			, 'date_add' => $now
			, 'card_number' => $cc_payment ? substr($params['payment_info']['cc_number'], -4) : NULL
		);

		$this->Order_Payment->save($order_payment_data);

		$discount_ids = array();
		if (!empty($cart['discounts'])) {
			foreach ($cart['discounts'] as $discount) {
				if (!in_array($discount['id_cart_rule'], $discount_ids)) { // prevent duplicates
					$cart_cart_rule_data = array(
						'id_order' => $data['id_order']
						, 'id_cart_rule' => $discount['id_cart_rule']
						, 'id_order_invoice' => $id_order_invoice
						, 'name' => $discount['name']
						, 'value' => $total_discounts_tax_incl
						, 'value_tax_excl' => $total_discount_tax_excl
					);

					$this->Order_Cart_Rule->save($cart_cart_rule_data);

					array_push($discount_ids, $discount['id_cart_rule']);
				}
			}
		}

		// Send email to user
		$this->load('Config', ADMIN_API_HOST, ADMIN_API_USER, ADMIN_API_PASSWORD, ADMIN_API_DATABASE);
		$email_domain = $_SERVER['HTTP_REFERER'];
		$order_email_prefix = $this->Config->get_value('Orders From Email Prefix');
		$from_email = $order_email_prefix . '@' . $email_domain;

		$subject = 'Order Confirmation';
		$custom_variables = array(
			'email' => $customer['email']
			, 'site_name' => $email_domain
			, 'domain' => $email_domain
			, 'cart' => $cart
		);

		$template_variables = array(
			'first_name' => $customer['firstname']
			, 'email' => $customer['email']
			, 'domain' => $email_domain
			, 'site_name' => $email_domain
			, 'cdn_domain' => ''
		);

		$Email_Template_Helper = new Email_Template_Helper();
		$email_results = $Email_Template_Helper->sendEmail('order-confirmation', $custom_variables, $template_variables, $email_domain, $from_email, $customer['firstname'] . ' ' . $customer['lastname'], $customer['email'], $subject, $from_email);

		// Send email to staff
		//$email_results = $Email_Template_Helper->sendEmail('order-confirmation', $custom_variables, $template_variables, $email_domain, $from_email, $from_email, $from_email, $subject, $from_email);

		// Send email to product users
		foreach ($cart['products'] as $i => $product) {
			if (!empty($product['product_info']['user_id'])) {
				$customer = $this->Customer->get_row(
					array(
						'user_id' => $product['product_info']['user_id']
					)
				);

				$subject = 'Product Order Notification';
				$custom_variables = array(
					'email' => $customer['email']
					, 'site_name' => $email_domain
					, 'domain' => $email_domain
					, 'product_name' => $product['product_info']['product_lang_name']
				);

				$template_variables = array(
					'first_name' => $customer['firstname']
					, 'email' => $customer['email']
					, 'domain' => $email_domain
					, 'site_name' => $email_domain
					, 'cdn_domain' => ''
				);

				$email_results = $Email_Template_Helper->sendEmail('product-order-notice', $custom_variables, $template_variables, $email_domain, $from_email, $customer['firstname'] . ' ' . $customer['lastname'], $customer['email'], $subject, $from_email);
			}
		}

		// Use points toward purchase
		$this->load('Dw_User_Point', DW_API_HOST, DW_API_USER, DW_API_PASSWORD, DW_API_DATABASE);
		/*if (!empty($cart['cart']['points'])) {
			$points_spent = array(
				'user_id' => $params['user_id']
				, 'point_id' => 11
				, 'points' => -1 * $cart['cart']['points']
				, 'id_order' => $data['id_order']
				, 'note' => 'Points spent toward purchase'
				);
			$this->Dw_User_Point->save($points_spent);
		}*/

		// Credit points
		// Point value * order total
		/*$this->load('Dw_Point', DW_API_HOST, DW_API_USER, DW_API_PASSWORD, DW_API_DATABASE);
		$point_value = $this->Dw_Point->get_buy_points_amount();
		$points = floor($point_value * $cart['cart']['totals']['grand_total']);*/
		$user_point_data = array(
			'user_id' => $params['user_id']
			, 'point_id' => 11
			, 'points' => $cart['points']['will_earn']
			, 'id_order' => $data['id_order']
		);
		$this->Dw_User_Point->save($user_point_data);

		// Deduct redeemed commissions
		if (!empty($cart['cart_commission']) && !empty($cart['cart_commission']['amount'])) {
			$redeemed_commission_data = array(
				'user_id' => $params['user_id']
				, 'id_order' => $data['id_order']
				, 'commission' => -1 * $cart['cart_commission']['amount']
				, 'note' => 'Commission Redemption'
			);
			$this->Commission->save($redeemed_commission_data);
		}
		// Deduct redeemed store credits
		if (!empty($cart['cart_store_credit']) && !empty($cart['cart_store_credit']['amount'])) {
			$redeemed_store_credit_data = array(
				'user_id' => $params['user_id']
				, 'id_order' => $data['id_order']
				, 'amount' => -1 * $cart['cart_store_credit']['amount']
				, 'note' => 'Store Credit Redemption'
			);
			$this->Store_Credit->save($redeemed_store_credit_data);
		}

		return static::wrap_result(true, $data);
	}

	public function test() {
		die();
		$this->load('Config');
		$this->load('Customer');
		$where_params = array(
			'user_id' => 669
		);
		$customer = $this->Customer->get_row($where_params);


		// Get cart
		$Cart_Controller = new Cart_Controller();
		$cart_result = $Cart_Controller->get_cart_from_db(
			array(
				'user_id' => 669
				, 'id_shop' => 3
				, 'id_lang' => 1
				, 'id_cart' => 100
				, 'shipping_address_id' => 8
				, 'id_delivery' => 2
				, 'error_on_invalid_carrier' => true
			)
		);
		$cart = $cart_result['data'];



		$email_domain = $_SERVER['HTTP_REFERER'];
		$order_email_prefix = $this->Config->get_value('Orders Email Prefix');
		$from_email = $order_email_prefix . '@' . $email_domain;

		$subject = 'Order Confirmation';
		$custom_variables = array(
			'email' => $customer['email']
			, 'site_name' => $email_domain
			, 'domain' => $email_domain
			, 'cart' => $cart
		);

		$template_variables = array(
			'first_name' => $customer['firstname']
			, 'email' => $customer['email']
			, 'domain' => $email_domain
			, 'site_name' => $email_domain
			, 'cdn_domain' => ''
		);

		$Email_Template_Helper = new Email_Template_Helper();

		$email_results = $Email_Template_Helper->sendEmail('order-confirmation', $custom_variables, $template_variables, $email_domain, $from_email, $customer['firstname'] . ' ' . $customer['lastname'], $customer['email'], $subject, $from_email);

		// Send email to product users
		foreach ($cart['products'] as $i => $product) {
			if (!empty($product['product_info']['user_id'])) {
				$customer = $this->Customer->get_row(
					array(
						'user_id' => $product['product_info']['user_id']
					)
				);

				$subject = 'Product Order Notification';
				$custom_variables = array(
					'email' => $customer['email']
					, 'site_name' => $email_domain
					, 'domain' => $email_domain
					, 'product_name' => $product['product_info']['product_lang_name']
				);

				$template_variables = array(
					'first_name' => $customer['firstname']
					, 'email' => $customer['email']
					, 'domain' => $email_domain
					, 'site_name' => $email_domain
					, 'cdn_domain' => ''
				);

				$Email_Template_Helper->sendEmail('product-order-notice', $custom_variables, $template_variables, $email_domain, $from_email, $customer['firstname'] . ' ' . $customer['lastname'], $customer['email'], $subject, $from_email);
			}
		}
	}

	public function get_user_orders($params = array()) {
		$this->load('Customer');
		$this->load('Orders');
		$this->load('Cart');
		$this->load('Order_Invoice');
		$this->load('Order_Payment');
		$this->load('Order_Detail');
		$this->load('Order_Detail_Tax');

		// Validations
		$input_validations = array(
			'user_id' => array(
				'label' => 'User Id'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'id_shop' => array(
				'label' => 'Shop Id'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'id_lang' => array(
				'label' => 'Language Id'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
		);
		$this->Validate->add_many($input_validations, $params, true);
		$this->Validate->run();

		// Get customer id
		$customer = $this->Customer->get_row(
			array(
				'user_id' => $params['user_id']
			)
		);

		if (empty($customer)) {
			_Model::$Exception_Helper->request_failed_exception('Customer not found.');
		}

		$orders = $this->Orders->get_rows(
			array(
				'id_customer' => $customer['id_customer']
				, 'id_shop' => $params['id_shop']
			)
		);

		$data = $orders;

		return static::wrap_result(true, $data);
	}

	public function get_user_order_details($params = array()) {
		$this->load('Customer');
		$this->load('Orders');
		$this->load('Cart');
		$this->load('Order_Invoice');
		$this->load('Order_Payment');
		$this->load('Order_Detail');
		$this->load('Order_Detail_Tax');
		$this->load('Order_Cart_Rule');
		$this->load('Product');
		$this->load('Cart_Commission');
		$this->load('Cart_Store_Credit');
		$this->load('Address', ADMIN_API_HOST, ADMIN_API_USER, ADMIN_API_PASSWORD, ADMIN_API_DATABASE);
		$this->load('Dw_User_Point', DW_API_HOST, DW_API_USER, DW_API_PASSWORD, DW_API_DATABASE);

		// Validations
		$input_validations = array(
			'user_id' => array(
				'label' => 'User Id'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'id_shop' => array(
				'label' => 'Shop Id'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'id_lang' => array(
				'label' => 'Language Id'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'id_order' => array(
				'label' => 'Order Id'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
		);
		$this->Validate->add_many($input_validations, $params, true);
		$this->Validate->run();

		// Get customer id
		$customer = $this->Customer->get_row(
			array(
				'user_id' => $params['user_id']
			)
		);

		if (empty($customer)) {
			_Model::$Exception_Helper->request_failed_exception('Customer not found.');
		}

		$order = $this->Orders->get_row(
			array(
				'id_customer' => $customer['id_customer']
				, 'id_shop' => $params['id_shop']
				, 'id_order' => $params['id_order']
			)
		);

		if (empty($order)) {
			_Model::$Exception_Helper->request_failed_exception('Order not found.');
		}

		// Get products
		$order['products'] = $this->Product->get_order_products($params['id_order'], $params['id_shop'], $params['id_lang']);
		if (!empty($order['products'])) {
			foreach ($order['products'] as $i => $product) {
				$order['products'][$i]['combinations'] = $this->Product->get_product_combinations($product['product_id'], $order['id_shop'], $order['id_lang']);
			}
		}

		// Get billing & shipping addresses
		$order['addresses'] = array();
		$order['addresses']['billing'] = $this->Address->get_row(
			array(
				'address_id' => $order['id_address_invoice']
			)
		);
		$order['addresses']['shipping'] = $this->Address->get_row(
			array(
				'address_id' => $order['id_address_delivery']
			)
		);

		// Payment method
		$order['order_payment'] = $this->Order_Payment->get_order_payment($params['id_order']);

		// Shipping method
		$order['shipping_method'] = $this->Orders->get_shipping_method($params['id_order']);

		// Discounts
		$order['discounts'] = $this->Order_Cart_Rule->get_rows(
			array(
				'id_order' => $params['id_order']
			)
		);

		// Cart Commission
		$order['cart_commission'] = $this->Cart_Commission->get_row(
			array(
				'id_cart' => $order['id_cart']
			)
		);
		// Cart Store Credit
		$order['cart_store_credit'] = $this->Cart_Store_Credit->get_row(
			array(
				'id_cart' => $order['id_cart']
			)
		);

		// Points spent/earned
		$order['points'] = array();
		$order['points']['earned'] = $this->Dw_User_Point->get_order_points_earned($params['id_order']);

		// Shipments


		$data = $order;

		return static::wrap_result(true, $data);
	}

	public function capture_order_payment($params) {
		$this->load('Orders');
		$this->load('Order_Invoice');
		$this->load('Order_Payment');
		$this->load('Payment_Method', ADMIN_API_HOST, ADMIN_API_USER, ADMIN_API_PASSWORD, ADMIN_API_DATABASE);

		// Validations
		$input_validations = array(
			'id_order' => array(
				'label' => 'Order ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
		);
		$this->Validate->add_many($input_validations, $params, true);
		$this->Validate->run();

		$cc_payment = false;
		$paypal_payment = false;

		$order = $this->Orders->get_row(
			array('id_order' => $params['id_order'])
		);

		if (empty($order)) {
			_Model::$Exception_Helper->request_failed_exception('Order could not be found.');
		}

		$order_invoice = $this->Order_Invoice->get_row(
			array('id_order' => $order['id_order'])
		);

		$order_payment = $this->Order_Payment->get_row(
			array('id_order' => $order['id_order'])
		);

		if (empty($order_payment)) {
			_Model::$Exception_Helper->request_failed_exception('Order Payment could not be found.');
		}
		else if ($order_payment['authorization_transaction_id'] == '') {
			_Model::$Exception_Helper->request_failed_exception('Authorization Transaction ID is not set.');
		}
		else if ($order_payment['amount_authorized'] == '') {
			_Model::$Exception_Helper->request_failed_exception('Authorization amount is not set.');
		}

		if (!empty($order_payment['payment_method_id'])) {
			// Payment method
			$pm = $this->Payment_Method->get_row(
				array(
					'payment_method_id' => $order_payment['payment_method_id']
				)
			);

			if (empty($pm)) {
				_Model::$Exception_Helper->request_failed_exception('Payment method not found.');
			}

			if ($pm['name'] == 'Credit Card') {
				$cc_payment = true;
			}
			else if ($pm['name'] == 'PayPal') {
				$paypal_payment = true;
			}
		}

		$success = false;
		$transaction_id = NULL;
		if ($cc_payment) {
			$calls = array(
				'capture_credit_card' => array(
					'authorization_transaction_id' => $order_payment['authorization_transaction_id']
					, 'amount' => $order_payment['amount_authorized']
				)
			);

			$API = new API(API_KEY_DEVELOPER, PRIVATE_KEY_DEVELOPER);
			$cc_result = $API->rest_api_request('payment', $calls);
			$cc_result_decoded = json_decode($cc_result, true);
			if ($cc_result_decoded == '') {
				_Model::$Exception_Helper->request_failed_exception($cc_result);
			}
			$api_errors = api_errors_to_array($cc_result_decoded);

			if (!empty($api_errors)) {
				return static::wrap_result(false, NULL, _Model::$Status_Code->get_status_code_request_failed(), $api_errors);
			}

			if ($cc_result_decoded['data']['capture_credit_card']['data']['transaction_id'] != '' && $cc_result_decoded['data']['capture_credit_card']['data']['transaction_id'] != '0') {
				$transaction_id = $cc_result_decoded['data']['capture_credit_card']['data']['transaction_id'];
				$success = true;
			}
			else { // failed (already captured)
				_Model::$Exception_Helper->request_failed_exception($cc_result_decoded['data']['capture_credit_card']['data']['response_reason_text']);
			}

			$data = $cc_result_decoded['data']['capture_credit_card']['data'];

		}
		else if ($paypal_payment) { // Process Paypal
			$calls = array(
				'capture_paypal_payment' => array(
					'authorization_transaction_id' => $order_payment['authorization_transaction_id']
					, 'amount' => $order_payment['amount_authorized']
				)
			);

			$API = new API(API_KEY_DEVELOPER, PRIVATE_KEY_DEVELOPER);
			$result = $API->rest_api_request('payment', $calls);
			$result_decoded = json_decode($result, true);

			$api_errors = api_errors_to_array($result_decoded);

			if (!empty($api_errors)) {
				return static::wrap_result(false, NULL, _Model::$Status_Code->get_status_code_request_failed(), $api_errors);
			}

			$transaction_id = $result_decoded['data']['capture_paypal_payment']['data']['TRANSACTIONID'];
			$data = $result_decoded['data']['capture_paypal_payment']['data'];
			$success = true;
		}

		if ($success) {
			$tax_excluded = $order_payment['amount_authorized'] - ($order['product_tax'] + $order['shipping_tax'] + $order['discount_tax'] + $order['wrapping_tax']);
			$this->Orders->save(
				array(
					'id_order' => $order['id_order']
					, 'total_paid' => $order_payment['amount_authorized']
					, 'total_paid_tax_incl' => $order_payment['amount_authorized']
					, 'total_paid_tax_excl' => $tax_excluded
					, 'payment_status' => 'Paid'
				)
			);

			if (!empty($order_invoice)) {
				$this->Order_Invoice->save(
					array(
						'id_order_invoice' => $order_invoice['id_order_invoice']
						, 'total_paid_tax_excl' => $tax_excluded
						, 'total_paid_tax_incl' => $order_payment['amount_authorized']
					)
				);
			}

			$this->Order_Payment->save(
				array(
					'id_order_payment' => $order_payment['id_order_payment']
					, 'amount' => $order_payment['amount_authorized']
					, 'transaction_id' => $transaction_id
				)
			);
		}

		return static::wrap_result(true, $data);

	}

	public function void_order($params) {
		$this->load('Orders');
		$this->load('Order_Invoice');
		$this->load('Order_Payment');
		$this->load('Payment_Method', ADMIN_API_HOST, ADMIN_API_USER, ADMIN_API_PASSWORD, ADMIN_API_DATABASE);

		// Validations
		$input_validations = array(
			'id_order' => array(
				'label' => 'Order ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
		);
		$this->Validate->add_many($input_validations, $params, true);
		$this->Validate->run();

		$cc_payment = false;
		$paypal_payment = false;

		$order = $this->Orders->get_row(
			array('id_order' => $params['id_order'])
		);

		if (empty($order)) {
			_Model::$Exception_Helper->request_failed_exception('Order could not be found.');
		}

		$order_invoice = $this->Order_Invoice->get_row(
			array('id_order' => $order['id_order'])
		);

		$order_payment = $this->Order_Payment->get_row(
			array('id_order' => $order['id_order'])
		);

		if (empty($order_payment)) {
			_Model::$Exception_Helper->request_failed_exception('Order Payment could not be found.');
		}
		else if ($order_payment['authorization_transaction_id'] == '') {
			_Model::$Exception_Helper->request_failed_exception('Authorization Transaction ID is not set.');
		}
		else if ($order_payment['amount_authorized'] == '') {
			_Model::$Exception_Helper->request_failed_exception('Authorization amount is not set.');
		}



		if (!empty($order_payment['payment_method_id'])) {
			// Payment method
			$pm = $this->Payment_Method->get_row(
				array(
					'payment_method_id' => $order_payment['payment_method_id']
				)
			);

			if (empty($pm)) {
				_Model::$Exception_Helper->request_failed_exception('Payment method not found.');
			}

			if ($pm['name'] == 'Credit Card') {
				$cc_payment = true;
			}
			else if ($pm['name'] == 'PayPal') {
				$paypal_payment = true;
			}
		}

		$success = false;
		$transaction_id = NULL;

		if ($cc_payment) {
			$calls = array(
				'void_credit_card' => array(
					'authorization_transaction_id' => $order_payment['authorization_transaction_id']
				)
			);

			$API = new API(API_KEY_DEVELOPER, PRIVATE_KEY_DEVELOPER);
			$cc_result = $API->rest_api_request('payment', $calls);
			$cc_result_decoded = json_decode($cc_result, true);
			if ($cc_result_decoded == '') {
				_Model::$Exception_Helper->request_failed_exception($cc_result);
			}
			$api_errors = api_errors_to_array($cc_result_decoded);

			if (!empty($api_errors)) {
				return static::wrap_result(false, NULL, _Model::$Status_Code->get_status_code_request_failed(), $api_errors);
			}

			if ($cc_result_decoded['data']['void_credit_card']['data']['transaction_id'] != '' && $cc_result_decoded['data']['void_credit_card']['data']['transaction_id'] != '0') {
				$success = true;
				$transaction_id = $cc_result_decoded['data']['void_credit_card']['data']['transaction_id'];
			}
			else { // failed (already voided)
				_Model::$Exception_Helper->request_failed_exception($cc_result_decoded['data']['void_credit_card']['data']['response_reason_text']);
			}

			$data = $cc_result_decoded['data']['void_credit_card']['data'];
		}
		else if ($paypal_payment) { // Process Paypal
			$calls = array(
				'void_paypal_payment' => array(
					'authorization_transaction_id' => $order_payment['authorization_transaction_id']
				)
			);

			$API = new API(API_KEY_DEVELOPER, PRIVATE_KEY_DEVELOPER);
			$result = $API->rest_api_request('payment', $calls);
			$result_decoded = json_decode($result, true);

			$api_errors = api_errors_to_array($result_decoded);

			if (!empty($api_errors)) {
				return static::wrap_result(false, NULL, _Model::$Status_Code->get_status_code_request_failed(), $api_errors);
			}

			$transaction_id = NULL; // does not return a transaction for void
			$data = $result_decoded['data']['void_paypal_payment']['data'];
			$success = true;
		}

		if ($success) {
			$this->Orders->save(
				array(
					'id_order' => $order['id_order']
					, 'payment_status' => 'Voided'
				)
			);

			$this->Order_Payment->save(
				array(
					'id_order_payment' => $order_payment['id_order_payment']
					, 'void_transaction_id' => $transaction_id
				)
			);

			// Reverse user points and
			$this->reverse_points($params['id_order']);
			$this->reverse_commissions($params['id_order'], 'Void Order');

			// Restore quantity
			$this->restore_quantity($params['id_order']);
		}

		return static::wrap_result(true, $data);

	}

	public function _delete_void_order($params) {
		// Validations
		$input_validations = array(
			'id_order' => array(
				'label' => 'Order Id'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
		);
		$this->Validate->add_many($input_validations, $params, true);
		$this->Validate->run();

		// Reverse user points and
		$this->reverse_points($params['id_order']);
		$this->reverse_commissions($params['id_order']);

		// Restore quantity
		$this->restore_quantity($params['id_order']);
	}

	public function return_order($params) {
		$this->load('Orders');
		$this->load('Order_Invoice');
		$this->load('Order_Payment');
		$this->load('Payment_Method', ADMIN_API_HOST, ADMIN_API_USER, ADMIN_API_PASSWORD, ADMIN_API_DATABASE);

		// Validations
		$input_validations = array(
			'id_order' => array(
				'label' => 'Order ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
		);
		$this->Validate->add_many($input_validations, $params, true);
		$this->Validate->run();

		$cc_payment = false;
		$paypal_payment = false;

		$order = $this->Orders->get_row(
			array('id_order' => $params['id_order'])
		);

		if (empty($order)) {
			_Model::$Exception_Helper->request_failed_exception('Order could not be found.');
		}

		$order_invoice = $this->Order_Invoice->get_row(
			array('id_order' => $order['id_order'])
		);

		$order_payment = $this->Order_Payment->get_row(
			array('id_order' => $order['id_order'])
		);

		if (empty($order_payment)) {
			_Model::$Exception_Helper->request_failed_exception('Order Payment could not be found.');
		}
		else if ($order_payment['transaction_id'] == '') {
			_Model::$Exception_Helper->request_failed_exception('Transaction ID is not set.');
		}
		else if ($order_payment['amount'] == '') {
			_Model::$Exception_Helper->request_failed_exception('Amount is not set.');
		}

		if (!empty($order_payment['payment_method_id'])) {
			// Payment method
			$pm = $this->Payment_Method->get_row(
				array(
					'payment_method_id' => $order_payment['payment_method_id']
				)
			);

			if (empty($pm)) {
				_Model::$Exception_Helper->request_failed_exception('Payment method not found.');
			}

			if ($pm['name'] == 'Credit Card') {
				$cc_payment = true;
			}
			else if ($pm['name'] == 'PayPal') {
				$paypal_payment = true;
			}
		}

		$success = false;
		$transaction_id = NULL;

		if ($cc_payment) {
			$calls = array(
				'credit_credit_card' => array(
					'transaction_id' => $order_payment['transaction_id']
					, 'amount' => $order_payment['amount']
					, 'card_number' => $order_payment['card_number']
				)
			);

			$API = new API(API_KEY_DEVELOPER, PRIVATE_KEY_DEVELOPER);
			$cc_result = $API->rest_api_request('payment', $calls);
			$cc_result_decoded = json_decode($cc_result, true);
			if ($cc_result_decoded == '') {
				_Model::$Exception_Helper->request_failed_exception($cc_result);
			}
			$api_errors = api_errors_to_array($cc_result_decoded);

			if (!empty($api_errors)) {
				return static::wrap_result(false, NULL, _Model::$Status_Code->get_status_code_request_failed(), $api_errors);
			}

			if ($cc_result_decoded['data']['credit_credit_card']['data']['transaction_id'] != '' && $cc_result_decoded['data']['credit_credit_card']['data']['transaction_id'] != '0') {
				$success = true;
				$transaction_id = $cc_result_decoded['data']['credit_credit_card']['data']['transaction_id'];
			}
			else { // failed (already returned)
				_Model::$Exception_Helper->request_failed_exception($cc_result_decoded['data']['credit_credit_card']['data']['response_reason_text']);
			}

			$data = $cc_result_decoded['data']['credit_credit_card']['data'];

		}
		else if ($paypal_payment) { // Process Paypal
			$calls = array(
				'return_paypal_payment' => array(
					'transaction_id' => $order_payment['transaction_id']
					, 'amount' => $order_payment['amount']
				)
			);

			$API = new API(API_KEY_DEVELOPER, PRIVATE_KEY_DEVELOPER);
			$result = $API->rest_api_request('payment', $calls);
			$result_decoded = json_decode($result, true);

			$api_errors = api_errors_to_array($result_decoded);

			if (!empty($api_errors)) {
				return static::wrap_result(false, NULL, _Model::$Status_Code->get_status_code_request_failed(), $api_errors);
			}

			$transaction_id = $result_decoded['data']['return_paypal_payment']['data']['REFUNDTRANSACTIONID'];
			$data = $result_decoded['data']['return_paypal_payment']['data'];
			$success = true;
		}

		if ($success) {
			$this->Orders->save(
				array(
					'id_order' => $order['id_order']
					, 'payment_status' => 'Refunded'
				)
			);

			$this->Order_Payment->save(
				array(
					'id_order_payment' => $order_payment['id_order_payment']
					, 'refund_transaction_id' => $transaction_id
				)
			);

			// Reverse user points and
			$this->reverse_points($params['id_order']);
			$this->reverse_commissions($params['id_order'], 'Return Order');

			// Restore quantity
			$this->restore_quantity($params['id_order']);
		}

		return static::wrap_result(true, $data);

	}

	public function _delete_return_order($params) {
		// Validations
		$input_validations = array(
			'id_order' => array(
				'label' => 'Order Id'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
		);
		$this->Validate->add_many($input_validations, $params, true);
		$this->Validate->run($params['id_order']);

		// Reverse user points and
		$this->reverse_points($params['id_order']);
		$this->reverse_commissions($params['id_order']);

		// Restore quantity
		$this->restore_quantity($params['id_order']);
	}

	private function reverse_points($id_order) {
		$this->load('Dw_User_Point', DW_API_HOST, DW_API_USER, DW_API_PASSWORD, DW_API_DATABASE);

		$this->Dw_User_Point->delete_order_points($id_order);
	}

	private function reverse_commissions($id_order, $note = '') {
		$this->load('Commission');

		$commissions = $this->Commission->get_rows(
			array(
				'id_order' => $id_order
			)
		);

		// Copy commission rows, but flip commission amount
		if (!empty($commissions)) {
			foreach ($commissions as $commission) {
				$commission_data = array(
					'user_id' => $commission['user_id']
					, 'id_order' => $commission['id_order']
					, 'id_product' => $commission['id_product']
					, 'commission' => -1 * $commission['commission']
					, 'note' => $note
				);
				$this->Commission->save($commission_data);
			}
		}
	}

	private function reverse_order_detail_commissions($id_order_detail, $product_quantity, $note = '') {
		$this->load('Commission');

		$commission = $this->Commission->get_row(
			array(
				'id_order_detail' => $id_order_detail
			)
		);

		if (!empty($commission)) {
			// Calculate commission from quantity
			$quantity_commission = ($commission['commission'] / $commission['product_quantity']) * $product_quantity;

			$commission_data = array(
				'user_id' => $commission['user_id']
				, 'id_order' => $commission['id_order']
				, 'id_product' => $commission['id_product']
				, 'id_order_detail' => $commission['id_order_detail']
				, 'commission' => -1 * $quantity_commission
				, 'product_quantity' => $product_quantity
				, 'note' => $note
			);
			$this->Commission->save($commission_data);
		}
	}

	// Admins will do this manually
	private function restore_quantity($id_order) {
		$this->load('Order_Detail');
		// Get order products
		$products = $this->Order_Detail->get_rows(
			array(
				'id_order' => $id_order
			)
		);

		// Loop through products and increment quantity
		if (!empty($products)) {
			foreach ($products as $i => $products) {

			}
		}
	}

	public function send_customer_confirmation_email($params = array()) {
		$this->load('Customer');
		$this->load('Orders');
		$this->load('User', ADMIN_API_HOST, ADMIN_API_USER, ADMIN_API_PASSWORD, ADMIN_API_DATABASE);
		$this->load('Order_Detail');

		$data = array();

		$validate_names = array(
			'user_id' => NULL
			, 'id_order' => NULL
		);

		$validate_params = array_merge($validate_names, $params);

		// Validations
		$input_validations = array(
			'id_order' => array(
				'label' => 'Order ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
		);

		$this->Validate->add_many($input_validations, $validate_params, true);
		$this->Validate->run();

		$now = _Model::$date_time;

		// Get Order Details
		$cart = array(
			'cart' => array(
				'totals' => array()
			)
			, 'products' => array()
		);

		// Get Order
		$details = $this->Orders->get_row(
			array(
				'id_order' => $params['id_order']
			)
		);

		if (empty($details)) {
			_Model::$Exception_Helper->request_failed_exception('Order not found.');
		}

		// Get Customer
		$customer = $this->Customer->get_row(
			array(
				'id_customer' => $details['id_customer']
			)
		);

		if (empty($customer)) {
			_Model::$Exception_Helper->request_failed_exception('Customer not found.');
		}

		// Get User
		$user = $this->User->get_row(
			array(
				'user_id' => $customer['user_id']
			)
		);

		if (empty($user)) {
			_Model::$Exception_Helper->request_failed_exception('User not found.');
		}

		$cart['cart']['totals'] = array(
			'products' => $details['total_products']
			, 'product_tax' => $details['product_tax']
			, 'shipping' => $details['total_shipping']
			, 'shipping_tax' => $details['shipping_tax']
			, 'discounts' => $details['total_discounts']
			, 'discount_tax' => $details['discount_tax']
			, 'grand_total' => $details['total']
		);

		$products = $this->Order_Detail->get_rows(
			array(
				'id_order' => $params['id_order']
			)
		);

		if (!empty($products)) {
			foreach ($products as $product) {
				$product_info = array(
					'quantity' => $product['product_quantity']
					, 'product_info' => array(
						'product_lang_name' => $product['product_name']
						, 'price' => $product['product_price']
					)
				);
				array_push($cart['products'], $product_info);
			}
		}

		// Send email to user
		$this->load('Config', ADMIN_API_HOST, ADMIN_API_USER, ADMIN_API_PASSWORD, ADMIN_API_DATABASE);
		$email_domain = 'dahliawolf.com';
		$order_email_prefix = $this->Config->get_value('Orders From Email Prefix');
		$from_email = $order_email_prefix . '@' . $email_domain;

		$subject = 'Order Confirmation';
		$custom_variables = array(
			'email' => $user['email']
			, 'site_name' => $email_domain
			, 'domain' => $email_domain
			, 'cart' => $cart
		);

		$template_variables = array(
			'first_name' => $user['first_name']
			, 'email' => $user['email']
			, 'domain' => $email_domain
			, 'site_name' => $email_domain
			, 'cdn_domain' => ''
		);

		$Email_Template_Helper = new Email_Template_Helper();
		$email_results = $Email_Template_Helper->sendEmail('order-confirmation', $custom_variables, $template_variables, $email_domain, $from_email, $user['first_name'] . ' ' . $user['last_name'], $user['email'], $subject, $from_email);

		if (!$email_results['sent']) {
			_Model::$Exception_Helper->request_failed_exception('Email not sent: ' . $email_results['error']);
		}

		return static::wrap_result(true, NULL, _Model::$Status_Code->get_status_code_no_content());
	}

	public function get_return_types($params = array()) {
		$this->load('Order_Detail_Return');

		$return_types = $this->Order_Detail_Return->get_enum_values('type');

		return static::wrap_result(true, $return_types);
	}

	public function order_return($params = array()) {
		$this->load('Orders');
		$this->load('Order_Detail_Return');
		$this->load('Product');
		$this->load('Customer');
		$this->load('User', ADMIN_API_HOST, ADMIN_API_USER, ADMIN_API_PASSWORD, ADMIN_API_DATABASE);

		// Valid return types
		$return_types = $this->Order_Detail_Return->get_enum_values('type');

		// Filter out empty quantities
		if (!empty($params['quantities_map'])) {
			$params['quantities_map'] = array_filter($params['quantities_map']);
		}

		// Validations
		$input_validations = array(
			'id_order' => array(
				'label' => 'Order ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'type' => array(
				'label' => 'Return Type'
				, 'rules' => array(
					'is_in' => $return_types
				)
			)
			, 'quantities_map' => array(
				'label' => 'Quantities Map'
				, 'rules' => array(
					'is_set' => NULL
				)
			)
			, 'product_attribute_id_map' => array(
				'label' => 'Exchange Size Map'
				, 'rules' => array(
					'is_set' => NULL
				)
			)
		);
		$this->Validate->add_many($input_validations, $params, true);
		$this->Validate->run();

		// Get order details to make sure pass in IDs are valid for the order
		$order_details = $this->Orders->get_order_details($params['id_order']);
		if (empty($order_details)) {
			_Model::$Exception_Helper->request_failed_exception('Order not found 1.');
		}

		$order = $this->Orders->get_row(
			array(
				'id_order' => $order_details[0]['id_order']
			)
		);

		if (empty($order)) {
			_Model::$Exception_Helper->request_failed_exception('Order not found 2.');
		}

		$id_order_detail_map = rows_to_groups($order_details, 'id_order_detail');
		$order_products = $this->Product->get_order_products($order['id_order'], $order['id_shop'], $order['id_lang']);
		$order_products = !empty($order_products) ? rows_to_groups($order_products, 'id_order_detail') : array();
		$products_returning = array();
		$product_subtotal = 0;
		$product_taxes = 0;
		$return_grand_total = 0;

		// Insert rows
		foreach ($params['quantities_map'] as $id_order_detail => $quantity) {
			if (!is_numeric($quantity) || $quantity <= 0) {
				_Model::$Exception_Helper->bad_request_exception('Requested return quantity must be a positive non zero number.');
			}

			// If valid product in the order
			if (!empty($id_order_detail_map[$id_order_detail])) {
				$available_quantity =
					$id_order_detail_map[$id_order_detail][0]['product_quantity']
					- max(
						(
						$id_order_detail_map[$id_order_detail][0]['return_product_quantity']
						- $id_order_detail_map[$id_order_detail][0]['rejected_return_quantity']
						)
						, 0
					);
				// and requested return quantity does not exceed available quantity (quantity - return quantity)
				if ($quantity <= $available_quantity) {

					$combinations = $this->Product->get_product_combinations($order_products[$id_order_detail][0]['product_id'], $order['id_shop'], $order['id_lang']);

					$combinations = !empty($combinations) ? rows_to_groups($combinations, 'id_product_attribute') : array();
					$product_attribute_id = NULL;
					$exchange_attribute = '';

					if ($params['type'] == 'Exchange') {
						if (
							empty($params['product_attribute_id_map'][$id_order_detail])
							|| empty($combinations[$params['product_attribute_id_map'][$id_order_detail]]))
							{
								_Model::$Exception_Helper->bad_request_exception('Invalid exchange size.');
						}

						$product_attribute_id = $params['product_attribute_id_map'][$id_order_detail];
						$exchange_attribute = $combinations[$params['product_attribute_id_map'][$id_order_detail]][0]['attribute_names'];

					}

					$order_detail_return_data = array(
						'id_order_detail' => $id_order_detail
						, 'product_quantity' => $quantity
						, 'type' => $params['type']
						, 'exchange_product_attribute_id' => $product_attribute_id
					);
					$this->Order_Detail_Return->save($order_detail_return_data);

					$return_amount = ($order_products[$id_order_detail][0]['unit_price_tax_incl'] * $quantity);

					array_push(
						$products_returning
						, array(
							'product_name' => $order_products[$id_order_detail][0]['product_name']
							, 'attributes' => $order_products[$id_order_detail][0]['attributes']
							, 'exchange_attribute' => $exchange_attribute
							, 'return_quantity' => $quantity
							, 'return_type' => $params['type']
							, 'status' => 'Pending'
							, 'product_price' => $order_products[$id_order_detail][0]['product_price']
							, 'return_amount' => $return_amount
						)
					);

					$product_subtotal += ($order_products[$id_order_detail][0]['product_price'] * $quantity);
					$product_taxes += ($order_products[$id_order_detail][0]['unit_tax'] * $quantity);
					$return_grand_total += $return_amount;

				}
				else {
					_Model::$Exception_Helper->bad_request_exception('Requested return quantity exceeds available return quantity.');
				}
			}
			else {
				_Model::$Exception_Helper->bad_request_exception('Product not found in order.');
			}
		}

		if (!empty($products_returning)) {
			// Get Customer
			$customer = $this->Customer->get_row(
				array(
					'id_customer' => $order['id_customer']
				)
			);

			if (empty($customer)) {
				_Model::$Exception_Helper->request_failed_exception('Customer not found.');
			}

			// Get User
			$user = $this->User->get_row(
				array(
					'user_id' => $customer['user_id']
				)
			);

			if (empty($user)) {
				_Model::$Exception_Helper->request_failed_exception('User not found.');
			}

			// Send email to user
			$this->load('Config', ADMIN_API_HOST, ADMIN_API_USER, ADMIN_API_PASSWORD, ADMIN_API_DATABASE);
			$email_domain = 'dahliawolf.com';
			$order_email_prefix = $this->Config->get_value('Orders From Email Prefix');
			$from_email = $order_email_prefix . '@' . $email_domain;

			$subject = 'Return Request Confirmation';
			$custom_variables = array(
				'email' => $user['email']
				, 'site_name' => $email_domain
				, 'domain' => $email_domain
				, 'products' => $products_returning
				, 'product_subtotal' => $product_subtotal
				, 'product_taxes' => $product_taxes
				, 'return_grand_total' => $return_grand_total
			);

			$template_variables = array(
				'first_name' => $user['first_name']
				, 'email' => $user['email']
				, 'domain' => $email_domain
				, 'site_name' => $email_domain
				, 'cdn_domain' => ''
			);

			$attachments = array();

			$Email_Template_Helper = new Email_Template_Helper();

			$email_name = 'return-request-confirmation';

			if ($params['type'] == 'Exchange') {
				$subject = 'Exchange Request Confirmation';
				$email_name = 'exchange-request-confirmation';
			}
			
			$email_results = $Email_Template_Helper->sendEmail($email_name, $custom_variables, $template_variables, $email_domain, $from_email, $user['first_name'] . ' ' . $user['last_name'], $user['email'], $subject, $from_email, '', '', $attachments);

			if (!$email_results['sent']) {
				_Model::$Exception_Helper->request_failed_exception('Email not sent: ' . $email_results['error']);
			}
			
			$shipping_email = 'shipping@dahliawolf.com';
			$subject .= ' for ' . $user['first_name'] . ' ' . $user['last_name'];
			$custom_variables['email'] = $shipping_email;
			$template_variables['email'] = $shipping_email;
			$email_results = $Email_Template_Helper->sendEmail($email_name, $custom_variables, $template_variables, $email_domain, $from_email, $shipping_email, $shipping_email, $subject, $from_email, '', '', $attachments);

		}

		return static::wrap_result(true, NULL);
	}

	// Make sure it's pending and belongs to user
	public function cancel_order_return($params = array()) {
		$this->load('Order_Detail_Return');

		$data = array();

		// Validations
		$input_validations = array(
			'id_order_detail_return' => array(
				'label' => 'Order Detail Return ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'user_id' => array(
				'label' => 'User ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
		);
		$this->Validate->add_many($input_validations, $params, true);
		$this->Validate->run();

		$return = $this->Order_Detail_Return->get_return($params['id_order_detail_return']);

		if (empty($return) || $return['user_id'] != $params['user_id']) {
			_Model::$Exception_Helper->request_failed_exception('Return not found.');
		}
		else if ($return['status'] != 'Pending') {
			_Model::$Exception_Helper->request_failed_exception('Return has already been processed.');
		}

		$this->Order_Detail_Return->delete_by_primary_key($params['id_order_detail_return']);

		return static::wrap_result(true, NULL, _Model::$Status_Code->get_status_code_no_content());
	}

	public function get_user_order_detail_returns($params = array()) {
		$this->load('Order_Detail_Return');

		// Validations
		$input_validations = array(
			'user_id' => array(
				'label' => 'User Id'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'id_shop' => array(
				'label' => 'Shop Id'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'id_lang' => array(
				'label' => 'Language Id'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'id_order' => array(
				'label' => 'Order Id'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
		);
		$this->Validate->add_many($input_validations, $params, true);
		$this->Validate->run();

		$returns = $this->Order_Detail_Return->get_returns_by_order($params['id_order'], $params['user_id'], $params['id_shop'], $params['id_lang']);

		return static::wrap_result(true, $returns);
	}

	public function accept_return($params = array()) {
		$this->load('Order_Detail_Return');
		$this->load('Store_Credit');
		$this->load('Orders');
		$this->load('Order_Invoice');
		$this->load('Order_Payment');
		$this->load('Payment_Method', ADMIN_API_HOST, ADMIN_API_USER, ADMIN_API_PASSWORD, ADMIN_API_DATABASE);
		$this->load('Order_Detail_Return_Payment');
		$this->load('Order_Detail_Exchange');

		$data = array();

		// Validations
		$input_validations = array(
			'id_order_detail_return' => array(
				'label' => 'Order Detail Return ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
		);
		$this->Validate->add_many($input_validations, $params, true);
		$this->Validate->run();

		$return = $this->Order_Detail_Return->get_return($params['id_order_detail_return']);

		if (empty($return)) {
			_Model::$Exception_Helper->request_failed_exception('Return not found.');
		}

		if ($return['status'] == 'Accepted') {
			_Model::$Exception_Helper->request_failed_exception('Return has already been accepted.');
		}
		else if ($return['return_amount'] <= 0) {
			_Model::$Exception_Helper->request_failed_exception('Invalid return amount: ' . $return['return_amount']);
		}

		$now = _Model::$date_time;

		// Row in store_credit
		if ($return['type'] == 'Store Credit') {
			$data['id_store_credit'] = $this->Store_Credit->save(
				array(
					'user_id' => $return['user_id']
					, 'id_order_detail_return' => $return['id_order_detail_return']
					, 'amount' => $return['return_amount']
				)
			);
		}
		// Exchange
		else if ($return['type'] == 'Exchange') {
			$data['id_order_detail_exchange'] = $this->Order_Detail_Exchange->save(
				array(
					'id_order_detail' => $return['id_order_detail']
					, 'id_order_detail_return' => $return['id_order_detail_return']
					, 'exchange_product_attribute_id' => $return['exchange_product_attribute_id']
				)
			);
		}
		// Refund: Issue refund via cc/paypal
		else if ($return['type'] == 'Refund') {

			$cc_payment = false;
			$paypal_payment = false;

			$order = $this->Orders->get_row(
				array('id_order' => $return['id_order'])
			);

			if (empty($order)) {
				_Model::$Exception_Helper->request_failed_exception('Order could not be found.');
			}

			$order_invoice = $this->Order_Invoice->get_row(
				array('id_order' => $order['id_order'])
			);

			$order_payment = $this->Order_Payment->get_row(
				array('id_order' => $order['id_order'])
			);

			if (empty($order_payment)) {
				_Model::$Exception_Helper->request_failed_exception('Order Payment could not be found.');
			}
			else if ($order_payment['transaction_id'] == '') {
				_Model::$Exception_Helper->request_failed_exception('Transaction ID is not set.');
			}
			else if ($order_payment['amount'] == '') {
				_Model::$Exception_Helper->request_failed_exception('Amount is not set.');
			}

			if (empty($order_payment['payment_method_id'])) {
				_Model::$Exception_Helper->request_failed_exception('Payment method is not set.');
			}

			// Payment method
			$pm = $this->Payment_Method->get_row(
				array(
					'payment_method_id' => $order_payment['payment_method_id']
				)
			);

			if (empty($pm)) {
				_Model::$Exception_Helper->request_failed_exception('Payment method not found.');
			}

			if ($pm['name'] == 'Credit Card') {
				$cc_payment = true;
			}
			else if ($pm['name'] == 'PayPal') {
				$paypal_payment = true;
			}

			$success = false;
			$transaction_id = NULL;

			if ($cc_payment) {
				$calls = array(
					'credit_credit_card' => array(
						'transaction_id' => $order_payment['transaction_id']
						, 'amount' => number_format($return['return_amount'], 2, '.', '')
						, 'card_number' => $order_payment['card_number']
					)
				);

				$API = new API(API_KEY_DEVELOPER, PRIVATE_KEY_DEVELOPER);
				$cc_result = $API->rest_api_request('payment', $calls);
				$cc_result_decoded = json_decode($cc_result, true);

				if ($cc_result_decoded == '') {
					_Model::$Exception_Helper->request_failed_exception($cc_result);
				}
				$api_errors = api_errors_to_array($cc_result_decoded);

				if (!empty($api_errors)) {
					return static::wrap_result(false, NULL, _Model::$Status_Code->get_status_code_request_failed(), $api_errors);
				}

				if ($cc_result_decoded['data']['credit_credit_card']['data']['transaction_id'] != '' && $cc_result_decoded['data']['credit_credit_card']['data']['transaction_id'] != '0') {
					$transaction_id = $cc_result_decoded['data']['credit_credit_card']['data']['transaction_id'];
				}
				else { // failed (already returned)
					_Model::$Exception_Helper->request_failed_exception($cc_result_decoded['data']['credit_credit_card']['data']['response_reason_text']);
				}

			}
			else if ($paypal_payment) { // Process Paypal
				$calls = array(
					'return_partial_paypal_payment' => array(
						'transaction_id' => $order_payment['transaction_id']
						, 'amount' => number_format($return['return_amount'], 2, '.', '')
					)
				);

				$API = new API(API_KEY_DEVELOPER, PRIVATE_KEY_DEVELOPER);
				$result = $API->rest_api_request('payment', $calls);
				$result_decoded = json_decode($result, true);

				$api_errors = api_errors_to_array($result_decoded);

				if (!empty($api_errors)) {
					return static::wrap_result(false, NULL, _Model::$Status_Code->get_status_code_request_failed(), $api_errors);
				}

				$transaction_id = $result_decoded['data']['return_partial_paypal_payment']['data']['REFUNDTRANSACTIONID'];

			}

			$this->Order_Detail_Return_Payment->save(
				array(
					'id_order_detail_return' => $return['id_order_detail_return']
					, 'payment_method_id' => $order_payment['payment_method_id']
					, 'amount' => $return['return_amount']
					, 'transaction_id' => $transaction_id
				)
			);
		}

		if ($return['type'] == 'Store Credit' || $return['type'] == 'Refund') {
			// Reverse user points and
			//$this->reverse_points($return['id_order']);
			$this->reverse_order_detail_commissions($return['id_order_detail'], $return['product_quantity'], $note = 'Return Order');

			// Restore quantity
			//$this->restore_quantity($return['id_order']);
		}

		$data['id_order_detail_return'] = $this->Order_Detail_Return->save(
			array(
				'id_order_detail_return' => $return['id_order_detail_return']
				, 'status' => 'Accepted'
				, 'date_accepted' => $now
			)
		);

		return static::wrap_result(true, $data);
	}

	public function reject_return($params = array()) {
		$this->load('Order_Detail_Return');

		$data = array();

		// Validations
		$input_validations = array(
			'id_order_detail_return' => array(
				'label' => 'Order Detail Return ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
		);

		$this->Validate->add_many($input_validations, $params, true);
		$this->Validate->run();

		$return = $this->Order_Detail_Return->get_row(
			array(
				'id_order_detail_return' => $params['id_order_detail_return']
			)
		);

		if (empty($return)) {
			_Model::$Exception_Helper->request_failed_exception('Return not found.');
		}

		if ($return['status'] == 'Rejected') {
			_Model::$Exception_Helper->request_failed_exception('Return has already been rejected.');
		}

		$now = _Model::$date_time;

		$data['id_order_detail_return'] = $this->Order_Detail_Return->save(
			array(
				'id_order_detail_return' => $return['id_order_detail_return']
				, 'status' => 'Rejected'
				, 'date_rejected' => $now
			)
		);

		return static::wrap_result(true, $data);

	}

	public function generate_return_shipping_label($params = array()) {
		$this->load('Orders');
		$this->load('Order_Detail_Return');
		$this->load('Customer');

		// Validations
		$input_validations = array(
			'id_order' => array(
				'label' => 'Order ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'user_id' => array(
				'label' => 'User ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
		);
		$this->Validate->add_many($input_validations, $params, true);
		$this->Validate->run();

		$customer = $this->Customer->get_row(
			array(
				'user_id' => $params['user_id']
			)
		);

		if (empty($customer)) {
			_Model::$Exception_Helper->request_failed_exception('Customer not found.');
		}

		$shipping_method = $this->Orders->get_user_order_shipping_method($params['id_order'], $params['user_id']);
		
		if (empty($shipping_method)) {
			_Model::$Exception_Helper->request_failed_exception('Shipping method not found.');
		}
		
		if (!isset($params['generate-new-label']) || $params['generate-new-label'] === false) { // just need label content type
			$order_shipment = $shipping_method;
			
			$content_type = '';
			if ($order_shipment['carrier'] == 'UPS') {
				$content_type = 'image/gif';
			}
			else if ($order_shipment['carrier'] == 'FedEx' || $order_shipment['carrier'] == 'USPS') {
				$content_type = 'application/pdf';
			}

			$data = array(
				'content-type' => $content_type
			);

			return static::wrap_result(true, $data);
		}

		$order_shipment = $this->Orders->get_return_shipment_info($params['id_order'], $params['user_id']);
		if (empty($order_shipment)) {
			_Model::$Exception_Helper->request_failed_exception('No pending returns to ship.');
		}

		$items = $this->Order_Detail_Return->get_pending_returns_by_order_for_shipment_label($order_shipment['id_order'], $order_shipment['user_id'], $order_shipment['id_shop'], $order_shipment['id_lang']);

		if (empty($items)) {
			_Model::$Exception_Helper->request_failed_exception('No pending returns to ship.');
		}

		if (
			$order_shipment['carrier'] != 'USPS'
			&& $order_shipment['carrier'] != 'UPS'
			&& $order_shipment['carrier'] != 'FedEx'
			) {
			_Model::$Exception_Helper->request_failed_exception('Can not print shipping label for carrier: ' . $order_shipment['carrier']);
		}
		else if ($order_shipment['service_code'] == '') {
			_Model::$Exception_Helper->request_failed_exception('Can not print shipping label for method: ' . $order_shipment['service_name']);
		}

		$carrier_params = array(
			'user_id' => $order_shipment['user_id']
			, 'id_lang' => $order_shipment['id_lang']
			, 'id_shop' => $order_shipment['id_shop']
			//, 'id_order_shipment' => $order_shipment['id_order_shipment']
			, 'shipping_address_id' => $order_shipment['id_address_delivery']
			, 'total_product_weight' => number_format($order_shipment['total_weight'], 2, '.', '')
			, 'service_code' => $order_shipment['service_code']
			, 'service_label_code' => $order_shipment['service_label_code']
			, 'service_name' => $order_shipment['service_name']
			, 'update-tracking-info' => false
			, 'is_intl' => ($order_shipment['is_intl'] == '1') ? true : false
			, 'items' => $items
			, 'return-label' => true
		);

		$carrier_controller = new Carrier_Controller();

		if ($order_shipment['carrier'] == 'UPS') {
			$result = $carrier_controller->ups_shipping_label($carrier_params);
		}
		else if ($order_shipment['carrier'] == 'USPS') {
			$result = $carrier_controller->usps_shipping_label($carrier_params);
		}
		else if ($order_shipment['carrier'] == 'FedEx') {
			$result = $carrier_controller->fedex_shipping_label($carrier_params);
		}

		$result_data = $result['data'];

		$content_type = '';
		if ($order_shipment['carrier'] == 'UPS') {
			$content_type = 'image/gif';
		}
		else if ($order_shipment['carrier'] == 'FedEx' || $order_shipment['carrier'] == 'USPS') {
			$content_type = 'application/pdf';
		}

		$data = array_merge(
			$result_data
			, array(
				'base64_encoded' => ($order_shipment['carrier'] == 'UPS' || $order_shipment['carrier'] == 'USPS') ? true : false
				, 'content-type' => $content_type
			)
		);

		return static::wrap_result(true, $data);

	}
}

?>
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

				$unit_tax = !empty($order_product['tax_info']) && is_numeric($order_product['tax_info']) ? $order_product['tax_info']['unit_amount'] : 0;
				$unit_price_tax_excl = $product_price;
				$unit_price_tax_incl = $unit_price_tax_excl + $unit_tax;
				$total_product_tax = !empty($order_product['tax_info']) && is_numeric($order_product['tax_info']) ? $order_product['tax_info']['total_amount'] : 0;
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
					, 'product_price' => ($order_product['product_info']['on_sale'] == '1') ? $order_product['product_info']['sale_price'] : $order_product['product_info']['price']
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
					, 'total_shipping_price_tax_incl' => 0
					, 'total_shipping_price_tax_excl' => 0
					, 'original_product_price' => ($order_product['product_info']['on_sale'] == '1') ? $order_product['product_info']['sale_price'] : $order_product['product_info']['price']
				);

				$id_order_detail = $this->Order_Detail->save($order_detail_data);

				$order_detail_tax_data = array(
					'id_order_detail' => $id_order_detail
					, 'id_tax' => !empty($order_product['tax_info']) && is_numeric($order_product['tax_info']['id_tax']) ? $order_product['tax_info']['id_tax'] : ''
					, 'unit_amount' => $unit_tax
					, 'total_amount' => $total_product_tax
				);

				$this->Order_Detail_Tax->save($order_detail_tax_data);
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

		// Credit commissions
		// For each product, credit product.user_id with product.commission * quantity
		$this->load('Commission');
		foreach ($cart['products'] as $i => $product) {
			if (!empty($product['product_info']['user_id'])) {
				$commission_data = array(
					'user_id' => $product['product_info']['user_id']
					, 'id_order' => $data['id_order']
					, 'id_product' => $product['product_info']['id_product']
					, 'commission' => $product['product_info']['commission'] * $product['quantity']
				);
				$this->Commission->save($commission_data);
			}
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
			$this->reverse_commissions($params['id_order']);

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
				$transaction_id = NULL;
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
			$this->reverse_commissions($params['id_order']);

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

	private function reverse_commissions($id_order) {
		$this->load('Commission');

		$this->Commission->delete_order_commissions($id_order);
	}

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
}

?>
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
		//$this->load('Payment_Method', DB_API_HOST, DB_API_USER, DB_API_PASSWORD, DB_API_DATABASE);
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
		else if ($params['payment_info']['amount'] != $cart['cart']['totals']['grand_total']) {
			_Model::$Exception_Helper->request_failed_exception('Cart total does not match payment total.');
		}
		else if (empty($cart['cart']['carrier'])) {
			_Model::$Exception_Helper->request_failed_exception('Invalid Carrier.');
		}
		
		// Process Payment
		$payment_success = false;
		$transaction_id = NULL;
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
				)
			);
			
			$API = new API(API_KEY_DEVELOPER, PRIVATE_KEY_DEVELOPER);
			$cc_result = $API->rest_api_request('payment', $calls);
			$cc_result_decoded = json_decode($cc_result, true);
			
			if ($cc_result_decoded == '') {
				_Model::$Exception_Helper->request_failed_exception($cc_result);
			}
			else if (!$cc_result_decoded['success']) {
				_Model::$Exception_Helper->request_failed_exception('Process Payment api request failed.');
			}
			else if (!$cc_result_decoded['data']['process_credit_card']['success'] || empty($cc_result_decoded['data']['process_credit_card']['data'])) {
				_Model::$Exception_Helper->request_failed_exception('Process Payment api request failed.');
			}
			
			if ($cc_result_decoded['data']['process_credit_card']['data']['approved']) { // Payment success
				$payment_success = true;
				$transaction_id = $cc_result_decoded['data']['process_credit_card']['data']['transaction_id'];
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
			$transaction_id = $result_decoded['data']['complete_paypal_purchase']['data']['payment_details']['PAYMENTINFO_0_TRANSACTIONID'];
		}
		
		if (!$payment_success) {
			_Model::$Exception_Helper->request_failed_exception('Your payment could not be processed.');
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
			, 'id_lang' => $params['id_lang']
			, 'id_cart' => $cart['cart']['id_cart']
			, 'id_currency' => 1
			, 'id_address_delivery' => $params['shipping_address_id']
			, 'id_address_invoice' => $params['billing_address_id']
			//, 'current_state'
			, 'total_discounts' => $cart['cart']['totals']['discounts']
			, 'total_discounts_tax_incl' => $total_discounts_tax_incl
			, 'total_discounts_tax_excl' => $total_discount_tax_excl
			, 'total_paid' => $cart['cart']['totals']['grand_total']
			, 'total_paid_tax_incl' => $total_paid_tax_incl
			, 'total_paid_tax_excl' => $total_paid_tax_excl
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
			, 'total_paid_tax_excl' => $total_paid_tax_excl
			, 'total_paid_tax_incl' => $total_paid_tax_incl
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
			, 'amount' => $cart['cart']['totals']['grand_total']
			, 'transaction_id' => $transaction_id
			, 'date_add' => $now
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
		$this->load('Config');
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
				
				$email_results = $Email_Template_Helper->sendEmail('product-order-notice', $custom_variables, $template_variables, $email_domain, $from_email, $customer['firstname'] . ' ' . $customer['lastname'], $customer['email'], $subject, $from_email);
				var_dump($email_results);
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
		$this->load('Product');
		
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
		
		$data = $order;
		
		return static::wrap_result(true, $data);
	}
	
}

?>
<?

require DR . '/includes/php/functions-api.php';

class Cart_Controller extends _Controller {

	public function get_cart_from_cookie($params = array()) {
		$this->load('Product');
		$this->load('Customer');
		$this->load('Cart_Rule');

		$data = array();
		$error = NULL;

		$validate_names = array(
			'cart_cookie' => NULL
			, 'id_shop' => NULL
			, 'id_lang' => NULL
		);

		$validate_params = array_merge($validate_names, $params);

		// Validations
		$input_validations = array(
			'cart_cookie' => array(
				'label' => 'Cart Cookie'
				, 'rules' => array(

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
		$this->Validate->add_many($input_validations, $validate_params, true);
		$this->Validate->run();

		if (!empty($params['cart_cookie'])) {
			$params['cart_cookie'] = json_decode($params['cart_cookie'], true);
		}

		if (empty($params['cart_cookie'])
			|| empty($params['cart_cookie']['cart'])
			|| !is_array($params['cart_cookie'])
			|| !is_array($params['cart_cookie']['cart'])
		) {
			return static::wrap_result(true, $data);
		}

		$data = $params['cart_cookie'];
		$data['cart']['carrier'] = array();
		$data['cart']['totals'] = array(
			'products' => 0
			, 'products_retail' => 0
			, 'product_weight' => 0
			, 'product_tax' => 0
			, 'products_retail_tax' => 0
			, 'discounts' => 0
			, 'discount_tax' => 0
			, 'shipping' => 0
			, 'shipping_tax' => 0
			, 'wrapping' => 0
			, 'wrapping_tax' => 0
		);

		$data['carrier_options'] = array();

		if (!empty($data['products']) && is_array($data['products'])) {
			foreach ($data['products'] as $i => $cart_product) {
				// Get attributes
				$data['products'][$i]['attributes'] = NULL;
				if (!empty($cart_product['id_product_attribute'])) {
					$product_combination = $this->Product->get_product_combinations($cart_product['id_product'], $params['id_shop'], $params['id_lang'], $cart_product['id_product_attribute']);
					if (!empty($product_combination)) {
						$data['products'][$i]['attributes'] = $product_combination['attribute_names'];
					}
				}

				// Get product info
				$data['products'][$i]['product_info'] = $this->Product->get_product($cart_product['id_product'], $params['id_shop'], $params['id_lang']);

				// Update cart totals
				if (!empty($data['products'][$i]['product_info'])) {
					$product_price = ($data['products'][$i]['product_info']['on_sale'] == '1') ? $data['products'][$i]['product_info']['sale_price'] : $data['products'][$i]['product_info']['price'];
					$data['cart']['totals']['products'] += ($product_price * $cart_product['quantity']);

					if ($data['products'][$i]['product_info']['on_sale'] != '1') {
						$data['cart']['totals']['products_retail'] += ($product_price * $cart_product['quantity']);
					}

					$data['cart']['totals']['product_weight'] += ($data['products'][$i]['product_info']['weight'] * $cart_product['quantity']);
				}
			}
		}

		// Calculate Discounts
		$remaining_subtotal = $data['cart']['totals']['products_retail']; //$data['cart']['totals']['products'];
		$remaining_product_tax = $data['cart']['totals']['products_retail_tax'];//$data['cart']['totals']['product_tax'];

		if (!empty($data['discounts']) && is_array($data['discounts'])) {
			foreach ($data['discounts'] as $i => $discount) {
				$discount = $this->Cart_Rule->get_cart_rule_by_id($discount['id_cart_rule'], $params['id_lang']);

				if (empty($discount)) {
					unset($data['discounts'][$i]);
					continue;
				}
				$data['discounts'][$i] = $discount;

				$data['discounts'][$i]['discount_amount'] = 0;

				if ($discount['is_amount_discount'] == '1') { // amount
					if ($discount['reduction_tax'] == '1' && ($remaining_product_tax + $remaining_subtotal) > 0) { // tax included
						$temp_a = min($discount['reduction_amount'], $remaining_product_tax);
						$remaining_product_tax -= $temp_a;
						$data['discounts'][$i]['discount_amount'] += $temp_a;
						if (($discount['reduction_amount'] - $temp_a) > 0) {
							$temp_b = min(($discount['reduction_amount'] - $temp_a), $remaining_subtotal);
							$remaining_subtotal -= $temp_b;
							$data['discounts'][$i]['discount_amount'] += $temp_b;
						}
					}
					else if ($remaining_subtotal > 0) {
						$data['discounts'][$i]['discount_amount'] = min($discount['reduction_amount'], $remaining_subtotal);
						$remaining_subtotal -= $data['discounts'][$i]['discount_amount'];
					}
				}
				else if ($discount['reduction_percent'] > 0) { // percent
					if ($discount['reduction_tax'] == '1' && ($remaining_product_tax + $remaining_subtotal) > 0) { // tax included
						$total_discount_amount = (($discount['reduction_percent'] / 100) * ($data['cart']['totals']['product_tax'] + $data['cart']['totals']['products']));
						$temp_a = min($total_discount_amount, $remaining_product_tax);
						$remaining_product_tax -= $temp_a;
						$data['discounts'][$i]['discount_amount'] += $temp_a;
						if (($total_discount_amount - $temp_a) > 0) {
							$temp_b = min(($total_discount_amount - $temp_a), $remaining_subtotal);
							$remaining_subtotal -= $temp_b;
							$data['discounts'][$i]['discount_amount'] += $temp_b;
						}
					}
					else if ($remaining_subtotal > 0) {
						$total_discount_amount = (($discount['reduction_percent'] / 100) * ($data['cart']['totals']['products_retail']));
						$data['discounts'][$i]['discount_amount'] = min($total_discount_amount, $remaining_subtotal);
						$remaining_subtotal -= $data['discounts'][$i]['discount_amount'];
					}
				}

				$data['cart']['totals']['discounts'] += $data['discounts'][$i]['discount_amount'];
			}
		}

		// Calculate grand total
		$grand_total = 	$data['cart']['totals']['products']
						+ $data['cart']['totals']['product_tax']
						+ (-1 * $data['cart']['totals']['discounts']) // discount after tax
						+ $data['cart']['totals']['discount_tax']
						+ $data['cart']['totals']['shipping']
						+ $data['cart']['totals']['shipping_tax']
						+ $data['cart']['totals']['wrapping']
						+ $data['cart']['totals']['wrapping_tax'];

		$data['cart']['totals']['grand_total'] = $grand_total;
		if (empty($data['products'])) { // if no products, set total to 0
			$data['cart']['totals']['grand_total'] = 0;
		}

		/*
		$_COOKIE['btg_mall']['cart'] = array(
			'cart' => array(
				'id_shop'
				, 'id_lang'
				, 'date_add'
				, 'date_upd'
			)
			, 'products' => array(
				array(
					'id_product'
					//, 'id_shop'
					, 'id_product_attribute'
					, 'quantity'
					, 'date_add'
				)
			)
		);
		*/

		// Points user will earn
		$data['points'] = array();
		$data['points']['will_earn'] = $this->get_points_will_earn($data['cart']['totals']['products_retail'] - $data['cart']['totals']['discounts']);

		return static::wrap_result(true, $data);
	}

	public function get_cart_from_db($params = array()) {
		$this->load('Cart');
		$this->load('Cart_Product');
		$this->load('Cart_Cart_Rule');
		$this->load('Product');
		$this->load('State');
		$this->load('Country');
		$this->load('Tax_Rule');
		$this->load('Cart_Rule');
		$this->load('Dw_User');
		$this->load('Commission');
		$this->load('Cart_Commission');
		$this->load('Store_Credit');
		$this->load('Cart_Store_Credit');

		$data = array();
		$error = NULL;
		$has_shipping_address = false;

		$validate_names = array(
			'user_id' => NULL
			, 'id_shop' => NULL
			, 'id_lang' => NULL
			, 'id_cart' => NULL
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
					'is_int' => NULL
				)
			)
			, 'id_delivery' => array(
				'label' => 'Delivery Address Id'
				, 'rules' => array(
					'is_int' => NULL
				)
			)
		);
		$this->Validate->add_many($input_validations, $validate_params, true);
		$this->Validate->run();

		$data['cart'] = $this->Cart->get_user_cart($params['user_id'], $params['id_shop'], $params['id_cart']);

		if (!empty($data['cart'])) {

			// Add membership discount
			$this->add_membership_discount($params['user_id'], $data['cart']['id_cart'], $params['id_lang']);

			///////////////////////////

			$data['cart']['carrier'] = array();
			$data['cart']['totals'] = array(
				'products' => 0
				, 'products_retail' => 0
				, 'product_weight' => 0
				, 'product_tax' => 0
				, 'products_retail_tax' => 0
				, 'discounts' => 0
				, 'discount_tax' => 0
				, 'shipping' => 0
				, 'shipping_tax' => 0
				, 'wrapping' => 0
				, 'wrapping_tax' => 0
			);

			// Get address from hq
			if (!empty($params['shipping_address_id'])) {
				$Address_Controller = new Address_Controller();
				$address_info = $Address_Controller->get_hq_user_address_info(
					array(
						'user_id' => $params['user_id']
						, 'address_id' => $params['shipping_address_id']
					)
				);

				if (empty($address_info)) {
					_Model::$Exception_Helper->request_failed_exception('Shipping address not found.');
				}

				$id_country = $address_info['country']['id_country'];
				$id_zone = $address_info['country']['id_zone'];
				$id_state = !empty($address_info['state']) ? $address_info['state']['id_state'] : NULL;
				$zip = $address_info['address']['zip'];
				$has_shipping_address = true;
			}

			// Products
			$data['products'] = $this->Cart_Product->get_rows(
				array(
					'id_cart' => $data['cart']['id_cart']
				)
			);

			if (!empty($data['products'])) {
				foreach ($data['products'] as $i => $cart_product) {
					// Get product attributes
					$data['products'][$i]['attributes'] = NULL;
					if (!empty($cart_product['id_product_attribute'])) {
						$product_combination = $this->Product->get_product_combinations($cart_product['id_product'], $data['cart']['id_shop'], $params['id_lang'], $cart_product['id_product_attribute']);
						if (!empty($product_combination)) {
							$data['products'][$i]['attributes'] = $product_combination['attribute_names'];
						}
					}

					// Get product
					$data['products'][$i]['product_info'] = $this->Product->get_product($cart_product['id_product'], $data['cart']['id_shop'], $params['id_lang']);
					$data['products'][$i]['tax_info'] = array();

					$product_price = ($data['products'][$i]['product_info']['on_sale'] == '1') ? $data['products'][$i]['product_info']['sale_price'] : $data['products'][$i]['product_info']['price'];

					if ($has_shipping_address) {
						$data['products'][$i]['tax_info'] = $this->Tax_Rule->get_tax_info($data['products'][$i]['product_info']['id_tax_rules_group'], $id_country, $id_state, $zip, $product_price, $data['products'][$i]['quantity']);
						$data['cart']['totals']['product_tax'] += $data['products'][$i]['tax_info']['total_amount'];

						if ($data['products'][$i]['product_info']['on_sale'] != '1') {
							$data['cart']['totals']['products_retail_tax'] += $data['products'][$i]['tax_info']['total_amount'];
						}
					}

					// Update cart totals
					$data['cart']['totals']['products'] += ($product_price * $cart_product['quantity']);

					if ($data['products'][$i]['product_info']['on_sale'] != '1') {
						$data['cart']['totals']['products_retail'] += ($product_price * $cart_product['quantity']);
					}

					$data['cart']['totals']['product_weight'] += ($data['products'][$i]['product_info']['weight'] * $cart_product['quantity']);
				}
			}

			// Calculate Discounts
			$data['discounts'] = $this->Cart_Cart_Rule->get_cart_discounts($data['cart']['id_cart'], $params['id_lang']);
			$remaining_subtotal = $data['cart']['totals']['products_retail'];//$data['cart']['totals']['products'];
			$remaining_product_tax = $data['cart']['totals']['products_retail_tax']; //$data['cart']['totals']['product_tax'];

			if (!empty($data['discounts'])) {
				foreach ($data['discounts'] as $i => $discount) {
					$data['discounts'][$i]['discount_amount'] = 0;

					if ($discount['is_amount_discount'] == '1') { // amount
						if ($discount['reduction_tax'] == '1' && ($remaining_product_tax + $remaining_subtotal) > 0) { // tax included
							$temp_a = min($discount['reduction_amount'], $remaining_product_tax);
							$remaining_product_tax -= $temp_a;
							$data['discounts'][$i]['discount_amount'] += $temp_a;
							if (($discount['reduction_amount'] - $temp_a) > 0) {
								$temp_b = min(($discount['reduction_amount'] - $temp_a), $remaining_subtotal);
								$remaining_subtotal -= $temp_b;
								$data['discounts'][$i]['discount_amount'] += $temp_b;
							}
						}
						else if ($remaining_subtotal > 0) {
							$data['discounts'][$i]['discount_amount'] = min($discount['reduction_amount'], $remaining_subtotal);
							$remaining_subtotal -= $data['discounts'][$i]['discount_amount'];
						}
					}
					else if ($discount['reduction_percent'] > 0) { // percent
						if ($discount['reduction_tax'] == '1' && ($remaining_product_tax + $remaining_subtotal) > 0) { // tax included
							$total_discount_amount = (($discount['reduction_percent'] / 100) * ($data['cart']['totals']['product_tax'] + $data['cart']['totals']['products']));
							$temp_a = min($total_discount_amount, $remaining_product_tax);
							$remaining_product_tax -= $temp_a;
							$data['discounts'][$i]['discount_amount'] += $temp_a;
							if (($total_discount_amount - $temp_a) > 0) {
								$temp_b = min(($total_discount_amount - $temp_a), $remaining_subtotal);
								$remaining_subtotal -= $temp_b;
								$data['discounts'][$i]['discount_amount'] += $temp_b;
							}
						}
						else if ($remaining_subtotal > 0) {
							$total_discount_amount = (($discount['reduction_percent'] / 100) * ($data['cart']['totals']['products_retail']));
							$data['discounts'][$i]['discount_amount'] = min($total_discount_amount, $remaining_subtotal);
							$remaining_subtotal -= $data['discounts'][$i]['discount_amount'];
						}
					}

					$data['cart']['totals']['discounts'] += $data['discounts'][$i]['discount_amount'];
				}
			}

			// Get carrier options
			$data['carrier_options'] = array();
			if ($has_shipping_address) {
				$Carrier_Controller = new Carrier_Controller();
				$carrier_options_result = $Carrier_Controller->get_carrier_options(
					array(
						'user_id' => $params['user_id']
						, 'id_shop' => $data['cart']['id_shop']
						, 'id_lang' => $params['id_lang']
						, 'id_country' => $id_country
						, 'id_zone' => $id_zone
						, 'id_state' => $id_state
						, 'zip' => $zip
						, 'total_products' => $data['cart']['totals']['products']
						, 'total_product_weight' => $data['cart']['totals']['product_weight']
						, 'shipping_address_id' => $params['shipping_address_id']
					)
				);
				$data['carrier_options'] = $carrier_options_result['data'];

				if (!empty($data['carrier_options'])) {
					$carrier_option_rows = rows_to_groups($data['carrier_options'], 'id_delivery');

					$data['cart']['carrier'] = $data['carrier_options'][0];
					if (!empty($params['id_delivery'])) {
						if (array_key_exists($params['id_delivery'], $carrier_option_rows)) {
							$data['cart']['carrier'] = $carrier_option_rows[$params['id_delivery']][0];
						}
						else if (array_key_exists('error_on_invalid_carrier', $params)
							&& $params['error_on_invalid_carrier'] == true
						) {
							_Model::$Exception_Helper->request_failed_exception('Invalid delivery carrier.');
						}
					}
				}
			}


			// Get available commissions
			$data['available_commissions'] = $this->Commission->get_user_total($params['user_id']);
			// Get cart commission redemptions
			$data['cart_commission'] = $this->Cart_Commission->get_row(
				array(
					'id_cart' => $data['cart']['id_cart']
				)
			);
			if (!empty($data['cart_commission'])) {
				$data['cart']['totals']['discounts'] += $data['cart_commission']['amount'];
			}

			// Get available store credits
			$data['available_store_credits'] = $this->Store_Credit->get_user_total($params['user_id']);
			// Get store credit redemptions
			$data['cart_store_credit'] = $this->Cart_Store_Credit->get_row(
				array(
					'id_cart' => $data['cart']['id_cart']
				)
			);
			if (!empty($data['cart_store_credit'])) {
				$data['cart']['totals']['discounts'] += $data['cart_store_credit']['amount'];
			}

			// Calculate shipping
			if (!empty($data['cart']['carrier'])) {
				$data['cart']['totals']['shipping'] = $data['cart']['carrier']['price'];
				$data['cart']['totals']['shipping_tax'] = !empty($data['cart']['carrier']['tax_info']) ? $data['cart']['carrier']['tax_info']['total_amount'] : 0;
			}

			// Calculate grand total
			$grand_total = 	$data['cart']['totals']['products']
							+ $data['cart']['totals']['product_tax']
							+ (-1 * $data['cart']['totals']['discounts']) // discount after tax
							+ $data['cart']['totals']['discount_tax']
							+ $data['cart']['totals']['shipping']
							+ $data['cart']['totals']['shipping_tax']
							+ $data['cart']['totals']['wrapping']
							+ $data['cart']['totals']['wrapping_tax'];

			$data['cart']['totals']['grand_total'] = $grand_total;
			if (empty($data['products'])) { // if no products, set total to 0
				$data['cart']['totals']['grand_total'] = 0;
			}

			// Points user will earn
			$data['points'] = array();
			$data['points']['will_earn'] = $this->get_points_will_earn($data['cart']['totals']['products_retail'] - $data['cart']['totals']['discounts']);
			// User points
			$data['points']['user'] = $this->get_user_points($params['user_id']);
			// Point spend levels
			$data['points']['levels'] = $this->get_eligible_levels($data['points']['user']);
		}

		return static::wrap_result(true, $data);

	}

	public function add_cookie_cart_discount($params = array()) {
		$this->load('Cart_Rule');
		$data = array();

		$validate_names = array(
			'cart_cookie' => NULL
			, 'id_shop' => NULL
			, 'id_lang' => NULL
			, 'code' => NULL
		);

		$validate_params = array_merge($validate_names, $params);

		// Validations
		$input_validations = array(
			'id_shop' => array(
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
			, 'code' => array(
				'label' => 'Code'
				, 'rules' => array(
					'is_set' => NULL
				)
			)

		);
		$this->Validate->add_many($input_validations, $validate_params, true);
		$this->Validate->run();

		// Get discount
		$new_discount = $this->Cart_Rule->get_cart_rule($params['code'], $params['id_lang']);

		if (empty($new_discount)) { // Not found
			_Model::$Exception_Helper->request_failed_exception('Invalid discount code.');
		}

		$membership_discounts = $this->Cart_Rule->get_membership_discounts();
		$membership_discounts = rows_to_groups($membership_discounts, 'id_cart_rule');
		$membership_discount_ids = array_keys($membership_discounts);

		if (in_array($new_discount['id_cart_rule'], $membership_discount_ids)) {
			_Model::$Exception_Helper->request_failed_exception('Invalid discount code.');
		}

		// Validate discount
		$result = $this->Cart_Rule->validate_discount();

		if (!empty($params['cart_cookie'])) {
			$params['cart_cookie'] = json_decode($params['cart_cookie'], true);
		}

		$now = _Model::$date_time;

		if (empty($params['cart_cookie'])
			|| empty($params['cart_cookie']['cart'])
			|| !is_array($params['cart_cookie'])
			|| !is_array($params['cart_cookie']['cart'])
		) { // Create new cookie
			$data['cart'] = array(
				'id_shop' => $params['id_shop']
				, 'id_lang' => $params['id_lang']
				, 'date_add' => $now
				, 'date_upd' => NULL
			);
		}
		else {
			$data = $params['cart_cookie'];
			$data['cart']['date_upd'] = $now;
		}

		$found_discount = false;

		if (empty($data['discounts']) || !is_array($data['discounts'])) {
			$data['discounts'] = array();
		}
		else {
			foreach ($data['discounts'] as $i => $discount) {
				if ($discount['id_cart_rule'] == $new_discount['id_cart_rule']) {
					$found_discount = true;
					_Model::$Exception_Helper->request_failed_exception('Discount is already being applied.');
				}
			}
		}

		if (!$found_discount) {
			$data['discounts'][] = array(
				'id_cart_rule' => $new_discount['id_cart_rule']
			);
		}

		return static::wrap_result(true, $data);
	}

	public function remove_cookie_cart_discount($params = array()) {
		$this->load('Cart_Rule');
		$data = array();

		$validate_names = array(
			'cart_cookie' => NULL
			, 'id_shop' => NULL
			, 'id_lang' => NULL
			, 'code' => NULL
		);

		$validate_params = array_merge($validate_names, $params);

		// Validations
		$input_validations = array(
			'id_shop' => array(
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
			, 'id_cart_rule' => array(
				'label' => 'Cart Rule Id'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)

		);
		$this->Validate->add_many($input_validations, $validate_params, true);
		$this->Validate->run();

		if (!empty($params['cart_cookie'])) {
			$params['cart_cookie'] = json_decode($params['cart_cookie'], true);
		}

		$now = _Model::$date_time;

		if (empty($params['cart_cookie'])
			|| empty($params['cart_cookie']['cart'])
			|| !is_array($params['cart_cookie'])
			|| !is_array($params['cart_cookie']['cart'])
		) { // Create new cookie
			$data['cart'] = array(
				'id_shop' => $params['id_shop']
				, 'id_lang' => $params['id_lang']
				, 'date_add' => $now
				, 'date_upd' => NULL
			);
		}
		else {
			$data = $params['cart_cookie'];
			$data['cart']['date_upd'] = $now;
		}

		if (empty($data['discounts']) || !is_array($data['discounts'])) {
			$data['discounts'] = array();
		}
		else {
			foreach ($data['discounts'] as $i => $discount) {
				if ($discount['id_cart_rule'] == $params['id_cart_rule']) {
					unset($data['discounts'][$i]);
				}
			}
		}

		return static::wrap_result(true, $data);
	}

	public function add_cookie_cart_item($params = array()) {
		$this->load('Product');

		$data = array();
		$error = NULL;

		$validate_names = array(
			'cart_cookie' => NULL
			, 'id_shop' => NULL
			, 'id_lang' => NULL
			, 'id_product' => NULL
			, 'quantity' => NULL
			, 'id_product_attribute' => NULL
		);

		$validate_params = array_merge($validate_names, $params);

		// Validations
		$input_validations = array(
			'id_shop' => array(
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
			, 'id_product' => array(
				'label' => 'Product Id'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'quantity' => array(
				'label' => 'Quantity'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
					, 'is_positive' => NULL
				)
			)
			, 'id_product_attribute' => array(
				'label' => 'Product Attribute Id'
				, 'rules' => array(
					'is_int' => NULL
				)
			)
		);
		$this->Validate->add_many($input_validations, $validate_params, true);
		$this->Validate->run();

		if (!empty($params['cart_cookie'])) {
			$params['cart_cookie'] = json_decode($params['cart_cookie'], true);
		}

		$now = _Model::$date_time;

		// Get product info
		$product_info = $this->Product->get_product($params['id_product'], $params['id_shop'], $params['id_lang']);
		if (empty($product_info)) {
			_Model::$Exception_Helper->request_failed_exception('Product not found.');
		}

		// Validate product attribute
		$product_combinations = $this->Product->get_product_combinations($params['id_product'], $params['id_shop'], $params['id_lang']);
		$product_combinations = !empty($product_combinations) ? rows_to_array($product_combinations, 'id_product_attribute', 'attribute_names') : array();

		if (!empty($product_combinations) && empty($params['id_product_attribute'])) {
			_Model::$Exception_Helper->request_failed_exception('Please select an option.');
		}
		else if (!empty($params['id_product_attribute']) && !array_key_exists($params['id_product_attribute'], $product_combinations)) {
			_Model::$Exception_Helper->request_failed_exception('Invalid product attribute.');
		}

		if (empty($params['cart_cookie'])
			|| empty($params['cart_cookie']['cart'])
			|| !is_array($params['cart_cookie'])
			|| !is_array($params['cart_cookie']['cart'])
		) { // Create new cookie
			$data['cart'] = array(
				'id_shop' => $params['id_shop']
				, 'id_lang' => $params['id_lang']
				, 'date_add' => $now
				, 'date_upd' => NULL
			);
		}
		else {
			$data = $params['cart_cookie'];
			$data['cart']['date_upd'] = $now;
		}

		$found_product = false;

		if (empty($data['products']) || !is_array($data['products'])) {
			$data['products'] = array();
		}
		else {
			foreach ($data['products'] as $i => $cart_product) {
				if ($cart_product['id_product'] == $params['id_product']
					&& $params['id_product_attribute'] == $cart_product['id_product_attribute']
				) {
					$data['products'][$i]['quantity'] += $params['quantity'];
					$found_product = true;
				}
			}
		}

		if (!$found_product) {
			$data['products'][] = array(
				'id_product' => $params['id_product']
				//, 'id_shop' => $params['id_shop']
				, 'id_product_attribute' => !empty($params['id_product_attribute']) ? $params['id_product_attribute'] : NULL
				, 'quantity' => $params['quantity']
				, 'date_add' => $now
				//, 'product_info' => $product_info
			);
		}



		/*
		$_COOKIE['btg_mall']['cart'] = array(
			'cart' => array(
				'id_shop'
				, 'id_lang'
				, 'date_add'
				, 'date_upd'
			)
			, 'products' => array(
				array(
					'id_product'
					, 'id_shop'
					, 'id_product_attribute'
					, 'quantity'
					, 'date_add'
		 			, 'product_info'
				)
			)
		);
		*/
		return static::wrap_result(true, $data);

	}

	public function update_db_cart($params = array()) {
		$this->load('Cart_Product');

		$data = array();

		$validate_names = array(
			'user_id' => NULL
			, 'id_shop' => NULL
			, 'id_lang' => NULL
			, 'id_products' => NULL
			, 'quantities' => NULL
			, 'id_product_attributes' => NULL
			, 'id_cart' => NULL
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
			, 'id_products' => array(
				'label' => 'Product Ids'
				, 'rules' => array(
					'is_array' => NULL
				)
			)
			, 'quantities' => array(
				'label' => 'Quantities'
				, 'rules' => array(
					'is_array' => NULL
				)
			)
			, 'id_product_attributes' => array(
				'label' => 'Product Attribute Ids'
				, 'rules' => array(
					'is_array' => NULL
				)
			)
			, 'id_cart' => array(
				'label' => 'Cart Id'
				, 'rules' => array(
					'is_int' => NULL
				)
			)
		);

		$this->Validate->add_many($input_validations, $validate_params, true);
		$this->Validate->run();

		$now = _Model::$date_time;

		if (!empty($params['id_products']) && empty($params['quantities'])) {
			_Model::$Exception_Helper->request_failed_exception('Missing quantities.');
		}
		else if (!empty($params['id_products']) && empty($params['id_product_attributes'])) {
			_Model::$Exception_Helper->request_failed_exception('Missing product attributes.');
		}

		$cart = array();

		if (!empty($params['id_cart'])) {
			$cart_result = $this->get_cart_from_db(
				array(
					'user_id' => $params['user_id']
					, 'id_shop' => $params['id_shop']
					, 'id_lang' => $params['id_lang']
					, 'id_cart' => $params['id_cart']
				)
			);
			$cart = $cart_result['data'];
		}

		if (!empty($cart)) { // Remove current items from cart
			$data['id_cart'] = $cart['cart']['id_cart'];
			$this->Cart_Product->delete_by_id_cart($cart['cart']['id_cart']);
		}

		if (!empty($params['id_products']) && !empty($params['quantities']) && !empty($params['id_product_attributes'])) {
			foreach ($params['id_products'] as $i => $id_product) {
				if ($params['quantities'][$i] < 0) {
					_Model::$Exception_Helper->request_failed_exception('Quantity must be a positive number.');
				}
			}
		}

		if (!empty($params['id_products']) && !empty($params['quantities']) && !empty($params['id_product_attributes'])) {
			foreach ($params['id_products'] as $i => $id_product) {
				if ($params['quantities'][$i] <= 0) {
					continue;
				}

				$cart_item_params = array(
					'user_id' => $params['user_id']
					, 'id_shop' => $params['id_shop']
					, 'id_lang' => $params['id_lang']
					, 'id_product' => $id_product
					, 'quantity' => $params['quantities'][$i]
					, 'id_product_attribute' => $params['id_product_attributes'][$i] != '' ? $params['id_product_attributes'][$i] : NULL
				);

				if (!empty($cart)) {
					$cart_item_params['id_cart'] = $cart['cart']['id_cart'];
				}

				$cart_item_result = $this->add_db_cart_item($cart_item_params);
				$data['id_cart'] = $cart_item_result['data']['id_cart'];
			}
		}

		return static::wrap_result(true, $data);
	}


	public function update_cookie_cart($params = array()) {
		$validate_names = array(
			'id_shop' => NULL
			, 'id_lang' => NULL
			, 'id_products' => NULL
			, 'quantities' => NULL
			, 'id_product_attributes' => NULL
		);

		$validate_params = array_merge($validate_names, $params);

		// Validations
		$input_validations = array(
			'id_shop' => array(
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
			, 'id_products' => array(
				'label' => 'Product Ids'
				, 'rules' => array(
					'is_array' => NULL
				)
			)
			, 'quantities' => array(
				'label' => 'Quantities'
				, 'rules' => array(
					'is_array' => NULL
				)
			)
			, 'id_product_attributes' => array(
				'label' => 'Product Attribute Ids'
				, 'rules' => array(
					'is_array' => NULL
				)
			)
		);

		$this->Validate->add_many($input_validations, $validate_params, true);
		$this->Validate->run();

		$now = _Model::$date_time;

		if (!empty($params['id_products']) && empty($params['quantities'])) {
			_Model::$Exception_Helper->request_failed_exception('Missing quantities.');
		}
		else if (!empty($params['id_products']) && empty($params['id_product_attributes'])) {
			_Model::$Exception_Helper->request_failed_exception('Missing product attributes.');
		}

		$cookie_cart = array(
			'cart' => array(
				'id_shop' => $params['id_shop']
				, 'id_lang' => $params['id_lang']
				, 'date_add' => $now
				, 'date_upd' => NULL
			)
		);

		if (!empty($params['id_products']) && !empty($params['quantities']) && !empty($params['id_product_attributes'])) {
			foreach ($params['id_products'] as $i => $id_product) {
				if ($params['quantities'][$i] < 0) {
					_Model::$Exception_Helper->request_failed_exception('Quantity must be a positive number.');
				}
			}
		}

		if (!empty($params['id_products']) && !empty($params['quantities']) && !empty($params['id_product_attributes'])) {
			foreach ($params['id_products'] as $i => $id_product) {
				if ($params['quantities'][$i] <= 0) {
					continue;
				}

				$cart_item_params = array(
					'cart_cookie' => json_encode($cookie_cart)
					, 'id_shop' => $params['id_shop']
					, 'id_lang' => $params['id_lang']
					, 'id_product' => $id_product
					, 'quantity' => $params['quantities'][$i]
					, 'id_product_attribute' => $params['id_product_attributes'][$i] != '' ? $params['id_product_attributes'][$i] : NULL
				);

				$cart_item_result = $this->add_cookie_cart_item($cart_item_params);
				$cookie_cart = $cart_item_result['data'];
			}
		}

		return static::wrap_result(true, $cookie_cart);

	}

	public function add_db_cart_item($params = array()) {
		$this->load('Product');
		$this->load('Cart_Product');
		$this->load('Customer');
		$this->load('Cart');

		$data = array();
		$error = NULL;

		$validate_names = array(
			'user_id' => NULL
			, 'id_shop' => NULL
			, 'id_lang' => NULL
			, 'id_product' => NULL
			, 'quantity' => NULL
			, 'id_product_attribute' => NULL
			, 'id_cart' => NULL
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
			, 'id_product' => array(
				'label' => 'Product Id'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'quantity' => array(
				'label' => 'Quantity'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
					, 'is_positive' => NULL
				)
			)
			, 'id_product_attribute' => array(
				'label' => 'Product Attribute Id'
				, 'rules' => array(
					'is_int' => NULL
				)
			)
			, 'id_cart' => array(
				'label' => 'Cart Id'
				, 'rules' => array(
					'is_int' => NULL
				)
			)
		);
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

		// Get product info
		$product_info = $this->Product->get_product($params['id_product'], $params['id_shop'], $params['id_lang']);
		if (empty($product_info)) {
			_Model::$Exception_Helper->request_failed_exception('Product not found.');
		}

		// Validate product attribute
		$product_combinations = $this->Product->get_product_combinations($params['id_product'], $params['id_shop'], $params['id_lang']);
		$product_combinations = !empty($product_combinations) ? rows_to_array($product_combinations, '', 'id_product_attribute') : array();

		if (!empty($product_combinations) && empty($params['id_product_attribute'])) {
			_Model::$Exception_Helper->request_failed_exception('Please select an option.');
		}
		else if (!empty($params['id_product_attribute']) && !in_array($params['id_product_attribute'], $product_combinations)) {
			_Model::$Exception_Helper->request_failed_exception('Invalid product attribute.');
		}

		// Get user cart
		$found_product = false;
		$cart = array();
		if (!empty($params['id_cart'])) {
			$cart_result = $this->get_cart_from_db(
				array(
					'user_id' => $params['user_id']
					, 'id_shop' => $params['id_shop']
					, 'id_lang' => $params['id_lang']
					, 'id_cart' => $params['id_cart']
				)
			);
			$cart = $cart_result['data'];
		}

		if (empty($cart)) { // Create new cart
			$cart_data = array(
				'id_shop' => $params['id_shop']
				, 'id_lang' => $params['id_lang']
				, 'id_customer' => $customer['id_customer']
				, 'date_add' => $now
				, 'date_upd' => NULL
			);

			$id_cart = $this->Cart->save($cart_data);
		}
		else {
			$cart_data = array(
				'id_cart' => $cart['cart']['id_cart']
				, 'date_upd' => $now
			);

			$id_cart = $this->Cart->save($cart_data);

			if (!empty($cart['products'])) {
				foreach ($cart['products'] as $i => $cart_product) {
					if ($cart_product['id_product'] == $params['id_product']
						&& $params['id_product_attribute'] == $cart_product['id_product_attribute']
					) {
						// Update quantity
						$cart_product_data = array(
							'quantity' => $params['quantity']
						);

						$where_values = array(
							':id_cart_product' => $cart_product['id_cart_product']
						);

						$operators = array(
							'quantity' => '+'
						);

						$this->Cart_Product->db_update($cart_product_data, 'id_cart_product = :id_cart_product', $where_values, $operators);

						$found_product = true;
					}
				}
			}
		}

		if (!$found_product) {
			$cart_product_data = array(
			 	'id_cart' => $id_cart
			 	, 'id_product' => $params['id_product']
			 	, 'id_shop' => $params['id_shop']
			 	, 'id_product_attribute' => $params['id_product_attribute'] != '' ? $params['id_product_attribute'] : NULL
			 	, 'quantity' => $params['quantity']
			 	, 'date_add' => $now
			);

			$this->Cart_Product->save($cart_product_data);
		}

		$data['id_cart'] = $id_cart;

		return static::wrap_result(true, $data);

	}

	public function add_db_cart_discount($params = array()) {
		$this->load('Cart_Cart_Rule');
		$this->load('Cart_Rule');
		$this->load('Customer');

		$data = array();
		$error = NULL;

		$validate_names = array(
			'cart_cookie' => NULL
			, 'id_shop' => NULL
			, 'id_lang' => NULL
			, 'code' => NULL
		);

		$validate_params = array_merge($validate_names, $params);

		// Validations
		$input_validations = array(
			'id_shop' => array(
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
			, 'code' => array(
				'label' => 'Code'
				, 'rules' => array(
					'is_set' => NULL
				)
			)
		);
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

		// Get discount
		$new_discount = $this->Cart_Rule->get_cart_rule($params['code'], $params['id_lang']);

		if (empty($new_discount)) { // Not found
			_Model::$Exception_Helper->request_failed_exception('Invalid discount code.');
		}

		$membership_discounts = $this->Cart_Rule->get_membership_discounts();
		$membership_discounts = rows_to_groups($membership_discounts, 'id_cart_rule');
		$membership_discount_ids = array_keys($membership_discounts);

		if (in_array($new_discount['id_cart_rule'], $membership_discount_ids)) {
			_Model::$Exception_Helper->request_failed_exception('Invalid discount code.');
		}

		// Validate discount
		$result = $this->Cart_Rule->validate_discount();

		// Get user cart
		$found_discount = false;
		$cart = array();
		if (!empty($params['id_cart'])) {
			$cart_result = $this->get_cart_from_db(
				array(
					'user_id' => $params['user_id']
					, 'id_shop' => $params['id_shop']
					, 'id_lang' => $params['id_lang']
					, 'id_cart' => $params['id_cart']
				)
			);
			$cart = $cart_result['data'];
		}

		if (empty($cart)) { // Create new cart
			$cart_data = array(
				'id_shop' => $params['id_shop']
				, 'id_lang' => $params['id_lang']
				, 'id_customer' => $customer['id_customer']
				, 'date_add' => $now
				, 'date_upd' => NULL
			);

			$id_cart = $this->Cart->save($cart_data);
		}
		else {
			if (!empty($cart['discounts'])) {
				foreach ($cart['discounts'] as $i => $discount) {
					if ($discount['id_cart_rule'] == $new_discount['id_cart_rule']) {
						$found_discount = true;
						_Model::$Exception_Helper->request_failed_exception('Discount is already being applied.');
					}
				}
			}

			if (!$found_discount) {
				$cart_data = array(
					'id_cart' => $cart['cart']['id_cart']
					, 'date_upd' => $now
				);

				$id_cart = $this->Cart->save($cart_data);
			}
		}

		if (!$found_discount) {
			$cart_cart_rule_data = array(
			 	'id_cart' => $id_cart
			 	, 'id_cart_rule' => $new_discount['id_cart_rule']
			);

			$this->Cart_Cart_Rule->save($cart_cart_rule_data);
		}

		$data['id_cart'] = $id_cart;

		return static::wrap_result(true, $data);

	}

	public function remove_db_cart_discount($params = array()) {
		$this->load('Cart_Cart_Rule');
		$this->load('Customer');

		$data = array();
		$error = NULL;

		$validate_names = array(
			'cart_cookie' => NULL
			, 'id_shop' => NULL
			, 'id_lang' => NULL
			, 'id_cart_rule' => NULL
		);

		$validate_params = array_merge($validate_names, $params);

		// Validations
		$input_validations = array(
			'id_shop' => array(
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
			, 'id_cart_rule' => array(
				'label' => 'Cart Rule Id'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
		);
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

		// Get user cart
		$found_discount = false;
		$cart = array();
		if (!empty($params['id_cart'])) {
			$cart_result = $this->get_cart_from_db(
				array(
					'user_id' => $params['user_id']
					, 'id_shop' => $params['id_shop']
					, 'id_lang' => $params['id_lang']
					, 'id_cart' => $params['id_cart']
				)
			);
			$cart = $cart_result['data'];
		}

		$data['id_cart'] = NULL;

		if (!empty($cart)) { // Create new cart
			if (!empty($cart['discounts'])) {
				foreach ($cart['discounts'] as $i => $discount) {
					if ($discount['id_cart_rule'] == $params['id_cart_rule']) {
						$found_discount = true;
						$this->Cart_Cart_Rule->delete_by_primary_key($discount['id_cart_cart_rule']);
					}
				}
			}

			if ($found_discount) {
				$cart_data = array(
					'id_cart' => $cart['cart']['id_cart']
					, 'date_upd' => $now
				);

				$id_cart = $this->Cart->save($cart_data);
			}

			$data['id_cart'] = $cart['cart']['id_cart'];
		}

		return static::wrap_result(true, $data);

	}

	public function save_cookie_cart_to_db($params = array()) {
		$this->load('Customer');
		$this->load('Cart');
		$this->load('Cart_Product');
		$this->load('Cart_Cart_Rule');

		$data = array();

		$validate_names = array(
			'user_id' => NULL
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
		);
		$this->Validate->add_many($input_validations, $validate_params, true);
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

		$cart_result = $this->get_cart_from_cookie($params); // handles validation
		$now = _Model::$date_time;
		if (!empty($cart_result['data']) && !empty($cart_result['data']['products'])) {
			$id_shop = $cart_result['data']['cart']['id_shop'];
			$id_lang = $cart_result['data']['cart']['id_lang'];

			$cart = array(
				'id_shop' => $id_shop
				, 'id_lang' => $id_lang
				, 'id_customer' => $customer['id_customer']
				, 'date_add' => $now
			);

			$id_cart = $this->Cart->save($cart);

			if (!empty($cart_result['data']['products'])) {
				foreach ($cart_result['data']['products'] as $item) {
					$cart_product = array(
					 	'id_cart' => $id_cart
					 	, 'id_product' => $item['id_product']
					 	, 'id_shop' => $id_shop
					 	, 'id_product_attribute' => $item['id_product_attribute'] != '' ? $item['id_product_attribute'] : NULL
					 	, 'quantity' => $item['quantity']
					 	, 'date_add' => $now
					);

					$this->Cart_Product->save($cart_product);

				}
			}

			$discount_ids = array();
			if (!empty($cart_result['data']['discounts'])) {
				foreach ($cart_result['data']['discounts'] as $discount) {
					if (!in_array($discount['id_cart_rule'], $discount_ids)) {// prevent duplicates
						$cart_cart_rule_data = array(
							'id_cart' => $id_cart
							, 'id_cart_rule' => $discount['id_cart_rule']
						);

						$this->Cart_Cart_Rule->save($cart_cart_rule_data);

						array_push($discount_ids, $discount['id_cart_rule']);
					}
				}
			}

			$data['id_cart'] = $id_cart;
		}

		return static::wrap_result(true, $data);

	}

	public function set_paypal_token($params = array()) {
		$this->load('Cart');

		$data = array();

		// Validations
		$input_validations = array(
			'user_id' => array(
				'label' => 'User ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'id_cart' => array(
				'label' => 'Card ID'
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
			, 'paypal_token' => array(
				'label' => 'PayPal Token'
				, 'rules' => array(
					'is_set' => NULL
				)
			)
		);
		$this->Validate->add_many($input_validations, $params, true);
		$this->Validate->run();

		$cart = $this->Cart->get_user_cart($params['user_id'], $params['id_shop'], $params['id_cart']);

		if (empty($cart)) {
			_Model::$Exception_Helper->request_failed_exception('Cart not found.');
		}

		$info = array(
			'id_cart' => $cart['id_cart']
			, 'paypal_token' => $params['paypal_token']
		);

		$data['id_cart'] = $this->Cart->save($info);

		return static::wrap_result(true, $data);
	}

	public function begin_paypal_purchase($params = array()) {
		$this->load('Cart');

		$data = array();

		// Validations
		$input_validations = array(
			'user_id' => array(
				'label' => 'User ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'id_cart' => array(
				'label' => 'Card ID'
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
			, 'return_url' => array(
				'label' => 'Return Url'
				, 'rules' => array(
					'is_set' => NULL
				)
			)
			, 'cancel_url' => array(
				'label' => 'Cancel Url'
				, 'rules' => array(
					'is_set' => NULL
				)
			)
			, 'item_name' => array(
				'label' => 'Item Name'
				, 'rules' => array(
					'is_set' => NULL
				)
			)
			, 'item_description' => array(
				'label' => 'Item Description'
				, 'rules' => array(
					'is_set' => NULL
				)
			)
			, 'shipping_address_id' => array(
				'label' => 'Shipping Address ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'id_delivery' => array(
				'label' => 'Delivery ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
		);
		$this->Validate->add_many($input_validations, $params, true);
		$this->Validate->run();

		$cart_result = $this->get_cart_from_db(
			array(
				'user_id' => $params['user_id']
				, 'id_shop' => $params['id_shop']
				, 'id_lang' => $params['id_lang']
				, 'id_cart' => $params['id_cart']
				, 'shipping_address_id' => $params['shipping_address_id']
				, 'id_delivery' => $params['id_delivery']
			)
		);
		$cart = $cart_result['data']['cart'];
		if (empty($cart)) {
			_Model::$Exception_Helper->request_failed_exception('Cart not found.');
		}

		$calls = array(
			'begin_paypal_purchase' => array(
				'purchase_info' => array(
					'amount' => $cart['totals']['grand_total']
					, 'item_description' => $params['item_description']
					, 'item_name' => $params['item_name']
				)
				, 'return_url' => $params['return_url']
				, 'cancel_url' => $params['cancel_url']
			)
		);

		$API = new API(API_KEY_DEVELOPER, PRIVATE_KEY_DEVELOPER);
		$result = $API->rest_api_request('payment', $calls);
		$result_decoded = json_decode($result, true);

		$api_errors = api_errors_to_array($result_decoded);

		if (!empty($api_errors)) {
			return static::wrap_result(false, NULL, _Model::$Status_Code->get_status_code_request_failed(), $api_errors);
		}

		$data = $result_decoded['data']['begin_paypal_purchase']['data'];

		return static::wrap_result(true, $data);
	}

	public function add_membership_discount($user_id, $id_cart, $id_lang) {
		$this->load('Cart_Rule');
		$this->load('Dw_User');
		$this->load('Cart_Cart_Rule');

		// Get membership discounts
		$membership_discounts = $this->Cart_Rule->get_membership_discounts();
		$membership_discounts = rows_to_groups($membership_discounts, 'id_cart_rule');
		// Get user membership level
		$membership_level = $this->Dw_User->get_membership_level($user_id);

		if (!empty($membership_level) && !empty($membership_discounts[$membership_level['commerce_id_cart_rule']])) {
			$membership_discount = $membership_discounts[$membership_level['commerce_id_cart_rule']][0];
			$cart_discounts = $this->Cart_Cart_Rule->get_cart_discounts($id_cart, $id_lang);

			$found_membership_discount = false;

			if (!empty($cart_discounts)) {
				$membership_discount_ids = array_keys($membership_discounts);

				foreach ($cart_discounts as $cart_discount) {
					if (in_array($cart_discount['id_cart_rule'], $membership_discount_ids)) {
						if ($found_membership_discount) { // delete row because it is duplicate
							$this->Cart_Cart_Rule->delete_by_primary_key($cart_discount['id_cart_cart_rule']);
						}

						$found_membership_discount = true;
						// Update row with current membership discount id
						$this->Cart_Cart_Rule->save(
							array(
								'id_cart_cart_rule' => $cart_discount['id_cart_cart_rule']
								, 'id_cart_rule' => $membership_level['commerce_id_cart_rule']
							)
						);
					}

				}
			}

			// if not there, add discount
			if (!$found_membership_discount) {
				$this->Cart_Cart_Rule->save(
					array(
						'id_cart' => $id_cart
						, 'id_cart_rule' => $membership_level['commerce_id_cart_rule']
					)
				);
			}

		}
	}

	/*
	 function add_membership_discount($user_id, $cart, $id_lang) {
		$this->load('Cart_Rule');
		$this->load('Dw_User');
		$this->load('Cart_Cart_Rule');

		// Get membership discounts
		$membership_discounts = $this->Cart_Rule->get_membership_discounts();
		$membership_discounts = rows_to_groups($membership_discounts, 'id_cart_rule');
		// Get user membership level
		$membership_level = $this->Dw_User->get_membership_level($user_id);

		// Get current discounts
		$cart_discounts = $this->Cart_Cart_Rule->get_cart_discounts($cart['id_cart'], $id_lang);

		// Remove if not applicable
		// Discount no longer exists
		if (isset($cart['user_id_cart_rule'])
			&& !empty($cart['user_id_cart_rule'])
			&& empty($membership_discounts[$cart['user_id_cart_rule']])
			&& !empty($cart_discounts)
		) {
			foreach ($cart_discounts as $cart_discount) {
				if ($cart_discount['id_cart_rule'] == $cart['user_id_cart_rule']) {
					$this->Cart_Cart_Rule->delete_by_primary_key($cart_discount['id_cart_cart_rule']);
				}
			}

			return;
		}

		// Discount has been removed
		if (!isset($cart['user_id_cart_rule'])
			|| empty($cart['user_id_cart_rule'])
		) {
			if (!empty($cart_discounts)) {
				foreach ($cart_discounts as $cart_discount) {
					if (in_array($cart_discount['id_cart_rule'], $membership_discount_ids)) {
						$this->Cart_Cart_Rule->delete_by_primary_key($cart_discount['id_cart_cart_rule']);
					}
				}
			}
			return;
		}

		if (!empty($membership_level)
			&& isset($cart['user_id_cart_rule'])
			&& !empty($cart['user_id_cart_rule'])
			&& !empty($membership_discounts[$cart['user_id_cart_rule']])
		) {
			$membership_discount = $membership_discounts[$cart['user_id_cart_rule']][0];

			$found_membership_discount = false;

			if (!empty($cart_discounts)) {
				$membership_discount_ids = array_keys($membership_discounts);

				foreach ($cart_discounts as $cart_discount) {
					if (in_array($cart_discount['id_cart_rule'], $membership_discount_ids)) {
						if ($found_membership_discount) { // delete row because it is duplicate
							$this->Cart_Cart_Rule->delete_by_primary_key($cart_discount['id_cart_cart_rule']);
						}

						$found_membership_discount = true;
						// Update row with current membership discount id
						$this->Cart_Cart_Rule->save(
							array(
								'id_cart_cart_rule' => $cart_discount['id_cart_cart_rule']
								, 'id_cart_rule' => $cart['user_id_cart_rule']
							)
						);
					}
				}
			}

			// if not there, add discount
			if (!$found_membership_discount) {
				$this->Cart_Cart_Rule->save(
					array(
						'id_cart' => $cart['id_cart']
						, 'id_cart_rule' => $cart['user_id_cart_rule']
					)
				);
			}

		}

		return;
	}
	*/

	private function get_user_points($user_id) {
		$this->load('Dw_User_Point', DW_API_HOST, DW_API_USER, DW_API_PASSWORD, DW_API_DATABASE);

		$points = $this->Dw_User_Point->get_user_points($user_id);

		return $points['points'];
	}

	private function get_points_will_earn($amount) {
		$this->load('Dw_Point', DW_API_HOST, DW_API_USER, DW_API_PASSWORD, DW_API_DATABASE);

		$dollar_point_value = $this->Dw_Point->get_buy_points_amount();

		$points_will_earn = floor($amount * $dollar_point_value);

		return $points_will_earn;
	}

	private function get_eligible_levels($points) {
		$this->load('Dw_Membership_Level', DW_API_HOST, DW_API_USER, DW_API_PASSWORD, DW_API_DATABASE);

		$levels = $this->Dw_Membership_Level->get_eligible_levels($points);

		return $levels;
	}

	public function set_user_cart_rule($params = array()) {
		$this->load('Cart');
		$this->load('Dw_Membership_Level', DW_API_HOST, DW_API_USER, DW_API_PASSWORD, DW_API_DATABASE);

		// Validations
		$input_validations = array(
			'user_id' => array(
				'label' => 'User ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'id_cart' => array(
				'label' => 'Card ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'id_cart_rule' => array(
				'label' => 'Cart Rule ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
		);
		$this->Validate->add_many($input_validations, $params, true);
		$this->Validate->run();

		// Check that user is eligible
		$user_points = $this->get_user_points($params['user_id']);
		$level = $this->Dw_Membership_Level->check_cart_rule_eligibility($params['id_cart_rule'], $user_points);

		// If eligible, then set user_id_cart_rule
		if (!empty($level)) {
			$save = $this->Cart->save(
				array(
					'id_cart' => $params['id_cart']
					, 'user_id_cart_rule' => $params['id_cart_rule']
				)
			);

			return $this->wrap_result(true, $save);
		}
		else {
			return $this->remove_user_cart_rule($params);
		}
	}

	public function remove_user_cart_rule($params = array()) {
		$this->load('Cart');

		// Validations
		$input_validations = array(
			'user_id' => array(
				'label' => 'User ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'id_cart' => array(
				'label' => 'Card ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
		);
		$this->Validate->add_many($input_validations, $params, true);
		$this->Validate->run();

		$save = $this->Cart->save(
			array(
				'id_cart' => $params['id_cart']
				, 'user_id_cart_rule' => NULL
			)
		);

		return $this->wrap_result(true, $save);
	}

	public function save_cart_commission($params = array()) {
		$this->load('Cart');
		$this->load('Cart_Commission');
		$this->load('Commission');

		// Validations
		$input_validations = array(
			'user_id' => array(
				'label' => 'User ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'id_cart' => array(
				'label' => 'Card ID'
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
			, 'id_shop' => array(
				'label' => 'Shop ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'amount' => array(
				'label' => 'Amount'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_number' => NULL
					, 'is_positive' => NULL
				)
			)
		);
		$this->Validate->add_many($input_validations, $params, true);
		$this->Validate->run();

		// Verify cart belongs to user
		$cart = $this->Cart->get_user_cart($params['user_id'], $params['id_shop'], $params['id_cart']);
		if (empty($cart)) {
			_Model::$Exception_Helper->request_failed_exception('Cart not found.');
		}
		$db_cart = $this->get_cart_from_db($params);

		// Check the commission amount does not exceed user's total commissions
		$user_total = $this->Commission->get_user_total($params['user_id']);
		if ($user_total['total_commissions'] < $params['amount']) {
			$this->Cart_Commission->save_cart_commission($cart['id_cart'], 0);

			_Model::$Exception_Helper->request_failed_exception('Commission redemption amount exceeds total earned commissions.');
		}

		// Check the commission amount does not exceed cart total
		if ($db_cart['data']['cart']['totals']['grand_total'] < $params['amount']) {
			$this->Cart_Commission->save_cart_commission($cart['id_cart'], 0);

			_Model::$Exception_Helper->request_failed_exception('Commission redemption amount exceeds cart total.');
		}

		$this->Cart_Commission->save_cart_commission($cart['id_cart'], $params['amount']);

		$cart_commission = $this->Cart_Commission->get_row(
			array(
				'id_cart' => $cart['id_cart']
			)
		);

		return static::wrap_result(true, $cart_commission);
	}

	public function save_cart_store_credit($params = array()) {
		$this->load('Cart');
		$this->load('Cart_Store_Credit');
		$this->load('Store_Credit');

		// Validations
		$input_validations = array(
			'user_id' => array(
				'label' => 'User ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'id_cart' => array(
				'label' => 'Card ID'
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
			, 'id_shop' => array(
				'label' => 'Shop ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'amount' => array(
				'label' => 'Amount'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_number' => NULL
					, 'is_positive' => NULL
				)
			)
		);
		$this->Validate->add_many($input_validations, $params, true);
		$this->Validate->run();

		// Verify cart belongs to user
		$cart = $this->Cart->get_user_cart($params['user_id'], $params['id_shop'], $params['id_cart']);
		if (empty($cart)) {
			_Model::$Exception_Helper->request_failed_exception('Cart not found.');
		}
		$db_cart = $this->get_cart_from_db($params);

		// Check the store credit amount does not exceed user's total store credits
		$user_total = $this->Store_Credit->get_user_total($params['user_id']);
		if ($user_total['total_credits'] < $params['amount']) {
			$this->Cart_Store_Credit->save_cart_store_credit($cart['id_cart'], 0);

			_Model::$Exception_Helper->request_failed_exception('Store credit redemption amount exceeds total store credits.');
		}

		// Check the store credit amount does not exceed cart total
		if ($db_cart['data']['cart']['totals']['grand_total'] < $params['amount']) {
			$this->Cart_Store_Credit->save_cart_store_credit($cart['id_cart'], 0);

			_Model::$Exception_Helper->request_failed_exception('Store credit redemption amount exceeds cart total.');
		}

		$this->Cart_Store_Credit->save_cart_store_credit($cart['id_cart'], $params['amount']);

		$cart_store_credit = $this->Cart_Store_Credit->get_row(
			array(
				'id_cart' => $cart['id_cart']
			)
		);

		return static::wrap_result(true, $cart_store_credit);
	}
}

?>
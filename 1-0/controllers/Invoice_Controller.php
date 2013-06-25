<?
class Invoice_Controller extends _Controller {
	
	public function create_estimate($params = array()) {
		$this->load('Cart');
		$this->load('Cart_Product');
		
		$data = array();
		
		$validate_names = array(
			'user_id' => NULL
		);
		
		$validate_params = array_merge($validate_names, $params);
		
		// Validations
		$input_validations = array(
			'id_shop_group' => array(
				'label' => 'Shop Group Id'
				, 'rules' => array(
					'is_int' => NULL
				)
			)
			, 'id_shop' => array(
				'label' => 'Shop Id'
				, 'rules' => array(
					'is_int' => NULL
				)
			)
			, 'id_carrier' => array(
				'label' => 'Carrier Id'
				, 'rules' => array(
					'is_int' => NULL
				)
			)
			, 'id_lang' => array(
				'label' => 'Lang Id'
				, 'rules' => array(
					'is_int' => NULL
				)
			)
			, 'id_address_delivery' => array(
				'label' => 'Address Delivery Id'
				, 'rules' => array(
					'is_int' => NULL
				)
			)
			, 'id_address_invoice' => array(
				'label' => 'Address Invoice Id'
				, 'rules' => array(
					'is_int' => NULL
				)
			)
			, 'id_currency' => array(
				'label' => 'Currency Id'
				, 'rules' => array(
					'is_int' => NULL
				)
			)
			, 'user_id' => array(
				'label' => 'User Id'
				, 'rules' => array(
					'is_int' => NULL
				)
			)
			, 'id_guest' => array(
				'label' => 'Guest Id'
				, 'rules' => array(
					'is_int' => NULL
				)
			)
			, 'id_payment_term' => array(
				'label' => 'Payment Term Id'
				, 'rules' => array(
					'is_int' => NULL
				)
			)
			, 'recyclable' => array(
				'label' => 'Is Recyclable'
				, 'rules' => array(
					'is_int' => NULL
					, 'is_boolean' => NULL
					, 'is_not_null' => NULL
				)
			)
			, 'gift' => array(
				'label' => 'Is Gift'
				, 'rules' => array(
					'is_int' => NULL
					, 'is_boolean' => NULL
				)
			)
			, 'allow_seperated_package' => array(
				'label' => 'Allow Separated Package'
				, 'rules' => array(
					'is_int' => NULL
					, 'is_boolean' => NULL
				)
			)
		);
		$this->Validate->add_many($input_validations, $validate_params, true);
		$this->Validate->run();
		
		$fields = array(
			'id_shop_group'
			, 'id_shop'
			, 'id_carrier'
			, 'delivery_option'
			, 'id_lang'
			, 'id_address_delivery'
			, 'id_address_invoice'
			, 'id_currency'
			, 'id_customer'
			, 'id_guest'
			, 'id_payment_term'
			, 'secure_key'
			, 'recyclable'
			, 'gift'
			, 'gift_message'
			, 'allow_seperated_package'
		);
		
		$info = array();
		$bools = array(
			'recyclable'
			, 'gift'
			, 'allow_seperated_package'
		);
		foreach ($fields as $field) {
			if (array_key_exists($field, $params)) {
				$info[$field] = $params[$field];
			}
		}
		
		// Create Estimate
		
		// Create Cart
		
		$id_cart = $this->Cart->save($info);
		
		// Cart Products
		if (!empty($params['products'])) {
			foreach ($params['products'] as $product) {
				$cart_product = array(
					'id_cart' => $id_cart
					, 'id_product' => $product['id_product']
					, 'id_address_delivery' => !empty($params['id_address_delivery']) ? $params['id_address_delivery'] : NULL
					, 'id_shop' => !empty($params['id_shop']) ? $params['id_shop'] : NULL
					, 'id_product_attribute' => !empty($params['id_product_attribute']) ? $params['id_product_attribute'] : NULL
					, 'quantity' => !empty($product['quantity']) ? $product['quantity'] : 0
				);
				
				$this->Cart_Product->save($cart_product);
			}
		}

		$data['id_cart'] = $id_cart;

		return static::wrap_result(true, $data);
	}
	
	public function create_invoice() {
	}
	
}

?>
<?
class Carrier_Controller extends _Controller {
	
	public function get_checkout_carrier_options($params = array()) {
		$this->load('Carrier');
		$this->load('Configuration');
		$this->load('Country');
		$this->load('Tax_Rule');
		
		$data = array();
		
		$validate_names = array(
			'user_id' => NULL
			, 'id_shop' => NULL
			, 'id_lang' => NULL
			, 'shipping_address_id' => NULL
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
			, 'shipping_address_id' => array(
				'label' => 'Shipping address Id'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'total_products' => array(
				'label' => 'Total Product Price'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_decimal' => '2'
					, 'is_positive' => NULL
				)
			)
			, 'total_product_weight' => array(
				'label' => 'Total Product Weight'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_decimal' => '2'
					, 'is_positive' => NULL
				)
			)
		);
		$this->Validate->add_many($input_validations, $validate_params, true);
		$this->Validate->run();
		
		// Get user hq address info
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
		$id_state = $address_info['state']['id_state'];
		$zip = $address_info['address']['zip'];
		
		$carrier_options_result = $this->get_carrier_options(
			array(
				'id_shop' => $params['id_shop']
				, 'id_lang' => $params['id_lang']
				, 'id_country' => $id_country
				, 'id_zone' => $id_zone
				, 'id_state' => $id_state
				, 'zip' => $zip 
				, 'total_products' => $params['total_products']
				, 'total_product_weight' => $params['total_product_weight']
			)
		);
		
		$data = $carrier_options_result['data'];
		
		return static::wrap_result(true, $data);
	}
	
	public function get_carrier_options($params = array()) {
		$this->load('Carrier');
		$this->load('Configuration');
		$this->load('Country');
		$this->load('Tax_Rule');
		
		$data = array();
		
		$validate_names = array(
			'id_shop' => NULL
			, 'id_lang' => NULL
			, 'id_country' => NULL
			, 'id_zone' => NULL
			, 'id_state' => NULL
			, 'zip' => NULL 
			, 'total_products' => NULL
			, 'total_product_weight' => NULL
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
			, 'id_country' => array(
				'label' => 'Country Id'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'id_zone' => array(
				'label' => 'Zone Id'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'id_state' => array(
				'label' => 'State Id'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'zip' => array(
				'label' => 'Zip'
				, 'rules' => array(
					'is_set' => NULL
				)
			)
			, 'total_products' => array(
				'label' => 'Total Product Price'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_decimal' => '2'
					, 'is_positive' => NULL
				)
			)
			, 'total_product_weight' => array(
				'label' => 'Total Product Weight'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_decimal' => '2'
					, 'is_positive' => NULL
				)
			)
		);
		$this->Validate->add_many($input_validations, $validate_params, true);
		$this->Validate->run();
		
		$id_country = $params['id_country'];
		$id_zone = $params['id_zone'];
		$id_state = $params['id_state'];
		$zip = $params['zip'];
		
		// Get shipping configuration
		$config = $this->Configuration->get_row(
			array(
				'name' => 'SHIPPING_METHOD'
			)
		);
		
		if (empty($config)) {
			_Model::$Exception_Helper->request_failed_exception('Shipping method configuration not found.');
		}
		
		if ($config['value'] == '0') {
			$carriers = $this->Carrier->get_carrier_options_by_price($id_zone, $params['id_shop'], $params['id_lang'], $params['total_products']);
		}
		else {
			$carriers = $this->Carrier->get_carrier_options_by_weight($id_zone, $params['id_shop'], $params['id_lang'], $params['total_product_weight']);
		}
		
		if (!empty($carriers)) {
			foreach ($carriers as $i => $carrier) {
				$carriers[$i]['tax_info'] = $this->Tax_Rule->get_tax_info($carrier['id_tax_rules_group'], $id_country, $id_state, $zip, $carrier['price'], 1);
			}
		}
		
		$data = $carriers;
		
		return static::wrap_result(true, $data);
	}
	
}

?>
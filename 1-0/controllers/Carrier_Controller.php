<?
require DR . '/includes/php/classes/shipping/fedex/rate.php';
require DR . '/includes/php/classes/shipping/ups/rate.php';
require DR . '/includes/php/classes/shipping/usps/rate.php';

class Carrier_Controller extends _Controller {
	private $carrier_rate_objects = array();

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
				, 'user_id' => $params['user_id']
				, 'shipping_address_id' => $params['shipping_address_id']
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
		$this->load('Shop');
		$this->load('Config',  DB_API_HOST, DB_API_USER, DB_API_PASSWORD, DB_API_DATABASE );

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
			, 'user_id' => array(
				'label' => 'User Id'
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
			$live_rate_options = array();

			// Shipping configs
			$shipping_configs = $this->Config->get_configs_by_section('Shipping APIs');

			// Get shop store address
			$shop_address = $this->Shop->get_primary_shop_store_address($params['id_shop']);
			if (empty($shop_address)) {
				_Model::$Exception_Helper->request_failed_exception('Shop address not found.');
			}

			// Get user hq address info
			$Address_Controller = new Address_Controller();
			$user_address = $Address_Controller->get_hq_user_address_info(
				array(
					'user_id' => $params['user_id']
					, 'address_id' => $params['shipping_address_id']
				)
			);
			if (empty($user_address)) {
				_Model::$Exception_Helper->request_failed_exception('Shipping address not found.');
			}

			foreach ($carriers as $i => $carrier) {
				$carriers[$i]['tax_info'] = $this->Tax_Rule->get_tax_info($carrier['id_tax_rules_group'], $id_country, $id_state, $zip, $carrier['price'], 1);

				// Check if delivery method needs to check for live rates
				if ($carrier['is_live_rate']) {
					$carrier_name = $carrier['carrier_name'];

					// Instantiate custom carrier class & get live rates
					if (empty($this->carrier_rate_objects[$carrier_name])) {
						$carrier_class = ucwords(strtolower($carrier_name)) . 'Rate';

						// Get rates
						if ($carrier_class == 'FedexRate') {
							// FedexRate->__construct($accessKey, $password, $accountNumber, $meterNumber, $useTestServer)
							$this->carrier_rate_objects[$carrier_name] = new $carrier_class($shipping_configs['FedEx API Authentication Key'], $shipping_configs['FedEx API Password'], $shipping_configs['FedEx API Account Number'], $shipping_configs['FedEx API Meter Number'], false);
							//$this->carrier_rate_objects[$carrier_name] = new $carrier_class('h1EQEtwlbFcpAnQp', '4Fn3jvaAe6NHljQuJYRrKQAax', '366607641', '105158564', false);
							// FedexRate->getRate($sendFromDetails, $sendToDetails, $service, $weight, $length, $width, $height)
							$live_rate_options[$carrier_name] = $this->carrier_rate_objects[$carrier_name]->getRates(
								array(
									'StreetLines' => array(
										$shop_address['address1']
										, $shop_address['address2']
									)
									, 'City' => $shop_address['city']
									, 'StateOrProvinceCode' => $shop_address['state']
									, 'PostalCode' => $shop_address['postcode']
									, 'CountryCode' => $shop_address['country']
									//, 'Residential' => '1'
								)
								, array(
									'StreetLines' => array(
										$user_address['address']['street']
										, $user_address['address']['street_2']
									)
									, 'City' => $user_address['address']['city']
									, 'StateOrProvinceCode' => $user_address['state']['iso_code']
									, 'PostalCode' => $user_address['address']['zip']
									, 'CountryCode' => $user_address['country']['iso_code']
									, 'Residential' => '1'
								)
								, ($params['total_product_weight'] / 16), NULL, NULL, NULL
							);
						}
						else if ($carrier_class == 'UpsRate') {
							// UpsRate->__construct($accessKey, $username, $password, $accountNumber, $shipFromZip, $useTestServer = false)
							$this->carrier_rate_objects[$carrier_name] = new $carrier_class('5C8F7E11A450B510', 'companycheckout', 'Dev#1234', '552V7V', '91730', true);
							// UpsRate->getRate($shipToZip,$service,$weight,$length,$width,$height)
							$live_rate_options[$carrier_name] = $this->carrier_rate_objects[$carrier_name]->getRate('91730', '', 1, 1, 1, 1);
						}
						else if ($carrier_class == 'UspsRate') {
							// UspsRate->__construct($username)
							$this->carrier_rate_objects[$carrier_name] = new $carrier_class($shipping_configs['USPS API Username']);
							// UspsRate->getRate($service, $firstClassMailType, $sendFromZip, $sendToZip, $pounds, $ounces, $containerSize, $machinable = "true")
							$lbs = floor($params['total_product_weight'] / 16);
							$oz = $params['total_product_weight'] % 16;

							$live_rate_options[$carrier_name] = $this->carrier_rate_objects[$carrier_name]->getRate('ALL', '', $shop_address['postcode'], $user_address['address']['zip'], $lbs, $oz, 'REGULAR');
						}
					}

					// Loop through live rates to set price
					if (!empty($live_rate_options[$carrier_name])) {
						//echo $carrier['delay'] . "\n";
						foreach ($live_rate_options[$carrier_name] as $option) {
							if ($carrier_class == 'FedexRate' || $carrier_class == 'UspsRate') {
								if ($option['service'] == $carrier['code']) {
									$carriers[$i]['price'] = $option['rate'];
								}
							}
							else if ($carrier_class == 'UpsRate') {

							}
						}
					}
					else {
						//print_r($carrier);
					}
				}
			}
		}
//echo '<pre>';print_r($live_rate_options);die();

		//usort($carriers, array($this, 'cmp_by_price'));
		$this->array_sort_by_column($carriers, 'price');
		$data = $carriers;
		//echo '<pre>';print_r($data);die();

		return static::wrap_result(true, $data);
	}
	private function cmp_by_price($a, $b) {
		return ($a['price'] + 0) - ($b['price'] + 0);
	}

	private function array_sort_by_column(&$arr, $col, $dir = SORT_ASC) {
	    $sort_col = array();
	    foreach ($arr as $key=> $row) {
	        $sort_col[$key] = $row[$col];
	    }

	    array_multisort($sort_col, $dir, $arr);
	}
}

?>
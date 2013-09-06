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
		//$this->load('Config',  DB_API_HOST, DB_API_USER, DB_API_PASSWORD, DB_API_DATABASE );
        $this->load('Config', ADMIN_API_HOST, ADMIN_API_USER, ADMIN_API_PASSWORD, ADMIN_API_DATABASE);

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
					/*'is_set' => NULL
					, */'is_int' => NULL
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
			
			$country = $this->Country->get_country($user_address['country']['id_country'], $params['id_lang']);
			if (empty($country)) {
				_Model::$Exception_Helper->request_failed_exception('Country not found.');
			}
			
			foreach ($carriers as $i => $carrier) {
				$carriers[$i]['tax_info'] = $this->Tax_Rule->get_tax_info($carrier['id_tax_rules_group'], $id_country, $id_state, $zip, $carrier['price'], 1);

				// Check if delivery method needs to check for live rates
				if ($carrier['is_live_rate']) {
					$carrier_name = $carrier['carrier_name'];
					$carrier_class = ucwords(strtolower($carrier_name)) . 'Rate';

					// Instantiate custom carrier class & get live rates
					if ($carrier_name == 'UPS') {
						if (empty($this->carrier_rate_objects[$carrier_name])) {
							$this->carrier_rate_objects[$carrier_name] = new $carrier_class($shipping_configs['UPS API Access Key'], $shipping_configs['UPS API Username'], $shipping_configs['UPS API Password'], $shipping_configs['UPS API Account Number'], $shop_address['postcode'], false);
							$live_rate_options[$carrier_name] = array();
						}
						
						$rate = $this->carrier_rate_objects[$carrier_name]->getRate($user_address['address']['zip'], $user_address['country']['iso_code'], $carrier['code'], 1, 1, 1, 1);
						array_push($live_rate_options[$carrier_name],
							array(
								'service' => $carrier['code']
								, 'rate' => $rate
							)
						);
					}
					else if (empty($this->carrier_rate_objects[$carrier_name])) {
					//if (1) {
						// Get rates
						if ($carrier_class == 'FedexRate') {
							// FedexRate->__construct($accessKey, $password, $accountNumber, $meterNumber, $useTestServer)
							$this->carrier_rate_objects[$carrier_name] = new $carrier_class($shipping_configs['FedEx API Authentication Key'], $shipping_configs['FedEx API Password'], $shipping_configs['FedEx API Account Number'], $shipping_configs['FedEx API Meter Number'], false);
							//$this->carrier_rate_objects[$carrier_name] = new $carrier_class('h1EQEtwlbFcpAnQp', '4Fn3jvaAe6NHljQuJYRrKQAax', '366607641', '105158564', false);
							$from_details = array(
								'StreetLines' => array(
									$shop_address['address1']
									, $shop_address['address2']
								)
								, 'City' => $shop_address['city']
								, 'StateOrProvinceCode' => $shop_address['state']
								, 'PostalCode' => $shop_address['postcode']
								, 'CountryCode' => $shop_address['country']
								//, 'Residential' => '1'
							);
							$to_details = array(
								'StreetLines' => array(
									$user_address['address']['street']
									, $user_address['address']['street_2']
								)
								, 'City' => $user_address['address']['city']
								//, 'StateOrProvinceCode' => $user_address['state']['iso_code']
								, 'PostalCode' => $user_address['address']['zip']
								, 'CountryCode' => $user_address['country']['iso_code']
								, 'Residential' => '1'
							);
							
							if (!empty($user_address['state'])) {
								$to_details['StateOrProvinceCode'] = $user_address['state']['iso_code'];
							}

							// FedexRate->getRate($sendFromDetails, $sendToDetails, $service, $weight, $length, $width, $height)
							$live_rate_options[$carrier_name] = $this->carrier_rate_objects[$carrier_name]->getRates($from_details, $to_details, ($params['total_product_weight'] / 16), NULL, NULL, NULL);
							//return $live_rate_options[$carrier_name];
							//echo'<pre>';print_r($from_details);print_r($to_details);print_r($live_rate_options[$carrier_name]);die('Carrier_Controller.php:Fedex');
						}
						/*else if ($carrier_class == 'UpsRate') {
							// UpsRate->__construct($accessKey, $username, $password, $accountNumber, $shipFromZip, $useTestServer = false)
							$this->carrier_rate_objects[$carrier_name] = new $carrier_class($shipping_configs['UPS API Access Key'], $shipping_configs['UPS API Username'], $shipping_configs['UPS API Password'], $shipping_configs['UPS API Account Number'], $shop_address['postcode'], false);
							// UpsRate->getRate($shipToZip,$service,$weight,$length,$width,$height)
							$live_rate_options[$carrier_name] = $this->carrier_rate_objects[$carrier_name]->getRate($user_address['address']['zip'], '01', 1, 1, 1, 1);
							//echo'<pre>';print_r($live_rate_options[$carrier_name]);die('Carrier_Controller.php:UPS');
						}*/
						else if ($carrier_class == 'UspsRate') {
						//if (1) {
							// UspsRate->__construct($username)
							$carrier_class = 'UspsRate';
							$this->carrier_rate_objects[$carrier_name] = new $carrier_class($shipping_configs['USPS API Username']);
							// UspsRate->getRate($service, $firstClassMailType, $sendFromZip, $sendToZip, $pounds, $ounces, $containerSize, $machinable = "true")
							$lbs = floor($params['total_product_weight'] / 16);
							$oz = $params['total_product_weight'] % 16;

							if ($user_address['country']['iso_code'] == 'US') {
								$live_rate_options[$carrier_name] = $this->carrier_rate_objects[$carrier_name]->getRate('ALL', '', $shop_address['postcode'], $user_address['address']['zip'], $lbs, $oz, 'REGULAR');
							}
							else {
								$live_rate_options[$carrier_name] = $this->carrier_rate_objects[$carrier_name]->getIntlRate($params['total_products'], 'Package', $country['name'], $lbs, $oz, 'REGULAR');
							}
							
							//echo'<pre>';print_r($live_rate_options[$carrier_name]);die('Carrier_Controller.php:usps');
							
						}
					}

					// Loop through live rates to set price
					if (!empty($live_rate_options[$carrier_name]) && is_array($live_rate_options[$carrier_name])) {
						//echo $carrier['delay'] . "\n";
						foreach ($live_rate_options[$carrier_name] as $option) {
							if ($option['service'] == $carrier['code']) {
								$carriers[$i]['price'] = $option['rate'];

								// Set flag to let us know the live rate was returned (so we can filter out ones that didn't)
								$carriers[$i]['live_rate'] = $option['rate'];
							}
						}
					}
				}
			}
		}
		
		// Filter out live rate options that didn't return a live rate
		$carriers = $this->filter_failed_rates($carriers);

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

	private function filter_failed_rates($carriers) {
		$carriers_return = array();

		foreach($carriers as $i => $carrier) {
			if (!$carrier['is_live_rate']) {
				array_push($carriers_return, $carrier);
			}
			else {
				if (!empty($carrier['live_rate'])) {
					array_push($carriers_return, $carrier);
				}
			}
		}

		return $carriers_return;
	}
	
	public function usps_shipping_label($params = array()) {
		$this->load('Carrier');
		$this->load('Configuration');
		$this->load('Country');
		$this->load('Shop');
		$this->load('Config', ADMIN_API_HOST, ADMIN_API_USER, ADMIN_API_PASSWORD, ADMIN_API_DATABASE);
		$this->load('Order_Shipment');

		$data = array();

		$validate_names = array(
			'id_shop' => NULL
			, 'shipping_address_id' => NULL
			, 'total_product_weight' => NULL
		);

		$validate_params = array_merge($validate_names, $params);

		// Validations
		$input_validations = array(
			'user_id' => array(
				'label' => 'User ID'
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
			, 'id_lang' => array(
				'label' => 'Lang ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
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
			, 'shipping_address_id' => array(
				'label' => 'Shipping Address ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
		);
		$this->Validate->add_many($input_validations, $validate_params, true);
		$this->Validate->run();
		
		$is_return_label = !empty($params['return-label']) && $params['return-label'] === true ? true : false;
		
		require DR . '/includes/php/classes/shipping/usps/label.php';
		
		// Shipping configs
		$shipping_configs = $this->Config->get_configs_by_section('Shipping APIs');

		// Get shop store address
		$shop_address = $this->Shop->get_primary_shop_store_address($params['id_shop']);
		if (empty($shop_address)) {
			_Model::$Exception_Helper->request_failed_exception('Shop address not found.');
		}
		
		$shop = $this->Shop->get_row(
			array(
				'id_shop' => $params['id_shop']
			)
		);
		
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
		
		$country = $this->Country->get_country($user_address['country']['id_country'], $params['id_lang']);
		if (empty($country)) {
			_Model::$Exception_Helper->request_failed_exception('Country not found.');
		}
			
		//echo '<pre>'; print_r(USPSLabel()); echo '</pre>';
		// $shop_address['country']
		// $username, $from, $to, $ounces
		//, 'CountryCode' => $user_address['country']['iso_code']
		
		if ($is_return_label && $params['is_intl'] === true) {
			_Model::$Exception_Helper->request_failed_exception('Cannot ship via USPS from this country.');
		}
		
		if ($params['is_intl'] === true) {
			if (in_array($params['service_label_code'], array('Express'))) {
				$USPSResponse = USPSIntlExpressLabel(
					$shipping_configs['USPS API Username']
					, array(
						'name' => $shop['name']
						, 'address' => $shop_address['address1']
						, 'address2' => $shop_address['address2']
						, 'city' => $shop_address['city']
						, 'state' => $shop_address['state']
						, 'zip' => $shop_address['postcode']
						, 'phone' => $shop_address['phone']
					)
					, array(
						'first_name' => $user_address['address']['first_name']
						, 'last_name' => $user_address['address']['last_name']
						, 'address' => $user_address['address']['street']
						, 'address2' => $user_address['address']['street_2']
						, 'city' => $user_address['address']['city']
						, 'state' => $user_address['state']['iso_code']
						, 'zip' => $user_address['address']['zip']
						, 'phone' => $user_address['address']['phone']
						, 'country' => $country['name']
					)
					, $params['items']
					, $params['total_product_weight'] // ounces
					, $params['service_label_code']
				);
				
				if (isset($USPSResponse['Error'])) {
					_Model::$Exception_Helper->request_failed_exception($USPSResponse['Error']['Description']['VALUE']);
				}
				
				$track_url = 'https://tools.usps.com/go/TrackConfirmAction_input?qtc_tLabels1=' . $USPSResponse['ExpressMailIntlResponse']['BarcodeNumber']['VALUE'];
				$tracking_number = $USPSResponse['ExpressMailIntlResponse']['BarcodeNumber']['VALUE'];
				$label = $USPSResponse['ExpressMailIntlResponse']['LabelImage']['VALUE'];
			
			}
			else if (in_array($params['service_label_code'], array('Priority'))) {
				$USPSResponse = USPSIntlPriorityLabel(
					$shipping_configs['USPS API Username']
					, array(
						'name' => $shop['name']
						, 'address' => $shop_address['address1']
						, 'address2' => $shop_address['address2']
						, 'city' => $shop_address['city']
						, 'state' => $shop_address['state']
						, 'zip' => $shop_address['postcode']
						, 'phone' => $shop_address['phone']
					)
					, array(
						'first_name' => $user_address['address']['first_name']
						, 'last_name' => $user_address['address']['last_name']
						, 'address' => $user_address['address']['street']
						, 'address2' => $user_address['address']['street_2']
						, 'city' => $user_address['address']['city']
						, 'state' => $user_address['state']['iso_code']
						, 'zip' => $user_address['address']['zip']
						, 'phone' => $user_address['address']['phone']
						, 'country' => $country['name']
					)
					, $params['items']
					, $params['total_product_weight'] // ounces
					, $params['service_label_code']
				);
				
				if (isset($USPSResponse['Error'])) {
					_Model::$Exception_Helper->request_failed_exception($USPSResponse['Error']['Description']['VALUE']);
				}
				
				$track_url = 'https://tools.usps.com/go/TrackConfirmAction_input?qtc_tLabels1=' . $USPSResponse['PriorityMailIntlResponse']['BarcodeNumber']['VALUE'];
				$tracking_number = $USPSResponse['PriorityMailIntlResponse']['BarcodeNumber']['VALUE'];
				$label = $USPSResponse['PriorityMailIntlResponse']['LabelImage']['VALUE'];
			
			}
			else if (in_array($params['service_label_code'], array('First Class'))) {
				$USPSResponse = USPSIntlFirstClassLabel(
					$shipping_configs['USPS API Username']
					, array(
						'name' => $shop['name']
						, 'address' => $shop_address['address1']
						, 'address2' => $shop_address['address2']
						, 'city' => $shop_address['city']
						, 'state' => $shop_address['state']
						, 'zip' => $shop_address['postcode']
						, 'phone' => $shop_address['phone']
					)
					, array(
						'first_name' => $user_address['address']['first_name']
						, 'last_name' => $user_address['address']['last_name']
						, 'address' => $user_address['address']['street']
						, 'address2' => $user_address['address']['street_2']
						, 'city' => $user_address['address']['city']
						, 'state' => $user_address['state']['iso_code']
						, 'zip' => $user_address['address']['zip']
						, 'phone' => $user_address['address']['phone']
						, 'country' => $country['name']
					)
					, $params['items']
					, $params['total_product_weight'] // ounces
					, $params['service_label_code']
				);
				
				if (isset($USPSResponse['Error'])) {
					_Model::$Exception_Helper->request_failed_exception($USPSResponse['Error']['Description']['VALUE']);
				}
				
				$track_url = 'https://tools.usps.com/go/TrackConfirmAction_input?qtc_tLabels1=' . $USPSResponse['FirstClassMailIntlResponse']['BarcodeNumber']['VALUE'];
				$tracking_number = $USPSResponse['FirstClassMailIntlResponse']['BarcodeNumber']['VALUE'];
				$label = $USPSResponse['FirstClassMailIntlResponse']['LabelImage']['VALUE'];
			
			}
			else {
				_Model::$Exception_Helper->request_failed_exception('Invalid service label code: ' . $params['service_label_code']);
			}
		}
		else {
			if (in_array($params['service_label_code'], array('Priority', 'First Class', 'Standard Post', 'Media Mail', 'Library Mail'))) {
				 
				 $shipper_info = array(
					'name' => $shop['name']
					, 'address' => $shop_address['address1']
					, 'address2' => $shop_address['address2']
					, 'city' => $shop_address['city']
					, 'state' => $shop_address['state']
					, 'zip' => $shop_address['postcode']
					, 'phone' => $shop_address['phone']
				);
				
				$recipient_info = array(
					'name' => $user_address['address']['first_name'] . ' ' . $user_address['address']['last_name']
					, 'address' => $user_address['address']['street']
					, 'address2' => $user_address['address']['street_2']
					, 'city' => $user_address['address']['city']
					, 'state' => $user_address['state']['iso_code']
					, 'zip' => $user_address['address']['zip']
					, 'phone' => $user_address['address']['phone']
				);
				 
				 if ($is_return_label) {
					 $temp_shipper_info = $shipper_info;
					 $shipper_info = $recipient_info;
					 $recipient_info = $temp_shipper_info;
				 }
				
				 $USPSResponse = USPSLabel(
					$shipping_configs['USPS API Username']
					, $shipper_info
					, $recipient_info
					, $params['total_product_weight'] // ounces
					, $params['service_label_code']
				);
				
				if (isset($USPSResponse['Error'])) {
					_Model::$Exception_Helper->request_failed_exception($USPSResponse['Error']['Description']['VALUE']);
				}
				
				$track_url = 'https://tools.usps.com/go/TrackConfirmAction_input?qtc_tLabels1=' . $USPSResponse['DeliveryConfirmationV4.0Response']['DeliveryConfirmationNumber']['VALUE'];
				$tracking_number = $USPSResponse['DeliveryConfirmationV4.0Response']['DeliveryConfirmationNumber']['VALUE'];
				$label = $USPSResponse['DeliveryConfirmationV4.0Response']['DeliveryConfirmationLabel']['VALUE'];
			
			}
			else if ($params['service_label_code'] == 'Express') {
				$shipper_info = array(
					'name' => $shop['name']
					, 'address' => $shop_address['address1']
					, 'address2' => $shop_address['address2']
					, 'city' => $shop_address['city']
					, 'state' => $shop_address['state']
					, 'zip' => $shop_address['postcode']
					, 'phone' => $shop_address['phone']
				);
				
				$recipient_info = array(
					'first_name' => $user_address['address']['first_name']
					, 'last_name' => $user_address['address']['last_name']
					, 'address' => $user_address['address']['street']
					, 'address2' => $user_address['address']['street_2']
					, 'city' => $user_address['address']['city']
					, 'state' => $user_address['state']['iso_code']
					, 'zip' => $user_address['address']['zip']
					, 'phone' => $user_address['address']['phone']
				);
				
				if ($is_return_label) {
					 $temp_shipper_info = $shipper_info;
					 $shipper_info = $recipient_info;
					 $recipient_info = $temp_shipper_info;
				 }
				
				$USPSResponse = USPSExpressLabel(
					$shipping_configs['USPS API Username']
					, $shipper_info
					, $recipient_info
					, $params['total_product_weight'] // ounces
					, $params['service_label_code']
				);
				
				if (isset($USPSResponse['Error'])) {
					_Model::$Exception_Helper->request_failed_exception($USPSResponse['Error']['Description']['VALUE']);
				}
				
				$track_url = 'https://tools.usps.com/go/TrackConfirmAction_input?qtc_tLabels1=' . $USPSResponse['ExpressMailLabelResponse']['EMConfirmationNumber']['VALUE'];
				$tracking_number = $USPSResponse['ExpressMailLabelResponse']['EMConfirmationNumber']['VALUE'];
				$label = $USPSResponse['ExpressMailLabelResponse']['EMLabel']['VALUE'];
			}
			else {
				_Model::$Exception_Helper->request_failed_exception('Invalid service label code: ' . $params['service_label_code']);
			}
		}
		
		if (isset($params['update-tracking-info']) && $params['update-tracking-info'] === true) {
			$this->Order_Shipment->save(
				array(
					'tracking_number' => $tracking_number
					, 'url' => $track_url
					, 'id_order_shipment' => $params['id_order_shipment']
				)
			);
		}
		
		$data = array(
			'tracking_number' => $tracking_number
			, 'label' => $label
			, 'tracking_url' => $track_url
		);
		
		return static::wrap_result(true, $data);
	}
	
	public function ups_shipping_label($params = array()) {
		$this->load('Carrier');
		$this->load('Configuration');
		$this->load('Country');
		$this->load('Shop');
		$this->load('Config', ADMIN_API_HOST, ADMIN_API_USER, ADMIN_API_PASSWORD, ADMIN_API_DATABASE);
		$this->load('Order_Shipment');

		$data = array();

		$validate_names = array(
			'id_shop' => NULL
			, 'shipping_address_id' => NULL
			, 'total_product_weight' => NULL
		);

		$validate_params = array_merge($validate_names, $params);

		// Validations
		$input_validations = array(
			'user_id' => array(
				'label' => 'User ID'
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
			, 'id_lang' => array(
				'label' => 'Lang ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'id_order_shipment' => array(
				'label' => 'Order Shipment ID'
				, 'rules' => array(
					'is_int' => NULL
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
			, 'shipping_address_id' => array(
				'label' => 'Shipping Address ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'service_code' => array(
				'label' => 'Service Code'
				, 'rules' => array(
					'is_set' => NULL
				)
			)
			, 'service_name' => array(
				'label' => 'Service Name'
				, 'rules' => array(
					'is_set' => NULL
				)
			)
		);
		$this->Validate->add_many($input_validations, $validate_params, true);
		$this->Validate->run();
		
		$is_return_label = !empty($params['return-label']) && $params['return-label'] === true ? true : false;
		
		require DR . '/includes/php/classes/shipping/ups/label.php';
		
		// Shipping configs
		$shipping_configs = $this->Config->get_configs_by_section('Shipping APIs');

		// Get shop store address
		$shop_address = $this->Shop->get_primary_shop_store_address($params['id_shop']);
		if (empty($shop_address)) {
			_Model::$Exception_Helper->request_failed_exception('Shop address not found.');
		}
		
		$shop = $this->Shop->get_row(
			array(
				'id_shop' => $params['id_shop']
			)
		);

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
		
		$country = $this->Country->get_country($user_address['country']['id_country'], $params['id_lang']);
		if (empty($country)) {
			_Model::$Exception_Helper->request_failed_exception('Country not found.');
		}
			
		//echo '<pre>'; print_r(USPSLabel()); echo '</pre>';
		// $shop_address['country']
		// $username, $from, $to, $ounces
		//, 'CountryCode' => $user_address['country']['iso_code']
		
		$shipper_info = array(
			'name' => $shop['name']
			, 'address' => $shop_address['address1']
			, 'address2' => $shop_address['address2']
			, 'city' => $shop_address['city']
			, 'state' => $shop_address['state']
			, 'zip' => $shop_address['postcode']
			, 'phone' => $shop_address['phone']
			, 'country' => $shop_address['country']
		);
		
		$recipient_info = array(
			'name' => $user_address['address']['first_name'] . ' ' . $user_address['address']['last_name']
			, 'address' => $user_address['address']['street']
			, 'address2' => $user_address['address']['street_2']
			, 'city' => $user_address['address']['city']
			, 'state' => $user_address['state']['iso_code']
			, 'zip' => $user_address['address']['zip']
			, 'phone' => $user_address['address']['phone']
			, 'country' => $user_address['country']['iso_code']
		);
		
		if ($is_return_label) { // not neccessary
			//$temp_shipper_info = $shipper_info;
			//$shipper_info = $recipient_info;
			//$recipient_info = $temp_shipper_info;
		}
		
		$UPSLabel = new UpsLable($shipping_configs['UPS API Access Key'], $shipping_configs['UPS API Username'], $shipping_configs['UPS API Password'], $shipping_configs['UPS API Account Number']
		, $shipper_info
		, $is_return_label
		, false
		);
		
		$result = $UPSLabel->shipConfirm(
		$recipient_info
		, $params['service_code']
		, $params['service_name']
		, ($params['total_product_weight'] / 16));
		
		if (!$result['success']) {
			_Model::$Exception_Helper->request_failed_exception($result['error']);
		}
		
		$result = $UPSLabel->shipAccept($result['data']['SHIPMENTCONFIRMRESPONSE']['SHIPMENTDIGEST']);
		if (!$result['success']) {
			_Model::$Exception_Helper->request_failed_exception($result['error']);
		}
		
		$track_url = 'http://wwwapps.ups.com/WebTracking/track?track=yes&trackNums=' . $result['data']['SHIPMENTACCEPTRESPONSE']['SHIPMENTRESULTS']['PACKAGERESULTS']['TRACKINGNUMBER'];
		if (isset($params['update-tracking-info']) && $params['update-tracking-info'] === true) {
			$this->Order_Shipment->save(
				array(
					'tracking_number' => $result['data']['SHIPMENTACCEPTRESPONSE']['SHIPMENTRESULTS']['PACKAGERESULTS']['TRACKINGNUMBER']
					, 'url' => $track_url
					, 'id_order_shipment' => $params['id_order_shipment']
				)
			);
		}
		
		$data = array(
			'tracking_number' => $result['data']['SHIPMENTACCEPTRESPONSE']['SHIPMENTRESULTS']['PACKAGERESULTS']['TRACKINGNUMBER']
			, 'label' => $result['data']['SHIPMENTACCEPTRESPONSE']['SHIPMENTRESULTS']['PACKAGERESULTS']['LABELIMAGE']['GRAPHICIMAGE']
			, 'tracking_url' => $track_url
		);
		
		return static::wrap_result(true, $data);
	}
	
	public function fedex_shipping_label($params = array()) {
		$this->load('Carrier');
		$this->load('Configuration');
		$this->load('Country');
		$this->load('Shop');
		$this->load('Config', ADMIN_API_HOST, ADMIN_API_USER, ADMIN_API_PASSWORD, ADMIN_API_DATABASE);
		$this->load('Order_Shipment');

		$data = array();

		$validate_names = array(
			'id_shop' => NULL
			, 'shipping_address_id' => NULL
			, 'total_product_weight' => NULL
		);

		$validate_params = array_merge($validate_names, $params);

		// Validations
		$input_validations = array(
			'user_id' => array(
				'label' => 'User ID'
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
			, 'id_lang' => array(
				'label' => 'Lang ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'id_order_shipment' => array(
				'label' => 'Order Shipment ID'
				, 'rules' => array(
					'is_int' => NULL
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
			, 'shipping_address_id' => array(
				'label' => 'Shipping Address ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'service_code' => array(
				'label' => 'Service Code'
				, 'rules' => array(
					'is_set' => NULL
				)
			)
			, 'service_name' => array(
				'label' => 'Service Name'
				, 'rules' => array(
					'is_set' => NULL
				)
			)
		);
		$this->Validate->add_many($input_validations, $validate_params, true);
		$this->Validate->run();
		
		$is_return_label = !empty($params['return-label']) && $params['return-label'] === true ? true : false;
		
		require DR . '/includes/php/classes/shipping/fedex/label.php';
		
		// Shipping configs
		$shipping_configs = $this->Config->get_configs_by_section('Shipping APIs');

		// Get shop store address
		$shop_address = $this->Shop->get_primary_shop_store_address($params['id_shop']);
		if (empty($shop_address)) {
			_Model::$Exception_Helper->request_failed_exception('Shop address not found.');
		}
		
		$shop = $this->Shop->get_row(
			array(
				'id_shop' => $params['id_shop']
			)
		);

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
		
		$country = $this->Country->get_country($user_address['country']['id_country'], $params['id_lang']);
		if (empty($country)) {
			_Model::$Exception_Helper->request_failed_exception('Country not found.');
		}
			
		$shippingStateField = ($user_address['country']['iso_code'] == 'US') ? 'state' : 'province';
		$accountInfo = array('key' => $shipping_configs['FedEx API Authentication Key']
							 , 'password' => $shipping_configs['FedEx API Password']
							 , 'account' => $shipping_configs['FedEx API Account Number']
							 , 'billingAccount' => $shipping_configs['FedEx API Account Number']
							 , 'meter' => $shipping_configs['FedEx API Meter Number']
							 );
		$fedex = new FedexLabel($accountInfo, 'ship');
		
		$recipient = array('name' => $user_address['address']['first_name'] . ' ' . $user_address['address']['last_name']
						   , 'company' => NULL
						   , 'phone' => $user_address['address']['phone']
						   , 'street' => $user_address['address']['street']
						   , 'street2' => $user_address['address']['street_2']
						   , 'city' => $user_address['address']['city']
						   , 'stateOrProvince' => $user_address['state']['iso_code']
						   , 'postalCode' => $user_address['address']['zip']
						   , 'country' => $user_address['country']['iso_code']
						   , 'residential' => '1'
						   );
		
		
		$shipper = array('name' => $shop['name']
						 , 'company' => $shop['name']
						 , 'phone' => $shop_address['phone']
						 , 'street' => $shop_address['address1']
						 , 'street2' => $shop_address['address2']
						 , 'city' => $shop_address['city']
						 , 'stateOrProvince' => $shop_address['state']
						 , 'postalCode' => $shop_address['postcode']
						 , 'country' => $shop_address['country']
						 , 'residential' => '0'
						 );
		
		if ($is_return_label) {
			$temp_recipient = $recipient;
			$recipient = $shipper;
			$shipper = $temp_recipient;
		}
			 
		$fedex->setRecipient($recipient);
		$fedex->setShipper($shipper);
		
		$package = array('weight' => ($params['total_product_weight'] / 16)
						// , 'length' => $package['length']
						// , 'width' => $package['width']
						// , 'height' => $package['height']
						 );
		$fedex->setPackage($package);
		
		$fedex->setServiceType($params['service_code']);
		
		if ($user_address['country']['iso_code'] != 'US') { // set custom clearance details for international shipments
			$fedex->setCustomsValue($params['items']);
		}
		
		$fedex->processShipment();
		
		if (!$fedex->error) { // everything went smoothly
			$track_url = 'http://www.fedex.com/fedextrack/?tracknumbers=' . $fedex->trackingNumber;
			if (isset($params['update-tracking-info']) && $params['update-tracking-info'] === true) {
				$this->Order_Shipment->save(
					array(
						'tracking_number' => $fedex->trackingNumber
						, 'url' => $track_url
						, 'id_order_shipment' => $params['id_order_shipment']
					)
				);
			}
		
			$data = array(
				'tracking_number' => $fedex->trackingNumber
				, 'label' => $fedex->response->CompletedShipmentDetail->CompletedPackageDetails->Label->Parts->Image
				, 'tracking_url' => $track_url
			);
			
			return static::wrap_result(true, $data);
		}
		else { 
			$errors = array();
			if (!empty($fedex->notifications)){ // notify user of errors
				foreach ($fedex->notifications as $notification) {
					if ($notification['Severity'] != 'ERROR') {
						continue;
					}
					array_push($errors, 'FedEx Shipping API Error: ' . $notification['Message']);
				}
			}
		}
		
		return static::wrap_result(false, NULL, _Model::$Status_Code->get_status_code_request_failed(), $errors);
	}
	
}

?>
<?
class Address_Controller extends _Controller {

	public function get_hq_user_address_info($params = array()) {
		$this->load('Country');
		$this->load('State');

		$calls = array(
			'get_user_address' => array(
				'user_id'		=> $params['user_id'],
				'address_id' 	=> $params['address_id']
			)
		);

		$HQ_API = new API(API_KEY_DEVELOPER, PRIVATE_KEY_DEVELOPER);
		$result = $HQ_API->rest_api_request('address', $calls);
		$result = json_decode($result, true);

		if (!$result['success'] || !$result['data']['get_user_address']['success']) {
			_Model::$Exception_Helper->request_failed_exception('API request failed.');
		}
		else if (empty($result['data']['get_user_address']['data'])) {
			return NULL;
		}
		else {
			// Get state and country ids
			$country = $this->Country->get_row(
				array(
					'iso_code' => $result['data']['get_user_address']['data']['country']
				)
			);

			if (empty($country)) {
				_Model::$Exception_Helper->request_failed_exception('Country not found.');
			}

			$state = $this->State->get_row(
				array(
					'iso_code' => $result['data']['get_user_address']['data']['state'],
					'id_country' => $country['id_country']
				)
			);

			/*if (empty($state) && strtolower($result['data']['get_user_address']['data']['state']) != 'n/a') {
				_Model::$Exception_Helper->request_failed_exception('State not found.');
			}*/

			//$id_country = $country['id_country'];
			//$id_state = $state['id_state'];
			$zip = $result['data']['get_user_address']['data']['zip'];
			$id_zone = $country['id_zone'];
		}

		return array(
			'country' => $country
			, 'state' => $state
			, 'address' => $result['data']['get_user_address']['data']
		);

	}

	public function get_countries($params = array()) {
		$this->load('Country');

		// Validations
		$input_validations = array(
			'id_lang' => array(
				'label' => 'Language ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
		);

		$this->Validate->add_many($input_validations, $params, true);
		$this->Validate->run();

		$countries = $this->Country->get_countries($params['id_lang']);

		return static::wrap_result(true, $countries);
	}

	public function get_states($params = array()) {
		$this->load('State');

		// Validations
		$input_validations = array(
			'id_country' => array(
				'label' => 'Country ID',
				'rules' => array(
					'is_set' => NULL,
					'is_int' => NULL
				)
			)
		);

		$this->Validate->add_many($input_validations, $params, true);
		$this->Validate->run();

		$states = $this->State->get_states($params['id_country']);

		return static::wrap_result(true, $states);
	}

	public function get_states_by_country_iso_code($params = array()) {
		$this->load('State');

		// Validations
		$input_validations = array(
			'iso_code' => array(
				'label' => 'ISO_CODE'
				, 'rules' => array(
					'is_set' => NULL
				)
			)
		);

		$this->Validate->add_many($input_validations, $params, true);
		$this->Validate->run();

		$states = $this->State->get_states_by_country_iso_code($params['iso_code']);

		return static::wrap_result(true, $states);
	}
}

?>
<?
class Address_Controller extends _Controller {
	
	public function get_hq_user_address_info($params = array()) {
		$this->load('Country');
		$this->load('State');
		
		$calls = array(	
			'get_user_address' => array(
				'user_id'		=> $params['user_id']
				, 'address_id' 	=> $params['address_id']
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
					'iso_code' => $result['data']['get_user_address']['data']['state']
					, 'id_country' => $country['id_country']
				)
			);
			
			if (empty($state)) {
				_Model::$Exception_Helper->request_failed_exception('State not found.');
			}
			
			$id_country = $country['id_country'];
			$id_state = $state['id_state'];
			$zip = $result['data']['get_user_address']['data']['zip'];
			$id_zone = $country['id_zone'];
		}

		return array(
			'country' => $country
			, 'state' => $state
			, 'address' => $result['data']['get_user_address']['data']
		);

	}

	
}

?>
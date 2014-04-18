<?
class Customer_Controller extends _Controller {
	
	public function save_customer($params = array()) {
		$this->load('Customer');
        $this->load('Store_Credit');
		
		$data = array();
		
		$is_insert = !empty($params['id_customer']) && is_numeric($params['id_customer']) ? false : true;
		
		// Validations
		$input_validations = array(
			'user_id' => array(
				'label' => 'User Id'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'firstname' => array(
				'label' => 'First Name'
				, 'rules' => array(
					//'is_set' => NULL
				)
			)
			, 'lastname' => array(
				'label' => 'Last Name'
				, 'rules' => array(
					//'is_set' => NULL
				)
			)
			, 'username' => array(
				'label' => 'Username'
				, 'rules' => array(
					'is_set' => NULL
				)
			)
			, 'email' => array(
				'label' => 'Email'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_email' => NULL
				)
			)
		);
		$this->Validate->add_many($input_validations, $params, $is_insert);
		$this->Validate->run();
		
		$customer = array(
			'user_id' => $params['user_id']
			, 'firstname' => $params['firstname']
			, 'lastname' => $params['lastname']
			, 'email' => $params['email']
			, 'username' => $params['username']
		);
		
		if (!$is_insert) {
			$customer['id_customer'] = $params['id_customer'];
		}
		
		$data['id_customer'] = $this->Customer->save($customer);
        $data = $this->Store_Credit->add_user_credit($params['user_id'], 10);
		
		return static::wrap_result(true, $data);
	}

	public function get_customer($params = array()) {
		$this->load('Customer');
		
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
		
		$data = $this->Customer->get_row(
			array(
				'user_id' => $params['user_id']
			)
		);
		
		return static::wrap_result(true, $data);
		
	}
}

?>
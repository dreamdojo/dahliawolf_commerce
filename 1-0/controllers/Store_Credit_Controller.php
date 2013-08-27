<?
class Store_Credit_Controller extends _Controller {
	public function get_user_credits($params = array()) {
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
		);
		$this->Validate->add_many($input_validations, $params, true);
		$this->Validate->run();

		$user_credits = $this->Store_Credit->get_rows(
			array(
				'user_id' => $params['user_id']
			)
		);

		return static::wrap_result(true, $user_credits);
	}

	public function get_user_credits_total($params = array()) {
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
		);
		$this->Validate->add_many($input_validations, $params, true);
		$this->Validate->run();

		$total_credits = $this->Store_Credit->get_user_total($params['user_id']);

		return static::wrap_result(true, $total_credits);
	}
}
?>
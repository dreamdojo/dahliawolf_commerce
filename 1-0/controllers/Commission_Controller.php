<?
class Commission_Controller extends _Controller {
	public function get_user_commissions($params = array()) {
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
		);
		$this->Validate->add_many($input_validations, $params, true);
		$this->Validate->run();

		$user_commissions = $this->Commission->get_rows(
			array(
				'user_id' => $params['user_id']
			)
		);

		return static::wrap_result(true, $user_commissions);
	}

	public function get_user_commissions_total($params = array()) {
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
		);
		$this->Validate->add_many($input_validations, $params, true);
		$this->Validate->run();

		$total_commissions = $this->Commission->get_user_total($params['user_id']);

		return static::wrap_result(true, $total_commissions);
	}
}
?>
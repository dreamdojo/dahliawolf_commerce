<?
class Shop_Controller extends _Controller {
	public function get_shop($params = array()) {
		$this->load('Shop');

		// Validations
		$input_validations = array(
			'id_shop' => array(
				'label' => 'Shop ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
		);
		$this->Validate->add_many($input_validations, $params, true);
		$this->Validate->run();

		$shop = $this->Shop->get_row(
			array(
				'id_shop' => $params['id_shop']
			)
		);

		return static::wrap_result(true, $shop);
	}
}
?>
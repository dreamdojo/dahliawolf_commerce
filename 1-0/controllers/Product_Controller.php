<?
class Product_Controller extends _Controller {
	
	public function get_product_details($params = array()) {
		$this->load('Product');
		
		$data = array();
		$error = NULL;
		
		$validate_names = array(
			'id_product' => NULL
			, 'id_shop' => NULL
			, 'id_lang' => NULL
			, 'user_id' => NULL
		);
		
		$validate_params = array_merge($validate_names, $params);
		
		// Validations
		$input_validations = array(
			'id_product' => array(
				'label' => 'Product Id'
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
			, 'user_id' => array(
				'label' => 'User Id'
				, 'rules' => array(
					'is_int' => NULL
				)
			)
		);
		$this->Validate->add_many($input_validations, $validate_params, true);
		$this->Validate->run();
		
		$user_id = !empty($params['user_id']) ? $params['user_id'] : NULL;
		
		$data['product'] = $this->Product->get_product($params['id_product'], $params['id_shop'], $params['id_lang'], $user_id);
		
		if (empty($data['product'])) {
			$error = 'Product not found.';
			
			return static::wrap_result(false, NULL, _Model::$Status_Code->get_status_code_request_failed(), $error);
		}
		
		$data['combinations'] = $this->Product->get_product_combinations($params['id_product'], $params['id_shop'], $params['id_lang']);
		
		$data['features'] = $this->Product->get_product_features($params['id_product'], $params['id_shop'], $params['id_lang']);
		
		$data['tags'] = $this->Product->get_product_tags($params['id_product'], $params['id_shop'], $params['id_lang']);
		
		$data['comments'] = $this->Product->get_product_comments($params['id_product'], $params['id_shop'], $params['id_lang']);
		
		$data['files'] = $this->Product->get_product_files($params['id_product'], $params['id_shop'], $params['id_lang']);
		
		return static::wrap_result(true, $data);
		
	}

	public function get_products($params = array()) {
		$this->load('Product');
		
		$validate_names = array(
			'id_shop' => NULL
			, 'id_lang' => NULL
			, 'user_id' => NULL
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
			, 'user_id' => array(
				'label' => 'User Id'
				, 'rules' => array(
					'is_int' => NULL
				)
			)
		);
		
		$this->Validate->add_many($input_validations, $validate_params, true);
		$this->Validate->run();
		
		$user_id = !empty($params['user_id']) ? $params['user_id'] : NULL;

		$data = $this->Product->get_products($params['id_shop'], $params['id_lang'], $user_id);
		
		return static::wrap_result(true, $data);
	}

}

?>
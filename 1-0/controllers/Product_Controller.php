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

        $logger = new Jk_Logger(APP_PATH . 'logs/product.log');
        $logger->LogInfo("request params: " . var_export($params,true));


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
		$viewer_user_id = !empty($params['viewer_user_id']) ? $params['viewer_user_id'] : NULL;

		$data = $this->Product->get_products($params['id_shop'], $params['id_lang'], $user_id, $viewer_user_id);

		return static::wrap_result(true, $data);
	}

	public function set_initial_user_id_from_posting_id($params = array()) {
		$this->load('Product');

		// Validations
		$input_validations = array(
			'posting_id' => array(
				'label' => 'Posting ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
		);

		$this->Validate->add_many($input_validations, $params, true);
		$this->Validate->run();

		$product = $this->Product->get_posting_product_user_id($params['posting_id']);

		if (!empty($product)) {
			if (empty($product['product_user_id'])) {
				$this->Product->set_user_id($product['id_product'], $product['user_id']);
			}
		}

		//return static::wrap_result(true, $data);
	}
}

?>
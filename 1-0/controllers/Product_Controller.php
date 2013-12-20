<?
/**
 * @property Product Product
 */
class Product_Controller extends _Controller {

	public function get_product_details($params = array()) {
		$this->load('Product', DB_API_HOST, DB_API_USER, DB_API_PASSWORD, DB_API_DATABASE);

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
			, 'user_id' => array(
				'label' => 'User Id'
				, 'rules' => array(
					'is_int' => NULL
				)
			)
		);

        $id_shop = $params['id_shop']? $params['id_shop'] : 3;
        $id_lang = $params['id_lang']? $params['id_lang'] : 1;


		$this->Validate->add_many($input_validations, $validate_params, true);
		$this->Validate->run();

		$user_id = !empty($params['user_id']) ? $params['user_id'] : NULL;
        $viewer_user_id = !empty($params['viewer_user_id']) ? $params['viewer_user_id'] : NULL;

		$data['product'] = $this->Product->get_product($params['id_product'], $id_shop, $id_lang, $user_id, $viewer_user_id);

		if (empty($data['product'])) {
			$error = 'Product not found.';

			return static::wrap_result(false, NULL, _Model::$Status_Code->get_status_code_request_failed(), $error);
		}

        if($data['product'])
        {
            $data['combinations'] = $this->Product->get_product_combinations($data['product']['id_product'], $id_shop, $id_lang);
            $data['features'] = $this->Product->get_product_features($data['product']['id_product'], $id_shop, $id_lang);
            $data['tags'] = $this->Product->get_product_tags($data['product']['id_product'], $id_shop, $id_lang);
            $data['comments'] = $this->Product->get_product_comments($data['product']['id_product'], $id_shop, $id_lang);
            $data['files'] = $this->Product->get_product_files($data['product']['id_product'], $id_shop, $id_lang);
        }


        $product_view = new Product_View();

        $view_data = array(
            'product_id' => $data['product']['id_product'],
            'user_id' => $data['product']['user_id'],
        );

        if(!empty($params['viewer_user_id'])) $view_data['viewer_user_id'] =  $params['viewer_user_id'];

        $product_view->addView($view_data);



		return static::wrap_result(true, $data);

	}

	public function get_products($request_params = array())
    {
        $logger = new Jk_Logger(APP_PATH . 'logs/product.log');
        $logger->LogInfo("request params: " . var_export($request_params,true));

		$this->load('Product');

		$validate_names = array(
			'id_shop' => NULL,
			'id_lang' => NULL,
			'user_id' => NULL,
		);

		$validate_params = array_merge($validate_names, $request_params);

		// Validations
		$input_validations = array(
		    /*
			, 'user_id' => array(
				'label' => 'User Id'
				, 'rules' => array(
					'is_int' => NULL
				)
			)*/
		);

        $id_shop = $request_params['id_shop']? $request_params['id_shop'] : 3;
        $id_lang = $request_params['id_lang']? $request_params['id_lang'] : 1;

        $request_params['filter_min_price'] = $request_params['filter_min_price']? floatval( $request_params['filter_min_price'] ): 0;
        $request_params['filter_max_price'] = $request_params['filter_max_price']? floatval( $request_params['filter_max_price'] ): 999999;

        /*
		$this->Validate->add_many($input_validations, $validate_params, true);
		$this->Validate->run();
        */

		$user_id = !empty($request_params['user_id']) ? $request_params['user_id'] : NULL;
		$viewer_user_id = !empty($request_params['viewer_user_id']) ? $request_params['viewer_user_id'] : NULL;

		$data = $this->Product->get_products($id_shop, $id_lang, $request_params, $user_id, $viewer_user_id);

		return static::wrap_result(true, $data);
	}


	public function get_category_products($params = array()) {

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
			'id_category' => array(
				'label' => 'Category Id',
				 'rules' => array(
                    'is_set' => NULL,
                    'is_int' => NULL
				)
			)
		);

		$this->Validate->add_many($input_validations, $validate_params, true);
		$this->Validate->run();

        $id_shop = $params['id_shop']? $params['id_shop'] : 3;
        $id_lang = $params['id_lang']? $params['id_lang'] : 1;

		$user_id = !empty($params['user_id']) ? $params['user_id'] : NULL;
        $viewer_user_id = !empty($request_params['viewer_user_id']) ? $request_params['viewer_user_id'] : NULL;


		$data = $this->Product->get_products_in_category($params, $id_shop, $id_lang, $viewer_user_id, $params);

		return static::wrap_result(true, $data);
	}


    public function get_user_comissions($params = array())
    {
        $logger = new Jk_Logger(APP_PATH . 'logs/product.log');
        $logger->LogInfo("request params: " . var_export($params,true));


		$this->load('Product');

		$validate_names = array(
			'id_shop' => NULL,
			'id_lang' => NULL,
			'user_id' => NULL,
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

	public function add_posting_product($params = array()) {
		$this->load('Product');
		$this->load('Dw_Posting_Product', DW_API_HOST, DW_API_USER, DW_API_PASSWORD, DW_API_DATABASE);

		// Validations
		$input_validations = array(
			'product_id' => array(
				'label' => 'Product ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'posting_id' => array(
				'label' => 'Posting ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'vote_period_id' => array(
				'label' => 'Vote Period ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'is_primary' => array(
				'label' => 'Is Primary'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
		);
		$this->Validate->add_many($input_validations, $params, true);
		$this->Validate->run();

		$posting_product_id = $this->Dw_Posting_Product->save(
			array(
				'product_id' => $params['product_id']
				, 'posting_id' => $params['posting_id']
				, 'vote_period_id' => $params['vote_period_id']
			)
		);

		// If is primary, clear is_primary on existing posting_product rows with this product_id & set user_id on product table
		if ($params['is_primary']) {
			$this->set_primary_posting_product($params);
		}

		return static::wrap_result(true, array('posting_product_id' => $posting_product_id));
	}

	public function set_primary_posting_product($params = array()) {
		$this->load('Product');
		$this->load('Dw_Posting_Product', DW_API_HOST, DW_API_USER, DW_API_PASSWORD, DW_API_DATABASE);

		// Validations
		$input_validations = array(
			'product_id' => array(
				'label' => 'Product ID'
				, 'rules' => array(
					'is_set' => NULL,
					'is_int' => NULL
				)
			)
			, 'posting_id' => array(
				'label' => 'Posting ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
			, 'vote_period_id' => array(
				'label' => 'Vote Period ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
		);
		$this->Validate->add_many($input_validations, $params, true);
		$this->Validate->run();

		// Clear is_primary on posting_product rows
		$this->Dw_Posting_Product->db_update(
			array(
				'is_primary' => NULL
			)
			, 'product_id = :product_id AND vote_period_id = :vote_period_id'
			, array(
				':product_id' => $params['product_id']
				, ':vote_period_id' => $params['vote_period_id']
			)
		);

		// Get the primary posting
		$posting_product = $this->Dw_Posting_Product->get_row(
			array(
				'product_id' => $params['product_id']
				, 'posting_id' => $params['posting_id']
				, 'vote_period_id' => $params['vote_period_id']
			)
		);

		// Set is_primary on the primary posting
		$this->Dw_Posting_Product->db_update(
			array(
				'is_primary' => 1
			)
			, 'posting_product_id = :posting_product_id'
			, array(
				':posting_product_id' => $posting_product['posting_product_id']
			)
		);

		// Also set user_id product row
		$posting = $this->Dw_Posting_Product->get_posting($posting_product['posting_product_id']);
		$test = $this->Product->db_update(
			array(
				'user_id' => $posting['user_id']
			)
			, 'id_product = :id_product'
			, array(
				':id_product' => $posting['product_id']
			)
		);

		return static::wrap_result(true, $data);
	}

	public function delete_posting_product($params = array()) {
		$this->load('Product');
		$this->load('Dw_Posting_Product', DW_API_HOST, DW_API_USER, DW_API_PASSWORD, DW_API_DATABASE);

		// Validations
		$input_validations = array(
			'posting_product_id' => array(
				'label' => 'Posting Product ID'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
		);
		$this->Validate->add_many($input_validations, $params, true);
		$this->Validate->run();

		// Don't allow if posting is primary
		$posting_product = $this->Dw_Posting_Product->get_row(
			array(
				'posting_product_id' => $params['posting_product_id']
			)
		);
		if (!empty($posting_product)) {
			if ($posting_product['is_primary']) {
				_Model::$Exception_Helper->request_failed_exception('Cannot delete primary posting.');
			}

			// Delete
			$this->Dw_Posting_Product->db_delete(
				'posting_product_id = :posting_product_id'
				, array(
					':posting_product_id' => $params['posting_product_id']
				)
			);
		}

		return static::wrap_result(true, $data);
	}




    public function get_sales($params = array())
    {
        $logger = new Jk_Logger(APP_PATH . 'logs/product.log');
        $logger->LogInfo("request params: " . var_export($params,true));


		//$this->load('Product');
		//$this->load('User');

		$validate_names = array(
			'id_shop' => NULL,
			'id_lang' => NULL,
			'user_id' => NULL,
		);

		$validate_params = array_merge($validate_names, $params);

		// Validations
		$input_validations = array(
            'user_id' => array(
				'label' => 'User Id',
				'rules' => array(
					'is_int' => NULL
				)
			),
            'product_id' => array(
				'label' => 'Product Id',
				'rules' => array(
					'is_int' => NULL
				)
			)
		);

		$this->Validate->add_many($input_validations, $validate_params, true);
		$this->Validate->run();

		$user_id = !empty($params['user_id']) ? $params['user_id'] : NULL;
		$product_id = !empty($params['product_id']) ? $params['product_id'] : NULL;

        $product = new Product();

        $summary =  isset($params['summary']) && (int)$params['summary'] == 1 ? true : false;
        $data = $product->get_sales($user_id, $product_id, $summary);

		return $data;
	}

}

?>
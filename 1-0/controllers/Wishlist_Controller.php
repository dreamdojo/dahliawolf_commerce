<?
class Wishlist_Controller extends _Controller {

	public function add_wishlist($params = array()) {
		$this->load('Wishlist');
		
		$data_check = $this->Wishlist->does_product_exist_in_wishlist($params['id_shop'], $params['id_customer'], $params['id_product']);
		
		if(empty($data_check)) {
			$mysqldate = date("Y-m-d H:i:s");	
			$favorite = array(
				'id_product' 	=> $params['id_product']
				, 'id_customer' => $params['id_customer']
				, 'id_shop'		=> $params['id_shop']
				, 'date_add' 	=> $mysqldate
				, 'date_upd' 	=> $mysqldate
			);
			//Validate
			$data = $this->Wishlist->save($favorite);
			
			return static::wrap_result(true, $data);
		} else {
			_Model::$Exception_Helper->request_failed_exception('Product already exists in your wishlist!');
		}
	}
	
	public function does_product_exist_in_wishlist($params = array()) {
		$this->load('Wishlist');
		//Validate
		$data = $this->Wishlist->does_product_exist_in_wishlist($params['id_shop'], $params['id_customer'], $params['id_product']);
		
		return static::wrap_result(true, $data);
	}
	
	public function get_wishlist($params = array()) {
		$this->load('Wishlist');
		//Validate
		$data = $this->Wishlist->get_wishlist($params['id_shop'], $params['id_lang'], $params['id_customer']);
		
		return static::wrap_result(true, $data);
	}
	
	public function delete_from_wishlist($params = array()) {
		$this->load('Wishlist');
		$this->load('Customer');
		
		$validate_names = array(
			'user_id' => NULL
			, 'id_favorite_product' => NULL
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
			, 'id_favorite_product' => array(
				'label' => 'Favorite Product Id'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_int' => NULL
				)
			)
		);
		$this->Validate->add_many($input_validations, $validate_params, true);
		$this->Validate->run();
		
		$num_rows = $this->Wishlist->remove_from_wishlist($params['id_favorite_product'], $params['user_id']);
		if (empty($num_rows)) {
			_Model::$Exception_Helper->request_failed_exception('Product not found in wishlist.');
		}
		
		return static::wrap_result(true, NULL, _Model::$Status_Code->get_status_code_no_content());
	}

}

?>
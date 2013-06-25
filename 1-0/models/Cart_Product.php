<?php

class Cart_Product extends _Model {

	const TABLE = 'cart_product';
	const PRIMARY_KEY_FIELD = 'id_cart_product';
	
	protected $fields = array(
		'id_cart'
		, 'id_product'
		, 'id_address_delivery'
		, 'id_shop'
		, 'id_product_attribute'
		, 'quantity'
		, 'date_add'
	);
	
	public function delete_by_id_cart($id_cart) {
		try {
			$params = array(
				':id_cart' => $id_cart
			);
			$this->db_delete('id_cart = :id_cart', $params);
			
			return $this->db_row_count();
			
		} catch(Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to delete row.');
		}
	}

}
?>

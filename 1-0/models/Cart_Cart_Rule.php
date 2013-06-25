<?php

class Cart_Cart_Rule extends _Model {

	const TABLE = 'cart_cart_rule';
	const PRIMARY_KEY_FIELD = 'id_cart_cart_rule';
	
	protected $fields = array(
		'id_cart'
		, 'id_cart_rule'
	);
	
	public function get_cart_discounts($id_cart, $id_lang) {
		$sql = '
			SELECT cart_cart_rule.id_cart_cart_rule, cart_rule.*, IF(cart_rule.reduction_percent > 0, 0, 1) AS is_amount_discount, cart_rule_lang.name
			FROM cart_cart_rule
			INNER JOIN cart_rule ON cart_cart_rule.id_cart_rule = cart_rule.id_cart_rule
				LEFT JOIN cart_rule_lang ON (cart_rule.id_cart_rule = cart_rule_lang.id_cart_rule AND cart_rule_lang.id_lang = :id_lang)
			WHERE cart_cart_rule.id_cart = :id_cart
			ORDER BY is_amount_discount DESC, cart_rule.reduction_tax ASC
		';
		
		$params = array(
			':id_cart' => $id_cart
			, ':id_lang' => $id_lang
		);
		
		try {
			$data = self::$dbs[$this->db_host][$this->db_name]->exec($sql, $params);
			
			return $data;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get cart rules.');
		}
	}
	
}
?>

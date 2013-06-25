<?php

class Cart_Rule extends _Model {

	const TABLE = 'cart_rule';
	const PRIMARY_KEY_FIELD = 'id_cart_rule';
	
	protected $fields = array();
	
	public function get_cart_rule($code, $id_lang) {
	
		$query = '
			SELECT cart_rule.*, cart_rule_lang.name
			FROM cart_rule
				LEFT JOIN cart_rule_lang ON (cart_rule.id_cart_rule = cart_rule_lang.id_cart_rule AND cart_rule_lang.id_lang = :id_lang)
			WHERE cart_rule.code = :code 
				AND NOW() BETWEEN cart_rule.date_from AND cart_rule.date_to 
				AND cart_rule.quantity > :quantity 
				AND cart_rule.active = :active
		';
		
		$params = array(
			':id_lang' => $id_lang
			, ':code' => $code
			, ':quantity' => 0
			, ':active' => 1
		);
		
		try {
			$result = self::$dbs[$this->db_host][$this->db_name]->select_single($query, $params);
			
			return $result;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Could not get cart rule.');
		}
	}
	
	public function validate_discount() {
	}
	
	public function get_cart_rule_by_id($id_cart_rule, $id_lang) {
		$sql = '
			SELECT cart_rule.*, IF(cart_rule.reduction_percent > 0, 0, 1) AS is_amount_discount, cart_rule_lang.name
			FROM cart_rule
				LEFT JOIN cart_rule_lang ON (cart_rule.id_cart_rule = cart_rule_lang.id_cart_rule AND cart_rule_lang.id_lang = :id_lang)
			WHERE cart_rule.id_cart_rule = :id_cart_rule
		';
		
		$params = array(
			':id_lang' => $id_lang
			, ':id_cart_rule' => $id_cart_rule
		);
		
		try {
			$data = self::$dbs[$this->db_host][$this->db_name]->select_single($sql, $params);
			
			return $data;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get cart rule.');
		}
	}
	
}
?>

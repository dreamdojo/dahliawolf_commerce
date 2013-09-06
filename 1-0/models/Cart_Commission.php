<?
class Cart_Commission extends _Model {
	const TABLE = 'cart_commission';
	const PRIMARY_KEY_FIELD = 'id_cart_commission';

	protected $fields = array(
		'id_cart'
		, 'amount'
	);

	public function save_cart_commission($id_cart, $amount) {
		$query = '
			INSERT INTO cart_commission (id_cart, amount)
			VALUES (:id_cart, :amount)
			ON DUPLICATE KEY UPDATE amount = :amount
		';

		$values = array(
			':id_cart' => $id_cart
			, ':amount' => $amount
		);

		try {
			$id_cart_commission = self::$dbs[$this->db_host][$this->db_name]->exec($query, $values);

			return $id_cart_commission;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to save cart commission.');
		}
	}
}
?>
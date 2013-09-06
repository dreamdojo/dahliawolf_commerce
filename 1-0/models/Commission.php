<?
class Commission extends _Model {
	const TABLE = 'commission';
	const PRIMARY_KEY_FIELD = 'id_commission';

	protected $fields = array(
		'user_id'
		, 'id_order'
		, 'id_product'
		, 'id_order_detail'
		, 'commission'
		, 'product_quantity'
		, 'note'
	);

	public function get_user_total($user_id) {
		$query = '
			SELECT IFNULL(SUM(commission.commission), 0) AS total_commissions
			FROM commission
			WHERE user_id = :user_id
		';

		$values = array(
			':user_id' => $user_id
		);

		try {
			$total_commissions = self::$dbs[$this->db_host][$this->db_name]->select_single($query, $values);

			return $total_commissions;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get total user commissions.');
		}
	}
}
?>
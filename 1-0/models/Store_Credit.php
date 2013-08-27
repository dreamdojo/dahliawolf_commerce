<?
class Store_Credit extends _Model {
	const TABLE = 'store_credit';
	const PRIMARY_KEY_FIELD = 'id_store_credit';

	protected $fields = array(
		'user_id'
		, 'id_order'
		, 'amount'
		, 'note'
	);

	public function get_user_total($user_id) {
		$query = '
			SELECT IFNULL(SUM(store_credit.amount), 0) AS total_credits
			FROM store_credit
			WHERE user_id = :user_id
		';

		$values = array(
			':user_id' => $user_id
		);

		try {
			$total_credits = self::$dbs[$this->db_host][$this->db_name]->select_single($query, $values);

			return $total_credits;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get total user store credits.');
		}
	}
}
?>
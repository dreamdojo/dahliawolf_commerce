<?
class Store_Credit extends _Model {
	const TABLE = 'store_credit';
	const PRIMARY_KEY_FIELD = 'id_store_credit';

	protected $fields = array(
		'user_id'
		, 'id_order'
		, 'id_order_detail_return'
		, 'amount'
		, 'note'
	);

    public function subtract($params = array()) {

    }

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

    public function add_user_credit($user_id, $amount) {
        $query = '
			INSERT INTO store_credit (user_id, amount) VALUES (:user_id, :amount)
		';

        $values = array(
            ':user_id' => $user_id,
            ':amount' => $amount
        );

        try {
            $total_credits = $this->query($query, $values);

            return $total_credits;
        } catch (Exception $e) {
            self::$Exception_Helper->server_error_exception('Unable to get total user store credits.');
        }
    }
}
?>
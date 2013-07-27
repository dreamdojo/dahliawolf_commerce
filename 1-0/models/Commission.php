<?
class Commission extends _Model {
	const TABLE = 'commission';
	const PRIMARY_KEY_FIELD = 'id_commission';

	protected $fields = array(
		'user_id'
		, 'id_order'
		, 'id_product'
		, 'commission'
		, 'deleted'
	);

	public function delete_order_commissions($id_order) {
		$query = '
			UPDATE commission
			SET deleted = NOW()
			WHERE id_order = :id_order
		';
		$values = array(
			':id_order' => $id_order
		);

		try {
			$update = self::$dbs[$this->db_host][$this->db_name]->exec($query, $values);

			return $update;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to reverse order commissions.');
		}
	}
}
?>
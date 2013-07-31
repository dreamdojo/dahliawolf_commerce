<?
class Dw_User_Point extends _Model {
	const TABLE = 'user_point';
	const PRIMARY_KEY_FIELD = 'user_point_id';

	protected $fields = array(
		'user_id'
		, 'point_id'
		, 'points'
		, 'posting_id'
		, 'id_order'
		, 'note'
	);

	public function delete_order_points($id_order) {
		/*return $this->db_delete(
			'id_order = :id_order'
			, array(
				':id_order' => $id_order
			)
		);*/
		return $this->reverse_order_points($id_order);
	}

	public function reverse_order_points($id_order, $note = '') {
		$note = !empty($note) ? $note : 'Points reversed due to void/return order';

		try {
			// Get original purchase rows
			// Can be multiple rows: points earned, points spent on order
			$purchase_rows = $this->get_rows(
				array(
					'id_order' => $id_order
				)
			);

			if (!empty($purchase_rows)) {
				foreach ($purchase_rows as $row) {
					$user_point = array(
						'user_id' => $row['user_id']
						, 'point_id' => $row['point_id']
						, 'points' => -1 * $row['points']
						, 'id_order' => $row['id_order']
						, 'note' => $note
					);
					$this->save($user_point);
				}
			}
		}
		catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to reverse order points.' . $e->getMessage());
		}
	}

	public function get_user_points($user_id) {
		$query = '
			SELECT SUM(user_point.points) AS points
			FROM user_point
			WHERE user_point.user_id = :user_id
		';
		$values = array(
			':user_id' => $user_id
		);
		try {
			$user_point = self::$dbs[$this->db_host][$this->db_name]->select_single($query, $values);

			return $user_point;
		}
		catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get user points.');
		}
	}

	public function get_order_points_earned($id_order) {
		$query = '
			SELECT *
			FROM user_point
			WHERE id_order = :id_order
				AND points > 0
			ORDER BY created ASC
		';
		$values = array(
			':id_order' => $id_order
		);
		try {
			$user_point = self::$dbs[$this->db_host][$this->db_name]->select_single($query, $values);

			return $user_point;
		}
		catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get order points earned.');
		}
	}
}
?>
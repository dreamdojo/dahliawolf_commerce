<?
class Dw_Point extends _Model {
	const TABLE = 'point';
	const PRIMARY_KEY_FIELD = 'point_id';

	public function get_buy_points_amount() {
		$query = '
			SELECT points
			FROM point
			WHERE name = :name
		';
		$values = array(
			':name' => 'Buy'
		);

		try {
			$config = self::$dbs[$this->db_host][$this->db_name]->select_single($query, $values);

			if (!empty($config)) {
				return $config['points'];
			}

			return NULL;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get Buy point value.');
		}
	}
}
?>
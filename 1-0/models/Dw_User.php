<?
class Dw_User extends _Model {
	public function get_membership_level($user_id) {
		$query = '
			SELECT user.points, user.points_threshold
				, membership_level.name, membership_level.commerce_id_cart_rule
			FROM
				(
					SELECT user_username.points
						, (
							SELECT MAX(points)
							FROM dahliawolf_v1_2013.membership_level
							WHERE membership_level.points <= user_username.points
							LIMIT 1
						) AS points_threshold
					FROM dahliawolf_v1_2013.user_username
					WHERE user_username.user_id = :user_id
				) AS user
				INNER JOIN dahliawolf_v1_2013.membership_level ON user.points_threshold = membership_level.points
		';
		$values = array(
			':user_id' => $user_id
		);

		try {
			$user = self::$dbs[$this->db_host][$this->db_name]->select_single($query, $values);

			return $user;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get user membership level.' . $e->getMessage());
		}
	}
}
?>
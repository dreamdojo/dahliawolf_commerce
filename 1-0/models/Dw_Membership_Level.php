<?
class Dw_Membership_Level extends _Model {
	const TABLE = 'membership_level';
	const PRIMARY_KEY_FIELD = 'membership_level_id';

	protected $fields = array(
		'name'
		, 'points'
		, 'commerce_id_cart_rule'
	);

	public function get_eligible_levels($points) {
		$query = '
			SELECT membership_level.*
				, cart_rule.reduction_percent
			FROM membership_level
				INNER JOIN offline_commerce_v1_2013.cart_rule ON membership_level.commerce_id_cart_rule = cart_rule.id_cart_rule
			WHERE points <= :points
			ORDER BY points DESC
		';
		$values = array(
			':points' => $points
		);

		try {
			$levels = self::$dbs[$this->db_host][$this->db_name]->exec($query, $values);

			return $levels;
		}
		catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get eligible membership levels.');
		}
	}

	public function check_cart_rule_eligibility($id_cart_rule, $points) {
		$query = '
			SELECT membership_level.membership_level_id
			FROM membership_level
			WHERE points <= :points
				AND commerce_id_cart_rule = :id_cart_rule
		';
		$values = array(
			':id_cart_rule' => $id_cart_rule
			, ':points' => $points
		);

		try {
			$level = self::$dbs[$this->db_host][$this->db_name]->select_single($query, $values);

			return $level;
		}
		catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to check cart rule eligibility.');
		}
	}
}
?>
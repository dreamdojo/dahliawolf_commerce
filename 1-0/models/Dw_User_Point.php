<?
class Dw_User_Point extends _Model {
	const TABLE = 'user_point';
	const PRIMARY_KEY_FIELD = 'user_point_id';

	protected $fields = array(
		'user_id'
		, 'point_id'
		, 'points'
		, 'posting_id'
	);

	public function reverse_points($user_point_id, $user_id) {
		return $this->db_delete(
			'user_point_id = :user_point_id AND user_id = :user_id'
			, array(
				':user_point_id' => $user_point_id
				, ':user_id' => $user_id
			)
		);
	}
}
?>
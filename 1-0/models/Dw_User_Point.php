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

	public function delete_order_points($id_order) {
		return $this->db_delete(
			'id_order = :id_order'
			, array(
				':id_order' => $id_order
			)
		);
	}
}
?>
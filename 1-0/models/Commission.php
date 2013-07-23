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

	public function reverse_commission($id_commission) {
		return $this->save(
			array(
				'id_commission' => $id_commission
				, 'deleted' => date('Y-m-d H:i:s')
			)
		);
	}
}
?>
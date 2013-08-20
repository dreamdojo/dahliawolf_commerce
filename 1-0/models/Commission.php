<?
class Commission extends _Model {
	const TABLE = 'commission';
	const PRIMARY_KEY_FIELD = 'id_commission';

	protected $fields = array(
		'user_id'
		, 'id_order'
		, 'id_product'
		, 'commission'
		, 'note'
	);
}
?>
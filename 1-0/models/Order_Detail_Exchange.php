<?
class Order_Detail_Exchange extends _Model {
	const TABLE = 'order_detail_exchange';
	const PRIMARY_KEY_FIELD = 'id_order_detail_exchange';

	protected $fields = array(
		'id_order_detail_return'
		, 'id_order_detail'
		, 'exchange_product_attribute_id'
	);

}
?>
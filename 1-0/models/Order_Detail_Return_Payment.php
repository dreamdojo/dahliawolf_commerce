<?
class Order_Detail_Return_Payment extends _Model {
	const TABLE = 'order_detail_return_payment';
	const PRIMARY_KEY_FIELD = 'id_order_detail_return_payment';

	protected $fields = array(
		'id_order_detail_return'
		, 'payment_method_id'
		, 'amount'
		, 'transaction_id'
	);

	
}
?>
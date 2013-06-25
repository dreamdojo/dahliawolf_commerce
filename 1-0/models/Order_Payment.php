<?php

class Order_Payment extends _Model {

	const TABLE = 'order_payment';
	const PRIMARY_KEY_FIELD = 'id_order_payment';
	
	protected $fields = array(
		'id_order'
		, 'payment_method_id'
		, 'order_reference'
		, 'id_currency'
		, 'amount'
		, 'payment_method'
		, 'conversion_rate'
		, 'transaction_id'
		, 'card_number'
		, 'card_brand'
		, 'card_expiration'
		, 'card_holder'
		, 'date_add'
	);
}
?>
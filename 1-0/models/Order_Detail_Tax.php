<?php

class Order_Detail_Tax extends _Model {

	const TABLE = 'order_detail_tax';
	const PRIMARY_KEY_FIELD = 'id_order_detail_tax';
	
	protected $fields = array(
		'id_order_detail'
		, 'id_tax'
		, 'unit_amount'
		, 'total_amount'
	);
}
?>
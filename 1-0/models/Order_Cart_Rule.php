<?php

class Order_Cart_Rule extends _Model {

	const TABLE = 'order_cart_rule';
	const PRIMARY_KEY_FIELD = 'id_order_cart_rule';
	
	protected $fields = array(
		'id_order'
		, 'id_cart_rule'
		, 'id_order_invoice'
		, 'name'
		, 'value'
		, 'value_tax_excl'
	);
}
?>
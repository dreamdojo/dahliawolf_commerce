<?php

class Order_State extends _Model {

	const TABLE = 'order_state';
	const PRIMARY_KEY_FIELD = 'id_order_state';
	
	protected $fields = array(
		'invoice'
		, 'send_email'
		, 'module_name'
		, 'color'
		, 'unremovable'
		, 'hidden'
		, 'logable'
		, 'delivery'
		, 'shipped'
		, 'paid'
		, 'deleted'
	);
}
?>
<?php

class Order_Shipment extends _Model {

	const TABLE = 'order_shipment';
	const PRIMARY_KEY_FIELD = 'id_order_shipment';
	
	protected $fields = array(
		'id_order_shipment'
		, 'id_order'
		, 'tracking_number'
		, 'url'
		, 'note'
	);
}
?>
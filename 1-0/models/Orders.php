<?php

class Orders extends _Model {

	const TABLE = 'orders';
	const PRIMARY_KEY_FIELD = 'id_order';

	protected $fields = array(
		'reference'
		, 'id_shop_group'
		, 'id_shop'
		, 'id_carrier'
		, 'id_lang'
		, 'id_customer'
		, 'id_cart'
		, 'id_currency'
		, 'id_address_delivery'
		, 'id_address_invoice'
		, 'current_state'
		, 'secure_key'
		, 'payment'
		, 'conversion_rate'
		, 'module'
		, 'recyclable'
		, 'gift'
		, 'gift_message'
		, 'shipping_number'
		, 'total'
		, 'total_discounts'
		, 'total_discounts_tax_incl'
		, 'total_discounts_tax_excl'
		, 'total_paid'
		, 'total_paid_tax_incl'
		, 'total_paid_tax_excl'
		, 'total_paid_real'
		, 'total_products'
		, 'total_products_wt'
		, 'total_shipping'
		, 'total_shipping_tax_incl'
		, 'total_shipping_tax_excl'
		, 'carrier_tax_rate'
		, 'total_wrapping'
		, 'total_wrapping_tax_incl'
		, 'total_wrapping_tax_excl'
		, 'invoice_number'
		, 'delivery_number'
		, 'invoice_date'
		, 'delivery_date'
		, 'valid'
		, 'date_add'
		, 'date_upd'
	);
}
?>